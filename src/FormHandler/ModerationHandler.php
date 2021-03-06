<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManager;
use App\Entity\Evaluation,
    App\Entity\User;

/**
 * ModerationType Handler
 */
class ModerationHandler
{
    private $form, $request, $em, $evaluation, $user;

    public function __construct(Form $form, Request $request, EntityManager $em, Evaluation $evaluation, User $user)
    {
        $this->form       = $form;
        $this->request    = $request;
        $this->em         = $em;
        $this->evaluation = $evaluation;
        $this->user       = $user;
    }

    public function process()
    {
        if ($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return true;
            }
        }

        return false;
    }

    public function onSuccess($form)
    {
        $new_eval = new Evaluation();
        $new_eval->setPlacement($this->evaluation->getPlacement());
        $new_eval->setEvalCriteria($this->evaluation->getEvalCriteria());
        $new_eval->setValue($form['value']);
        $new_eval->setCreatedAt(new \DateTime('now'));
        $new_eval->setValidated(false);
        $new_eval->setModerated(false);
        $new_eval->setModerator($this->user);
        $new_eval->setStructure($this->evaluation->getStructure());
        $this->em->persist($new_eval);
        $this->evaluation->setModerated(true);
        $this->em->persist($this->evaluation);
        $this->em->flush();
    }

}

