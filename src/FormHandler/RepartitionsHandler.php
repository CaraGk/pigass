<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licences/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManager;
use App\Entity\Repartition,
    App\Entity\Structure;

/**
 * Repartitions Type Handler
 */
class RepartitionsHandler
{
    private $form, $request, $em, $repartitions, $structure;

    public function __construct(Form $form, Request $request, EntityManager $em, array $repartitions, Structure $structure)
    {
        $this->form         = $form;
        $this->request      = $request;
        $this->em           = $em;
        $this->repartitions = $repartitions;
        $this->structure    = $structure;
    }

    public function process()
    {
        if($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if($this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return true;
            }
        }
        return false;
    }

    public function onSuccess($data)
    {
        foreach($this->repartitions as $repartition) {
            $repartition->setNumber(isset($data['number_' . $repartition->getId()])?$data['number_' . $repartition->getId()]:0);
            $repartition->setCluster(isset($data['cluster_' . $repartition->getId()])?$data['cluster_' . $repartition->getId()]:null);
            $repartition->setStructure($this->structure);

            $this->em->persist($repartition);
        }
        $this->em->flush();
    }
}
