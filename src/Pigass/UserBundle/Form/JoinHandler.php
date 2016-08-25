<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Pigass\UserBundle\Entity\Membership,
    Pigass\UserBundle\Entity\Person;

/**
 * JoinType Handler
 */
class JoinHandler
{
    private $form, $request, $em, $um, $payment, $token;

    public function __construct(Form $form, Request $request, EntityManager $em, $payment, Person $person, $reg_date = "2015-09-01", $reg_periodicity = "+ 1 year")
    {
      $this->form    = $form;
      $this->request = $request;
      $this->em      = $em;
      $this->payment = $payment;
      $this->person  = $person;
      $this->date    = $reg_date;
      $this->periodicity = $reg_periodicity;
    }

    public function process()
    {
        if($this->request->getMethod() == 'POST') {
            $this->form->bind($this->request);

            if($this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return $this->form->getData();
            }
        }

        return false;
    }

    public function onSuccess(Membership $membership)
    {
        $expire = new \DateTime($this->date);
        $now = new \DateTime('now');
        while ($expire <= $now) {
            $expire->modify($this->periodicity);
        }

        $membership->setAmount($this->payment);
        $membership->setExpiredOn($expire);
        $membership->setPerson($this->person);

        $this->em->persist($membership);
        $this->em->flush();
    }
}
