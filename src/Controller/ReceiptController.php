<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2018 Pierre-François Angrand
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
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;
use Knp\Snappy\Pdf;
use App\Entity\Receipt,
    App\Form\ReceiptType,
    App\FormHandler\ReceiptHandler,
    App\Entity\Structure,
    App\Entity\Membership;

/**
 * Receipt controller.
 *
 * @Route("/")
 */
class ReceiptController extends AbstractController
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
     * List the receipts for receipt
     *
     * @Route("/{slug}/receipts", name="core_receipt_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function indexAction($slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
        }
        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure)
            throw $this->createNotFoundException('Structure inconnue');

        $receipts = $this->em->getRepository('App:Receipt')->getForStructure($structure);

        return array(
            'structure' => $structure,
            'receipts'  => $receipts,
        );
    }

    /**
     * Add a new receipt
     *
     * @Route("/{slug}/receipt/new", name="core_receipt_new", requirements={"slug" = "\w+"})
     * @Template("receipt/edit.html.twig")
     */
    public function newAction($slug, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
        }
        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));

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
            'form'    => $form->createView(),
            'receipt' => null,
            'slug'    => $slug,
        );
    }

    /**
     * Edit a receipt
     *
     * @Route("/{slug}/receipt/{id}/edit", name="core_receipt_edit", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Template("receipt/edit.html.twig")
     */
    public function editAction($slug, Receipt $receipt, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        }

        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure) {
            $this->session->getFlashBag()->add('error', 'La receipt n\'existe pas ou vous n\'y avez pas accès.');
            return $this->redirect($this->generateUrl('core_receipt_index', array('slug' => $slug)));
        }

        $form = $this->createForm(ReceiptType::class, $receipt, array('structure' => $structure));
        $formHandler = new ReceiptHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->em->flush();
            $this->session->getFlashBag()->add('notice', 'Receipt "' . $receipt . '" modifiée.');
            return $this->redirect($this->generateUrl('core_receipt_index', array('slug' => $slug)));
        }

        return array(
            'form'    => $form->createView(),
            'receipt' => $receipt,
            'slug'    => $slug,
        );
    }

    /**
     * Delete a receipt
     *
     * @Route("/receipt/{id}/delete", name="core_receipt_delete", requirements={"id" = "\d+"})
     */
    public function deleteAction(Receipt $receipt)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $this->em->remove($receipt);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Session "' . $receipt . '" supprimée.');
        return $this->redirect($this->generateUrl('core_receipt_index'));
    }

    /**
     * Build and download receipt for membership
     *
     * @Route("/member/{id}/receipt", name="core_receipt_build", requirements={"id" = "\d+"})
     */
    public function buildAction(Membership $membership, Pdf $snappy_pdf)
    {
        if (!$this->security->isGranted('ROLE_MEMBER'))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            if ($user->hasRole('ROLE_STRUCTURE')) {
                if ($membership->getStructure()->getSlug() != $this->session->get('slug')) {
                    $this->session->getFlashBag()->add('error', 'Vous n\'avez pas les autorisations pour accéder à cette adhésion.');
                    return $this->redirect($this->generateUrl('user_register_index', ['slug' => $this->session->get('slug')]));
                }
            } else {
                $person = $this->em->getRepository('App:Person')->findOneBy(['user' => $user->getId()]);
                if ($membership->getPerson() !== $person) {
                    $this->session->getFlashBag()->add('error', 'Vous n\'avez pas les autorisations pour accéder à cette adhésion.');
                    return $this->redirect($this->generateUrl('user_register_list'));
                }
            }

        }
        $receipt = $this->em->getRepository('App:Receipt')->getOneByDate($membership->getStructure(), $membership->getExpiredOn());
        if (!$receipt) {
            $this->session->getFlashBag()->add('error', 'Aucun signataire de reçu fiscal défini. Contactez votre structure.');
            return $this->redirect($this->generateUrl('user_register_list'));
        }

        $html = $this->renderView(
            'receipt/printPDF.html.twig',
            [
                'receipt'    => $receipt,
                'membership' => $membership,
            ]
        );
        $filename = "Recu_" . $membership->getStructure()->getSlug() . "_" . $membership->getPerson()->getName() . "_" . $membership->getExpiredOn()->format('Y') . ".pdf";

        return new Response(
            $snappy_pdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
}
