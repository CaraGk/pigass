<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2018 Pierre-François Angrand
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
use App\Entity\Structure,
    App\Form\StructureType,
    App\FormHandler\StructureHandler,
    App\Entity\Parameter,
    App\Entity\Gateway;
use Symfony\Component\HttpFoundation\File\File,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Structure controller.
 *
 * @Route("/")
 */
class StructureController extends AbstractController
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
     * Redirect to the right action
     *
     * @Route("/", name="core_structure_redirect")
     */
    public function redirectAction()
    {
        $user = $this->getUser();
        if ($user) {
            if ($slug = $this->session->get('slug', false)) {
                return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
            } elseif ($user->hasRole('ROLE_STRUCTURE')) {
                $person = $this->em->getRepository('App:Person')->getByUsername($user->getUsername());
                $membership = $this->em->getRepository('App:Membership')->getCurrentForPerson($person);
                if (!$membership) {
                    return $this->redirect($this->generateUrl('core_structure_map'));
                } else {
                    $slug = $membership->getStructure()->getSlug();
                    $this->session->set('slug', $slug);
                    return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
                }
            }

            if ($user->hasRole('ROLE_ADMIN')) {
                return $this->redirect($this->generateUrl('core_structure_map'));
            }

            if ($user->hasRole('ROLE_MEMBER')) {
                return $this->redirect($this->generateUrl('user_register_list'));
            }
        } else {
            return $this->redirect($this->generateUrl('core_structure_map'));
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

        $structures = $this->em->getRepository('App:Structure')->getAll($activated);

        return array(
            'structures' => $structures,
        );
    }

    /**
     * Map the structures
     *
     * @Route("/map", name="core_structure_map")
     * @Template()
     */
    public function mapAction()
    {
        $user = $this->getUser();
        if (isset($user) and $user->hasRole('ROLE_ADMIN'))
            $activated = false;
        else
            $activated = true;

        $structures = $this->em->getRepository('App:Structure')->getAll($activated);

        $areaMap = array();
        foreach ($structures as $structure) {
            if ($areas = $structure->getAreamap()) {
                foreach ($areas as $area) {
                    $areaMap[$area] = $structure;
                }
            }
        }

        return array(
            'structures' => $structures,
            'areaMap'    => $areaMap,
        );
    }

    /**
     * Add a new structure
     *
     * @Route("/structure/new", name="core_structure_new")
     * @Template("structure/edit.html.twig")
     */
    public function newAction(Request $request)
    {
        if (!$this->security->isGranted('ROLE_ADMIN'))
            throw new AccessDeniedException();

        $structure = new Structure();
        $form = $this->createForm(StructureType::class, $structure);
        $formHandler = new StructureHandler($form, $request, $this->em, $this->getParameter('app.logo_dir'));

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

            $this->session->getFlashBag()->add('notice', 'Structure "' . $structure . '" enregistrée.');
            return $this->redirect($this->generateUrl('core_structure_map'));
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
     * @Template("structure/edit.html.twig")
     */
    public function editAction(Structure $structure, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
            if (!$structure) {
                $this->session->getFlashBag()->add('error', 'La structure n\'existe pas ou vous n\'y avez pas accès.');
                return $this->redirect($this->generateUrl('core_structure_edit', array('slug' => $slug)));
            }
        }

        if ($structure->getLogo()) {
            $structure->setLogo(new File($this->getParameter('app.logo_dir') . '/' . $structure->getLogo()));
        }
        $form = $this->createForm(StructureType::class, $structure);
        $formHandler = new StructureHandler($form, $request, $this->em, $this->getParameter('app.logo_dir'));

        if ($oldName = $formHandler->process()) {
            $parameters = $this->em->getRepository('App:Parameter')->getBySlug($oldName);
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                $name = str_replace($oldName, $structure->getSlug(), $name);
                $parameter->setName($name);
                $this->em->persist($parameter);
            }
            $gateways = $this->em->getRepository('App:Gateway')->getBySlug($oldName);
            foreach ($gateways as $gateway) {
                $name = $gateway->getGatewayName();
                $name = str_replace($oldName, $structure->getSlug(), $name);
                $gateway->setGatewayName($name);
                $this->em->persist($gateway);
            }

            $this->em->flush();

            $this->session->getFlashBag()->add('notice', 'Structure "' . $structure . '" modifiée.');
            if ($user->hasRole('ROLE_ADMIN'))
                return $this->redirect($this->generateUrl('core_structure_map'));
            else
                return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
        }

        return array(
            'form'      => $form->createView(),
            'structure' => $structure,
        );
    }

    /**
     * Delete a structure
     *
     * @Route("/structure/{slug}/delete", name="core_structure_delete", requirements={"id" = "\d+"})
     */
    public function deleteAction($slug)
    {
        if (!$this->security->isGranted('ROLE_ADMIN'))
            throw new AccessDeniedException();

        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);

        $this->em->remove($structure);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Session "' . $structure . '" supprimée.');
        return $this->redirect($this->generateUrl('core_structure_map'));
    }
}
