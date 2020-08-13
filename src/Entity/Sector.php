<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Sector
 *
 * @ORM\Table(name="sector")
 * @ORM\Entity(repositoryClass="App\Repository\SectorRepository")
 */
class Sector
{
  /**
   * @var integer $id
   *
   * @ORM\Column(name="id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @var string $name
   *
   * @ORM\Column(name="name", type="string", length=255)
   * @Assert\NotBlank()
   */
  private $name;

    /**
     * @var boolean $is_default
     *
     * @ORM\Column(name="is_default", type="boolean")
     */
    private $is_default = false;

    /**
     * @ORM\OneToMany(targetEntity="Accreditation", mappedBy="sector", cascade={"remove", "persist"})
     */
    private $accreditations;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __construct()
    {
        $this->accreditations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
      return $this->name;
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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set is_default
     *
     * @param boolean $is_default
     * @return Sector
     */
    public function setDefault($is_default)
    {
        $this->is_default = $is_default;

        return $this;
    }

    /**
     * Get is_default
     *
     * @return boolean
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * Add Accreditation
     *
     * @param App\Entity\Accreditation $accreditation
     * @return Sector
     */
    public function addAccreditatiton(\App\Entity\Accreditation $accreditation)
    {
        $this->accreditations[] = $accreditation;

        return $this;
    }

    /**
     * Remove Accreditation
     *
     * @param App\Entity\Accreditation $accreditation
     */
    public function removeAccreditation(\App\Entity\Accreditation $accreditation)
    {
        $this->accreditations->removeElement($accreditation);
    }

    /**
     * Get Accreditations
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getAccreditations()
    {
        return $this->accreditations;
    }
    /**
     * Set structure
     *
     * @param \App\Entity\Structure $structure
     *
     * @return Fee
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

}
