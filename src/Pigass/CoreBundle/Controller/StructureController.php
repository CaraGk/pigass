<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\CoreBundle\Entity\Structure,
    Pigass\CoreBundle\Form\StructureType,
    Pigass\CoreBundle\Form\StructureHandler;

/**
 * Structure controller.
 *
 * @Route("/")
 */
class StructureController extends Controller
{
    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /**
     * List the structures
     *
     * @Route("/", name="core_structure_index")
     * @Template()
     */
    public function indexAction()
    {
        $structures = $this->em->getRepository('PigassCoreBundle:Structure')->findAll();

        return array(
            'structures' => $structures,
        );
    }

    /**
     * Add a new structure
     *
     * @Route("/structure/new", name="core_structure_new")
     * @Template("PigassCoreBundle:Structure:edit.html.twig")
     * @Security\PreAuthorize("hasRole('ROLE_ADMIN')")
     */
    public function newAction()
    {
        $structures = $this->em->getRepository('PigassCoreBundle:Structure')->findAll();

        $structure = new Structure();
        $form = $this->createForm(new StructureType(), $structure);
        $formHandler = new StructureHandler($form, $this->$request, $this->em);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Structure "' . $structure . '" enregistrée.');
            return $this->redirect($this->generateUrl('core_structure_index'));
        }

        return array(
            'form'      => $form->createView(),
            'structure' => null,
        );
    }

    /**
     * Edit a structure
     *
     * @Route("/structure/{id}/edit", name="core_structure_edit", requirements={"id" = "\d+"})
     * @Template("PigassCoreBundle:Structure:edit.html.twig")
     * @Security\PreAuthorize("hasRole('ROLE_ADMIN')")
     */
    public function editAction(Structure $structure, Request $request)
    {
        $form = $this->createForm(new StructureType(), $structure);
        $formHandler = new StructureHandler($form, $request, $this->em);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Structure "' . $structure . '" modifiée.');
            return $this->redirect($this->generateUrl('core_structure_index'));
        }

        return array(
            'form'      => $form->createView(),
            'structure' => $structure,
        );
    }

    /**
     * Delete a structure
     *
     * @Route("/structure/{id}/delete", name="core_structure_delete", requirements={"id" = "\d+"})
     * @Security\PreAuthorize("hasRole('ROLE_ADMIN')")
     */
    public function deleteAction(Structure $structure)
    {
        $this->em->remove($structure);
        $this->em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Session "' . $structure . '" supprimée.');
        return $this->redirect($this->generateUrl('core_structure_index'));
    }
}
