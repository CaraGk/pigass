<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\CoreBundle\Entity\Fee,
    Pigass\CoreBundle\Form\FeeType,
    Pigass\CoreBundle\Form\FeeHandler,
    Pigass\CoreBundle\Entity\Structure,
    Pigass\UserBundle\Entity\Membership;

/**
 * Fee controller.
 *
 * @Route("/")
 */
class FeeController extends Controller
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
     * List the fees for fee
     *
     * @Route("/{slug}/fees", name="core_fee_index", requirements={"slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     * @Template()
     */
    public function indexAction($slug)
    {
        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
        }
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure)
            throw $this->createNotFoundException('Structure inconnue');

        $fees = $this->em->getRepository('PigassCoreBundle:Fee')->getForStructure($structure);

        return array(
            'structure' => $structure,
            'fees'  => $fees,
        );
    }

    /**
     * Add a new fee
     *
     * @Route("/{slug}/fee/new", name="core_fee_new", requirements={"slug" = "\w+"})
     * @Template("PigassCoreBundle:Fee:edit.html.twig")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function newAction($slug, Request $request)
    {
        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
        }
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure)
            throw $this->createNotFoundException('Structure inconnue');

        $fee = new Fee();
        $form = $this->createForm(feeType::class, $fee, array('structure' => $structure));
        $formHandler = new FeeHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Tarification pour "' . $structure . '" enregistrée.');
            return $this->redirect($this->generateUrl('core_fee_index', array('slug' => $structure->getSlug())));
        }

        return array(
            'form'  => $form->createView(),
            'fee'   => null,
        );
    }

    /**
     * Edit a fee
     *
     * @Route("/{slug}/fee/{id}/edit", name="core_fee_edit", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Template("PigassCoreBundle:Fee:edit.html.twig")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function editAction($slug, Fee $fee, Request $request)
    {
        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        }

        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure) {
            $this->session->getFlashBag()->add('error', 'La fee n\'existe pas ou vous n\'y avez pas accès.');
            return $this->redirect($this->generateUrl('core_fee_index', array('slug' => $slug)));
        }

        $form = $this->createForm(FeeType::class, $fee, array('structure' => $structure));
        $formHandler = new FeeHandler($form, $request, $this->em, $structure);

        if ($oldName = $formHandler->process()) {
            $parameters = $this->em->getRepository('PigassParameterBundle:Parameter')->getBySlug($oldName);

            $this->em->flush();

            $this->session->getFlashBag()->add('notice', 'fee "' . $fee . '" modifiée.');

            return $this->redirect($this->generateUrl('core_fee_index', array('slug' => $slug)));
        }

        return array(
            'form'      => $form->createView(),
            'fee' => $fee,
        );
    }

    /**
     * Delete a fee
     *
     * @Route("/{slug}/fee/{id}/delete", name="core_fee_delete", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function deleteAction($slug, Fee $fee)
    {
        $this->em->remove($fee);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Tarification "' . $fee . '" supprimée.');
        return $this->redirect($this->generateUrl('core_fee_index', ['slug' => $slug]));
    }

}
