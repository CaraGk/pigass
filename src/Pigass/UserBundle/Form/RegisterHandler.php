<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
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
    Pigass\UserBundle\Entity\Person;

/**
 * UserType Handler
 */
class UserHandler
{
    private $form, $request, $em, $um, $payment, $token;

    public function __construct(Form $form, Request $request, EntityManager $em, UserManager $um, $payment, $token, $reg_date = "2015-09-01", $reg_periodicity = "+ 1 year")
    {
      $this->form    = $form;
      $this->request = $request;
      $this->em      = $em;
      $this->um      = $um;
      $this->payment = $payment;
      $this->token   = $token;
      $this->date    = $reg_date;
      $this->periodicity = $reg_periodicity;
    }

    public function process()
    {
        if($this->request->getMethod() == 'POST') {
            $this->form->bind($this->request);

            if($this->form->isValid()) {
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
