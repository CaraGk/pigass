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

        if (!$membership or ($membership->getPerson()->getUser() !== $user and !($user->hasRole('ROLE_ADMIN') or ($user->hasRole('ROLE_STRUCTURE')))))
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
            $membership = $this->em->getRepository('PigassUserBundle:Membership')->find($payment->getClientId());
            $structure = $membership->getStructure();
            $toPrintParam = $this->pm->findParamByName('reg_' . $structure->getSlug() . '_print')->getValue();
            $details = $payment->getDetails();

            if ($method->getFactoryName() == 'offline') {
                $config = $method->getConfig();
                if (isset($config['address'])) {
                    $address = $config['address']['number'] . ' ' . $config['address']['type'] . ' ' . $config['address']['street'];
                    if ($config['address']['complement'])
                        $address .= ', ' . $config['address']['complement'];
                    $address .= ', ' . $config['address']['code'] . ', ' . $config['address']['city'] . ', ' . $config['address']['country'];
                } else {
                    $address = 'non définie';
                }

                $this->addFlash('warning', 'L\'adhésion ne pourra être validée qu\'une fois le paiement reçu.');
                $this->addFlash('notice', 'Pour un paiement par chèque : le chèque de ' . $membership->getAmount() . ' euros est à libeller à l\'ordre de ' . (isset($config['payableTo'])?$config['payableTo']:'non défini') . ' et à retourner à l\'adresse ' . $address . '.');
                if (isset($config['iban'])) {
                    $this->addFlash('notice', 'Pour un paiement par virement bancaire : l\'IBAN du compte est ' . $config['iban'] . '. N\'oubliez pas de préciser « Adhésion ' . $membership->getPerson()->getSurname() . ' ' . $membership->getPerson()->getName() . ' » en commentaire.');
                } else {
                    $this->addFlash('notice', 'Pour un paiement par virement : veuillez contacter la structure pour effectuer le virement.');
                }
                if ($toPrintParam) {
                    $this->addFlash('warning', 'Attention : pour que votre adhésion soit validée, il faut également que vous imprimiez la fiche d\'adhésion et que vous la retourniez signée à l\'adresse ' . $structure->getPrintableAddress() . '.');
                }
            } elseif ($method->getFactoryName() == 'paypal_express_checkout') {
                if ($details['ACK'] == 'Success') {
                    $membership->setPayedOn(new \DateTime('now'));
                    $this->addFlash('notice', 'Le paiement de ' . $membership->getAmount() . ' euros par Paypal Express a réussi. L\'adhésion est validée.');
                    if ($toPrintParam) {
                        $membership->setStatus('paid');
                        $this->addFlash('warning', 'Attention : pour que votre adhésion soit validée, il faut également que vous imprimiez la fiche d\'adhésion et que vous la retourniez signée à l\'adresse ' . $structure->getPrintableAddress() . '.');
                    } else {
                        $membership->setStatus('validated');
                    }
                } elseif ($details['ACK'] == 'Pending') {
                    $this->addFlash('notice', 'Le paiement de ' . $membership->getAmount() . ' euros par Paypal Express est en attente d\'une confirmation. L\'adhésion sera validée dès la confirmation reçue.');
                } else {
                    $this->addFlash('notice', 'Le paiement de ' . $membership->getAmount() . ' euros par Paypal Express a échoué. Veuillez contacter l\'administrateur du site.');
                }

                $membership->setPayment($payment);
            }
            $membership->setMethod($method);

            $this->em->persist($membership);
            $this->em->flush();

            $params = array(
                'membership'  => $membership,
                'print'       => $toPrintParam,
                'pay_address' => ($address?$address:null),
            );
            $sendmail = \Swift_Message::newInstance()
                ->setSubject('PIGASS - Demande d\'adhésion enregistré')
                ->setFrom($this->container->getParameter('mailer_mail'))
                ->setTo($membership->getPerson()->getUser()->getEmailCanonical())
                ->setBody($this->renderView('PigassUserBundle:Payment:confirmMember.html.twig', $params, 'text/html'))
                ->addPart($this->renderView('PigassUserBundle:Payment:confirmMember.txt.twig', $params, 'text/plain'))
            ;
            $this->get('mailer')->send($sendmail);
        } else {
             $this->addFlash('error', 'Le paiement a échoué ou a été annulé. En cas de problème, veuillez contacter l\'administrateur du site.');
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
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(['slug' => $slug]);
        $gateways = $this->em->getRepository('PigassUserBundle:Gateway')->getBySlug($slug);

        if (!$gateways)
            throw $this->createNotFoundException('Impossible de trouver une Gateway associée à ' . $slug);

        return array(
            'gateways'  => $gateways,
            'structure' => $structure,
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
