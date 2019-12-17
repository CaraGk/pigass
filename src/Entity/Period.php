<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\Period
 *
 * @ORM\Table(name="period")
 * @ORM\Entity(repositoryClass="App\Repository\PeriodRepository")
 */
class Period
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
     * @var text $name
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var date $begin
     *
     * @ORM\Column(name="begin", type="date")
     */
    private $begin;

    /**
     * @var date $end
     *
     * @ORM\Column(name="end", type="date")
     */
    private $end;

    /**
     * @var date $simul_begin
     *
     * @ORM\Column(name="simul_begin", type="date", nullable=true)
     */
    private $simul_begin;

    /**
     * @var date $simul_end
     *
     * @ORM\Column(name="simul_end", type="date", nullable=true)
     */
    private $simul_end;

    /**
     * @ORM\OneToMany(targetEntity="Repartition", mappedBy="period", cascade={"remove"})
     */
    private $repartitions;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->repartitions = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set begin
     *
     * @param date $begin
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;
    }

    /**
     * Get begin
     *
     * @return date
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Set end
     *
     * @param date $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * Get end
     *
     * @return date
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set simul_begin
     *
     * @param date $simul_begin
     */
    public function setSimulBegin($simul_begin)
    {
        $this->simul_begin = $simul_begin;
    }

    /**
     * Get simul_begin
     *
     * @return date
     */
    public function getSimulBegin()
    {
        return $this->simul_begin;
    }

    /**
     * Set simul_end
     *
     * @param date $simul_end
     */
    public function setSimulEnd($simul_end)
    {
        $this->simul_end = $simul_end;
    }

    /**
     * Get simul_end
     *
     * @return date
     */
    public function getSimulEnd()
    {
        return $this->simul_end;
    }

    /**
     * Add repartitions
     *
     * @param App\Entity/Repartition $repartition
     */
    public function addRepartition(\App\Entity\Repartition $repartition)
    {
        $this->repartitions[] = $repartition;
    }

    /**
     * Remove repartition
     *
     * @param App\Entity\Repartition $repartition
     */
    public function removeRepartition(\App\Entity\Repartition $repartition)
    {
        $this->repartitions->removeElement($repartition);
    }

    /**
     * Get repartitions
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getRepartitions()
    {
        return $this->repartitions;
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
