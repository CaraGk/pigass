<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Payum\Core\Model\GatewayConfig as BaseGatewayConfig;

/**
 * Gateway
 *
 * @ORM\Table(name="payum_gateway")
 * @ORM\Entity(repositoryClass="Pigass\UserBundle\Entity\GatewayRepository")
 */
class Gateway extends BaseGatewayConfig
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Pigass\CoreBundle\Entity\Structure", inversedBy="gateways", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __toString()
    {
        return $this->gatewayName;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set structure
     *
     * @param \Pigass\CoreBundle\Entity\Structure $structure
     *
     * @return Gateway
     */
    public function setStructure(\Pigass\CoreBundle\Entity\Structure $structure = null)
    {
        $this->structure = $structure;

        return $this;
    }

    /**
     * Get structure
     *
     * @return \Pigass\CoreBundle\Entity\Structure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription()
    {
        if ($this->factoryName == "offline")
            return "Chèque, virement bancaire ou espèces";
        elseif ($this->factoryName == "paypal_express_checkout")
            return "Paypal";
        else
            return false;
    }
}
