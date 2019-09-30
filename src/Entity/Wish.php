<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Wish
 *
 * @ORM\Table(name="wish")
 * @ORM\Entity(repositoryClass="App\Repository\WishRepository")
 */
class Wish
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Department")
     * @ORM\JoinColumn(name="department", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Department")
     */
    private $department;

    /**
     * @var integer $rank
     *
     * @ORM\Column(name="rank", type="integer")
     */
    private $rank;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Simulation", inversedBy="wishes", cascade={"persist"})
     * @ORM\JoinColumn(name="sim_id", referencedColumnName="id", nullable=false)
     * @Assert\Type(type="App\Entity\Simulation")
     */
    private $simulation;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __toString()
    {
      return $this->department;
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
     * Set department
     *
     * @param App\Entity\Department $department
     */
    public function setDepartment(\App\Entity\Department $department)
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
     * Set rank
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
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
     * Set simulation
     *
     * @param App\Entity\Simulation $simulation
     */
    public function setSimulation(\App\Entity\Simulation $simulation)
    {
      $this->simulation = $simulation;
    }

    /**
     * Get simulation
     *
     * @return App\Entity\Simulation
     */
    public function getSimulation()
    {
      return $this->simulation;
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
