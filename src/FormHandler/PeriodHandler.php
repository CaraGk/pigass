<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use App\Entity\Period,
    App\Entity\Structure;

/**
 * PeriodType Handler
 */
class PeriodHandler
{
    private $form, $request, $em, $structure;

    public function __construct(Form $form, Request $request, EntityManager $em, Structure $structure)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->em      = $em;
        $this->structure = $structure;
    }

    public function process()
    {
        if ( $this->request->getMethod() == 'POST' ) {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess(($this->form->getData()));
                return true;
            }
        }
        return false;
    }

    public function onSuccess(Period $period)
    {
        if (!$period->getStructure())
            $period->setStructure($this->structure);
        $this->em->persist($period);
        $this->em->flush();
    }
}
