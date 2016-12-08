<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Pigass\UserBundle\Entity\Gateway;
use Pigass\CoreBundle\Entity\Structure;

/**
 * GatewayType Handler
 */
class GatewayHandler
{
    private $form;
    private $request;
    private $em;
    private $structure;

    public function __construct(Form $form, Request $request, EntityManager $em, Structure $structure)
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

    public function onSuccess(Gateway $gateway)
    {
        $gatewayName = $this->structure->getSlug() . '_' . $gateway->getFactoryName();
        $otherGateways = $this->structure->getGateways();
        foreach ($otherGateways as $otherGateway) {
            if ($otherGateway->getGatewayName() == $gatewayName)
                $gatewayName .= '_' . md5($gateway->getLabel());
        }
        $gateway->setGatewayName($gatewayName);
        $gateway->setStructure($this->structure);
        $this->em->persist($gateway);
        $this->em->flush();
    }
}
