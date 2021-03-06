<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use App\Entity\MemberQuestion;
use App\Entity\Structure;

/**
 * MemberQuestionType Handler
 */
class MemberQuestionHandler
{
    private $form;
    private $request;
    private $em;
    private $structure;

    public function __construct(Form $form, Request $request, EntityManager $em, Structure $structure = null)
    {
        $this->form      = $form;
        $this->request   = $request;
        $this->em        = $em;
        $this->structure = $structure;
    }

    public function process()
    {
        if ($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isSubmitted() and $this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return true;
            }
        }

        return false;
    }

    public function onSuccess(MemberQuestion $question)
    {
        if (!$question->getStructure())
            $question->setStructure($this->structure);

        $this->em->persist($question);
        $this->em->flush();
    }
}
