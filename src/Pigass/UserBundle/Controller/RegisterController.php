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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\ResponseHeaderBag,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\UserBundle\Entity\Membership,
    Pigass\UserBundle\Entity\MemberQuestion;
use Pigass\UserBundle\Form\FilterType,
    Pigass\UserBundle\Form\FilterHandler,
    Pigass\UserBundle\Form\RegisterType,
    Pigass\UserBundle\Form\RegisterHandler,
    Pigass\UserBundle\Form\JoinType,
    Pigass\UserBundle\Form\JoinHandler,
    Pigass\UserBundle\Form\QuestionType,
    Pigass\UserBundle\Form\QuestionHandler,
    Pigass\UserBundle\Form\MemberQuestionType,
    Pigass\UserBundle\Form\MemberQuestionHandler,
    Pigass\UserBundle\Form\MembershipType,
    Pigass\UserBundle\Form\MembershipHandler;
use Symfony\Component\HttpFoundation\Response;

/**
 * UserBundle RegisterController
 *
 * @Route("/")
 */
class RegisterController extends Controller
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
     * List active memberships
     *
     * @Route("/{slug}/members", name="user_register_index", requirements={"slug" = "\w+"})
     * @Template()
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function indexAction($slug, Request $request)
    {
        $session_slug = $this->session->get('slug', null);
        $security = $this->container->get('security.authorization_checker');
        if (!$session_slug and !($security->isGranted('ROLE_ADMIN'))) {
            $actualUser = $this->get('security.token_storage')->getToken()->getUser();
            $actualPerson = $this->em->getRepository('PigassUserBundle:Person')->getByUser($actualUser);
            $actualMembership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($actualPerson, true);
            $slug = $actualMembership->getStructure()->getSlug();
            $this->session->set('slug', $slug);
        }
        $limit = $request->query->get('limit', null);
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneby(array('slug' => $slug));
        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($structure);

        $filters = $this->session->get('user_register_filter', array(
            'valid'     => null,
            'questions' => null,
        ));

        if (!isset($filters['valid'])) {
            $filters['valid'] = null;
        }

        if (!isset($filters['questions'])) {
            $filters['questions'] = null;
        }

        if (isset($filters['user']))
            $filters['user'] = null;
        $this->session->set('user_register_filter', $filters);

        $memberships = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentByStructure($slug, $filters);
        $count = count($memberships);

        return array(
            'memberships' => $memberships,
            'filters'     => $filters,
            'count'       => $count,
            'questions'   => $questions,
            'slug'        => $slug,
        );
    }

    /**
     * Validate offline payment
     *
     * @Route("/member/{id}/validate", name="user_register_validate", requirements={"id" = "\d+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function validateAction(Membership $membership, Request $request)
    {
        $userid = $request->query->get('userid', null);
        $view = $request->query->get('view', null);

        if (!$membership)
            throw $this->createNotFoundException('Unable to find Membership entity');

        if ($membership->getStatus() == 'registered') {
            if ($toPrintParam = $this->pm->findParamByName('reg_' . $membership->getStructure()->getSlug() . '_print')->getValue()) {
                $membership->setStatus('paid');
                $this->session->getFlashBag()->add('notice', 'Le paiement a été validé. La fiche d\'adhésion doit encore être validée.');
            } else {
                $membership->setStatus('validated');
                $this->session->getFlashBag()->add('notice', 'Le paiement a été validé. L\'adhésion est validée.');
            }
            $membership->setPayedOn(new \DateTime('now'));

            $params = array(
                'membership' => $membership,
                'print'      => $toPrintParam,
            );
            $sendmail = \Swift_Message::newInstance()
                ->setSubject('PIGASS - Paiement reçu')
                ->setFrom($this->container->getParameter('mailer_mail'))
                ->setTo($membership->getPerson()->getUser()->getEmailCanonical())
                ->setBody($this->renderView('PigassUserBundle:Payment:confirmPayment.html.twig', $params, 'text/html'))
                ->addPart($this->renderView('PigassUserBundle:Payment:confirmPayment.txt.twig', $params, 'text/plain'))
            ;
            $this->get('mailer')->send($sendmail);
        } elseif ($membership->getStatus() == 'paid') {
            $membership->setStatus('validated');
            $this->session->getFlashBag()->add('notice', 'La fiche d\'adhésion a été validée.');
            $sendmail = \Swift_Message::newInstance()
                ->setSubject('PIGASS - Adhésion validée')
                ->setFrom($this->container->getParameter('mailer_mail'))
                ->setTo($membership->getPerson()->getUser()->getEmailCanonical())
                ->setBody($this->renderView('PigassUserBundle:Payment:confirmPrint.html.twig', array('membership' => $membership), 'text/html'))
                ->addPart($this->renderView('PigassUserBundle:Payment:confirmPrint.txt.twig', array('membership' => $membership), 'text/plain'))
            ;
            $this->get('mailer')->send($sendmail);
        } elseif ($membership->getStatus() == 'validated') {
            $this->session->getFlashBag()->add('error', 'L\'adhésion a déjà été validée.');
        } else {
            $this->session->getFlashBag()->add('error', 'Le statut de l\'adhésion est inconnu.');
        }

        $this->em->persist($membership);
        $this->em->flush();
        $slug = $membership->getStructure()->getSlug();

        if ($view == 'index')
            return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
        else
            return $this->redirect($this->generateUrl('user_register_list', array('slug' => $slug, 'userid' => $userid)));
    }

    /**
     * Delete membership
     *
     * @Route("/member/{id}/delete", name="user_register_delete", requirements={"id" = "\d+"})
     * @Security\Secure(roles="ROLE_MEMBER, ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function deleteAction(Membership $membership, Request $request)
    {
        $security = $this->container->get('security.authorization_checker');
        $actualUser = $this->get('security.token_storage')->getToken()->getUser();
        $actualPerson = $this->em->getRepository('PigassUserBundle:Person')->getByUser($actualUser);
        $actualMembership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($actualPerson, true);

        if ($membership->getPerson()->getId() != $actualPerson->getId()) {
            if (!($security->isGranted('ROLE_ADMIN')) and
                !($security->isGranted('ROLE_STRUCTURE') and $membership->getStructure()->getId() != $actualMembership->getId())
            ) {
                throw $this->createAccessDeniedException();
            }
        }
        $view = $request->query->get('view', null);
        $userid = $request->query->get('userid', null);

        if (!$membership or $membership->getPayedOn() != null)
            throw $this->createNotFoundException('Unable to find Membership entity');

        $slug = $membership->getStructure()->getSlug();
        $this->em->remove($membership);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Adhésion supprimée !');

        if ($view == 'index')
            return $this->redirect($this->generateUrl('user_register_index'));
        else
            return $this->redirect($this->generateUrl('user_register_list', array('userid' => $userid, 'slug' => $slug)));
    }

    /**
     * Export active memberships
     *
     * @Route("/{slug}/members/export", name="user_register_export", requirements={"slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function exportAction($slug)
    {
        $memberships = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentByStructureWithInfos($slug);
        $memberquestions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->findAll();
        $memberinfos = $this->em->getRepository('PigassUserBundle:MemberInfo')->getCurrentInArray();

        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("PIGASS")
                       ->setTitle("Listing adhérents")
                       ->setSubject("Listing adhérents PIGASS");
        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->setTitle('Adherents');

        $i = 2;
        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Titre')
            ->setCellValue('B1', 'Nom')
            ->setCellValue('C1', 'Prénom')
            ->setCellValue('D1', 'Date de naissance')
            ->setCellValue('E1', 'Lieu de naissance')
            ->setCellValue('F1', 'Téléphone')
            ->setCellValue('G1', 'E-mail')
            ->setCellValue('H1', 'Nº')
            ->setCellValue('I1', 'Type')
            ->setCellValue('J1', 'Adresse')
            ->setCellValue('K1', 'Complément')
            ->setCellValue('L1', 'Code postal')
            ->setCellValue('M1', 'Ville')
            ->setCellValue('N1', 'Pays')
            ;
        $column = array('O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AZ');
        foreach ($memberquestions as $question) {
            $key = each($column);
            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue($key['value'] . '1', $question->getName());
            $columns[$question->getName()] = $key['value'];
        }
        $key = each($column);
        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue($key['value'] . '1', 'Mode de paiement');
        $columns['Mode de paiement'] = $key['value'];
        $key = each($column);
        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue($key['value'] . '1', 'Date d\'adhésion');
        $columns['Date d\'adhésion'] = $key['value'];

        foreach ($memberships as $membership) {
            $address = $membership->getPerson()->getAddress();
            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A'.$i, $membership->getPerson()->getTitle())
                ->setCellValue('B'.$i, $membership->getPerson()->getSurname())
                ->setCellValue('C'.$i, $membership->getPerson()->getName())
                ->setCellValue('D'.$i, $membership->getPerson()->getBirthday())
                ->setCellValue('E'.$i, $membership->getPerson()->getBirthplace())
                ->setCellValue('F'.$i, $membership->getPerson()->getPhone())
                ->setCellValue('G'.$i, $membership->getPerson()->getUser()->getEmail())
                ->setCellValue('H'.$i, $address['number'])
                ->setCellValue('I'.$i, $address['type'])
                ->setCellValue('J'.$i, $address['street'])
                ->setCellValue('K'.$i, $address['complement'])
                ->setCellValue('L'.$i, $address['code'])
                ->setCellValue('M'.$i, $address['city'])
                ->setCellValue('N'.$i, $address['country'])
                ->setCellValue($columns['Mode de paiement'].$i, $membership->getMethod()->getDescription())
                ->setCellValue($columns['Date d\'adhésion'].$i, $membership->getPayedOn())
            ;
            $count = 0;
            if (isset($memberinfos[$membership->getId()])) {
                foreach ($memberinfos[$membership->getId()] as $question => $info) {
                    $phpExcelObject->setActiveSheetIndex(0)
                        ->setCellValue($columns[$question].$i, $info);
                }
            }
            $i++;
        }

        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'adherents.xls');
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * Add Filter action
     *
     * @Route("/{slug}/filter/add/{type}/{id}/{value}", name="user_register_filter_add", requirements={"slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function addFilterAction($type, $id, $value, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $filters = $this->session->get('user_register_filter', array(
            'valid'     => null,
            'questions' => null,
        ));

        if ($type == "valid") {
            $filters['valid'] = $value;
        } else {
            $filters[$type][$id] = $value;
        }

        $this->session->set('user_register_filter', $filters);

        return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
    }

    /**
     * Remove Filter action
     *
     * @Route("/{slug}/filter/remove/{type}/{id}", name="user_register_filter_remove", requirements={"slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function removeFilterAction($type, $id, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $filters = $this->session->get('user_register_filter', array(
            'valid'     => null,
            'questions' => null,
        ));

        if ($type == "valid") {
            $filters['valid'] = null;
        } else {
            if ($filters[$type][$id] != null) {
                unset($filters[$type][$id]);
            }
        }

        $this->session->set('user_register_filter', $filters);

        return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
    }

    /**
     * Register Person and create Membership
     *
     * @Route("/{slug}/register/", name="user_register_register", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function registerAction(Request $request, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->session->getFlashBag()->add('error', 'Utilisateur déjà enregistré');
            return $this->redirect($this->generateUrl('user_register_join', array('slug' => $slug)));
        }

        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $token = $tokenGenerator->generateToken();

        $form = $this->createForm(RegisterType::class);
        $form_handler = new RegisterHandler($form, $request, $this->em, $this->um, $token);

        if($result = $form_handler->process()) {
            /* If User is allready in db (former registration error) */
            if (!$result['error']) {
                $this->session->set('user_register_tmp', 1);
                $this->session->getFlashBag()->add('notice', 'Utilisateur ' . $result['user']->getUsername() . ' créé.');

                return $this->redirect($this->generateUrl('user_register_confirmation_send', array('email' => $result['user']->getUsername(), 'slug' => $slug)));
            } else {
                $this->session->getFlashBag()->add('error', 'L\'utilisateur ' . $result['user']->getUsername() . ' existe déjà en base de données.');

                /* If User has never been activated, act as new */
                if (!$result['user']->isEnabled()) {
                    $this->session->set('user_register_tmp', 1);
                    $this->session->getFlashBag()->add('error', 'L\'utilisateur n\'a jamais été activé. Renvoi du mail d\'activation en cours.');

                    return $this->redirect($this->generateUrl('user_register_confirmation_send', array('email' => $result['user']->getUsername(), 'slug' => $slug)));
                } else {
                    $this->session->getFlashBag()->add('error', 'Veuillez vous connecter pour adhérer. En cas de perte du mot de passe, réinitialisez-le ci-dessous.');

                    return $this->redirect($this->generateUrl('fos_user_resetting_request'));
                }
            }
        }

        return array(
            'form'      => $form->createView(),
            'structure' => $structure,
        );
    }

    /**
     * Send confirmation email
     *
     * @Route("/{slug}/register/send/{email}", name="user_register_confirmation_send", requirements={"email" = ".+\@.+\.\w+", "slug" = "\w+" })
     * @Template()
     */
    public function sendConfirmationAction($email, Request $request, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $user = $this->um->findUserByUsername($email);
        if(!$user)
            throw $this->createNotFoundException('Aucun utilisateur lié à cette adresse mail.');
        $person = $this->em->getRepository('PigassUserBundle:Person')->findOneBy(array('user' => $user));

        if(!$user->getConfirmationToken())
            throw $this->createNotFoundException('Cet utilisateur n\'a pas de jeton de confirmation défini. Est-il déjà validé ? Contactez un administrateur.');

        if ($this->session->get('user_register_tmp'))
            $this->session->set('user_register_tmp', $email);

        $url = $this->generateUrl('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
        $params = array(
            'user'      => $user,
            'url'       => $url,
            'person'    => $person,
            'structure' => $structure,
        );
        $sendmail = \Swift_Message::newInstance()
                ->setSubject('PIGASS - Confirmation d\'adresse mail')
                ->setFrom($this->container->getParameter('mailer_mail'))
                ->setTo($user->getEmailCanonical())
                ->setBody($this->renderView('PigassUserBundle:Register:confirmation.html.twig', $params, 'text/html'))
                ->addPart($this->renderView('PigassUserBundle:Register:confirmation.txt.twig', $params, 'text/plain'))
        ;
        $this->get('mailer')->send($sendmail);

        return array(
            'email' => $user->getEmailCanonical(),
            'slug'  => $slug,
        );
    }

    /**
     * Join action
     *
     * @Route("/{slug}/member/join", name="user_register_join", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function joinAction(Request $request, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED') and !$this->session->get('user_register_tmp'))
            return $this->redirect($this->generateUrl('user_register_register', array('slug' => $slug)));

        if ($username = $this->session->get('user_register_tmp'))
            $user = $this->um->findUserByUsername($username);
        else
            $user = $this->getUser();

        $userid = $request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);
        $current_membership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person);
        $now = new \DateTime('now');
        $reg_anticipated = $this->pm->findParamByName('reg_' . $slug . '_anticipated')->getValue();
        $anticipated = $now->modify($reg_anticipated);

        if (null !== $current_membership and $current_membership->getExpiredOn() > $anticipated) {
            $this->session->getFlashBag()->add('error', 'Adhésion déjà à jour de cotisation.');

            if ($userid and $user->hasRole('ROLE_ADMIN'))
                return $this->redirect($this->generateUrl('user_register_list', array("userid" => $userid)));
            else
                return $this->redirect($this->generateUrl('user_register_list'));
        }

        $membership = new Membership();
        $form = $this->createForm(JoinType::class, $membership, array('structure' => $structure));
        $form_handler = new JoinHandler($form, $request, $this->em, $this->pm->findParamByName('reg_' . $slug . '_payment')->getValue(), $person, $structure, $this->pm->findParamByName('reg_' . $slug . '_date')->getValue(), $this->pm->findParamByName('reg_' . $slug . '_periodicity')->getValue(), $reg_anticipated);

        if($form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Adhésion enregistrée pour ' . $person . '.');

            return $this->redirect($this->generateUrl('user_payment_prepare', array('gateway' => $membership->getMethod()->getGatewayName(), 'memberid' => $membership->getId())));
        }

        return array(
            'form'      => $form->createView(),
            'structure' => $structure,
        );

    }

    /**
     * Get printable PDF for membership
     *
     * @Route("/member/{id}/print/", name="user_register_print", requirements={"id" = "\d+"})
     */
    public function printPDFAction(Membership $membership)
    {
        $config = $membership->getMethod()->getConfig();
        if (isset($config['address'])) {
            $address = $config['address']['number'] . ' ' . $config['address']['type'] . ' ' . $config['address']['street'];
            if ($config['address']['complement'])
                $address .= ', ' . $config['address']['complement'];
            $address .= ', ' . $config['address']['code'] . ', ' . $config['address']['city'] . ', ' . $config['address']['country'];
        } else {
            $address = 'non définie';
        }
        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($membership->getStructure());
        $infos = $this->em->getRepository('PigassUserBundle:MemberInfo')->getByMembership($membership->getPerson(), $membership);
        if (count($questions) > count($infos)) {
            $form = $this->createForm(QuestionType::class, null, array('questions' => $questions));
        }

        $html = $this->renderView(
            'PigassUserBundle:Register:printPDF.html.twig',
            array(
                'membership' => $membership,
                'config'     => $config,
                'address'    => $address,
                'payableTo'  => (isset($config['payableTo'])?$config['payableTo']:'non défini'),
                'form'       => ($form?$form->createView():null),
                'questions'  => $questions,
                'infos'      => $infos,
                'iban'       => (isset($config['iban'])?$config['iban']:null),
        ));
        $filename = "Adhesion_" . $membership->getPerson()->getName() . "_" . $membership->getExpiredOn()->format('Y');

        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
    /**
     * Complementary questions to member
     *
     * @Route("/member/questions", name="user_register_question")
     * @Template()
     * @Security\Secure(roles="ROLE_MEMBER")
     */
    public function questionAction(Request $request)
    {
        $user = $this->getUser();
        $filter = $this->session->get('user_register_filter', null);
        $userid = isset($filter['user'])?$filter['user']:$request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);
        $membership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person);
        $structure = $membership->getStructure();
        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($structure);
        $member_infos = $this->em->getRepository('PigassUserBundle:MemberInfo')->getByMembership($person, $membership);

        if ($member_infos and count($member_infos) < count($questions)) {
            $exclude = '(';
            foreach ($member_infos as $member_info) {
                $exclude .= $member_info->getQuestion()->getId() . ', ';
            }
            $exclude .= '0)';
            $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($structure, $exclude);
        }

        $form = $this->createForm(QuestionType::class, null, array('questions' => $questions));
        $form_handler = new QuestionHandler($form, $request, $this->em, $membership, $questions);
        if($form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Informations complémentaires enregistrées.');

            return $this->redirect($this->generateUrl('user_register_list'));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * List complementary questions
     *
     * @Route("/question", name="user_register_question_index")
     * @Template()
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function questionIndexAction()
    {
        $slug = $this->session->get('slug');
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($structure);

        return array(
            'questions' => $questions,
            'slug'      => $slug,
        );
    }

    /**
     * Add a new complementary question
     *
     * @Route("/question/new", name="user_register_question_new")
     * @Template("PigassUserBundle:Register:questionEdit.html.twig")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function questionNewAction(Request $request)
    {
        $slug = $this->session->get('slug');
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if ($slug and !$user->hasRole('ROLE_ADMIN')) {
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
            if (!$structure)
                throw $this->createNotFoundException('Impossible de trouver la structure réferente.');
        } else {
            $structure = null;
        }

        $question = new MemberQuestion();
        $form = $this->createForm(MemberQuestionType::class, $question, array('structure' => $structure));
        $formHandler = new MemberQuestionHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Question complémentaire "' . $question . '" enregistrée.');
            return $this->redirect($this->generateUrl('user_register_question_index'));
        }

        return array(
            'form'     => $form->createView(),
            'question' => null,
        );
    }

    /**
     * Edit a complementary question
     *
     * @Route("/question/{id}/edit", name="user_register_question_edit")
     * @Template()
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function questionEditAction(MemberQuestion $question, Request $request)
    {
        $slug = $this->session->get('slug');
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if ($slug and !$user->hasRole('ROLE_ADMIN')) {
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
            if (!$structure)
                throw $this->createNotFoundException('Impossible de trouver la structure réferente.');
        } else {
            $structure = null;
        }

        $form = $this->createForm(MemberQuestionType::class, $question, array('structure' => $structure));
        $formHandler = new MemberQuestionHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Question complémentaire "' . $question . '" enregistrée.');
            return $this->redirect($this->generateUrl('user_register_question_index'));
        }

        return array(
            'form'     => $form->createView(),
            'question' => $question,
        );
    }

    /**
     * List own memberships action
     *
     * @Route("/member/list", name="user_register_list")
     * @Template()
     * @Security\Secure(roles="ROLE_MEMBER, ROLE_STRUCTURE")
     */
    public function listAction(Request $request)
    {
        $user = $this->getUser();
        $filter = $this->session->get('user_register_filter', null);
        $register = $this->session->get('user_register_register', false);
        $userid = isset($filter['user'])?$filter['user']:$request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);
        $current_membership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person);
        $reJoinable = false;

        if (($userid == null or $register == true) and $current_membership) {
            $structure = $current_membership->getStructure();
            $slug = $structure->getSlug();
            $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($structure);
            $member_infos = $this->em->getRepository('PigassUserBundle:MemberInfo')->getByMembership($person, $current_membership);
            if (count($member_infos) < count($questions)) {
                return $this->redirect($this->generateUrl('user_register_question'));
            } elseif ($register) {
                $this->session->remove('user_register_register');
            }
            $now = new \DateTime('now');
            $now->modify($this->pm->findParamByName('reg_' . $slug . '_anticipated')->getValue());
            if ($current_membership->getExpiredOn() <= $now) {
                $reJoinable = true;
            }
        } else {
            $slug = $request->get('slug', null);
            if (!$current_membership)
                $reJoinable = true;
        }

        $memberships = $this->em->getRepository('PigassUserBundle:Membership')->findBy(array('person' => $person));

        return array(
            'memberships' => $memberships,
            'userid'      => $userid,
            'person'      => $person,
            'slug'        => $slug,
            'reJoinable'  => $reJoinable,
        );
    }

    /**
     * Show MemberInfo action
     *
     * @Route("/member/{id}/infos/", name="user_register_infos", requirements={"id" = "\d+"})
     * @Template()
     * @Security\Secure(roles="ROLE_MEMBER, ROLE_STRUCTURE")
     */
    public function showInfosAction(Membership $membership, Request $request)
    {
        $user = $this->getUser();
        $userid = $request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);

        if (!$membership) {
            $this->session->getFlashBag()->add('error', 'Adhésion inconnue.');
            return $this->redirect($this->generateUrl('user_register_list'));
        }

        $memberinfos = $this->em->getRepository('PigassUserBundle:MemberInfo')->getByMembership($person, $membership);

        return array(
            'infos'   => $memberinfos,
            'userid'  => $userid,
            'person'  => $person,
        );
    }

    /**
     * Add membership and person
     *
     * @Route("/{slug}/new", name="user_register_membership_new")
     * @Template("PigassUserBundle:Register:join.html.twig")
     * @Security\Secure(roles="ROLE_ADMIN, ROLE_STRUCTURE")
     */
    public function newMembershipAction($slug, Request $request)
    {
        $adminUser = $this->getUser();
        $adminPerson = $this->em->getRepository('PigassUserBundle:Person')->getByUser($adminUser);
        $adminMembership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($adminPerson, true);
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');
        if (!$adminUser->hasRole('ROLE_ADMIN') and $adminMembership->getStructure()->getSlug() != $slug)
            throw $this->createNotFoundException('Vous n\'avez pas les droits pour accéder à cette structure.');

        $membership = new Membership();
        $options = array(
            'payment'     => $this->pm->findParamByName('reg_' . $slug . '_payment')->getValue(),
            'date'        => $this->pm->findParamByName('reg_' . $slug . '_date')->getValue(),
            'periodicity' => $this->pm->findParamByName('reg_' . $slug . '_periodicity')->getValue(),
            'anticipated' => $this->pm->findParamByName('reg_' . $slug . '_anticipated')->getValue(),
        );
        $form = $this->createForm(MembershipType::class, $membership, array('structure' => $structure));
        $form_handler = new MembershipHandler($form, $request, $this->em, $this->um, $structure, $options);

        if($form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Adhésion enregistrée pour ' . $membership->getPerson() . '.');

            $filter = $this->session->get('user_register_filter', array());
            $filter['user'] = $membership->getPerson()->getUser()->getId();
            $this->session->set('user_register_filter', $filter);
            $this->session->set('user_register_register', true);

            return $this->redirect($this->generateUrl('user_payment_prepare', array(
                'gateway' => $membership->getMethod()->getGatewayName(),
                'memberid' => $membership->getId()
            )));
        }

        return array(
            'form'      => $form->createView(),
            'structure' => $structure,
        );
    }

    /**
     * Test for admin take over function
     *
     * @return Person
     */
    private function testAdminTakeOver($user, $user_id = null)
    {
        if (($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_STRUCTURE')) and $user_id != null) {
            $user = $this->um->findUserBy(array(
                'id' => $user_id,
            ));
        }

        $person = $this->em->getRepository('PigassUserBundle:Person')->getByUsername($user->getUsername());

        if (!$person) {
            $this->session->getFlashBag()->add('error', 'Étudiant inconnu.');
            return $this->redirect($this->generateUrl('user_register_list'));
        } else {
            return $person;
        }
    }
}
