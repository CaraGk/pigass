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
use Pigass\UserBundle\Entity\Membership,
    Pigass\UserBundle\Entity\Person,
    Pigass\CoreBundle\Entity\Structure;

/**
 * JoinType Handler
 */
class JoinHandler
{
    private $form, $request, $em, $um, $payment, $person, $structure;

    public function __construct(Form $form, Request $request, EntityManager $em, $payment, Person $person, Structure $structure, $reg_date = "2015-09-01", $reg_periodicity = "+ 1 year", $reg_anticipated = "+ 0 day")
    {
      $this->form        = $form;
      $this->request     = $request;
      $this->em          = $em;
      $this->payment     = $payment;
      $this->person      = $person;
      $this->date        = $reg_date;
      $this->periodicity = $reg_periodicity;
      $this->anticipated = $reg_anticipated;
      $this->structure   = $structure;
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
        $expire = new \DateTime($this->date);
        $now = new \DateTime('now');
        $now->modify($this->anticipated);
        while ($expire <= $now) {
            $expire->modify($this->periodicity);
        }

        $membership->setAmount($this->payment);
        $membership->setExpiredOn($expire);
        $membership->setPerson($this->person);
        $membership->setStructure($this->structure);
        $membership->setStatus('registered');

        $this->em->persist($membership);
        $this->em->flush();
    }
}
