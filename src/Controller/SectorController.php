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
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Hospital,
    App\Form\HospitalType,
    App\Form\HospitalDescriptionType,
    App\Form\HospitalHandler;
use App\Entity\Sector,
    App\Form\SectorType,
    App\Form\SectorHandler;
use App\Entity\Department,
    App\Form\DepartmentDescriptionType,
    App\Form\DepartmentHandler;
use App\Entity\Accreditation,
    App\Form\AccreditationType,
    App\Form\AccreditationHandler;

/**
 * Sector controller.
 */
class SectorController extends AbstractController
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
   * Lists all Sector entities.
   *
   * @Route("/{slug}/sector", name="GCore_FSASector")
   * @Template()
   */
  public function sectorAction($slug)
  {
    $sectors = $this->em->getRepository('App:Sector')->findAll();

    return array(
      'sectors'       => $sectors,
      'sector_id'     => null,
      'sector_form'   => null,
    );
  }

  /**
   * Displays a form to create a new Sector entity.
   *
   * @Route("/{slug}/sector/new", name="GCore_FSANewSector")
   * @Template("App:FieldSetAdmin:sector.html.twig")
   */
  public function newSectorAction($slug)
  {
    $sectors = $this->em->getRepository('App:Sector')->findAll();

    $sector = new Sector();

    $editForm = $this->createForm(SectorType::class, $sector);
    $formHandler = new SectorHandler($editForm, $this->get('request'), $this->em);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Catégorie "' . $sector->getName() . '" enregistrée.');

      return $this->redirect($this->generateUrl('GCore_FSASector'));
    }

    return array (
      'sectors'       => $sectors,
      'sector_id'     => null,
      'sector_form'   => $editForm->createView(),
    );
  }

  /**
   * Displays a form to edit an existing Sector entity.
   *
   * @Route("/{slug}/sector/{id}/edit", name="GCore_FSAEditSector", requirements={"id" = "\d+"})
   * @Template("App:FieldSetAdmin:sector.html.twig")
   */
  public function editSectorAction($slug, $sector)
  {
    $sectors = $this->em->getRepository('App:Sector')->findAll();

    $editForm = $this->createForm(SectorType::class, $sector);
    $formHandler = new SectorHandler($editForm, $this->get('request'), $this->em);

    if ( $formHandler->process() ) {
      $this->session->getFlashBag()->add('notice', 'Catégorie "' . $sector->getName() . '" modifiée.');

      return $this->redirect($this->generateUrl('GCore_FSASector'));
    }

    return array (
      'sectors'       => $sectors,
      'sector_id'     => $id,
      'sector_form'   => $editForm->createView(),
    );
  }

  /**
   * Deletes a Sector entity.
   *
   * @Route("/{slug}/sector/{id}/delete", name="GCore_FSADeleteSector")
   */
  public function deleteSectorAction($slug, $sector)
  {
    $this->em->remove($sector);
    $this->em->flush();

    $this->session->getFlashBag()->add('notice', 'Catégorie "' . $sector->getName() . '" supprimée.');

    return $this->redirect($this->generateUrl('GCore_FSASector'));
  }
}
