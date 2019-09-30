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
use App\Entity\Wish;

/**
 * WishType Handler
 */
class WishHandler
{
  private $form, $request, $em, $simulation;

  public function __construct(Form $form, Request $request, EntityManager $em, \App\Entity\Simulation $simulation)
  {
    $this->form    = $form;
    $this->request = $request;
    $this->em      = $em;
    $this->simulation = $simulation;
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

  public function onSuccess(Wish $wish)
  {
    $rank = $this->em->getRepository('App:Wish')->getMaxRank($this->simulation->getPerson());

    $wish->setSimperson($this->simulation);
    $wish->setRank($rank+1);
    if (!$wish->getStructure())
        $wish->setStructure($simulation->getStructure());

    $this->em->persist($wish);
    $this->em->flush();
  }
}
