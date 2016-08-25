<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\RegisterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Payum\Core\Request\GetHumanStatus;

class PaymentController extends Controller
{
    /** @DI\Inject */
    private $request;

    /** @DI\Inject */
    private $router;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("for_user.user_manager") */
    private $um;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /**
     * Prepare action
     *
     * @Route("/member/{memberid}/payment/{gateway}", name="user_payment_prepare", requirements={"memberid" = "\d+", "gateway" = "\w+"})
     * @Security\PreAuthorize("hasRole('ROLE_MEMBER')")
     */
    public function prepareAction($gateway, $memberid)
    {
        $user = $this->um->findUserByUsername($this->get('security.token_storage')->getToken()->getUsername());
        $membership = $this->em->getRepository('PigassRegisterBundle:Membership')->find($memberid);

        if (!$membership or $membership->getPerson()->getUser() !== $user)
            throw $this->createNotFoundException('Impossible d\'effectuer la transaction. Contactez un administrateur.');

        if ($gateway == 1)
            $gateway = 'offline';
        elseif ($gateway == 2)
            $gateway = 'paypal';

        $storage = $this->get('payum')->getStorage('Pigass\RegisterBundle\Entity\Payment');

        $payment = $storage->create();

        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($pm->findParamByName('reg_payment')->getValue() * 100);
        $payment->setDescription('Adhésion de ' . $user->getEmail() . ' via ' . $gateway);
        $payment->setClientId($memberid);
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
     * @Route("/member/payment/valid", name="GRegister_PDone")
     * @Security\PreAuthorize("hasRole('ROLE_MEMBER')")
     */
    public function doneAction(Request $request)
    {
        $token = $this->get('payum')->getHttpRequestVerifier()->verify($request);
        $gateway = $this->get('payum')->getGateway($token->getGatewayName());
        $gateway->execute($status = new GetHumanStatus($token));
        $payment = $status->getFirstModel();

        if ($status->isCaptured()) {
            if ($gateway == 'offline') {
                 $this->addFlash('warning', 'Choix enregistré. L\'adhésion sera validée un fois le chèque reçu.');
            } else {
                $membership = $this->em->getRepository('PigassRegisterBundle:Membership')->find($payment->getClientId());
                $membership->setPayedOn(new \DateTime('now'));
                $membership->setPayment($payment);
                $membership->setMethod($gateway);

                $this->em->persist($membership);
                $this->em->flush();

                $this->addFlash('notice', 'Le paiement a réussi. L\'adhésion est validée.');
            }
        } else {
             $this->addFlash('error', 'Le paiement a échoué.');
        }
        return $this->redirect($this->generateUrl('user_register_list'));
    }
}
