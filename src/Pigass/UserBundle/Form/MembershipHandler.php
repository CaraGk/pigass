<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use Pigass\UserBundle\Entity\Membership,
    Pigass\UserBundle\Entity\Person,
    Pigass\CoreBundle\Entity\Structure;

/**
 * MembershipType Handler
 */
class MembershipHandler
{
    private $form, $request, $em, $um, $options, $structure, $person;

    public function __construct(Form $form, Request $request, EntityManager $em, UserManager $um, Structure $structure, $options = ['payment' => '60', 'date' => "2015-09-01", 'periodicity' => "+ 1 year", 'anticipated' => "+ 0 day"], $person = null)
    {
      $this->form        = $form;
      $this->request     = $request;
      $this->em          = $em;
      $this->um          = $um;
      $this->options     = $options;
      $this->structure   = $structure;
      $this->person      = $person;
    }

    public function process()
    {
        if($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if($this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return $this->form->getData();
            }
        }

        return false;
    }

    public function onSuccess(Membership $membership)
    {
        $expire = new \DateTime($this->options['date']);
        $expire->modify('- 1 day');
        $now = new \DateTime('now');
        $now->modify($this->options['anticipated']);
        while ($expire <= $now) {
            $expire->modify($this->options['periodicity']);
        }

        $membership->setAmount($membership->getFee()->getAmount());
        $membership->setExpiredOn($expire);
        $membership->setStructure($this->structure);
        $membership->setStatus('registered');

        if ($this->person) {
            $membership->setPerson($this->person);
        } else {
            $this->updateUser($membership->getPerson()->getUser());
            $membership->getPerson()->setAnonymous(false);
        }

        $this->em->persist($membership);
        $this->em->flush();
    }

    private function updateUser($user)
    {
        if (null == $user->getId()) {
            $this->um->createUser();
            if (!$user->getPlainPassword()) {
                $user->setPlainPassword($this->generatePwd(8));
            }
            $user->setConfirmationToken(null);
            $user->setEnabled(true);
            $user->addRole('ROLE_MEMBER');
        }
        $user->setUsername($user->getEmail());

        $this->um->updateUser($user);
    }

    private function generatePwd($length)
    {
        $characters = array ('a','z','e','r','t','y','u','p','q','s','d','f','g','h','j','k','m','w','x','c','v','b','n','2','3','4','5','6','7','8','9','A','Z','E','R','T','Y','U','P','S','D','F','G','H','J','K','L','M','W','X','C','V','B','N');
        $password = '';

        for ($i = 0 ; $i < $length ; $i++) {
            $rand = array_rand($characters);
            $password .= $characters[$rand];
        }

        return $password;
    }
}
