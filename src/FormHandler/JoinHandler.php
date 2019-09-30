<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2015 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManager,
    App\Entity\Membership;

/**
 * JoinType Handler
 */
class JoinHandler
{
    private $form, $request, $em, $um, $token, $params;

    public function __construct(Form $form, Request $request, EntityManager $em, $person, $params)
    {
      $this->form    = $form;
      $this->request = $request;
      $this->em      = $em;
      $this->person = $person;
      $this->params  = $params;
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
        $expire = new \DateTime($this->params['date']);
        $now = new \DateTime('now');
        while ($expire <= $now) {
            $expire->modify($this->params['periodicity']);
        }

        $membership->setAmount($this->params['payment']);
        $membership->setExpiredOn($expire);
        $membership->setPerson($this->person);

        $this->em->persist($membership);
        $this->em->flush();
    }
}
