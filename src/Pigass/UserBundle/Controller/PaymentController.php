<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Payum\Core\Request\GetHumanStatus;
use Pigass\UserBundle\Entity\Gateway,
    Pigass\UserBundle\Form\GatewayType,
    Pigass\UserBundle\Form\GatewayHandler;

class PaymentController extends Controller
{
    /** @DI\Inject */
    private $session;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("fos_user.user_manager") */
    private $um;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /**
     * Prepare action
     *
     * @Route("/member/{memberid}/payment/{gateway}", name="user_payment_prepare", requirements={"memberid" = "\d+", "gateway" = "\w+"})
     * @Security\PreAuthorize("hasRole('ROLE_MEMBER') or container.get('session').get('user_register_tmp')")
     */
    public function prepareAction($gateway, $memberid)
    {
        $membership = $this->em->getRepository('PigassUserBundle:Membership')->find($memberid);
        if ($this->session->get('user_register_tmp', false)) {
            $user = $membership->getPerson()->getUser();
        } else {
            $user = $this->um->findUserByUsername($this->get('security.token_storage')->getToken()->getUsername());
        }

        if (!$membership or $membership->getPerson()->getUser() !== $user)
            throw $this->createNotFoundException('Impossible d\'effectuer la transaction. Contactez un administrateur.');

        $slug = $membership->getStructure()->getSlug();

        $storage = $this->get('payum')->getStorage('Pigass\UserBundle\Entity\Payment');

        $payment = $storage->create();

        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($this->pm->findParamByName('reg_' . $slug . '_payment')->getValue() * 100);
        $payment->setDescription('Adhésion de ' . $user->getEmail() . ' via ' . $gateway);
        $payment->setClientId($membership->getId());
        $payment->setClientEmail($user->getEmail());

        $storage->update($payment);

        $captureToken = $this->get('payum')->getTokenFactory()->createCaptureToken(
            $gateway,
            $payment,
            'user_payment_done'
        );

        return $this->redirect($captureToken->getTargetUrl());
    }

    /**
     * Done transaction action
     *
     * @Route("/member/payment/valid", name="user_payment_done")
     * @Security\PreAuthorize("hasRole('ROLE_MEMBER') or container.get('session').get('user_register_tmp')")
     */
    public function doneAction(Request $request)
    {
        $token = $this->get('payum')->getHttpRequestVerifier()->verify($request);
        $gateway = $this->get('payum')->getGateway($token->getGatewayName());
        $gateway->execute($status = new GetHumanStatus($token));
        $payment = $status->getFirstModel();

        if ($status->isCaptured()) {
            if ($this->session->get('user_register_tmp'))
                $this->session->remove('user_register_tmp');

            $method = $this->em->getRepository('PigassUserBundle:Gateway')->findOneBy(array('gatewayName' => $token->getGatewayName()));
            if ($method->getFactoryName() == 'offline') {
                 $this->addFlash('warning', 'Choix enregistré. L\'adhésion sera validée une fois le chèque reçu.');
            } else {
                $membership = $this->em->getRepository('PigassUserBundle:Membership')->find($payment->getClientId());
                $membership->setPayedOn(new \DateTime('now'));
                $membership->setPayment($payment);
                $membership->setMethod($method);

                $this->em->persist($membership);
                $this->em->flush();

                $this->addFlash('notice', 'Le paiement a réussi. L\'adhésion est validée.');
            }
        } else {
             $this->addFlash('error', 'Le paiement a échoué.');
        }
        return $this->redirect($this->generateUrl('user_register_list'));
    }

    /**
     * Show gateways for structure
     *
     * @Route("/{slug}/gateway/index", name="user_payment_index", requirements={"slug" = "\w+"})
     * @Template()
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function indexAction($slug)
    {
        $gateways = $this->em->getRepository('PigassUserBundle:Gateway')->getBySlug($slug);

        if (!$gateways)
            throw $this->createNotFoundException('Impossible de trouver une Gateway associée à ' . $slug);

        return array(
            'gateways' => $gateways,
            'slug'     => $slug,
        );
    }

    /**
     * Add a new gateway for structure
     *
     * @Route("/{slug}/gateway/new", name="user_payment_new", requirements={"slug" = "\w+"})
     * @Template("PigassUserBundle:Payment:edit.html.twig")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function newAction(Request $request, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver la structure correspondante à "' . $slug . '".');

        $gateway = new Gateway();
        $form = $this->createForm(GatewayType::class, $gateway);
        $formHandler = new GatewayHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Moyen de paiement "' . $gateway . '" enregistré.');
            return $this->redirect($this->generateUrl('user_payment_index', array('slug' => $slug)));
        }

        return array(
            'form'    => $form->createview(),
            'gateway' => null,
            'slug'    => $slug,
        );
    }

    /**
     * Edit a gateway
     *
     * @Route("/{slug}/gateway/{id}/edit", name="user_payment_edit", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Template("PigassUserBundle:Payment:edit.html.twig")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function editAction(Gateway $gateway, Request $request, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver la structure correspondante à "' . $slug . '".');

        $form = $this->createForm(GatewayType::class, $gateway);
        $formHandler = new GatewayHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Moyen de paiement "' . $gateway . '" modifié.');
            return $this->redirect($this->generateUrl('user_payment_index', array('slug' => $slug)));
        }

        return array(
            'form'    => $form->createview(),
            'gateway' => $gateway,
            'slug'    => $slug,
        );
    }

    /**
     * Delete a gateway
     *
     * @Route("/{slug}/gateway/{id}/delete", name="user_payment_delete", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Security\Secure(roles="ROLE_ADMIN")
     */
    public function deleteAction(Gateway $gateway, $slug)
    {
        $this->em->remove($gateway);
        $this->em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Moyen de paiement "' . $gateway . '" supprimé.');
        return $this->redirect($this->generateUrl('user_payment_index', array('slug' => $slug)));
    }
}
