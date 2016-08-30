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
    Symfony\Component\HttpFoundation\Request;
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
    Pigass\UserBundle\Form\MemberQuestionHandler;

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
     * @Route("/{slug}/members", name="user_register_index")
     * @Template()
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function indexAction($slug, Request $request)
    {
        $limit = $request->query->get('limit', null);
        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->findAll();
        $membership_filters = $this->session->get('user_register_filter', array(
            'valid'     => null,
            'questions' => null,
        ));
        $memberships = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentByStructure($slug, $membership_filters);

        return array(
            'memberships' => $memberships,
            'filters'     => $membership_filters,
            'questions'   => $questions,
            'slug'        => $slug,
        );
    }

    /**
     * Validate offline payment
     *
     * @Route("/member/{id}/validate", name="user_register_validate", requirements={"id" = "\d+"})
     * @Security\Secure(roles="ROLE_STRUCTURE")
     */
    public function validateAction(Membership $membership, Request $request)
    {
        $userid = $request->query->get('userid', null);
        $view = $request->query->get('view', null);

        if (!$membership or $membership->getPayedOn() != null)
            throw $this->createNotFoundException('Unable to find Membership entity');

        $membership->setPayedOn(new \DateTime('now'));
        $this->em->persist($membership);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Paiement validé !');

        if ($view == 'index')
            return $this->redirect($this->generateUrl('user_registre_index'));
        else
            return $this->redirect($this->generateUrl('user_register_list', array('userid' => $userid)));
    }

    /**
     * Delete membership
     *
     * @Route("/member/{id}/delete", name="user_register_delete", requirements={"id" = "\d+"})
     * @Security\Secure(roles="ROLE_ADMIN")
     */
    public function deleteAction(Membership $membership, Request $request)
    {
        $userid = $request->query->get('userid', null);
        $view = $request->query->get('view', null);

        if (!$membership or $membership->getPayedOn() != null)
            throw $this->createNotFoundException('Unable to find Membership entity');

        $this->em->remove($membership);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Adhésion supprimée !');

        if ($view == 'index')
            return $this->redirect($this->generateUrl('user_register_index'));
        else
            return $this->redirect($this->generateUrl('user_register_list', array('userid' => $userid)));
    }

    /**
     * Export active memberships
     *
     * @Route("/{slug}/members/export", name="user_register_export")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function exportAction($slug)
    {
        $memberships = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentByStructureComplete($slug);
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
            ->setCellValue('O1', 'Ville d\'externat')
            ->setCellValue('P1', 'Rang de classement')
            ->setCellValue('Q1', 'ECN')
            ->setCellValue('R1', 'Stages validés')
            ;
        $column = array('S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AZ');
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
                ->setCellValue('P'.$i, $membership->getPerson()->getRanking())
                ->setCellValue('Q'.$i, $membership->getPerson()->getGraduate())
                ->setCellValue($columns['Mode de paiement'].$i, $membership->getReadableMethod())
                ->setCellValue($columns['Date d\'adhésion'].$i, $membership->getPayedOn())
            ;
            $count = 0;
            foreach ($membership->getPerson()->getPlacements() as $placement) {
                if ($placement->getRepartition()->getPeriod()->getEnd() < new \DateTime('now')) {
                    $count++;
                    $phpExcelObject->setActiveSheetIndex(0)
                        ->setCellValue($columns[$placement->getRepartiton()->getDepartment()->getSector()->getName()].$i, 'oui');
                }
            }
            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('R'.$i, $count);
            foreach ($memberinfos[$membership->getId()] as $question => $info) {
                $phpExcelObject->setActiveSheetIndex(0)
                    ->setCellValue($columns[$question].$i, $info);
            }
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
     * @Route("/{slug}/filter/add/{type}/{id}/{value}", name="user_register_filter_add")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function addFilterAction($type, $id, $value, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $membership_filters = $this->session->get('user_register_filter', array(
            'valid'     => null,
            'questions' => null,
        ));

        if ($type == "valid") {
            $membership_filters['valid'] = $value;
        } else {
            $membership_filters[$type][$id] = $value;
        }

        $this->session->set('user_register_filter', $membership_filters);

        return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
    }

    /**
     * Remove Filter action
     *
     * @Route("/{slug}/filter/remove/{type}/{id}", name="user_register_filter_remove")
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function removeFilterAction($type, $id, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $membership_filters = $this->session->get('user_register_filter', array(
            'valid'     => null,
            'questions' => null,
        ));

        if ($type == "valid") {
            $membership_filters['valid'] = null;
        } else {
            if ($membership_filters[$type][$id] != null) {
                unset($membership_filters[$type][$id]);
            }
        }

        $this->session->set('user_register_filter', $membership_filters);

        return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
    }

    /**
     * Register Person and create Membership
     *
     * @Route("/{slug}/register/", name="user_register_register")
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

        if($username = $form_handler->process()) {
            $this->session->set('user_register_tmp', 1);
            $this->session->getFlashBag()->add('notice', 'Utilisateur ' . $username . ' créé.');

            return $this->redirect($this->generateUrl('user_register_confirmation_send', array('email' => $username, 'slug' => $slug)));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * Send confirmation email
     *
     * @Route("/{slug}/register/send/{email}", name="user_register_confirmation_send", requirements={"email" = ".+\@.+\.\w+" })
     * @Template()
     */
    public function sendConfirmationAction($email, Request $request, $slug)
    {
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $username = $request->query->get('username');
        $user = $this->um->findUserByUsername($email);

        if(!$user)
            throw $this->createNotFoundException('Aucun utilisateur lié à cette adresse mail.');

        if(!$user->getConfirmationToken())
            throw $this->createNotFoundException('Cet utilisateur n\'a pas de jeton de confirmation défini. Est-il déjà validé ? Contactez un administrateur.');

        if ($this->session->get('user_register_tmp'))
            $this->session->set('user_register_tmp', $email);

        $url = $this->generateUrl('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), true);
        $sendmail = \Swift_Message::newInstance()
                ->setSubject('PIGASS - Confirmation d\'adresse mail')
                ->setFrom($this->container->getParameter('mailer_mail'))
                ->setTo($user->getEmailCanonical())
                ->setBody($this->renderView('PigassUserBundle:Register:confirmation.html.twig', array('user' => $user, 'url' => $url)), 'text/html')
                ->addPart($this->renderView('PigassUserBundle:Register:confirmation.txt.twig', array('user' => $user, 'url' => $url)), 'text/plain')
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
     * @Route("/{slug}/member/join", name="user_register_join")
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
            $user = $this->um->findUserByUsername($this->get('security.token_storage')->getToken()->getUsername());

        $userid = $request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);

        if (null !== $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person)) {
            $this->session->getFlashBag()->add('error', 'Adhésion déjà à jour de cotisation.');

            if ($userid and $user->hasRole('ROLE_ADMIN'))
                return $this->redirect($this->generateUrl('user_register_list', array("userid" => $userid)));
            else
                return $this->redirect($this->generateUrl('user_register_list'));
        }

        $form = $this->createForm(JoinType::class);
        $form_handler = new JoinHandler($form, $request, $this->em, $this->pm->findParamByName('reg_payment')->getValue(), $person, $structure, $this->pm->findParamByName('reg_date')->getValue(), $this->pm->findParamByName('reg_periodicity')->getValue());

        if($membership = $form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Adhésion enregistrée pour ' . $person . '.');

            return $this->redirect($this->generateUrl('user_payment_prepare', array('gateway' => $membership->getMethod()->getGatewayName(), 'memberid' => $membership->getId())));
        }

        return array(
            'form' => $form->createView(),
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
        $username = $this->get('security.token_storage')->getToken()->getUsername();
        $person = $this->em->getRepository('PigassUserBundle:Person')->getByUsername($username);

        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->findAll();
        $membership = $this->em->getRepository('Pigass\UserBundle\Entity\Membership')->getCurrentForPerson($person);

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
        $structure_filter = $this->session->get('slug');

        $questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->getAll($structure_filter);

        return array(
            'questions' => $questions,
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
        $structure_filter = $this->session->get('slug');
        if ($structure_filter and !$this->um->findUserByUsername($username)->hasRole('ROLE_ADMIN')) {
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $structure_filter));
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
            'form' => $form->createView(),
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
        $structure_filter = $this->session->get('slug');
        if ($structure_filter and !$this->um->findUserByUsername($username)->hasRole('ROLE_ADMIN')) {
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $structure_filter));
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
        $user = $this->um->findUserByUsername($this->get('security.token_storage')->getToken()->getUsername());
        $userid = $request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);

        if ($userid == null && $current_membership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person)) {
            $count_infos = $this->em->getRepository('PigassUserBundle:MemberInfo')->countByMembership($person, $current_membership);
            $count_questions = $this->em->getRepository('PigassUserBundle:MemberQuestion')->countAll();
            if ($count_infos < $count_questions) {
                return $this->redirect($this->generateUrl('user_register_question'));
            }
            $slug = $current_membership->getStructure()->getSlug();
        } else {
            $slug = $request->get('slug');
        }

        $memberships = $this->em->getRepository('PigassUserBundle:Membership')->findBy(array('person' => $person));

        return array(
            'memberships' => $memberships,
            'userid'      => $userid,
            'person'      => $person,
            'slug'        => isset($slug)?$slug:null,
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
        $user = $this->um->findUserByUsername($this->get('security.token_storage')->getToken()->getUsername());
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
     * Test for admin take over function
     *
     * @return Person
     */
    private function testAdminTakeOver($user, $user_id = null)
    {
        if ($user->hasRole('ROLE_ADMIN') and $user_id != null) {
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
