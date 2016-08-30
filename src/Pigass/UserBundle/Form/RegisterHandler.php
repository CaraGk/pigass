<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManager,
    FOS\UserBundle\Doctrine\UserManager;
use Pigass\UserBundle\Entity\Membership,
    Pigass\UserBundle\Entity\Person,
    Pigass\CoreBundle\Entity\Structure;

/**
 * RegisterType Handler
 */
class RegisterHandler
{
    private $form, $request, $em, $um, $token;

    public function __construct(Form $form, Request $request, EntityManager $em, UserManager $um, $token)
    {
      $this->form    = $form;
      $this->request = $request;
      $this->em      = $em;
      $this->um      = $um;
      $this->token   = $token;
    }

    public function process()
    {
        if($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isSubmitted() and $this->form->isValid()) {
                $username = $this->onSuccess($this->form->getData());

                return $username;
            }
        }

        return false;
    }

    public function onSuccess(Person $person)
    {
        $person->setAnonymous(false);

        $user = $person->getUser();
        $user->addRole('ROLE_MEMBER');
        $user->setConfirmationToken($this->token);

        $this->em->persist($person);
        $this->um->createUser();
        $this->um->updateUser($user);

        $this->em->flush();

        return $user->getUsername();
    }
}
