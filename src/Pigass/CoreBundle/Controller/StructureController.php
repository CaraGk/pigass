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
use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\CoreBundle\Entity\Structure,
    Pigass\CoreBundle\Form\StructureType,
    Pigass\CoreBundle\Form\StructureHandler;
use Pigass\ParameterBundle\Entity\Parameter,
    Pigass\UserBundle\Entity\Gateway;

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
     * @Security\Secure(roles="ROLE_ADMIN")
     */
    public function newAction(Request $request)
    {
        $structures = $this->em->getRepository('PigassCoreBundle:Structure')->findAll();

        $structure = new Structure();
        $form = $this->createForm(StructureType::class, $structure);
        $formHandler = new StructureHandler($form, $request, $this->em);

        if ($formHandler->process()) {
            $now = new \DateTime('now');
            $parameters = array(
                0 => array('setName' => 'reg_date', 'setValue' => $now->format('Y-m-d H:i:s'), 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Date anniversaire des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                1 => array('setName' => 'reg_periodicity', 'setValue' => '+ 1 year', 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Périodicité des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => 'a:6:{s:9:"+ 1 month";s:6:"1 mois";s:10:"+ 2 months";s:6:"2 mois";s:10:"+ 6 months";s:6:"6 mois";s:8:"+ 1 year";s:4:"1 an";s:9:"+ 2 years";s:5:"2 ans";s:9:"+ 3 years";s:5:"3 ans";}', 'setStructure' => $structure),
                2 => array('setName' => 'reg_payment', 'setValue' => 60, 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Montant de la cotisation (EUR)', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
            );
            foreach ($parameters as $parameter) {
                $structure_parameter = new Parameter();
                foreach ($parameter as $name => $value) {
                    $structure_parameter->$name($value);
                }
                $this->em->persist($structure_parameter);
            }

            $gateway = new Gateway();
            $gateway->setStructure($structure);
            $gateway->setReadableName('Chèque ou espèces');
            $gateway->setGatewayName($structure->getSlug() . '_offline');
            $gateway->setFactoryName('offline');
            $this->em->persist($gateway);

            $this->em->flush();

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
        $form = $this->createForm(StructureType::class, $structure);
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
