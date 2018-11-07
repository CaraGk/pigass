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
    public function indexAction(Request $request, $slug)
    {
        if (!$this->security->isGranted('ROLE_ADMIN'))
            throw new AccessDeniedException();

        $user = $this->getUser();
        $search = $request->query->get('search', null);
        $persons_query = $this->em->getRepository('App:Person')->getAll($slug, $search);
        $persons_count = $this->em->getRepository('App:Person')->countAll(true, $slug, $search);
        $persons = $persons_query->getResult();

        $member_list = null;
        foreach ($members = $this->em->getRepository('App:Membership')->getCurrentForPersonArray($slug) as $member) {
            $member_list[] = $member['id'];
        }

    return array(
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
        return $this->redirect($this->generateUrl('user_register_list', array('userid' => $user->getId(), 'slug' => $structure->getSlug())));
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
        return $this->redirect($this->generateUrl('user_register_list', array('userid' => $user->getId(), 'slug' => $structure->getSlug())));
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
            $slug = $actualMembership->getStructure()->getSlug();
            $this->session->set('slug', $slug);
        }
        $filters = $this->session->get('user_register_filter', [
            'valid'     => null,
            'ending'    => null,
            'fee'       => null,
            'questions' => null,
            'search'    => null,
        ]);
        $mails = $this->em->getRepository('App:Membership')->getMailsByStructure($slug, $filters);


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
        $redirect = $request->query->get('redirect', 'user_register_list');
        $slug = $request->query->get('slug');

        if (!$person)
            throw $this->createNotFoundException('Unable to find person entity.');

        if (!$slug) {
            $membership = $this->em->getRepository('App:Membership')->getCurrentForPerson($person);
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
