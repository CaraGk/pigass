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
use Pigass\CoreBundle\Entity\Receipt,
    Pigass\CoreBundle\Form\ReceiptType,
    Pigass\CoreBundle\Form\ReceiptHandler,
    Pigass\CoreBundle\Entity\Structure,
    Pigass\UserBundle\Entity\Membership;

/**
 * Receipt controller.
 *
 * @Route("/")
 */
class ReceiptController extends Controller
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
     * List the receipts for receipt
     *
     * @Route("/{slug}/receipts", name="core_receipt_index", requirements={"slug" = "\w+"})
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

        $receipts = $this->em->getRepository('PigassCoreBundle:Receipt')->getForStructure($structure);

        return array(
            'structure' => $structure,
            'receipts'  => $receipts,
        );
    }

    /**
     * Add a new receipt
     *
     * @Route("/{slug}/receipt/new", name="core_receipt_new", requirements={"slug" = "\w+"})
     * @Template("PigassCoreBundle:Receipt:edit.html.twig")
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

        $receipt = new Receipt();
        $form = $this->createForm(ReceiptType::class, $receipt, array('structure' => $structure));
        $formHandler = new ReceiptHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Reçus fiscaux par "' . $receipt->getPerson() . '" enregistrés.');
            return $this->redirect($this->generateUrl('core_receipt_index', array('slug' => $structure->getSlug())));
        }

        return array(
            'form'      => $form->createView(),
            'receipt'   => null,
        );
    }

    /**
     * Edit a receipt
     *
     * @Route("/{slug}/receipt/{id}/edit", name="core_receipt_edit", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Template("PigassCoreBundle:Receipt:edit.html.twig")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function editAction($slug, Receipt $receipt, Request $request)
    {
        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        }

        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure) {
            $this->session->getFlashBag()->add('error', 'La receipt n\'existe pas ou vous n\'y avez pas accès.');
            return $this->redirect($this->generateUrl('core_receipt_index', array('slug' => $slug)));
        }

        if ($receipt->getImage()) {
            $receipt->setImage(new File($this->getParameter('logo_dir') . '/signs/' . $receipt->getImageName()));
        }
        $form = $this->createForm(ReceiptType::class, $receipt, array('structure' => $structure));
        $formHandler = new ReceiptHandler($form, $request, $this->em, $structure);

        if ($oldName = $formHandler->process()) {
            $parameters = $this->em->getRepository('PigassParameterBundle:Parameter')->getBySlug($oldName);

            $this->em->flush();

            $this->session->getFlashBag()->add('notice', 'Receipt "' . $receipt . '" modifiée.');

            return $this->redirect($this->generateUrl('core_receipt_index', array('slug' => $slug)));
        }

        return array(
            'form'      => $form->createView(),
            'receipt' => $receipt,
        );
    }

    /**
     * Delete a receipt
     *
     * @Route("/receipt/{id}/delete", name="core_receipt_delete", requirements={"id" = "\d+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function deleteAction(Receipt $receipt)
    {
        $this->em->remove($receipt);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Session "' . $receipt . '" supprimée.');
        return $this->redirect($this->generateUrl('core_receipt_index'));
    }

    /**
     * Build and download receipt for membership
     *
     * @Route("/member/{id}/receipt", name="core_receipt_build", requirements={"id" = "\d+"})
     * @Security\Secure(roles="ROLE_MEMBER")
     */
    public function buildAction(Membership $membership)
    {
        $receipt = $this->em->getRepository('PigassCoreBundle:Receipt')->getOneByDate($membership->getStructure(), $membership->getExpiredOn());
        $html = $this->renderView(
            'PigassCoreBundle:Receipt:printPDF.html.twig',
            [
                'receipt' => $receipt,
                'membership' => $membership,
            ]
        );
        $filename = "Recu_" . $membership->getStructure()->getSlug() . "_" . $membership->getPerson()->getName() . "_" . $membership->getExpiredOn()->format('Y');

        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
}
