<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\Security\Core\Security,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity,
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Hospital,
    App\Form\HospitalType,
    App\Form\HospitalDescriptionType,
    App\FormHandler\HospitalHandler;
use App\Entity\Department,
    App\Form\DepartmentDescriptionType,
    App\FormHandler\DepartmentHandler;
use App\Entity\Structure;

/**
 * Hospital controller.
 */
class HospitalController extends AbstractController
{
    protected $security, $session, $em, $um;

    public function __construct(Security $security, SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
    }

  /**
   * @Route("/{slug}/hospital/list", name="GCore_FSIndex")
   * @Template()
   */
  public function indexAction(Structure $structure, Request $request)
  {
    $user = $this->getUser();

    $sectors = $this->em->getRepository('App:Sector')->findAll();
    if ($sector_default = $this->em->getRepository('App:Sector')->findOneBy(array('is_default' => true,)))
    {
        $limit_default = array(
            'type'        => 's.id',
            'value'       => $sector_default->getId(),
            'description' => $sector_default,
        );
    } else {
        $limit_default = null;
    }

    /* Affiche les terrains de stage sans accreditation si admin */
    if ($user and $user->hasRole('ROLE_ADMIN')) {
        $arg['admin'] = true;
    }

    /* Filtre sur le username pour l'entrée du menu Teacher */
    $arg['limit'] = $request->query->get('limit', $limit_default);
    if ($arg['limit'] and $arg['limit']['type'] == 'u.id' and $arg['limit']['value'] == '') {
        $arg['limit']['value'] = $user->getId();
        $arg['limit']['description'] = $user->getUsername();
    }

    $period = $this->em->getRepository('App:Period')->getLast($structure);
    if($period) {
        $arg['period'] = $period->getId();
    } else {
        $arg['period'] = null;
    }

    $hospitals = $this->em->getRepository('App:Hospital')->getAllWithDepartments($arg);
    $orphaneds = $this->em->getRepository('App:Hospital')->getAll($structure);

    return array(
        'structure' => $structure,
        'hospitals' => $hospitals,
        'sectors'   => $sectors,
        'limit'     => $arg['limit'],
        'orphaneds' => $orphaneds,
    );
  }

    /**
     * @Route("/{slug}/hospital/{id}/show", name="GCore_FSShowDepartment", requirements={"id" = "\d+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function showAction(Structure $structure, Department $department, Request $request)
    {
        $user = $this->getUser();
        $limit = $request->query->get('limit', null);
        $clusters = null;

        foreach($department->getRepartitions() as $repartition) {
            if ($cluster_name = $repartition->getCluster()) {
                $period = $repartition->getPeriod();
                $clusters[] = array(
                    'period'       => $period,
                    'repartitions' => $this->em->getRepository('App:Repartition')->getByPeriodAndCluster($structure, $period, $cluster_name),
                );
            }
        }

        $evaluated = array();
        $placements = $this->em->getRepository('App:Placement')->getByUsernameAndDepartment($user?$user->getUsername():null, $department->getId());
        if (true == $this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_active')->getValue() and null !== $placements) {
            foreach ($placements as $placement) {
                $evaluated[$placement->getId()] = $this->em->getRepository('App:Evaluation')->getByPlacement($structure, $placement->getId());
            }
        }

        return array(
            'department' => $department,
            'evaluated'  => $evaluated,
            'limit'      => $limit,
            'clusters'   => $clusters,
        );
    }

  /**
   * Displays a form to create a new Hospital entity.
   *
   * @Route("/{slug}/hospital/new", name="GCore_FSANewHospital")
   * @Template("hospital/form.html.twig")
   */
  public function newHospitalAction(Structure $structure, Request $request)
  {
    $limit = $request->query->get('limit', null);
    $periods = $this->em->getRepository('App:Period')->findAll();

    $hospital = new Hospital();
    $form   = $this->createForm(HospitalType::class, $hospital);
    $formHandler = new HospitalHandler($form, $request, $this->em, $periods, $structure);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Hôpital "' . $hospital->getName() . '" enregistré.');

      return $this->redirect($this->generateUrl('GCore_FSIndex', ['slug' => $structure->getSlug()]));
    }

    return array(
        'hospital_form' => $form->createView(),
        'limit'         => $limit,
    );
  }

  /**
   * Displays a form to edit an existing Hospital entity.
   *
   * @Route("/{slug}/hospital/{id}/edit", name="GCore_FSAEditHospital", requirements={"id" = "\d+"})
   * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   * @Template("hospital/form.html.twig")
   */
  public function editHospitalAction(Structure $structure, Hospital $hospital, Request $request)
  {
    $limit = $request->query->get('limit', null);
    $periods = $this->em->getRepository('App:Period')->findAll();
    if (!$structure)
        throw $this->createNotFoundException('Structure inconnue');

    $editForm = $this->createForm(HospitalType::class, $hospital);
    $formHandler = new HospitalHandler($editForm, $request, $this->em, $periods, $structure);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Hôpital "' . $hospital->getName() . '" modifié.');

      return $this->redirect($this->generateUrl('GCore_FSIndex', ['slug' => $structure->getSlug()]));
    }

    return array(
        'hospital_form' => $editForm->createView(),
        'limit'         => $limit,
    );
  }

  /**
   * Deletes a Hospital entity.
   *
   * @Route("/{slug}/hospital/{id}/delete", name="GCore_FSADeleteHospital", requirements={"id" = "\d+"}))
   * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function deleteHospitalAction(Structure $structure, Hospital $hospital, Request $request)
  {
    $limit = $request->query->get('limit', null);

    $this->em->remove($hospital);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Hôpital "' . $hospital->getName() . '" supprimé.');

    return $this->redirect($this->generateUrl('GCore_FSIndex', ['slug' => $slug, 'limit' => $limit]));
  }

  /**
   * Deletes a Department entity.
   *
   * @Route("/{slug}/department/{id}/delete", name="GCore_FSADeleteDepartment", requirements={"id" = "\d+"}))
   * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   */
  public function deleteDepartmentAction(Structure $structure, Department $department, Request $request)
  {
    $limit = $request->query->get('limit', null);

    $this->em->remove($department);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Service "' . $department->getName() . '" supprimé.');

    return $this->redirect($this->generateUrl('GCore_FSIndex', ['slug' => $slug, 'limit' => $limit]));
  }

  /**
   * Edit the description of the Department entity.
   *
   * @Route("/{slug}/hospital/department/{id}", name="GCore_FSAEditDepartmentDescription", requirements={"id" = "\d+"})
   * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   * @Template("hospital/departmentForm.html.twig")
   */
  public function editDepartmentDescriptionAction(Structure $structure, Department $department, Request $request)
  {
      $limit = $request->query->get('limit', null);
      if (!$structure)
        throw $this->createNotFoundException('Structure inconnue');

    $editForm = $this->createForm(DepartmentDescriptionType::class, $department, ['structure' => $structure]);
    $formHandler = new DepartmentHandler($editForm, $request, $this->em, $structure);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Description du service "' . $department->getName() . '" enregistrée.');

      return $this->redirect($this->generateUrl('GCore_FSAEditDepartmentDescription', ['slug' => $structure->getSlug(), 'id' => $department->getId(), 'limit' => $limit]));
    }

    return array(
      'entity'    => $department,
      'form' => $editForm->createView(),
      'limit'     => $limit,
    );
  }

  /**
   * Edit the description of the Hospital entity.
   *
   * @Route("/{slug}/hospital/{id}", name="GCore_FSAEditHospitalDescription", requirements={"id" = "\d+"})
   * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
   * @Template("App:FieldSetAdmin:editDescription.html.twig")
   */
  public function editHospitalDescriptionAction(Structure $structure, Hospital $hospital, Request $request)
  {
    $limit = $request->query->get('limit', null);
    $periods = $this->em->getRepository('App:Period')->findAll();

    $editForm = $this->createForm(HospitalDescriptionType::class, $hospital);
    $formHandler = new HospitalHandler($editForm, $request, $this->em, $periods, $structure);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Description de l\'hôpital "' . $hospital->getName() . '" enregistrée.');

      return $this->redirect($this->generateUrl('GCore_FSAEditHospitalDescription', array('id' => $id, 'limit' => $limit)));
    }

    return array(
      'entity'    => $hospital,
      'edit_form' => $editForm->createView(),
      'limit'     => $limit,
    );
  }
}
