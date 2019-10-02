<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\Security\Core\Security as BaseSecurity,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Security,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity,
    Symfony\Component\Validator\Constraints\File;
use App\Entity\Person,
    App\Entity\Structure,
    App\Entity\User;
use App\Form\PersonType,
    App\Form\PersonUserType,
    App\FormHandler\PersonHandler;

/**
 * Person controller.
 *
 * @Route("/")
 */
class PersonController extends AbstractController
{
    protected $security, $session, $em, $um;

    public function __construct(BaseSecurity $security, SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
    }

    /**
     * List persons
     *
     * @Route("/{slug}/persons", name="user_person_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function indexAction(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $person = $this->em->getRepository('App:Person')->getByUser($user);
        if (!(
            ($this->security->isGranted('ROLE_STRUCTURE') and $person->getStructure() === $structure)
            or $this->security->isGranted('ROLE_ADMIN')
        ))
            throw new AccessDeniedException();

        $search = $request->query->get('search', null);
        $persons_query = $this->em->getRepository('App:Person')->getAll($structure, $search);
        $persons_count = $this->em->getRepository('App:Person')->countAll($structure, true, $search);
        $persons = $persons_query->getResult();

        $member_list = null;
        foreach ($members = $this->em->getRepository('App:Membership')->getCurrentForPersonArray($structure->getSlug()) as $member) {
            $member_list[] = $member['id'];
        }

        return array(
            'structure'      => $structure,
            'persons'        => $persons,
            'persons_count'  => $persons_count,
            'search'         => $search,
            'members'        => $member_list,
    );
  }

    /**
     * Add a new person
     *
     * @Route("/person/new", name="user_person_new")
     * @Template("person/edit.html.twig")
     */
    public function newAction(Request $request)
    {
        if (!$this->security->isGranted('ROLE_STRUCTURE'))
            throw new AccessDeniedException();

        $person = new person();
        $form = $this->createForm(PersonType::class,$person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Individu "' . $person . '" enregistré.');

            return $this->redirect($this->generateUrl('user_person_index'));
        }

        return array(
            'person'      => null,
            'form' => $form->createView(),
        );
    }

    /**
     * Edit a person
     *
     * @Route("/person/{id}/edit", name="user_person_edit", requirements={"id" = "\d+"})
     * @Template("person/edit.html.twig")
     */
    public function editAction(Person $person, Request $request)
    {
        if (!$this->security->isGranted('ROLE_STRUCTURE'))
            throw new AccessDeniedException();

        $form = $this->createForm(PersonType::class, $person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Individu "' . $person . '" modifié.');
            return $this->redirect($this->generateUrl('user_person_index'));
        }

        return array(
            'person'      => $person,
            'person_form' => $form->createView(),
        );
    }

    /**
     * Delete a person
     *
     * @Route("/person/{id}/delete", name="user_person_delete", requirements={"id" = "\d+"})
     */
    public function deleteAction(Person $person, Request $request)
    {
        if (!$this->security->isGranted('ROLE_ADMIN'))
            throw new AccessDeniedException();

        $search = $request->query->get('search', null);

        if ($memberships = $this->em->getRepository('App:Membership')->findBy(array('person' => $person))) {
            foreach($memberships as $membership) {
                $this->em->remove($membership);
            }
        }

        $this->em->remove($person);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Etudiant "' . $person . '" supprimé.');

        return $this->redirect($this->generateUrl('user_person_index', array('search' => $search)));
    }

    /**
     * Promote a person to higher rights
     *
     * @Route("/{slug}/person/{id}/promote", name="user_person_promote", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Entity("structure", expr="repository.findOneBySlug(slug)")
     * @Security("is_granted('ROLE_ADMIN') or (is_granted('ROLE_STRUCTURE') and  is_granted(structure.getRole()))")
     */
    public function promoteAction(Structure $structure, Person $person, Request $request)
    {
        $user = $person->getUser();
        $user->addRole('ROLE_STRUCTURE');
        $user->addRole($structure->getRole());

        $this->um->updateUser($user);

        $this->session->getFlashBag()->add('notice', 'Droits d\'administration donnés à l\'individu "' . $person . '"');
        return $this->redirect($this->generateUrl('app_dashboard_user(', array('userid' => $user->getId(), 'slug' => $structure->getSlug())));
    }

    /**
     * Demote a person to lower rights
     *
     * @Route("/{slug}/person/{id}/demote", name="user_person_demote", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Entity("structure", expr="repository.findOneBySlug(slug)")
     * @Security("is_granted('ROLE_ADMIN') or (is_granted('ROLE_STRUCTURE') and  is_granted(structure.getRole()))")
     */
    public function demoteAction(Structure $structure, Person $person, Request $request)
    {
        $user = $person->getUser();
        if ($user->hasRole($structure->getRole()))
            $user->removeRole($structure->getRole());
        if (!preg_grep("/ROLE_STRUCTURE_(.*)/", $user->getRoles()))
            $user->removeRole('ROLE_STRUCTURE');

        $this->um->updateUser($user);

        $this->session->getFlashBag()->add('notice', 'Droits d\'administration retirés à l\'individu "' . $person . '"');
        return $this->redirect($this->generateUrl('app_dashboard_user(', array('userid' => $user->getId(), 'slug' => $structure->getSlug())));
    }

    /**
     * Export filtered mails by structure
     *
     * @Route("/export/{slug}/mail", name="user_person_export", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function exportMailAction($slug)
    {
        if (!$this->security->isGranted('ROLE_STRUCTURE'))
            throw new AccessDeniedException();

        $session_slug = $this->session->get('slug', null);
        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);
        $adminUser = $this->getUser();
        $adminPerson = $this->em->getRepository('App:Person')->getByUser($adminUser);
        $adminMembership = $this->em->getRepository('App:Membership')->getCurrentForPerson($adminPerson, true);
        if (!$adminMembership) {
            $this->session->getFlashBag()->add('warning', 'Adhésion périmée. Veuillez réadhérer pour accéder aux fonctionnalités d\'administration.');
            return $this->redirect($this->generateUrl('user_register_join', array('slug' => $slug)));
        }
        if (!$adminUser->hasRole('ROLE_ADMIN') and $session_slug != $slug) {
            $slug = $adminMembership->getStructure()->getSlug();
            $this->session->set('slug', $slug);
        }
        $filters = $this->session->get('user_register_filter', [
            'valid'     => null,
            'ending'    => null,
            'fee'       => null,
            'questions' => null,
            'search'    => null,
            'expiration'=> null,
        ]);
        $mails = $this->em->getRepository('App:Membership')->getMailsByStructure($slug, $filters['expiration'], $filters);


        return array(
            'mails'     => $mails,
            'structure' => $structure,
        );
    }

    /**
     * Edit own infos
     *
     * @Route("/user/edit", name="user_person_edit_me")
     * @Template()
     */
    public function editMeAction(Request $request)
    {
        if (!$this->security->isGranted('ROLE_MEMBER'))
            throw new AccessDeniedException();

        $user = $this->getUser();
        $userid = $request->query->get('userid', null);
        $person = $this->testAdminTakeOver($user, $userid);
        $redirect = $request->query->get('redirect', 'app_dashboard_user');
        $slug = $request->query->get('slug');

        if (!$person)
            throw $this->createNotFoundException('Unable to find person entity.');

        if (!$slug) {
            $membership = $this->em->getRepository('App:Membership')->getLastForPerson($person);
            if (!$membership)
                throw $this->createNotFoundException('Unable to find membership entity.');
            $slug = $membership->getStructure()->getSlug();
        }

        $form = $this->createForm(PersonUserType::class, $person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            if ($userid)
                $this->session->getFlashBag()->add('notice', 'Le compte de ' . $person . ' a bien été modifié.');
            else
                $this->session->getFlashBag()->add('notice', 'Votre compte a bien été modifié.');
            return $this->redirect($this->generateUrl($redirect, array('slug' => $slug, 'userid' => $userid)));
        }

        return array(
            'form'   => $form->createView(),
            'userid' => $userid,
        );
    }

  /**
   * Import persons from file into a grade
   *
   * @Route("/import", name="user_person_import")
   * @Template()
   */
  public function importAction(Request $request)
  {
    $error = null;
    $listUsers = $this->em->getRepository('App:User')->getAllEmail();
    $form = $this->createForm(new ImportType());
    $form->handleRequest($request);

    if ($form->isValid()) {
        $fileConstraint = new File();
        $fileConstraint->mimeTypes = array(
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/octet-stream',
            'application/vnd.ms-excel',
            'application/msexcel',
            'application/x-msexcel',
            'application/x-ms-excel',
            'application/x-excel',
            'application/x-dos_ms_excel',
            'application/xls',
            'application/x-xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-office',
        );
        $errorList = $this->get('validator')->validate($form['file']->getData(), $fileConstraint);

        if(count($errorList) == 0) {
            $objPHPExcel = $this->get('phpexcel')->createPHPExcelObject($form['file']->getData())->setActiveSheetIndex();
            if ($form['first_row']->getData() == true)
                $first_row = 2;
            else
                $first_row = 1;
            $persons_count = $first_row;
            $persons_error = 0;
            $newUsers = array();

            while ($objPHPExcel->getCellByColumnAndRow($form['surname']->getData(), $persons_count)->getValue()) {
                $person = new Person();
                if ($form['title']->getData() != null)
                   $person->setTitle($objPHPExcel->getCellByColumnAndRow($form['title']->getData(), $persons_count)->getValue());
                $person->setSurname($objPHPExcel->getCellByColumnAndRow($form['surname']->getData(), $persons_count)->getValue());
                $person->setName($objPHPExcel->getCellByColumnAndRow($form['name']->getData(), $persons_count)->getValue());
                if ($form['birthday']->getData() != null) {
                    $date = $objPHPExcel->getCellByColumnAndRow($form['birthday']->getData(), $persons_count)->getValue();
                    $birthday = \PHPExcel_Shared_Date::ExcelToPHPObject($date);
                    $person->setBirthday($birthday);
                }
                if ($form['birthplace']->getData() != null)
                    $person->setBirthplace($objPHPExcel->getCellByColumnAndRow($form['birthplace']->getData(), $persons_count)->getValue());
                if ($form['phone']->getData() != null)
                    $person->setPhone($objPHPExcel->getCellByColumnAndRow($form['phone']->getData(), $persons_count)->getValue());
                if ($form['ranking']->getData() != null)
                    $person->setRanking($objPHPExcel->getCellByColumnAndRow($form['ranking']->getData(), $persons_count)->getValue());
                if ($form['graduate']->getData() != null)
                    $person->setGraduate($objPHPExcel->getCellByColumnAndRow($form['graduate']->getData(), $persons_count)->getValue());
                $person->setAnonymous(false);
                $person->setGrade($form['grade']->getData());

                $user = new User();
                $this->um->createUser();
                $user->setEmail($objPHPExcel->getCellByColumnAndRow($form['email']->getData(), $persons_count)->getValue());
                $user->setUsername($user->getEmail());
                $user->setConfirmationToken(null);
                $user->setEnabled(true);
                $user->addRole('ROLE_STUDENT');
                $user->generatePassword();
                $person->setUser($user);

                if (!(in_array(array("emailCanonical" => $user->getEmail()), $listUsers) || in_array($user->getEmail(), $newUsers))) {
                    $this->em->persist($person);
                    $this->um->updateUser($user);
                    $newUsers[] = $user->getEmail();
                } else {
                    $this->session->getFlashBag()->add('error', $person->getName() . ' ' . $person->getSurname() . ' (' . $person->getUser()->getEmail() . ') : l\'utilisateur existe déjà dans la base de données.');
                    $persons_error++;
                }
                $persons_count++;
            }

            $this->em->flush();

            if ($persons_count - $first_row > 1) {
                $message = $persons_count - $first_row . " lignes ont été traitées. ";
            } elseif ($persons_count - $first_row == 1) {
                $message = "Une ligne a été traitée. ";
            } else {
                $message = "Aucune ligne n'a été traitée.";
            }
            if ($persons_error) {
                if ($persons_count - $first_row - $persons_error > 1) {
                    $message .= $persons_count - $first_row - $persons_error . " étudiants ont été enregistrés dans la base de données. ";
                } elseif ($persons_count - $first_row - $persons_error == 1) {
                    $message .= "Un étudiant a été enregistré dans la base de données. ";
                } else {
                    $message .= "Aucun étudiant n'a été enregistré dans la base de données. ";
                }
                if ($persons_error > 1) {
                    $message .= $persons_error . " doublons n'ont pas été ajoutés.";
                } else {
                    $message .= "Un doublon n'a pas été ajouté.";
                }
            } else {
                $message .= $persons_count - $first_row . " étudiants ont été enregistrés dans la base de données. Il n'y a pas de doublons traités.";
            }
            $this->session->getFlashBag()->add('notice', $message);

            return $this->redirect($this->generateUrl('GUser_SAIndex'));
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
     * View and do merge for person
     *
     * @Route("/merge", name="user_person_merge")
     * @Template()
     */
    public function doMergeAction(Request $request)
    {
        $mergeArray = $this->session->get('merge', null);

        if (!$mergeArray or !$mergeArray['orig']) {
            $this->session->getFlashBag()->add('error', 'Il n\'y a pas de fusion d\'étudiant en cours');
            return $this->redirect($this->generateUrl('GUser_SAIndex'));
        }

        $person_orig = $this->em->getRepository('App:Person')->find($mergeArray['orig']);
        $person_dest = $this->em->getRepository('App:Person')->find($mergeArray['dest']);

        if ($request->get('confirm', false)) {
            /* Placement */
            $placements = $this->em->getRepository('App:Placement')->findBy(array('person' => $mergeArray['orig']));
            foreach ($placements as $placement) {
                $placement->setPerson($person_dest);
                $this->em->persist($placement);
            }

            /* Membership */
            $memberships = $this->em->getRepository('App:Membership')->findBy(array('person' => $mergeArray['orig']));
            foreach ($memberships as $membership) {
                $membership->setPerson($person_dest);
                $this->em->persist($membership);
            }

            /* Simperson */
            $simulation = $this->em->getRepository('App:Simulation')->findOneBy(array('person' => $mergeArray['orig']));
            if (isset($simulation)) {
                $simulation->setPerson($person_dest);
                $this->em->persist($simulation);
            }

            $this->em->flush();

            /* Delete person_orig */
            $this->em->remove($person_orig);
            $this->em->remove($person_orig->getUser());
            $this->em->flush();

            $mergeArray['orig'] = null;
            $this->session->set('merge', $mergeArray);

            return $this->redirect($this->generateUrl('GUser_SAIndex'));
        }

        return array(
            'orig'  => $person_orig,
            'dest'  => $person_dest,
            'merge' => $mergeArray,
        );
    }

    /**
     * Cancel merging and delete session's flags
     *
     * @Route("/merge/cancel", name="user_person_cancel_merge")
     */
    public function cancelMergeAction()
    {
        $mergeArray = $this->session->remove('merge');

        if (!$mergeArray)
            $this->session->getFlashBag()->add('warning', 'Aucun jeton de fusion d\'étudiants retrouvés');
        else
            $this->session->getFlashBag()->add('notice', 'Jetons de fusion d\'étudiants supprimés.');

        return $this->redirect($this->generateUrl('GUser_SAIndex'));
    }

    /**
     * Test for admin take over function
     *
     * @return Person
     */
    private function testAdminTakeOver($user, $userid = null)
    {
        if ($user->hasRole('ROLE_ADMIN') and $userid != null) {
            $user = $this->um->findUserBy(array(
                'id' => $userid,
            ));
        }

        $person = $this->em->getRepository('App:Person')->getByUsername($user->getUsername());

        if (!$person) {
            $this->session->getFlashBag()->add('error', 'Adhérent inconnu.');
            return $this->redirect($this->generateUrl('user_register_index'));
        } else {
            return $person;
        }
    }
}
