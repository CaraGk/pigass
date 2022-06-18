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
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Accreditation,
    App\Form\AccreditationType,
    App\FormHandler\AccreditationHandler;
use App\Entity\Department;
use App\Entity\Structure;

/**
 * Accreditation controller.
 */
class AccreditationController extends AbstractController
{
    protected $security, $session, $em, $um;

    public function __construct(SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
    }

  /**
   * Displays a form to add a new Accreditation entity.
   *
   * @Route("/{slug}/accreditation/{id}/new", name="GCore_FSANewAccreditation", requirements={"slug" = "\w+","id" = "\d+"})
   * @Template("hospital/accreditationForm.html.twig")
   */
  public function newAction($slug, Department $department, Request $request)
  {
    $limit = $request->query->get('limit', null);

    $accreditation = new Accreditation();
    $form = $this->createForm(AccreditationType::class, $accreditation);
    $formHandler = new AccreditationHandler($form, $request, $this->em, $this->um, $department);

    if($formHandler->process()) {
      $this->session->getFlashBag()->add('notice', 'Agrément "' . $accreditation->getSector()->getName() . '" modifié.');

    return $this->redirect($this->generateUrl('GCore_FSShowDepartment', array(
        'slug'  => $slug,
      'id'    => $department->getId(),
      'limit' => $limit,
    )));
  }

    return array(
        'department_id' => $department->getId(),
        'accreditation' => null,
        'form'          => $form->createView(),
        'limit'         => $limit,
    );
  }

  /**
   * Displays a form to edit an existing Accreditation entity.
   *
   * @Route("/{slug}/accreditation/{id}/edit", name="GCore_FSAEditAccreditation", requirements={"id" = "\d+"})
   * @Template("hospital/accreditationForm.html.twig")
   */
  public function editAccreditationAction($slug, Accreditation $accreditation, Request $request)
  {
    $limit = $request->query->get('limit', null);

    $form = $this->createForm(AccreditationType::class, $accreditation);
    $formHandler = new AccreditationHandler($form, $request, $this->em, $this->um);

    if($formHandler->process()) {
      $this->session->getFlashBag()->add('notice', 'Agrément "' . $accreditation->getSector()->getName() . '" modifié.');

      return $this->redirect($this->generateUrl('GCore_FSShowDepartment', array(
        'slug'  => $slug,
        'id'    => $accreditation->getDepartment()->getId(),
        'limit' => $limit,
      )));
    }

    return array(
        'department_id' => $accreditation->getDepartment()->getId(),
        'accreditation' => $accreditation,
        'form'          => $form->createView(),
        'limit'         => $limit,
    );
  }

  /**
   * Deletes a Accreditation entity.
   *
   * @Route("/{slug}/accreditation/{id}/delete", name="GCore_FSADeleteAccreditation", requirements={"id" = "\d+"}))
   */
  public function deleteAccreditationAction($slug, Accreditation $accreditation, Request $request)
  {
    $limit = $request->query->get('limit', null);
    $department_id = $accreditation->getDepartment()->getId();

    $this->em->remove($accreditation);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Agrément "' . $accreditation->getSector()->getName() . '" supprimé.');

    return $this->redirect($this->generateUrl('GCore_FSShowDepartment', array(
        'slug'  => $slug,
        'id'    => $department_id,
        'limit' => $limit,
    )));
  }

  /**
   * Give a teacher the role ROLE_SUPERTEACHER
   *
   * @Route("/{slug}/accreditation/{id}/promote", name="GCore_FSAPromote", requirements={"id" = "\d+"})
   * @Security("has_role('ROLE_ADMIN')")
   */
  public function promoteAction($slug, Accreditation $accreditation, Request $request)
  {
    $limit = $request->query->get('limit', null);

    $user = $accreditation->getUser();
    $user->addRole('ROLE_SUPERTEACHER');
    $this->um->updateUser($user);

    $this->session->getFlashBag()->add('notice', 'Droits d\'administration donnés à l\'enseignant "' . $accreditation->getSupervisor() . '"');

    return $this->redirect($this->generateUrl('GCore_FSShowDepartment', [
        'id'    => $accreditation->getDepartment()->getId(),
        'limit' => $limit,
        'slug'  => $slug,
    ]));
  }

  /**
   * Remove the role ROLE_SUPERTEACHER from a teacher
   *
   * @Route("/{slug}/accreditation/{id}/demote", name="GCore_FSADemote", requirements={"id" = "\d+"})
   * @Security("has_role('ROLE_ADMIN')")
   */
  public function demoteAction($slug, Accreditation $accreditation, Request $request)
  {
    $limit = $request->query->get('limit', null);

    $user = $accreditation->getUser();
    if( $user->hasRole('ROLE_SUPERTEACHER') )
      $user->removeRole('ROLE_SUPERTEACHER');
    $this->um->updateUser($user);

    $this->session->getFlashBag()->add('notice', 'Droits d\'administration retirés à l\'enseignant "' . $accreditation->getSupervisor() . '"');

    return $this->redirect($this->generateUrl('GCore_FSShowDepartment', [
        'id'    => $accreditation->getDepartment()->getId(),
        'limit' => $limit,
        'slug'  => $slug,
    ]));
  }
}
