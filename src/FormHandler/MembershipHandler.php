<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use App\Entity\Membership,
    App\Entity\MemberInfo,
    App\Entity\MemberQuestion,
    App\Entity\Person,
    App\Entity\Structure;

/**
 * MembershipType Handler
 */
class MembershipHandler
{
    private $form, $request, $em, $um, $options, $structure, $person, $questions, $admin;

    public function __construct(Form $form, Request $request, EntityManager $em, UserManager $um, Structure $structure, $options = ['payment' => '60', 'date' => "2015-09-01", 'periodicity' => "+ 1 year", 'anticipated' => "+ 0 day"], $person = null, $questions = null, $admin = false)
    {
      $this->form        = $form;
      $this->request     = $request;
      $this->em          = $em;
      $this->um          = $um;
      $this->options     = $options;
      $this->structure   = $structure;
      $this->person      = $person;
      $this->questions   = $questions;
      $this->admin       = $admin;
    }

    public function process()
    {
        if($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    public function onSuccess(Membership $membership)
    {
        if ($this->questions) {
            foreach ($this->questions as $question) {
                $info = $this->form->get('question_' . $question->getId())->getData();
                if($question->getType() == 3) {
                    foreach($info as $item) {
                        $this->setQuestionInfo($membership, $question, $item);
                    }
                } else {
                    $this->setQuestionInfo($membership, $question, $info);
                }
            }
        }

        $expire = new \DateTime($this->options['date']);
        $expire->modify('- 1 day');
        $date = $membership->getPayedOn()?$membership->getPayedOn():new \DateTime('now');
        $date->modify($this->options['anticipated']);
        while ($expire <= $date) {
            $expire->modify($this->options['periodicity']);
        }

        $membership->setAmount($membership->getFee()->getAmount());
        $membership->setExpiredOn($expire);
        $membership->setStructure($this->structure);
        $membership->setStatus('registered');

        if ($this->person) {
            $membership->setPerson($this->person);
        } else {
            $user = $membership->getPerson()->getUser();
            if (!$db_user = $this->em->getRepository('App:User')->findOneBy(['username' => $user->getUsername()])) {
                $this->updateUser($user);
                $membership->getPerson()->setAnonymous(false);
            } else {
                if ($db_user->isEnabled())
                    return 'exists';
                else
                    return 'disabled';
            }
        }

        $this->em->persist($membership);
        $this->em->flush();

        return true;
    }

    private function updateUser($user)
    {
        if (null == $user->getId()) {
            $this->um->createUser();
            if (!$user->getPlainPassword())
                $user->setPlainPassword($this->generatePwd(8));
            $user->addRole('ROLE_MEMBER');
            if (isset($this->options['token']) and $this->options['token']) {
                $user->setConfirmationToken($this->options['token']);
            } else {
                $user->setConfirmationToken(null);
                $user->setEnabled(true);
            }
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

    private function setQuestionInfo(Membership $membership, MemberQuestion $question, $value)
    {
        $member_info = new MemberInfo();
        $member_info->setMembership($membership);
        $member_info->setValue($value);
        $member_info->setQuestion($question);

        if (!$this->admin or $member_info->getValue())
            $this->em->persist($member_info);
    }
}
