<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\FormHandler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use App\Entity\Hospital,
    App\Entity\Department,
    App\Entity\Repartition,
    App\Entity\Structure;

/**
 * HospitalType Handler
 */
class HospitalHandler
{
  private $form, $request, $em, $periods, $structure;

  public function __construct(Form $form, Request $request, EntityManager $em, array $periods, Structure $structure)
  {
    $this->form    = $form;
    $this->request = $request;
    $this->em      = $em;
    $this->periods = $periods;
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

  public function onSuccess(Hospital $hospital)
  {
    foreach($hospital->getDepartments() as $department) {
        if(!$department->getId()) {
            foreach($this->periods as $period) {
                $repartition = new Repartition();
                $repartition->setDepartment($department);
                $repartition->setPeriod($period);
                $repartition->setNumber(0);
                $repartition->setStructure($this->structure);
                $this->em->persist($repartition);
            }
        }
    }
    $hospital->setStructure($this->structure);

    $this->em->persist($hospital);
    $this->em->flush();
  }
}
