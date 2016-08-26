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
    Pigass\UserBundle\Form\PersonHandler,
    Pigass\UserBundle\Form\UserAdminType,
    Pigass\UserBundle\Form\UserHandler;


/**
 * Person controller.
 *
 * @Route("/")
 */
class PersonController extends Controller
{
    /** @DI\Inject */
    private $router;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("fos_user.user_manager") */
    private $um;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /**
     * List persons
     *
     * @Route("/{slug}/persons", name="user_person_index")
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
        $mod_simul = $this->pm->findParamByName('simul_active');

        $person = new person();
        $form = $this->createForm(new PersonType($mod_simul->getValue()), $person);
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
        $mod_simul = $this->pm->findParamByName('simul_active');

        $form = $this->createForm(new PersonType($mod_simul->getValue()), $person);
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

        if (true == $this->pm->findParamByName('reg_active')->getValue()) {
            if ($memberships = $this->em->getRepository('PigassUserBundle:Membership')->findBy(array('person' => $person))) {
                foreach($memberships as $membership) {
                    $this->em->remove($membership);
                }
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
     * @Route("/person/{id}/promote", name="user_person_promote", requirements={"id" = "\d+"})
     * @Security\PreAuthorize("hasRole('ROLE_STRUCTURE')")
     */
    public function promoteAction(Person $person, Request $request)
    {
        $search = $request->query->get('search', null);
        $user = $person->getUser();
        $user->addRole('ROLE_STRUCTURE');

        $this->um->updateUser($user);

        $this->get('session')->getFlashBag()->add('notice', 'Droits d\'administration donnés à l\'individu "' . $person . '"');
        return $this->redirect($this->generateUrl('user_person_index', array('search' => $search)));
    }

    /**
     * Demote a person to lower rights
     *
     * @Route("/person/{id}/demote", name="user_person_demote", requirements={"id" = "\d+"})
     * @Security\PreAuthorize("hasRole('ROLE_STRUCTURE')")
     */
    public function demoteAction(Person $person, Request $request)
    {
        $search = $request->query->get('search', null);
        $user = $person->getUser();
        if( $user->hasRole('ROLE_ADMIN') )
            $user->removeRole('ROLE_ADMIN');
        $this->um->updateUser($user);

        $this->get('session')->getFlashBag()->add('notice', 'Droits d\'administration retirés à l\'individu "' . $person . '"');
        return $this->redirect($this->generateUrl('user_person_index', array('search' => $search)));
    }

    /**
     * Export mail by structure
     *
     * @Route("/export/{slug}/mail", name="user_person_export")
     * @Template()
     * @Security\PreAuthorize("hasRole('ROLE_STRUCTURE')")
     */
    public function exportMailAction($slug)
    {
        $mails = $this->em->getRepository('PigassUserBundle:Person')->getMailsByStructure($structure_id);
        $structure = $this->em->getRepository('PigassUserBundle:Structure')->findBy(array('slug' => $slug));

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
     * @Security\PreAuthorize("hasRole('ROLE_MEMBER')")
     */
    public function editMeAction(Request $request)
    {
        $user = $this->get('security.token_storage')->getToken()->getUsername();
        $person = $this->em->getRepository('PigassUserBundle:Person')->getByUsername($user);

        if (!$person)
            throw $this->createNotFoundException('Unable to find person entity.');

        $form = $this->createForm(new PersonUserType(), $person);
        $formHandler = new PersonHandler($form, $request, $this->em, $this->um);

        if ($formHandler->process()) {
            $this->get('session')->getFlashBag()->add('notice', 'Votre compte a bien été modifié.');
            return $this->redirect($this->generateUrl('user_person_SEdit'));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * Install first user
     *
     * @Route("/firstuser", name="user_person_install")
     * @Template()
     */
    public function installAction(Request $request)
    {
        if ($em->getRepository('PigassUserBundle:User')->findAll()) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $user = $this->um->createUser();
        $form = $this->createForm(new UserAdminType($user));
        $formHandler = new UserHandler($form, $request, $this->um);

        if ( $formHandler->process() ) {
            $this->get('session')->getFlashBag()->add('notice', 'Administrateur "' . $user->getUsername() . '" enregistré. Vous pouvez maintenant vous identifier.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        return array(
            'form' => $form->createView(),
        );
    }

}
