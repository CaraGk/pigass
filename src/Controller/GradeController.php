<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
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
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Grade;
use App\Form\GradeType;
use App\Form\GradeHandler;

/**
 * Grade controller.
 */
class GradeController extends AbstractController
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
   * @Route("/{slug}/grade/list", name="GUser_GAIndex")
   * @Template()
   */
  public function indexAction()
  {
    $grades = $this->em->getRepository('App:Grade')->getAll();

    return array(
      'grades'       => $grades,
      'grade_id'     => null,
      'grade_form'   => null,
    );
  }

  /**
   * @Route("/{slug}/grade/new", name="GUser_GANew")
   * @Template("grade/index.html.twig")
   */
  public function newAction()
  {
    $grades = $this->em->getRepository('App:Grade')->getAll();

    $grade = new Grade();
    $grade->setRank($this->em->getRepository('App:Grade')->getLastActiveRank() + 1);
    $form = $this->createForm(GradeType::class, $grade);
    $formHandler = new GradeHandler($form, $this->get('request'), $this->em);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Promotion "' . $grade . '" enregistrée.');

      return $this->redirect($this->generateUrl('GUser_GAIndex'));
    }

    return array(
      'grades'       => $grades,
      'grade_id'     => null,
      'grade_form'   => $form->createView(),
    );
  }

  /**
   * @Route("/{slug}/grade/{id}/edit", name="GUser_GAEdit", requirements={"id" = "\d+"})
   * @Template("grade/index.html.twig")
   */
  public function editAction($slug, $grade)
  {
    $grades = $this->em->getRepository('App:Grade')->getAll();

    $form = $this->createForm(GradeType::class, $grade);
    $formHandler = new GradeHandler($form, $this->get('request'), $this->em);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Promotion "' . $grade . '" modifiée.');

      return $this->redirect($this->generateUrl('GUser_GAIndex'));
    }

    return array(
      'grades'       => $grades,
      'grade_id'     => $id,
      'grade_form'   => $form->createView(),
    );
  }

  /**
   * @Route("/{slug}/grade/{id}/delete", name="GUser_GADelete", requirements={"id" = "\d+"})
   */
  public function deleteAction($slug, $grade)
  {
    if ($rules = $this->em->getRepository('App:SectorRule')->getByGrade($id)) {
        foreach ($rules as $rule) {
            $this->em->remove($rule);
            $this->session->getFlashBag()->add('notice', 'Règle "' . $rule . '" supprimée.');
        }
    }

    $this->em->remove($grade);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Promotion "' . $grade . '" supprimée.');

    return $this->redirect($this->generateUrl('GUser_GAIndex'));
  }

  /**
   * @Route("/{slug}/grade/next", name="GUser_GAUpdateNext")
   */
  public function updateAllPersonsToNextAction()
  {
    $grades = $this->em->getRepository('App:Grade')->getAllActiveInverted();

    foreach ($grades as $grade) {
      $next_grade = $this->em->getRepository('App:Grade')->getNext($grade->getRank());
      if (null !== $next_grade) {
        $this->em->getRepository('App:Person')->setGradeUp($grade->getId(), $next_grade->getId());
      }
    }

    $this->session->getFlashBag()->add('notice', 'Étudiants passés dans la promotion supérieure.');

    return $this->redirect($this->generateUrl('GUser_GAIndex'));
  }
}
