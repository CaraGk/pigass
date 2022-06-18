<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Security,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Department;
use App\Entity\Evaluation,
    App\Form\EvaluationType,
    App\FormHandler\EvaluationHandler,
    App\Form\ModerationType,
    App\FormHandler\ModerationHandler;
use App\Entity\EvalForm,
    App\Form\EvalFormType,
    App\FormHandler\EvalFormHandler;
use App\Entity\EvalSector,
    App\Form\EvalSectorType,
    App\FormHandler\EvalSectorHandler;
use App\Entity\Placement;
use App\Entity\EvalCriteria;
use App\Entity\Structure;

/**
 * EvaluationController
 *
 * @Route("/")
 */
class EvaluationController extends AbstractController
{
    protected $session, $em, $um;

    public function __construct(SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
    }

    /**
     * Affiche les évaluations d'un terrain de stage
     *
     * @Route("/{slug}/eval/department/{id}", name="GEval_DShow", requirements={"id" = "\d+"})
     * @Template()
     * @Entity("department", expr="repository.find(id)")
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function showAction(Structure $structure, Department $department)
    {
        /* Vérification des droits ROLE_STUDENT sinon sélection uniquement des EvalCriteria où isPrivate == false */
        $user = $this->getUser();
        if ($user->hasRole('ROLE_STUDENT') or $user->hasRole('ROLE_MEMBER') or $user->hasRole('ROLE_ADMIN')) {
            $limit['role'] = false;

            /* Vérification de l'évaluation de tous ses stages (sauf le courant) par l'étudiant */
            $person = $this->em->getRepository('App:Person')->getByUser($user);
            $current_period = $this->em->getRepository('App:Period')->getCurrent($structure);
            if (!$current_period)
                $current_period = $this->em->getRepository('App:Period')->getLast($structure);
            $count_placements = $this->em->getRepository('App:Placement')->getCountByPersonWithoutCurrentPeriod($person, $current_period);
            if ($this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_unevaluated')->getValue() and $this->em->getRepository('App:Evaluation')->personHasNonEvaluated($structure, $person, $current_period, $count_placements)) {
                $this->session->getFlashBag()->add('error', 'Il y a des évaluations non réalisées. Veuillez évaluer tous vos stages avant de pouvoir accéder aux autres évaluations.');
                return $this->redirect($this->generateUrl('app_dashboard_user', ['slug' => $structure->getSlug()]));
            }

            /* Vérification de l'adhésion de l'étudiant */
            if ($this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_nonmember')->getValue() and !$this->em->getRepository('App:Membership')->getCurrentForPerson($person, true)) {
                $this->session->getFlashBag()->add('error', 'Il faut être à jour de ses cotisations pour pouvoir accéder aux évaluations.');
                return $this->redirect($this->generateUrl('app_dashboard_user', ['slug' => $structure->getSlug()]));
            }
        } else {
            $limit['role'] = true;

            if ($user->hasRole('ROLE_SUPERTEACHER') or $this->em->getRepository('App:Accreditation')->getByDepartmentAndUser($department->getId(), $user->getId())) {
            } else {
                $this->session->getFlashBag()->add('error', 'Vous n\'avez pas les droits suffisants pour accéder aux évaluations d\'autres terrain de stage.');
                return $this->redirect($this->generateUrl('GCore_FSIndex', ['slug' => $structure->getSlug()]));
            }
        }

        $limit['date'] = date('Y-m-d H:i:s', strtotime('-' . $this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_limit')->getValue() . ' year'));
        $limit['date'] = date('Y-m-d H:i-s', strtotime('-10 year'));

        if (!$department)
            throw $this->createNotFoundException('Unable to find department entity.');

        $eval = $this->em->getRepository('App:Evaluation')->getByDepartment($structure, $department->getId(), $limit);
        $count_eval = $this->em->getRepository('App:Evaluation')->countByDepartment($structure, $department->getId(), $limit);
        if (!($user->hasRole('ROLE_STUDENT') or $user->hasRole('ROLE_MEMBER')) and $count_eval < $this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_min')->getValue()) {
            $eval = null;
        }

        return array(
            'department' => $department,
            'eval'       => $eval,
        );
    }

    /**
     * Evaluer un stage
     *
     * @Route("/{slug}/eval/placement/{id}", name="GEval_DEval", requirements={"id" = "\d+"})
     * @Template()
     * @Security("is_granted('ROLE_MEMBER') or is_granted('ROLE_STUDENT')")
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function evaluateAction(Structure $structure, Placement $placement, Request $request)
    {
        if (!$placement)
            throw $this->createNotFoundException('Unable to find placement entity.');

        $eval_forms = array();
        $accreditations = $this->em->getRepository('App:Accreditation')->getByDepartmentAndPeriod($placement->getRepartition()->getDepartment()->getId(), $placement->getRepartition()->getPeriod());
        foreach ($accreditations as $accreditation) {
            if($eval_sector = $this->em->getRepository('App:EvalSector')->getEvalSector($accreditation->getSector()->getId()))
                $eval_forms[] = $eval_sector->getForm();
        }

        if (null != $eval_forms) {
            $form = $this->createForm(EvaluationType::class, null, ['eval_forms' => $eval_forms]);
            $form_handler = new EvaluationHandler($form, $request, $this->em, $placement, $eval_forms, $this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_moderate')->getValue());

            if ($form_handler->process()) {
                $this->session->getFlashBag()->add('notice', 'Évaluation du stage "' . $placement->getRepartition()->getDepartment()->getName() . ' à ' . $placement->getRepartition()->getDepartment()->getHospital()->getName() . '" enregistrée.');

                return $this->redirect($this->generateUrl('app_dashboard_user', ['slug' => $structure->getSlug()]));
            }

            return array(
                'placement' => $placement,
                'form'      => $form->createView(),
            );
        } else {
            return array(
                'placement' => $placement,
                'form'      => null,
            );
        }
    }

    /**
     * Affiche l'évaluation d'un étudiant
     *
     * @Route("/{slug}/eval/placement/{id}/show", name="GEval_DShowPerson", requirements={"id" = "\d+"})
     * @Template()
     * @Security("has_role('ROLE_SUPERTEACHER')")
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function showPersonAction(Structure $structure, Placement $placement)
    {
        $limit['role'] = true;
        $evals = $this->em->getRepository('App:Evaluation')->getByPlacement($structure, $placement->getId(), $limit);

        return array(
            'evals' => $evals,
        );
    }

  /**
   * @Route("/{slug}/eval", name="GEval_AIndex")
   * @Template()
   */
  public function indexAction($slug)
  {
    $eval_forms = $this->em->getRepository('App:EvalForm')->findAll();
    $sectors = $this->em->getRepository('App:EvalSector')->getAllByForm($eval_forms);

    return array(
      'eval_forms'     => $eval_forms,
      'eval_form_id'   => null,
      'eval_form_form' => null,
      'sectors'        => $sectors,
      'sector_form'    => null,
      'form_id'        => null,
    );
  }

  /**
   * Displays a form to create a new eval_form entity.
   *
   * @Route("/{slug}/eval/form/new", name="GEval_ANew")
   * @Template("App:Admin:form.html.twig")
   */
  public function newFormAction(Structure $structure, Request $request)
  {
    $eval_form = new EvalForm();
    $form = $this->createForm(EvalFormType::class, $eval_form);
    $formHandler = new EvalFormHandler($form, $request, $this->em);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Formulaire d\'évaluation "' . $eval_form->getName() . '" enregistré.');

      return $this->redirect($this->generateUrl('GEval_AIndex', ['slug' => $structure->getSlug()]));
    }

    return array(
      'form' => $form->createView(),
    );
  }

   /**
   * Displays a form to edit an existing eval_form entity.
   *
   * @Route("/{slug}/eval/form/{id}/edit", name="GEval_AEdit", requirements={"id" = "\d+"})
   * @Template("evaluation/form.html.twig")
   * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function editFormAction(Structure $structure, EvalForm $eval_form, Request $request)
  {
    $exclude_sectors = $this->em->getRepository('App:EvalSector')->getAssignedSectors();
    $form = $this->createForm(EvalFormType::class, $eval_form, ['exclude_sectors' => $exclude_sectors]);
    $formHandler = new EvalFormHandler($form, $request, $this->em, $structure);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Formulaire d\'évaluation "' . $eval_form->getName() . '" modifié.');

      return $this->redirect($this->generateUrl('GEval_AEdit', ['slug' => $structure->getSlug(), 'id' => $eval_form->getId()]));
    }

    return array(
      'form' => $form->createView(),
    );
  }

  /**
   * Deletes a eval_form entity.
   *
   * @Route("/{slug}/eval/form/{id}/delete", name="GEval_ADelete", requirements={"id" = "\d+"}))
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function deleteFormAction(Structure $structure, EvalForm $eval_form)
  {
    if (!$eval_form)
      throw $this->createNotFoundException('Unable to find eval_form entity.');

    foreach ($eval_form->getCriterias() as $criteria) {
      if ($evaluations = $this->em->getRepository('App:Evaluation')->findByEvalCriteria($structure, $criteria->getId())) {
        foreach ($evaluations as $evaluation) {
          $this->em->remove($evaluation);
        }
      }
    }

    $this->em->remove($eval_form);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Formulaire d\'évaluation "' . $eval_form->getName() . '" supprimé.');

    return $this->redirect($this->generateUrl('GEval_AIndex', ['slug' => $structure->getSlug()]));
  }

  /**
   * Deletes a eval_criteria entity.
   *
   * @Route("/{slug}/eval/criteria/{id}/delete", name="GEval_ADeleteCriteria", requirements={"id" = "\d+"}))
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function deleteCriteriaAction(Structure $structure, EvalCriteria $criteria)
  {
    if (!$criteria)
      throw $this->createNotFoundException('Unable to find eval_criteria entity.');

    $this->em->remove($criteria);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Critère d\'évaluation "' . $criteria->getName() . '" supprimé.');

    return $this->redirect($this->generateUrl('GEval_AIndex', ['slug' => $structure->getSlug()]));
  }

  /**
   * Display a form to add a sector to an eval_form entity
   *
   * @Route("/{slug}/eval/form/{id}/sector/add", name="GEval_ASectorAdd", requirements={"form_id" = "\d+"})
   * @Template("App:Admin:index.html.twig")
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function addSectorAction(Structure $structure, EvalForm $eval_form, Request $request)
  {
    $eval_forms = $this->em->getRepository('App:EvalForm')->findAll();
    $sectors = $this->em->getRepository('App:EvalSector')->getAllByForm($eval_forms);
    $exclude_sectors = $this->em->getRepository('App:EvalSector')->getAssignedSectors();

    $eval_sector = new EvalSector();
    $form = $this->createForm(EvalSectorType::class, $eval_sector, ['exclude_sectors' => $exclude_sectors, 'eval_form' => $eval_form]);
    $formHandler = new EvalSectorHandler($form, $request, $this->em, $eval_form);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Relation "' . $eval_sector->getSector() . " : " . $eval_sector->getForm() . '" enregistrée.');

      return $this->redirect($this->generateUrl('GEval_AIndex', ['slug' => $structure->getSlug()]));
    }

    return array(
      'eval_forms'     => $eval_forms,
      'eval_form_id'   => null,
      'eval_form_form' => null,
      'sectors'        => $sectors,
      'sector_form'    => $form->createView(),
      'form_id'        => $eval_form->getId(),
    );
  }

  /**
   * Deletes a eval_sector entity.
   *
   * @Route("/{slug}/eval/sector/{id}/delete", name="GEval_ASectorDelete", requirements={"id" = "\d+"}))
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function deleteSectorAction(Structure $structure, EvalSector $eval_sector)
  {
    if (!$eval_sector)
      throw $this->createNotFoundException('Unable to find eval_sector entity.');

    $this->em->remove($eval_sector);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Relation "' . $eval_sector->getSector() . " : " . $eval_sector->getForm() . '" supprimée.');

    return $this->redirect($this->generateUrl('GEval_AIndex', ['slug' => $structure->getSlug()]));
  }

  /**
   * Affiche les evaluations textuelles pour modération
   *
   * @Route("/{slug}/eval/moderation/", name="GEval_ATextIndex")
   * @Template()
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function toModerateAction(Structure $structure, Request $request)
  {
    $evaluations = $this->em->getRepository('App:Evaluation')->getToModerate($structure);

    return array(
      'evaluations' => $evaluations,
    );
  }

    /**
     * Valide une évaluation textuelle
     *
     * @Route("/{slug}/eval/moderation/{id}/valid", name="GEval_AModerationValid", requirements={"id" = "\d+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function validModeration(Structure $structure, Evaluation $evaluation)
    {
// Réécrire getToModerate() pour tester si l'évaluation est bien à modérer
// Ajouter un test pour les droits admin

        $evaluation->setValidated(true);
        $evaluation->setModerator($user);
        $this->em->persist($evaluation);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Évaluation validée.');

        return $this->redirect($this->generateUrl('GEval_ATextIndex', ['slug' => $structure->getSlug()]));
    }

    /**
     * Invalide une évaluation
     *
     * @Route("/{slug}/eval/moderation/{id}/delete", name="GEval_AModerationInvalid", requirements={"id" = "\d+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function textDeleteAction(Structure $structure, Evaluation $evaluation)
    {
// Réécrire getToModerate() pour tester si l'évaluation est bien à modérer
// Ajouter un test pour les droits admin

        $evaluation->setValidated(false);
        $evaluation->setModerated(true);
        $evaluation->setModerator($user);
        $this->em->persist($evaluation);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Évaluation supprimée.');

        return $this->redirect($this->generateUrl('GEval_ATextIndex', ['slug' => $structure->getSlug()]));
    }

    /**
     * Modère une évaluation
     *
     * @Route("/{slug}/eval/moderation/{id}/edit", name="GEval_AModerationEdit", requirements={"id" = "\d+"})
     * @Template()
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function moderationEditAction(Structure $structure, Evaluation $evaluation, Request $request)
    {
// Réécrire getToModerate() pour tester si l'évaluation est bien à modérer
// Ajouter un test pour les droits admin

        $form = $this->createForm(ModerationType, null, ['evaluation' => $evaluation]);
        $formHandler = new ModerationHandler($form, $request, $this->em, $evaluation, $user);

        if ( $formHandler->process() ) {
            $this->session->getFlashBag()->add('notice', 'Évaluation modérée.');

            return $this->redirect($this->generateUrl('GEval_ATextIndex', ['slug' => $structure->getSlug()]));
        }
        return array(
            'evaluation' => $evaluation,
            'form'       => $form->createView(),
        );
    }

    /**
     * Exporte les évaluations en PDF
     *
     * @Route("/{slug}/eval/export", name="GEval_APdfExport")
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function pdfExportAction(Structure $structure)
    {
        $hospitals = $this->em->getRepository('App:Hospital')->getAll();
        $limit['date'] = date('Y-m-d H:i:s', strtotime('-' . $this->em->getRepository('App:Parameter')->findByName('eval_' . $slug . '_limit')->getValue() . ' year'));
        $limit['role'] = null;
        $pdf = $this->get("white_october.tcpdf")->create();

        foreach ($hospitals as $hospital) {
            foreach ($hospital->getDepartments() as $department) {
                $eval[$department->getId()] = $this->em->getRepository('App:Evaluation')->getByDepartment($structure, $department->getId(), $limit);
            }
        }

        $content = $this->renderView('App:Admin:pdfExport.html.twig', array(
            'eval'        => $eval,
            'hospitals'   => $hospitals,
        ));

        $pdf->SetTitle($this->em->getRepository('App:Parameter')->findByName('general_title')->getValue() . ' : évaluations');
        $pdf->AddPage();
        $pdf->writeHTML($content);
        $pdf->lastPage();

        return new Response(
            $pdf->Output('Evaluations.pdf'),
            200,
            array(
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Evaluations.pdf"',
            )
        );
    }

    /**
     * Envoie un mail de rappel aux étudiants n'ayant pas évalué tous leurs
     * stages
     *
     * @Route("/{slug}/eval/mail", name="GEval_ASendMails")
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function sendMailsAction(Structure $structure)
    {
        $evaluatedList = $this->em->getRepository('App:Evaluation')->getEvaluatedList($structure);
        $persons = $this->em->getRepository('App:Person')->getWithPlacementNotIn($evaluatedList);
        $count = 0;

        foreach($persons as $person) {
            $mail = \Swift_Message::newInstance()
                ->setSubject('[GESSEH] Des évaluations sont en attente')
                ->setFrom('tmp@angrand.fr')
                ->setTo($person->getUser()->getEmail())
                ->setBody($this->renderView('App:Admin:sendMails.txt.twig', array(
                    'person' => $person,
                )));
            ;
            $this->get('mailer')->send($mail);
            $count++;
        }

        $this->session->getFlashBag()->add('notice', $count . ' email(s) ont été envoyé(s).');
        return $this->redirect($this->generateUrl('GEval_AIndex', ['slug' => $structure->getSlug()]));
    }

    /**
     * Supprime l'évaluation d'un stage
     *
     * @Route("/{slug}/eval/placement/{id}/delete", name="GEval_ADeleteEval", requirements={"id" = "\d+"})
     */
    public function deleteEval(Structure $structure, Placement $placement, Request $request)
    {
        $this->em = $this->getDoctrine()->getManager();
        $evaluations = $this->em->getRepository('App:Evaluation')->findByPlacement($structure, $placement->getId());

        if (!$evaluations)
          throw $this->createNotFoundException('Unable to find evaluation entity.');

        foreach($evaluations as $evaluation) {
            $this->em->remove($evaluation);
        }
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Évaluation complète supprimée.');

        $queryArray = [];
        if($limit = $request()->query->get('limit')) {
            $queryArray['limit'] = array(
                'slug'        => $structure->getSlug(),
                'type'        => $limit['type'],
                'value'       => $limit['value'],
                'description' => $limit['description'],
            );
        }
        return $this->redirect($this->generateUrl('GCore_PAPlacementIndex', $queryArray));
    }
}
