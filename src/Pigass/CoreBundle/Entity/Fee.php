<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Pigass\CoreBundle\Entity\Fee
 *
 * @ORM\Table(name="fee")
 * @ORM\Entity(repositoryClass="Pigass\CoreBundle\Entity\FeeRepository")
 * @Vich\Uploadable
 */
class Fee
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
     * @ORM\Column(name="title", type="string", length=20)
     *
     * @var string $title
     */
    private $title;

    /**
     * @ORM\Column(name="amount", type="smallint")
     *
     * @var smallint $amount
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity="\Pigass\CoreBundle\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    /**
     * @ORM\Column(name="help", type="text", nullable=true)
     *
     * @var text $help
     */
    private $help;

    public function __toString()
    {
        return $this->title . ' - ' . number_format($this->amount / 100, 2,',',' ') . ' €';
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
     * Set title
     *
     * @param string $title
     *
     * @return Fee
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set amount
     *
     * @param string $amount
     *
     * @return Fee
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @param boolean $humanReadable
     *
     * @return string
     */
    public function getAmount($humanReadable = false)
    {
        if ($humanReadable)
            return number_format($this->amount / 100, 2, ',', ' ') . ' €';
        else
            return $this->amount;
    }

    /**
     * Set help
     *
     * @param string $help
     *
     * @return Fee
     */
    public function setHelp($help)
    {
        $this->help = $help;

        return $this;
    }

    /**
     * Get help
     *
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * Set structure
     *
     * @param \Pigass\CoreBundle\Entity\Structure $structure
     *
     * @return Fee
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
}