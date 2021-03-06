<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\Security\Core\Security,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Payum\Core\Payum,
    Payum\Core\Request\GetHumanStatus;
use App\Entity\Gateway,
    App\Form\GatewayType,
    App\FormHandler\GatewayHandler;

class PaymentController extends Controller
{
    protected $security, $session, $em, $um, $checker;

    public function __construct(Security $security, SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em, AuthorizationCheckerInterface $checker)
    {
        $this->security = $security;
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
        $this->checker = $checker;
    }

    /**
     * Prepare action
     *
     * @Route("/member/{memberid}/payment/{gateway}", name="user_payment_prepare", requirements={"memberid" = "\d+", "gateway" = "\w+"})
     */
    public function prepareAction($gateway, $memberid)
    {
        $payum = $this->get('payum');
        $membership = $this->em->getRepository('App:Membership')->find($memberid);
        if (!$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $membership->getPerson()->getUser();
        } else {
            $user = $this->getUser();
        }

        if (!$membership or ($membership->getPerson()->getUser() !== $user
            and !($user->hasRole('ROLE_ADMIN')
                or ($user->hasRole('ROLE_STRUCTURE')
        ))))
            throw $this->createNotFoundException('Impossible d\'effectuer la transaction. Contactez un administrateur.');

        $slug = $membership->getStructure()->getSlug();

        $storage = $payum->getStorage('App\Entity\Payment');

        $gateway_object = $this->em->getRepository('App:Gateway')->findOneBy(['gatewayName' => $gateway]);
        if ($membership->getAmount() <= 0 and $gateway_object->getFactoryName() != "offline") {
            $gateways = $this->em->getRepository('App:Gateway')->findBy(['structure' => $gateway_object->getStructure(), 'factoryName' => 'offline']);
            $gateway = $gateways[0]->getGatewayName();
        }

        $payment = $storage->create();

        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($membership->getAmount());
        $payment->setDescription('Adhésion de ' . $user->getEmail() . ' via ' . $gateway);
        $payment->setClientId($membership->getId());
        $payment->setClientEmail($user->getEmail());

        $storage->update($payment);

        $captureToken = $payum->getTokenFactory()->createCaptureToken(
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
     */
    public function doneAction(Request $request)
    {
        $payum = $this->get('payum');
        $token = $payum->getHttpRequestVerifier()->verify($request);
        $gateway = $payum->getGateway($token->getGatewayName());
        $gateway->execute($status = new GetHumanStatus($token));
        $payment = $status->getFirstModel();

        if ($status->getValue() == "captured" OR $status->getValue() == "pending") {
            $method = $this->em->getRepository('App:Gateway')->findOneBy(array('gatewayName' => $token->getGatewayName()));
            $membership = $this->em->getRepository('App:Membership')->find($payment->getClientId());
            $structure = $membership->getStructure();
            $toPrintParam = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_print')->getValue();
            $details = $payment->getDetails();

            $address = '';
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
                if (!isset($config['external'])) {
                    $this->addFlash('notice', 'Pour un paiement par chèque : le chèque de ' . $membership->getAmount(true) . ' est à libeller à l\'ordre de ' . (isset($config['payableTo'])?$config['payableTo']:'non défini') . ' et à retourner à l\'adresse ' . $address . '.');
                    if (isset($config['iban'])) {
                        $this->addFlash('notice', 'Pour un paiement par virement bancaire : l\'IBAN du compte est ' . $config['iban'] . '. N\'oubliez pas de préciser « Adhésion ' . $membership->getPerson()->getSurname() . ' ' . $membership->getPerson()->getName() . ' » en commentaire.');
                    } else {
                        $this->addFlash('notice', 'Pour un paiement par virement : veuillez contacter la structure pour effectuer le virement.');
                    }
                }
                if ($toPrintParam) {
                    $this->addFlash('warning', 'Attention : pour que votre adhésion soit validée, il faut également que vous imprimiez la fiche d\'adhésion et que vous la retourniez signée à l\'adresse ' . $structure->getPrintableAddress() . '.');
                }
            } elseif ($method->getFactoryName() == 'paypal_express_checkout') {
                if ($details['ACK'] == 'Success') {
                    if ($details['CHECKOUTSTATUS'] == 'PaymentActionNotInitiated') {
                        $this->addFlash('error', 'Le paiement a été annulé. Veuillez reconsidérer vos moyens de paiement.');
                        return $this->redirect($this->generateUrl('user_register_join', ['slug' => $structure->getSlug(), 'userid' => $membership->getPerson()->getUser()->getId()]));
                    } elseif ($details['CHECKOUTSTATUS'] == 'PaymentActionFailed') {
                        $this->addFlash('error', 'Le paiement est indiqué en erreur par Paypal. Veuillez reconsidérer vos moyens de paiement.');
                        return $this->redirect($this->generateUrl('user_register_join', ['slug' => $structure->getSlug(), 'userid' => $membership->getPerson()->getUser()->getId()]));
                    } else {
                        $membership->setPayedOn(new \DateTime('now'));
                        $this->addFlash('notice', 'Le paiement de ' . $membership->getAmount(true) . ' par Paypal Express a réussi. L\'adhésion est validée.');
                    }

                    $fee = $this->em->getRepository('App:Fee')->findOneBy(['amount' => $membership->getAmount(), 'structure' => $membership->getStructure()]);
                    if ($toPrintParam) {
                        if (!$fee->isDefault()) {
                            $membership->setStatus('registered');
                            $this->addFlash('warning', 'Rappel : pour que votre adhésion à tarif réduit soit validée, il faut que vous transmettiez les pièces justificatives de votre statut à ' . $structure->getEmail() . '.');
                        } else {
                            $membership->setStatus('paid');
                        }
                        $this->addFlash('warning', 'Attention : pour que votre adhésion soit validée, il faut également que vous imprimiez la fiche d\'adhésion et que vous la retourniez signée à l\'adresse ' . $structure->getPrintableAddress() . '.');
                    } else {
                        if (!$fee->isDefault()) {
                            $membership->setStatus('registered');
                            $this->addFlash('warning', 'Rappel : pour que votre adhésion à tarif réduit soit validée, il faut que vous transmettiez les pièces justificatives de votre statut à ' . $structure->getEmail() . '.');
                        } else {
                            $membership->setStatus('validated');
                        }
                    }
                } elseif ($details['ACK'] == 'Pending') {
                    $this->addFlash('notice', 'Le paiement de ' . $membership->getAmount(true) . ' par Paypal Express est en attente d\'une confirmation. L\'adhésion sera validée dès la confirmation reçue.');
                } else {
                    $this->addFlash('notice', 'Le paiement de ' . $membership->getAmount(true) . ' par Paypal Express a échoué. Veuillez contacter l\'administrateur du site. ('.$details['ACK'].')');
                }

            }
            $membership->setPayment($payment);
            $membership->setMethod($method);

            $this->em->persist($membership);
            $this->em->flush();

            $params = array(
                'membership'  => $membership,
                'print'       => $toPrintParam,
                'pay_address' => ($address?$address:null),
            );
            $sendmail = (new \Swift_Message('PIGASS - Demande d\'adhésion enregistrée'))
                ->setSubject('PIGASS - Demande d\'adhésion enregistrée')
                ->setFrom($this->getParameter('app.mailer_admin'))
                ->setReplyTo($structure->getEmail())
                ->setTo($membership->getPerson()->getUser()->getEmailCanonical())
                ->setBody($this->renderView('payment/confirm_member.txt.twig', $params, 'text/plain'))
            ;
            $this->get('mailer')->send($sendmail);

            if (isset($config['external'])) {
                return $this->redirect($config['external']);
            }

            if (!$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $user = $membership->getPerson()->getUser();
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                return $this->redirect($this->generateUrl('user_register_confirmation_send', ['email' => $user->getUsername(), 'slug' => $structure->getSlug()]));
            }
            return $this->redirect($this->generateUrl('app_dashboard_user', ['slug' => $membership->getStructure()->getSlug()]));
        } else {
            $this->addFlash('error', 'Le paiement a échoué ou a été annulé. En cas de problème, veuillez contacter l\'administrateur du site.');
        }
        return $this->redirect($this->generateUrl('app_structure_map'));
    }

    /**
     * Show gateways for structure
     *
     * @Route("/{slug}/gateway/index", name="user_payment_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function indexAction($slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);
        $gateways = $this->em->getRepository('App:Gateway')->getBySlug($slug);

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
     * @Template("payment/edit.html.twig")
     */
    public function newAction(Request $request, $slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver la structure correspondante à "' . $slug . '".');

        $gateway = new Gateway();
        $form = $this->createForm(GatewayType::class, $gateway);
        $formHandler = new GatewayHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Moyen de paiement "' . $gateway . '" enregistré.');
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
     * @Template("payment/edit.html.twig")
     */
    public function editAction(Gateway $gateway, Request $request, $slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver la structure correspondante à "' . $slug . '".');

        $form = $this->createForm(GatewayType::class, $gateway);
        $formHandler = new GatewayHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Moyen de paiement "' . $gateway . '" modifié.');
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
     */
    public function deleteAction(Gateway $gateway, $slug)
    {
        if (!$this->security->isGranted('ROLE_ADMIN'))
            throw new AccessDeniedException();

        $this->em->remove($gateway);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Moyen de paiement "' . $gateway . '" supprimé.');
        return $this->redirect($this->generateUrl('user_payment_index', array('slug' => $slug)));
    }
}
