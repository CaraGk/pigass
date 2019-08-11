<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2018 Pierre-François Angrand
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
use Symfony\Component\HttpFoundation\ResponseHeaderBag,
    Symfony\Component\Routing\Generator\UrlGeneratorInterface,
    Symfony\Component\Validator\Constraints\File,
    Symfony\Component\HttpFoundation\Response,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\UserBundle\Util\TokenGeneratorInterface,
    Knp\Snappy\Pdf,
    Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet,
    PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Entity\Membership,
    App\Entity\MemberQuestion,
    App\Entity\MemberInfo,
    App\Entity\User,
    App\Entity\Person,
    App\Entity\Structure,
    App\Entity\Fee;
use App\Form\FilterType,
    App\FormHandler\FilterHandler,
    App\FormHandler\QuestionHandler,
    App\Form\MemberQuestionType,
    App\FormHandler\MemberQuestionHandler,
    App\Form\MembershipType,
    App\FormHandler\MembershipHandler,
    App\Form\ImportType;

/**
 * UserBundle RegisterController
 *
 * @Route("/")
 */
class RegisterController extends AbstractController
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
     * List active memberships
     *
     * @Route("/{slug}/members", name="user_register_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function indexAction($slug, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $session_slug = $this->session->get('slug', null);
        if (!$session_slug and !($this->security->isGranted('ROLE_ADMIN'))) {
            $connectedUser = $this->getUser();
            $connectedPerson = $this->em->getRepository('App:Person')->getByUser($connectedUser);
            $connectedMembership = $this->em->getRepository('App:Membership')->getCurrentForPerson($connectedPerson, true);
            if (!$connectedMembership) {
                $this->session->getFlashBag()->add('notice', 'Adhésion périmée. Veuillez réadhérer pour accéder aux fonctionnalités d\'administration.');
                return $this->redirect($this->generateUrl('user_register_register', array('slug' => $slug)));
            }
            $slug = $connectedMembership->getStructure()->getSlug();
            $this->session->set('slug', $slug);
        }
        $limit = $request->query->get('limit', null);
        $structure = $this->em->getRepository('App:Structure')->findOneby(array('slug' => $slug));
        $questions = $this->em->getRepository('App:MemberQuestion')->getAll($structure, "('8', '3', '5', '1')", null);
        $fees = $this->em->getRepository('App:Fee')->getForStructure($structure);

        $filters = $this->session->get('user_register_filter', [
            'valid'     => null,
            'ending'    => null,
            'fee'       => null,
            'questions' => null,
            'search'    => null,
            'expiration'=> null,
        ]);

        $filters['user'] = null;
        if ($search = $request->query->get('search', null))
            $filters['search'] = $search;
        elseif ($request->query->get('skipsearch', false))
            $filters['search'] = null;
        if (!isset($filters['valid']))
            $filters['valid'] = null;
        if (!isset($filters['ending']))
            $filters['ending'] = null;
        if (!isset($filters['questions']))
            $filters['questions'] = null;
        if (!isset($filters['fee']))
            $filters['fee'] = null;
        if (!isset($filters['search']))
            $filters['search'] = null;
        if (!isset($filters['expiration']))
            $filters['expiration'] = null;
        $this->session->set('user_register_filter', $filters);
        $reg_anticipated = $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_anticipated')->getValue();
        $now = new \DateTime('now');
        $anticipated = $now->modify($reg_anticipated);

        $date = new \DateTime($request->query->get('date', 'now'));
        $expire = $this->getExpirationDate($structure, $date);

        if (isset($filters['expiration']) and $filters['expiration']) {
            $expire_string = $filters['expiration'][0]->format('Y-m-d') . '|' . $filters['expiration'][1]->format('Y-m-d');
        } else {
            $expire_string = $this->getExpirationDate($structure, $date, true)->format('Y-m-d') . '|' . $expire->format('Y-m-d');
        }

        if ($filters['search'])
            $memberships = $this->em->getRepository('App:Membership')->getByStructure($slug, null, $filters, $anticipated);
        else
            $memberships = $this->em->getRepository('App:Membership')->getByStructure($slug, $expire, $filters, $anticipated);
        $count = count($memberships);

        return array(
            'memberships' => $memberships,
            'filters'     => $filters,
            'count'       => $count,
            'questions'   => $questions,
            'slug'        => $slug,
            'fees'        => count($fees)>1?$fees:null,
            'expire'      => $expire_string,
        );
    }

    /**
     * Validate offline payment
     *
     * @Route("/member/{id}/validate", name="user_register_validate", requirements={"id" = "\d+"})
     * @Template()
     */
    public function validateAction(Membership $membership, Request $request, \Swift_Mailer $mailer)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $userid = $request->query->get('userid', null);
        $view = $request->query->get('view', null);

        if (!$membership)
            throw $this->createNotFoundException('Unable to find Membership entity');

        $structure = $membership->getStructure();
        $slug = $structure->getSlug();

        if ($membership->getStatus() == 'paid') {
                $membership->setStatus('validated');
                $this->session->getFlashBag()->add('notice', 'La fiche d\'adhésion a été validée.');
                $sendmail = (new \Swift_Message('PIGASS - Adhésion validée'))
                    ->setSubject('PIGASS - Adhésion validée')
                    ->setFrom($this->getParameter('app.mailer_admin'))
                    ->setReplyTo($structure->getEmail())
                    ->setTo($membership->getPerson()->getUser()->getEmailCanonical())
                    ->setBody($this->renderView('payment/confirm_print.txt.twig', array('membership' => $membership), 'text/plain'))
                ;
                $mailer->send($sendmail);
                $this->em->persist($membership);
                $this->em->flush();

                if ($view == 'index')
                    return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
                else
                    return $this->redirect($this->generateUrl('user_register_list', array('slug' => $slug, 'userid' => $userid)));
        } else {
            $options = [
                'payment'     => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_payment')->getValue(),
                'date'        => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_date')->getValue(),
                'periodicity' => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_periodicity')->getValue(),
                'anticipated' => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_anticipated')->getValue(),
            ];
            $form = $this->createForm(MembershipType::class, $membership, ['structure' => $structure, 'withPayment' => true]);
            $form_handler = new MembershipHandler($form, $request, $this->em, $this->um, $structure, $options, $membership->getPerson());

            if($form_handler->process()) {
                if ($membership->getStatus() == 'registered') {
                    if ($toPrintParam = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_print')->getValue()) {
                        $membership->setStatus('paid');
                        $this->session->getFlashBag()->add('notice', 'Le paiement a été validé. La fiche d\'adhésion doit encore être validée.');
                    } else {
                        $membership->setStatus('validated');
                        $this->session->getFlashBag()->add('notice', 'Le paiement a été validé. L\'adhésion est validée.');
                    }

                    $params = array(
                        'membership' => $membership,
                        'print'      => $toPrintParam,
                    );
                    $sendmail = (new \Swift_Message('PIGASS - Paiement reçu'))
                        ->setSubject('PIGASS - Paiement reçu')
                        ->setFrom($this->getParameter('app.mailer_admin'))
                        ->setReplyTo($structure->getEmail())
                        ->setTo($membership->getPerson()->getUser()->getEmailCanonical())
                        ->setBody($this->renderView('payment/confirm_payment.txt.twig', $params, 'text/plain'))
                    ;
                    $mailer->send($sendmail);
                } elseif ($membership->getStatus() == 'validated') {
                    $this->session->getFlashBag()->add('warning', 'L\'adhésion a déjà été mise à jour.');
                } else {
                    $this->session->getFlashBag()->add('error', 'Le statut de l\'adhésion est inconnu.');
                }

                $this->em->persist($membership);
                $this->em->flush();

                if ($view == 'index')
                    return $this->redirect($this->generateUrl('user_register_index', array('slug' => $slug)));
                else
                    return $this->redirect($this->generateUrl('user_register_list', array('slug' => $slug, 'userid' => $userid)));
            }
        }

        return array(
            'form'       => $form->createView(),
            'membership' => $membership,
        );
    }

    /**
     * Exclude a member
     *
     * @Route("/member/{id}/exclude", name="user_register_exclude", requirements={"slug" = "\w+", "id" = "\d+"})
     */
    public function excludeAction(Membership $membership, Request $request)
    {
        $user_membership = $this->em->getRepository('App:Membership')->getCurrentForPerson($this->em->getRepository('App:Person')->getByUser($this->getUser()));
        $user_structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $user_membership->getStructure()->getSlug()]);
        if (!$this->security->isGranted('ROLE_STRUCTURE') or $user_structure !== $membership->getStructure())
            throw new AccessDeniedException();

        $membership->setStatus('excluded');
        $this->em->persist($membership);
        $this->em->flush();

        return $this->redirect($this->generateUrl('user_register_list', ['slug' => $user_structure->getSlug(), 'userid' => $membership->getPerson()->getUser()->getId()]));
    }

    /**
     * Delete membership
     *
     * @Route("/member/{id}/delete", name="user_register_delete", requirements={"id" = "\d+"})
     */
    public function deleteAction(Membership $membership, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN') or $this->security->isGranted('ROLE_MEMBER')))
            throw new AccessDeniedException();

        $connectedPerson = $this->em->getRepository('App:Person')->getByUser($this->getUser());
        $connectedMembership = $this->em->getRepository('App:Membership')->getCurrentForPerson($connectedPerson, true);
        if (!$connectedMembership) {
            $this->session->getFlashBag()->add('notice', 'Adhésion périmée. Veuillez réadhérer pour accéder aux fonctionnalités d\'administration.');
            return $this->redirect($this->generateUrl('user_register_register', array('slug' => $slug)));
        }

        if (
            $membership->getPerson()->getId() != $connectedPerson->getId()
            and !(
                $this->security->isGranted('ROLE_ADMIN')
                or (
                    $this->security->isGranted('ROLE_STRUCTURE')
                    and $membership->getStructure() === $connectedMembership->getStructure()
                )
            )
        ) {
            throw new AccessDeniedException();
        }
        $view = $request->query->get('view', null);
        $userid = $request->query->get('userid', null);

        if (!$membership or
            ($membership->getPayedOn() != null and $membership->getStatus() != "excluded")
        )
            throw $this->createNotFoundException('Unable to find Membership entity');

        $slug = $membership->getStructure()->getSlug();
        $this->em->remove($membership);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Adhésion supprimée !');

        if ($view == 'index')
            return $this->redirect($this->generateUrl('user_register_index', ['slug' => $slug]));
        else
            return $this->redirect($this->generateUrl('user_register_list', array('userid' => $userid, 'slug' => $slug)));
    }

    /**
     * Export active memberships
     *
     * @Route("/{slug}/members/export", name="user_register_export", requirements={"slug" = "\w+"})
     */
    public function exportAction(Structure $structure, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $date = new \DateTime($request->query->get('date', 'now'));
        $expire = $this->getExpirationDate($structure, $date);

        $memberships = $this->em->getRepository('App:Membership')->getByStructureWithInfos($structure->getSlug(), $expire);
        $memberquestions = $this->em->getRepository('App:MemberQuestion')->findAll();
        $memberinfos = $this->em->getRepository('App:MemberInfo')->getCurrentInArray();

        $spreadsheet = new spreadsheet();
        $spreadsheet->getProperties()->setCreator("PIGASS")
                       ->setTitle("Listing adhérents")
                       ->setSubject("Listing adhérents PIGASS");
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Adherents');

        $i = 2;
        $sheet
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
            $sheet->setCellValue($key['value'] . '1', $question->getName());
            $columns[$question->getName()] = $key['value'];
        }
        $key = each($column);
        $sheet->setCellValue($key['value'] . '1', 'Mode de paiement');
        $columns['Mode de paiement'] = $key['value'];
        $key = each($column);
        $sheet->setCellValue($key['value'] . '1', 'Date d\'adhésion');
        $columns['Date d\'adhésion'] = $key['value'];

        foreach ($memberships as $membership) {
            $address = $membership->getPerson()->getAddress();
            $sheet
                ->setCellValue('A'.$i, $membership->getPerson()->getTitle())
                ->setCellValue('B'.$i, $membership->getPerson()->getSurname())
                ->setCellValue('C'.$i, $membership->getPerson()->getName())
                ->setCellValue('D'.$i, $membership->getPerson()->getBirthday()->format("d-m-Y"))
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
                ->setCellValue($columns['Date d\'adhésion'].$i, $membership->getPayedOn()->format("d-m-Y"))
            ;
            $count = 0;
            if (isset($memberinfos[$membership->getId()])) {
                foreach ($memberinfos[$membership->getId()] as $question => $info) {
                    $sheet->setCellValue($columns[$question].$i, $info);
                }
            }
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "Adherents.xlsx";
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        return $this->file($temp_file, $filename, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * Import memberships in a structure
     *
     * @Route("/{slug}/members/import", name="user_register_import", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function importAction($slug, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $error = null;
        $listUsers = $this->em->getRepository('App:User')->getAllEmail();
        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');
        $fields = array(
            ['name' => 'title', 'label' => 'Titre', 'required' => false],
            ['name' => 'surname', 'label' => 'Nom', 'required' => true],
            ['name' => 'name', 'label' => 'Prénom', 'required' => true],
            ['name' => 'email', 'label' => 'E-mail', 'required' => true],
            ['name' => 'birthday', 'label' => 'Date de naissance', 'required' => false],
            ['name' => 'birthplace', 'label' => 'Lieu de naissance', 'required' => false],
            ['name' => 'phone', 'label' => 'Phone', 'required' => false],
            ['name' => 'address_number', 'label' => 'Adresse : numéro (ou adresse complète si champ unique)', 'required' => false],
            ['name' => 'address_type', 'label' => 'Adresse : type', 'required' => false],
            ['name' => 'address_street', 'label' => 'Adresse : voie', 'required' => false],
            ['name' => 'address_complement', 'label' => 'Adresse : complément', 'required' => false],
            ['name' => 'address_code', 'label' => 'Adresse : code postal', 'required' => false],
            ['name' => 'address_city', 'label' => 'Adresse : ville', 'required' => false],
            ['name' => 'address_country', 'label' => 'Adresse : pays', 'required' => false],
            ['name' => 'membership_amount', 'label' => 'Adhésion : montant', 'required' => false],
            ['name' => 'membership_method', 'label' => 'Adhésion : moyen de paiement', 'required' => true],
            ['name' => 'membership_date', 'label' => 'Adhésion : date', 'required' => true],
        );
        $member_questions = $this->em->getRepository('App:MemberQuestion')->getAll($structure);
        foreach ($member_questions as $member_question) {
            $fields[] = ['name' => 'question_' . $member_question->getId(), 'label' => $member_question->getName(), 'required' => false];
        }
        $gateways = $this->em->getRepository('App:Gateway')->findByStructure($structure->getId());
        $form = $this->createForm(ImportType::class, null, ['fields' => $fields, 'gateways' => $gateways]);

        $form->handleRequest($request);
        if ($form->isSubmitted() and $form->isValid()) {
            $fileConstraint = new File();
            $fileConstraint->mimeTypes = array(
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream',
            );
            $errorList = $this->get('validator')->validate($form['file']->getData(), $fileConstraint);

            if(count($errorList) == 0) {

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($form['file']->getData());
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
                if ($form['first_row']->getData() == true)
                    $first_row = 2;
                else
                    $first_row = 1;
                $import_count = $first_row;
                $import_error = 0;
                $newUsers = array();

                $method = array();
                foreach ($gateways as $gateway) {
                    $method[$form['gateway_' . $gateway->getId()]->getData()] = $gateway;
                }

                while ($sheet->getCellByColumnAndRow($form['surname']->getData(), $import_count)->getValue()) {
                    $email = $sheet->getCellByColumnAndRow($form['email']->getData(), $import_count)->getValue();
                    $surname = $sheet->getCellByColumnAndRow($form['surname']->getData(), $import_count)->getValue();
                    $name = $sheet->getCellByColumnAndRow($form['name']->getData(), $import_count)->getValue();

                    if (!(in_array(["email" => $email], $listUsers) || in_array($email, $newUsers)) || $form['rewrite']->getData()) {
                        if (in_array(["email" => $email], $listUsers) || in_array($email, $newUsers)) {
                            $person = $this->em->getRepository('App:Person')->getByUsername($email);
                            $this->session->getFlashBag()->add('notice', $name . ' ' . $surname . ' (' . $email . ') : l\'utilisateur a été mis à jour.');
                        } else {
                            $person = new Person();
                            if ($form['title']->getData() != null)
                               $person->setTitle($sheet->getCellByColumnAndRow($form['title']->getData(), $import_count)->getValue());
                            $person->setSurname($surname);
                            $person->setName($name);

                            $user = new User();
                            $this->um->createUser();
                            $user->setEmail($email);
                            $user->setUsername($user->getEmail());
                            $user->setConfirmationToken(null);
                            $user->setEnabled(true);
                            $user->addRole('ROLE_person');
                            $user->generatePassword(8);
                            $person->setUser($user);

                            $this->um->updateUser($user);
                            $newUsers[] = $user->getEmail();
                        }

                        if ($form['birthday']->getData() != null) {
                            $date = $sheet->getCellByColumnAndRow($form['birthday']->getData(), $import_count)->getValue();
                            $birthday = \PhpOffice\Phpsheet\Shared\Date::excelToDateTimeObject($date);
                            $person->setBirthday($birthday);
                        }
                        if ($form['birthplace']->getData() != null)
                            $person->setBirthplace($sheet->getCellByColumnAndRow($form['birthplace']->getData(), $import_count)->getValue());
                        if ($form['phone']->getData() != null)
                            $person->setPhone($sheet->getCellByColumnAndRow($form['phone']->getData(), $import_count)->getValue());
                        if ($form['address_number']->getData() != null) {
                            if ($form['address_type']->getData() != null) {
                                $address['type'] = $sheet->getCellByColumnAndRow($form['address_type']->getData(), $import_count)->getValue();
                                $address['street'] = $sheet->getCellByColumnAndRow($form['address_street']->getData(), $import_count)->getValue();
                            } else {
                                $address['type'] = '';
                                $address['street'] = '';
                            }
                            $address['number'] = $sheet->getCellByColumnAndRow($form['address_number']->getData(), $import_count)->getValue();
                            if ($form['address_complement']->getData())
                                $address['complement'] = $sheet->getCellByColumnAndRow($form['address_complement']->getData(), $import_count)->getValue();
                            else
                                $address['complement'] = '';
                            $address['code'] = $sheet->getCellByColumnAndRow($form['address_code']->getData(), $import_count)->getValue();
                            $address['city'] = $sheet->getCellByColumnAndRow($form['address_city']->getData(), $import_count)->getValue();
                            if ($form['address_country']->getData() != null)
                                $address['country'] = $sheet->getCellByColumnAndRow($form['address_country']->getData(), $import_count)->getValue();
                            else
                                $address['country'] = "France";
                            $person->setAddress($address);
                        }

                        $this->em->persist($person);
                    } else {
                        $person = $this->em->getRepository('App:Person')->getByUsername($email);
                        $this->session->getFlashBag()->add('error', $name . ' ' . $surname . ' (' . $email . ') : l\'utilisateur existe déjà dans la base de données.');
                    }

                    if ($form['membership_date']->getData() != null) {
                        $date = $sheet->getCellByColumnAndRow($form['membership_date']->getData(), $import_count)->getValue();
                        $payed_on = \PhpOffice\Phpsheet\Shared\Date::excelToDateTimeObject($date);
                    } else {
                        $payed_on = new \DateTime('now');
                    }
                    $expire = new \DateTime($this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_date')->getValue());
                    $expire->modify('- 1 day');
                    $payed_on->modify($this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_anticipated')->getValue());
                    while ($expire <= $payed_on) {
                        $expire->modify($this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_periodicity')->getValue());
                    }
                    $membership = $this->em->getRepository('App:Membership')->findOneBy(['person' => $person->getId(), 'structure' => $structure->getId(), 'expiredOn' => $expire]);
                    if (!$membership) {
                        $membership = new Membership();
                        $membership->setPerson($person);
                        $membership->setMethod($method[$sheet->getCellByColumnAndRow($form['membership_method']->getData(), $import_count)->getValue()]);
                        $membership->setStructure($structure);
                        $membership->setPayedOn($payed_on);
                        $membership->setExpiredOn($expire);
                        $membership->setStatus('validated');

                        if ($form['membership_amount']->getData() != null)
                            $membership->setAmount($sheet->getCellByColumnAndRow($form['membership_amount']->getData(), $import_count)->getValue());
                        else
                            $membership->setAmount($this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_payment')->getValue());

                        $this->em->persist($membership);
                    } else {
                        $this->session->getFlashBag()->add('error', $name . ' ' . $surname . ' (' . $email . ') : l\'utilisateur est déjà adhérent dans la base de données.');
                        $import_error++;
                    }

                    foreach ($member_questions as $member_question) {
                        if ($form['question_' . $member_question->getId()]->getData() != null) {
                            if (!$this->em->getRepository('App:MemberInfo')->findOneBy(['membership' => $membership->getId(), 'question' => $member_question->getId()])) {
                                $question_info = new MemberInfo();
                                $question_info->setQuestion($member_question);
                                $question_info->setMembership($membership);
                                $question_info->setValue($sheet->getCellByColumnAndRow($form['question_' . $member_question->getId()]->getData(), $import_count)->getValue());
                                $this->em->persist($question_info);
                            }
                        }
                    }

                    $import_count++;
                }

                $this->em->flush();

                if ($import_count - $first_row > 1) {
                    $message = $import_count - $first_row . " lignes ont été traitées. ";
                } elseif ($import_count - $first_row == 1) {
                    $message = "Une ligne a été traitée. ";
                } else {
                    $message = "Aucune ligne n'a été traitée.";
                }
                if ($import_error) {
                    if ($import_count - $first_row - $import_error > 1) {
                        $message .= $import_count - $first_row - $import_error . " adhérents ont été enregistrés dans la base de données. ";
                    } elseif ($import_count - $first_row - $import_error == 1) {
                        $message .= "Un adhérent a été enregistré dans la base de données. ";
                    } else {
                        $message .= "Aucun adhérent n'a été enregistré dans la base de données. ";
                    }
                    if ($import_error > 1) {
                        $message .= $import_error . " doublons n'ont pas été ajoutés.";
                    } else {
                        $message .= "Un doublon n'a pas été ajouté.";
                    }
                } else {
                    $message .= $import_count - $first_row . " adhérents ont été enregistrés dans la base de données. Il n'y a pas de doublons traités.";
                }
                $this->session->getFlashBag()->add('notice', $message);

                return $this->redirect($this->generateUrl('user_register_index', ['slug' => $slug]));
            } else {
                $error = $errorList[0]->getMessage();
            }
        }

        return array(
            'form'  => $form->createView(),
            'error' => $error,
        );
    }

    /**
     * Add Filter action
     *
     * @Route("/{slug}/filter/add/{type}/{id}/{value}", name="user_register_filter_add", requirements={"slug" = "\w+"})
     */
    public function addFilterAction($type, $id, $value, $slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $filters = $this->session->get('user_register_filter', [
            'valid'     => null,
            'ending'    => null,
            'questions' => null,
            'user'      => null,
            'search'    => null,
            'fee'       => null,
            'expiration' => null,
        ]);

        if ($type == "valid" or $type == "ending" or $type == "fee") {
            $filters[$type] = $value;
        } elseif ($type == "expiration") {
            $value = explode('|', $value);
            foreach ($value as &$date) {
                $date = new \DateTime($date);
            }
            $filters[$type] = $value;
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
     */
    public function removeFilterAction($type, $id, $slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $filters = $this->session->get('user_register_filter', [
            'valid'     => null,
            'ending'    => null,
            'questions' => null,
            'user'      => null,
            'search'    => null,
            'fee'       => null,
            'expiration' => null,
        ]);

        if ($type == "valid" or $type == "ending" or $type == "fee" or $type == "expiration") {
            $filters[$type] = null;
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
     * @Route("/{slug}/member/join", name="user_register_join", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function registerAction(Request $request, $slug, TokenGeneratorInterface $tokenGenerator, AuthorizationCheckerInterface $checker)
    {
        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $options = [
            'payment'     => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_payment')->getValue(),
            'date'        => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_date')->getValue(),
            'periodicity' => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_periodicity')->getValue(),
            'anticipated' => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_anticipated')->getValue(),
        ];

        $is_admin = false;
        if (!$checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $options['token'] = $tokenGenerator->generateToken();
        } else {
            $options['token'] = null;
            $user = $this->getUser();
            $rejoin = $request->query->get('rejoin', false);
            /* if person's account exists and is about to rejoin */
            if ($rejoin) {
                $userid = $request->query->get('userid', null);
                $person = $this->testAdminTakeOver($user, $userid);
                $current_membership = $this->em->getRepository('App:Membership')->getCurrentForPerson($person);
                $last_membership = $this->em->getRepository('App:Membership')->getLastForPerson($person);
                /* test if person can rejoin at this time */
                $now = new \DateTime('now');
                $now->modify($options['anticipated']);
                if (null !== $current_membership and !$current_membership->isRejoinable($now)) {
                    $this->session->getFlashBag()->add('error', 'Adhésion déjà à jour de cotisation.');
                    if ($user->hasRole('ROLE_ADMIN') or ($user->hasRole('ROLE_STRUCTURE') and $current_membership->getStructure()->getSlug() == $slug)) {
                        return $this->redirect($this->generateUrl('user_register_index', ["slug" => $slug]));
                    } else {
                        return $this->redirect($this->generateUrl('user_register_list', ["userid" => $userid]));
                    }
                }
            } else {
                if ($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_STRUCTURE')) {
                    $adminPerson = $this->em->getRepository('App:Person')->getByUser($user);
                    $adminMembership = $this->em->getRepository('App:Membership')->getCurrentForPerson($adminPerson, true);
                    if (!$user->hasRole('ROLE_ADMIN') and $adminMembership->getStructure()->getSlug() != $slug) {
                        $this->session->getFlashBag()->add('error', 'Vous n\'avez pas les droits pour accéder à cette structure.');
                        return $this->redirect($this->generateUrl('user_register_join', ["slug" => $slug, "rejoin" => true]));
                    }
                } else {
                    $this->session->getFlashBag()->add('error', 'Opération interdite.');
                    return $this->redirect($this->generateUrl('user_register_join', ["slug" => $slug, "rejoin" => true]));
                }
            }
            if ($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_STRUCTURE'))
                $is_admin = true;
        }

        if (isset($last_membership) and $last_membership->getStatus() == 'excluded')
            throw new AccessDeniedException("Vous avez été exclu de " . $slug . ". Veuillez contacter un responsable.");

        if (isset($current_membership) and $current_membership->getStatus() == 'registered')
            $membership = $current_membership;
        else
            $membership = new Membership();

        if (isset($person))
            $membership->setPerson($person);

        $questions = $this->em->getRepository('App:MemberQuestion')->getAll($structure);

        $form = $this->createForm(MembershipType::class, $membership, [
            'structure' => $structure,
            'withPerson' => true,
            'withPrivacy' => true,
            'withPayment' => false,
            'withQuestions' => true,
            'questions' => $questions,
            'admin' => $is_admin
        ]);
        $form_handler = new MembershipHandler($form, $request, $this->em, $this->um, $structure, $options, isset($person)?$person:null, $questions, $is_admin);

        if($result = $form_handler->process()) {
            /* If User is allready in db and action is not to rejoin */
            if ($result === 'exists' and !isset($rejoin)) {
                $this->session->getFlashBag()->add('error', 'L\'utilisateur ' . $membership->getPerson()->getUser()->getUsername() . ' existe déjà en base de données. Vous devez vous connecter pour accéder à votre compte.');
                return $this->redirect($this->generateUrl('fos_user_security_login'));
            } elseif ($result === 'disabled') {
                $this->session->getFlashBag()->add('error', 'L\'utilisateur ' . $membership->getPerson()->getUser()->getUsername() . ' existe mais n\'a jamais été activé. Renvoi du mail d\'activation en cours.');
                return $this->redirect($this->generateUrl('user_register_confirmation_send', ['email' => $membership->getPerson()->getUser()->getUsername(), 'slug' => $slug]));
            } elseif (isset($rejoin)) {
                $this->session->getFlashBag()->add('success', 'Adhésion enregistrée pour ' . $membership->getPerson() . '.');
            } else {
                $this->session->getFlashBag()->add('success', 'Utilisateur ' . $membership->getPerson()->getUser()->getUsername() . ' créé et adhésion enregistrée pour ' . $membership->getPerson() . '. Envoi du mail d\'activation en cours.');
            }

            /* if user is admin and has taken over an account */
            if (isset($adminMembership) or isset($userid)) {
                $filter = $this->session->get('user_register_filter', []);
                $filter['user'] = $membership->getPerson()->getUser()->getId();
                $this->session->set('user_register_filter', $filter);
	    }

            return $this->redirect($this->generateUrl('user_payment_prepare', [
                'gateway'  => $membership->getMethod()->getGatewayName(),
                'memberid' => $membership->getId(),
            ]));
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
     */
    public function sendConfirmationAction($email, Request $request, $slug, \Swift_Mailer $mailer)
    {
        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondant à "' . $slug . '"');

        $user = $this->um->findUserByUsername($email);
        if(!$user)
            throw $this->createNotFoundException('Aucun utilisateur lié à cette adresse mail.');
        $person = $this->em->getRepository('App:Person')->findOneBy(['user' => $user]);

        if(!$user->getConfirmationToken()) {
            $this->session->getFlashBag()->add('error', 'Cet utilisateur n\'a pas de jeton de confirmation défini. Est-il déjà validé ? Contactez un administrateur si nécessaire.');
            return $this->redirect($this->generateUrl('user_register_list'));
        }

        $url = $this->generateUrl('fos_user_registration_confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
        $params = array(
            'user'      => $user,
            'url'       => $url,
            'person'    => $person,
            'structure' => $structure,
        );
        $sendmail = (new \Swift_Message('PIGASS - Confirmation d\'adresse mail'))
                ->setSubject('PIGASS - Confirmation d\'adresse mail')
                ->setFrom($this->getParameter('app.mailer_admin'))
                ->setReplyTo($structure->getEmail())
                ->setTo($user->getEmailCanonical())
                ->setBody($this->renderView('register/confirmation.txt.twig', $params, 'text/plain'))
        ;
        $mailer->send($sendmail);

        $this->session->getFlashBag()->add('success', 'E-mail d\'activation envoyé.');

        if ($adminUser = $this->getUser() and ($adminUser->hasRole('ROLE_ADMIN') or $adminUser->hasRole('ROLE_STRUCTURE')))
            return $this->redirect($this->generateUrl('user_register_index', ['slug' => $slug]));
        else
            return $this->redirect($this->generateUrl('user_register_list'));
    }

    /**
     * Get printable PDF for membership
     *
     * @Route("/member/{id}/print/", name="user_register_print", requirements={"id" = "\d+"})
     */
    public function printPDFAction(Membership $membership, Pdf $snappy_pdf)
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
        $questions = $this->em->getRepository('App:MemberQuestion')->getAll($membership->getStructure());
        $infos = $this->em->getRepository('App:MemberInfo')->getByMembership($membership->getPerson(), $membership);

        $html = $this->renderView(
            'register/printPDF.html.twig',
            array(
                'membership' => $membership,
                'config'     => $config,
                'address'    => $address,
                'payableTo'  => (isset($config['payableTo'])?$config['payableTo']:'non défini'),
                'form'       => null,
                'questions'  => $questions,
                'infos'      => $infos,
                'iban'       => (isset($config['iban'])?$config['iban']:null),
        ));
        $filename = "Adhesion_" . $membership->getPerson()->getSurname() . "_" . $membership->getExpiredOn()->format('Y') . ".pdf";

        return new Response(
            $snappy_pdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    /**
     * List complementary questions
     *
     * @Route("/{slug}/question", name="user_register_question_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function questionIndexAction($slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN'))
            $slug = $this->session->get('slug');
        if ($slug != 'all')
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        else
            $structure = null;
        $questions = $this->em->getRepository('App:MemberQuestion')->getAll($structure);

        return array(
            'questions' => $questions,
            'slug'      => $slug,
        );
    }

    /**
     * Add a new complementary question
     *
     * @Route("/{slug}/question/new", name="user_register_question_new", requirements={"slug" = "\w+"})
     * @Template("register/question_edit.html.twig")
     */
    public function questionNewAction($slug, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN'))
            $slug = $this->session->get('slug');
        if ($slug != 'all') {
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
            if (!$structure)
                throw $this->createNotFoundException('Impossible de trouver la structure réferente.');
        } else {
            $structure = null;
        }

        $question = new MemberQuestion();
        $form = $this->createForm(MemberQuestionType::class, $question, array('structure' => $structure));
        $formHandler = new MemberQuestionHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Question complémentaire "' . $question . '" enregistrée.');
            return $this->redirect($this->generateUrl('user_register_question_index', ['slug' => $slug]));
        }

        return array(
            'form'     => $form->createView(),
            'question' => null,
            'slug'     => $slug,
        );
    }

    /**
     * Edit a complementary question
     *
     * @Route("/{slug}/question/{id}/edit", name="user_register_question_edit", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function questionEditAction(MemberQuestion $question, $slug, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN'))
            $slug = $this->session->get('slug');
        if ($slug != 'all') {
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
            if (!$structure)
                throw $this->createNotFoundException('Impossible de trouver la structure réferente.');
        } else {
            $structure = null;
        }

        $form = $this->createForm(MemberQuestionType::class, $question, array('structure' => $structure));
        $formHandler = new MemberQuestionHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Question complémentaire "' . $question . '" enregistrée.');
            return $this->redirect($this->generateUrl('user_register_question_index', ['slug' => $slug]));
        }

        return array(
            'form'     => $form->createView(),
            'question' => $question,
            'slug'     => $slug,
        );
    }

    /**
     * List own memberships action
     *
     * @Route("/member/list", name="user_register_list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $user = $this->getUser();
        $filter = $this->session->get('user_register_filter', null);
        $userid = isset($filter['user'])?$filter['user']:$request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);
        $current_membership = $this->em->getRepository('App:Membership')->getCurrentForPerson($person);
        $last_membership = $this->em->getRepository('App:Membership')->getLastForPerson($person);
        $reJoinable = false;

        /* Test memberships and rejoinability */
        if ($current_membership) {
            $membership = $current_membership;
            $slug = $membership->getStructure()->getSlug();
            $now = new \DateTime('now');
            $now->modify($this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_anticipated')->getValue());
            if ($current_membership->getExpiredOn() <= $now and $current_membership->getStatus() != 'excluded') {
                $reJoinable = true;
            }
        } elseif ($last_membership) {
            $membership = $last_membership;
            $slug = $membership->getStructure()->getSlug();
            if ($last_membership->getStatus() != 'excluded')
                $reJoinable = true;
        } else {
            $slug = $request->get('slug', null);
            $reJoinable = true;
        }

        $memberships = $this->em->getRepository('App:Membership')->findBy(['person' => $person]);

        return [
            'memberships' => $memberships,
            'current'     => $current_membership?$current_membership:$last_membership?$last_membership:false,
            'userid'      => $userid,
            'person'      => $person,
            'slug'        => $slug,
            'reJoinable'  => $reJoinable,
        ];
    }

    /**
     * Edit membership's payment
     *
     * @Route("/member/{id}/edit", name="user_register_edit", requirements={"id" = "\d+"})
     * @Template("register/validate.html.twig")
     */
    public function editAction(Membership $membership, Request $request)
    {
        if (!$this->security->isGranted('ROLE_STRUCTURE'))
            throw new AccessDeniedException();

        if (!$membership) {
            $this->session->getFlashBag()->add('error', 'Adhésion inconnue.');
            return $this->redirect($this->generateUrl('user_register_list'));
        }

        if ($membership->getStatus() == "validated") {
                throw $this->createNotFoundException('L\'adhésion a déjà été validée. Vous ne pouvez plus la modifier.');
        }

        $user = $this->getUser();
        $userid = $request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);
        $structure = $membership->getStructure();
        $slug = $structure->getSlug();

        $options = array(
            'payment'     => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_payment')->getValue(),
            'date'        => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_date')->getValue(),
            'periodicity' => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_periodicity')->getValue(),
            'anticipated' => $this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_anticipated')->getValue(),
        );
        $form = $this->createForm(MembershipType::class, $membership, ['structure' => $structure, 'withPerson' => false]);
        $form_handler = new MembershipHandler($form, $request, $this->em, $this->um, $structure, $options);

        if($form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Adhésion enregistrée pour ' . $membership->getPerson() . '.');

            $filter = $this->session->get('user_register_filter', array());
            $filter['user'] = $membership->getPerson()->getUser()->getId();
            $this->session->set('user_register_filter', $filter);

            return $this->redirect($this->generateUrl('user_register_list', [
                'gateway' => $membership->getMethod()->getGatewayName(),
                'memberid' => $membership->getId(),
            ]));
	}

        return array(
            'form'       => $form->createView(),
            'membership' => $membership,
        );
    }

    /**
     * Show MemberInfo action
     *
     * @Route("/member/{id}/infos/", name="user_register_infos", requirements={"id" = "\d+"})
     * @Template()
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

        $memberinfos = $this->em->getRepository('App:MemberInfo')->getByMembership($person, $membership);

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
        if (($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_STRUCTURE')) and $user_id != null) {
            $user_taken_over = $this->um->findUserBy(array(
                'id' => $user_id,
            ));

            if (!$user_taken_over)
                throw $this->createAccessDeniedException('Vous n\'avez pas les autorisations pour accéder à cette fiche.');

            $person = $this->em->getRepository('App:Person')->getByUsername($user_taken_over->getUsername());

            if (!$user->hasRole('ROLE_ADMIN')) {
                $membership = $this->em->getRepository('App:Membership')->getLastForPerson($person);
                if ($membership and $membership->getStructure()->getSlug() != $this->session->get('slug'))
                    throw $this->createAccessDeniedException('Vous n\'avez pas les autorisations pour accéder à cette fiche.');
            }
        } else {
            $person = $this->em->getRepository('App:Person')->getByUsername($user->getUsername());
        }

        return $person;
    }

    private function getExpirationDate(Structure $structure, \DateTime $date, $rev = false)
    {
        $init = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_date')->getValue();
        $periodicity = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_periodicity')->getValue();
        $anticipated = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_anticipated')->getValue();
        $expire = new \DateTime($init);
        $expire->modify('- 1 day');
        $date->modify($anticipated);
        while ($expire <= $date) {
            $expire->modify($periodicity);
        }
        if ($rev)
            $expire->modify("-$periodicity");
        return $expire;
    }
}
