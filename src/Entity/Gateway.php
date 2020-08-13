<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Payum\Core\Model\GatewayConfig as BaseGatewayConfig;

/**
 * Gateway
 *
 * @ORM\Table(name="payum_gateway")
 * @ORM\Entity(repositoryClass="App\Repository\GatewayRepository")
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
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="gateways", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    /**
     * @ORM\Column(name="label", type="string", length=100)
     *
     * @var integer $label
     */
    private $label;

    /**
     * @ORM\Column(name="active", type="boolean")
     *
     * @var boolean $active
     */
    private $active;

    public function __toString()
    {
        return $this->label;
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
     * @param \App\Entity\Structure $structure
     *
     * @return Gateway
     */
    public function setStructure(\App\Entity\Structure $structure = null)
    {
        $this->structure = $structure;

        return $this;
    }

    /**
     * Get structure
     *
     * @return \App\Entity\Structure
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

    /**
     * Set label
     *
     * @param string $label
     *
     * @return Gateway
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Gateway
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Is active?
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }
}
