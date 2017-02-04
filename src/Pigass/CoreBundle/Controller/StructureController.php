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
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File;
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
    /** @DI\Inject */
    private $session;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /** @DI\Inject("fos_user.user_manager") */
    private $um;

    /**
     * Redirect to the right action
     *
     * @Route("/", name="core_structure_redirect")
     */
    public function redirectAction()
    {
        $username = $this->get('security.token_storage')->getToken()->getUsername();
        if ($username != "anon.") {
            $user = $this->um->findUserByUsername($username);
            if ($user->hasRole('ROLE_ADMIN')) {
                return $this->redirect($this->generateUrl('core_structure_index'));
            } elseif($slug = $this->session->get('slug', false)) {
                return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
            } elseif ($user->hasRole('ROLE_STRUCTURE')) {
                $person = $this->em->getRepository('PigassUserBundle:Person')->getByUsername($username);
                $membership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person);
                $slug = $membership->getStructure()->getSlug();
                $this->session->set('slug', $slug);
                return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
            } elseif ($user->hasRole('ROLE_MEMBER')) {
                return $this->redirect($this->generateUrl('user_register_list'));
            } else {
                return $this->redirect($this->generateUrl('core_structure_index'));
            }
        } else {
            return $this->redirect($this->generateUrl('core_structure_index'));
        }
    }

    /**
     * List the structures
     *
     * @Route("/structure", name="core_structure_index")
     * @Template()
     */
    public function indexAction()
    {
        $user = $this->getUser();
        if (isset($user) and $user->hasRole('ROLE_ADMIN'))
            $activated = false;
        else
            $activated = true;

        $structures = $this->em->getRepository('PigassCoreBundle:Structure')->getAll($activated);

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
        $structure = new Structure();
        $form = $this->createForm(StructureType::class, $structure);
        $formHandler = new StructureHandler($form, $request, $this->em, $this->getParameter('logo_dir'));

        if ($formHandler->process()) {
            $slug = $structure->getSlug();
            $now = new \DateTime('now');
            $parameters = array(
                0 => array('setName' => 'reg_' . $slug . '_date', 'setValue' => $now->format('d-m-Y'), 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Date anniversaire des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                1 => array('setName' => 'reg_' . $slug . '_periodicity', 'setValue' => '+ 1 year', 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Périodicité des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("1 mois" => "+ 1 month", "2 mois" => "+ 2 months", "6 mois" => "+ 6 months", "1 an" => "+ 1 year", "2 ans" => "+ 2 years", "3 ans" => "+ 3 years"), 'setStructure' => $structure),
                2 => array('setName' => 'reg_' . $slug . '_payment', 'setValue' => 60, 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Montant de la cotisation (EUR)', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                3 => array('setName' => 'reg_' . $slug . '_print', 'setValue' => true, 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Nécessité de retourner le bulletin d\'adhésion imprimé et signé', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("Oui" => true, "Non" => false), 'setStructure' => $structure),
                4 => array('setName' => 'reg_' . $slug . '_anticipated', 'setValue' => '+ 3 months', 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Adhésions anticipées de :', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("Pas d'adhésion anticipée" => "- 0 day", "1 mois" => "- 1 month", "2 mois" => "- 2 months", "3 mois" => "- 3 months", "4 mois" => "- 4 months", "5 mois" => "-5 months", "6 mois" => "-6 months"), 'setStructure' => $structure),
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
            $gateway->setGatewayName($slug . '_offline');
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
     * @Route("/structure/{slug}/edit", name="core_structure_edit", requirements={"slug" = "\w+"})
     * @Template("PigassCoreBundle:Structure:edit.html.twig")
     * @Security\Secure(roles="ROLE_ADMIN")
     */
    public function editAction(Structure $structure, Request $request)
    {
        if ($structure->getLogo()) {
            $structure->setLogo(new File($this->getParameter('logo_dir') . '/' . $structure->getLogo()));
        }
        $form = $this->createForm(StructureType::class, $structure);
        $formHandler = new StructureHandler($form, $request, $this->em, $this->getParameter('logo_dir'));

        if ($oldName = $formHandler->process()) {
            $parameters = $this->em->getRepository('PigassParameterBundle:Parameter')->getBySlug($oldName);
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                $name = str_replace($oldName, $structure->getSlug(), $name);
                $parameter->setName($name);
                $this->em->persist($parameter);
            }
            $gateways = $this->em->getRepository('PigassUserBundle:Gateway')->getBySlug($oldName);
            foreach ($gateways as $gateway) {
                $name = $gateway->getGatewayName();
                $name = str_replace($oldName, $structure->getSlug(), $name);
                $gateway->setGatewayName($name);
                $this->em->persist($gateway);
            }

            $this->em->flush();

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
