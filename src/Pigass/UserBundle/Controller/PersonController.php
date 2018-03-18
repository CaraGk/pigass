<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Validator\Constraints\File;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\UserBundle\Entity\Person,
    Pigass\UserBundle\Entity\User;
use Pigass\UserBundle\Form\PersonType,
    Pigass\UserBundle\Form\PersonUserType,
    Pigass\UserBundle\Form\PersonHandler;


/**
 * Person controller.
 *
 * @Route("/")
 */
class PersonController extends Controller
{
    /** @DI\Inject */
    private $router;

    /** @DI\Inject */
    private $session;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("fos_user.user_manager") */
    private $um;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /**
     * List persons
     *
     * @Route("/{slug}/persons", name="user_person_index", requirements={"slug" = "\w+"})
     * @Template()
     * @Security\Secure(roles="ROLE_ADMIN")
     */
    public function indexAction(Request $request, $slug)
    {
        $username = $this->get('security.token_storage')->getToken()->getUsername();
        $user = $this->um->findUserByUsername($username);
        $search = $request->query->get('search', null);
        $paginator = $this->get('knp_paginator');
        $persons_query = $this->em->getRepository('PigassUserBundle:Person')->getAll($slug, $search);
        $persons_count = $this->em->getRepository('PigassUserBundle:Person')->countAll(true, $slug, $search);
        $persons = $paginator->paginate($persons_query, $request->query->get('page', 1), 20);

        $member_list = null;
        foreach ($members = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPersonArray($slug) as $member) {
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
     * @Template("PigassUserBundle:Person:edit.html.twig")
     * @Security\PreAuthorize("hasRole('ROLE_STRUCTURE')")
     */
    public function newAction(Request $request)
    {
        $person = new person();
        $form = $this->createForm(PersonType::class,$person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Individu "' . $person . '" enregistré.');

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
     * @Template("PigassUserBundle:Person:edit.html.twig")
     * @Security\PreAuthorize("hasRole('ROLE_STRUCTURE')")
     */
    public function editAction(Person $person, Request $request)
    {
        $form = $this->createForm(PersonType::class, $person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Individu "' . $person . '" modifié.');
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
     * @Security\PreAuthorize("hasRole('ROLE_ADMIN')")
     */
    public function deleteAction(Person $person, Request $request)
    {
        $search = $request->query->get('search', null);

        if ($memberships = $this->em->getRepository('PigassUserBundle:Membership')->findBy(array('person' => $person))) {
            foreach($memberships as $membership) {
                $this->em->remove($membership);
            }
        }

        $this->em->remove($person);
        $this->em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Etudiant "' . $person . '" supprimé.');

        return $this->redirect($this->generateUrl('user_person_index', array('search' => $search)));
    }

    /**
     * Promote a person to higher rights
     *
     * @Route("/{slug}/person/{id}/promote", name="user_person_promote", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function promoteAction(Person $person, Request $request, $slug)
    {
        $user = $person->getUser();
        $user->addRole('ROLE_STRUCTURE');

        $this->um->updateUser($user);

        $this->get('session')->getFlashBag()->add('notice', 'Droits d\'administration donnés à l\'individu "' . $person . '"');
        return $this->redirect($this->generateUrl('user_register_list', array('userid' => $user->getId(), 'slug' => $slug)));
    }

    /**
     * Demote a person to lower rights
     *
     * @Route("/{slug}/person/{id}/demote", name="user_person_demote", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Security\Secure(roles="ROLE_STRUCTURE, ROLE_ADMIN")
     */
    public function demoteAction(Person $person, Request $request, $slug)
    {
        $user = $person->getUser();
        if ($user->hasRole('ROLE_STRUCTURE'))
            $user->removeRole('ROLE_STRUCTURE');

        $this->um->updateUser($user);

        $this->get('session')->getFlashBag()->add('notice', 'Droits d\'administration retirés à l\'individu "' . $person . '"');
        return $this->redirect($this->generateUrl('user_register_list', array('userid' => $user->getId(), 'slug' => $slug)));
    }

    /**
     * Export filtered mails by structure
     *
     * @Route("/export/{slug}/mail", name="user_person_export", requirements={"slug" = "\w+"})
     * @Template()
     * @Security\PreAuthorize("hasRole('ROLE_STRUCTURE')")
     */
    public function exportMailAction($slug)
    {
        $session_slug = $this->session->get('slug', null);
        $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(['slug' => $slug]);
        $adminUser = $this->getUser();
        $adminPerson = $this->em->getRepository('PigassUserBundle:Person')->getByUser($adminUser);
        $adminMembership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($adminPerson, true);
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
        $mails = $this->em->getRepository('PigassUserBundle:Membership')->getMailsByStructure($slug, $filters);


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
     * @Security\Secure(roles="ROLE_MEMBER")
     */
    public function editMeAction(Request $request)
    {
        $user = $this->getUser();
        $userid = $request->query->get('userid', null);
        $person = $this->testAdminTakeOver($user, $userid);
        $redirect = $request->query->get('redirect', 'user_register_list');
        $slug = $request->query->get('slug');

        if (!$person)
            throw $this->createNotFoundException('Unable to find person entity.');

        if (!$slug) {
            $membership = $this->em->getRepository('PigassUserBundle:Membership')->getCurrentForPerson($person);
            $slug = $membership->getStructure()->getSlug();
        }

        $form = $this->createForm(PersonUserType::class, $person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            if ($userid)
                $this->get('session')->getFlashBag()->add('notice', 'Le compte de ' . $person . ' a bien été modifié.');
            else
                $this->get('session')->getFlashBag()->add('notice', 'Votre compte a bien été modifié.');
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

        $person = $this->em->getRepository('PigassUserBundle:Person')->getByUsername($user->getUsername());

        if (!$person) {
            $this->session->getFlashBag()->add('error', 'Adhérent inconnu.');
            return $this->redirect($this->generateUrl('Pigass_register_index'));
        } else {
            return $person;
        }
    }
}
