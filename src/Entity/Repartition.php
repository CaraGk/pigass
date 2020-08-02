<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Repartition
 *
 * @ORM\Table(name="repartition")
 * @ORM\Entity(repositoryClass="App\Repository\RepartitionRepository")
 */
class Repartition
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
     * @ORM\ManyToOne(targetEntity="Period", inversedBy="repartitions", cascade={"persist"})
     * @ORM\JoinColumn(name="period_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Period")
     */
    private $period;

    /**
     * @ORM\ManyToOne(targetEntity="Department", inversedBy="repartitions", cascade={"persist"})
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Department")
     */
    private $department;

    /**
     * @var smallint $number
     *
     * @ORM\Column(name="number", type="smallint", nullable=true)
     */
    private $number;

    /**
     * @ORM\OneToMany(targetEntity="Placement", mappedBy="repartition", cascade={"remove"}, orphanRemoval=true)
     */
    private $placements;

    /**
     * @ORM\Column(name="cluster", type="string", length=100, nullable=true)
     */
    private $cluster;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __toString()
    {
        return $this->department->getName() . " à " . $this->department->getHospital()->getName() . " (" . $this->period->getBegin()->format('d/m/Y') . "-" . $this->period->getEnd()->format('d/m/Y') . ")";
    }
    public function __construct()
    {
      $this->placements = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set period
     *
     * @param App\Entity\Period $period
     */
    public function setPeriod(\App\Entity\Period $period)
    {
        $this->period = $period;
    }

    /**
     * Get period
     *
     * @return App\Entity\Period
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * Set department
     *
     * @param App\Entity\Department $department
     */
    public function setDepartment(\App\Entity\Department $department)
    {
        $this->department = $department;
        $department->addRepartition($this);
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
     * Set number
     *
     * @param smallint $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get number
     *
     * @return smallint
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Add placement
     *
     * @param App\Entity\Placement $placement
     */
    public function addPlacement(\App\Entity\Placement $placement)
    {
        $this->placements[] = $placement;
    }

    /**
     * Remove placement
     *
     * @param App\Entity\Placement $placement
     */
    public function removePlacement(\App\Entity\Placement $placement)
    {
    }

    /**
     * Get placements
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPlacements()
    {
        return $this->placements;
    }

    /**
     * Set cluster
     *
     * @param  string     $cluster
     * @return Department
     */
    public function setCluster($cluster)
    {
        $this->cluster = $cluster;

        return $this;
    }

    /**
     * Get cluster
     *
     * @return string
     */
    public function getCluster()
    {
        return $this->cluster;
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
