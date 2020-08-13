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
use Doctrine\ORM\Query\Expr\OrderBy as OrderBy;

/**
 * App\Entity\Simulation
 *
 * @ORM\Table(name="simulation")
 * @ORM\Entity(repositoryClass="App\Repository\SimulationRepository")
 */
class Simulation
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var int $rank
     *
     * @ORM\Column(name="rank", type="integer")
     */
    private $rank;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Person")
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Department")
     * @ORM\JoinColumn(name="department", referencedColumnName="id", nullable=true)
     */
    private $department;

    /**
     * @var smallint $extra
     *
     * @ORM\Column(name="extra", type="smallint", nullable=true)
     */
    private $extra;

    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * var $is_excess
     * @ORM\Column(name="excess", type="boolean", nullable=true)
     */
    private $is_excess;

    /**
     * var $is_validated;
     *
     * @ORM\Column(name="validated", type="boolean", nullable=true)
     */
    private $is_validated;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Wish", mappedBy="simulation", cascade={"remove"})
     * @ORM\OrderBy({"rank" = "asc"})
     */
    private $wishes;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __construct()
    {
      $this->wishes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set id
     *
     * @param integer
     */
    public function setId($id)
    {
      $this->id = $id;
    }

    /**
     * Get rank
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return Simulation
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }
    /**
     * Set person
     *
     * @param App\Entity\Person $person
     */
    public function setPerson(\App\Entity\Person $person)
    {
        $this->person = $person;
    }

    /**
     * Get person
     *
     * @return App\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set department
     *
     * @param App\Entity\Department $department
     */
    public function setDepartment($department)
    {
        $this->department = $department;
    }

    /**
     * Get department
     *
     * @return App\Entity\Department
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Set extra
     *
     * @param smallint $extra
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * Get extra
     *
     * @return smallint
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set is_excess
     *
     * @param boolean $is_excess
     * @return Simulation
     */
    public function setIsExcess($is_excess)
    {
        $this->is_excess = $is_excess;

        return $this;
    }

    /**
     * Is excess?
     *
     * @return boolean
     */
    public function isExcess()
    {
        return $this->is_excess;
    }

    /**
     * Set is_validated
     *
     * @param boolean $is_validated
     * @return Simulation
     */
    public function setIsValidated($is_validated)
    {
        $this->is_validated = $is_validated;

        return $this;
    }

    /**
     * Is validated?
     *
     * @return boolean
     */
    public function isValidated()
    {
        return $this->is_validated;
    }

    /**
     * Set wishes
     *
     * @param Doctrine\Common\Collections\Collection $wishes
     */
    public function setWishes(\Doctrine\Common\Collections\Collection $wishes)
    {
      $this->wishes = $wishes;
    }

    /**
     * Get wishes
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getWishes()
    {
      return $this->wishes;
    }

    /**
     * Count wishes
     *
     * @return integer
     */
    public function countWishes()
    {
      return count($this->wishes);
    }

    /**
     * Add wishes
     *
     * @param App\Entity\Wish $wishes
     */
    public function addWish(\App\Entity\Wish $wishes)
    {
        $this->wishes[] = $wishes;
    }

    /**
     * Remove wishes
     *
     * @param App\Entity\Wish $wishes
     */
    public function removeWish(\App\Entity\Wish $wishes)
    {
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
