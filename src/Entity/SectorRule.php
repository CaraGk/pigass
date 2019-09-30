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
 * App\Entity\SectorRule
 *
 * @ORM\Table(name="sector_rule")
 * @ORM\Entity(repositoryClass="App\Repository\SectorRuleRepository")
 */
class SectorRule
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Grade")
     * @ORM\JoinColumn(name="grade_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Grade")
     */
    private $grade;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Sector")
     * @ORM\JoinColumn(name="sector", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Sector")
     */
    private $sector;

    /**
     * @var string $relation
     *
     * @ORM\Column(name="relation", type="string", length=10)
     * @Assert\NotBlank()
     */
    private $relation;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __toString()
    {
      return '(' . $this->grade . ' ' . $this->relation . ' ' . $this->sector . ')';
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
     * Set grade
     *
     * @param App\Entity\Grade $grade
     */
    public function setGrade(\App\Entity\Grade $grade)
    {
        $this->grade = $grade;
    }

    /**
     * Get grade
     *
     * @return App\Entity\Grade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Set sector
     *
     * @param App\Entity\Sector $sector
     */
    public function setSector(\App\Entity\Sector $sector)
    {
        $this->sector = $sector;
    }

    /**
     * Get sector
     *
     * @return App\Entity\Sector
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * Set relation
     *
     * @param string $relation
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;
    }

    /**
     * Get relation
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Get relation explained
     *
     * @return string (or null)
     */
    public function getRelationExplained()
    {
      if ($this->relation == "NOT")
        return 'ne doit pas faire de stage de';
      elseif ($this->relation == "FULL")
        return 'doit compléter les stages de';
      else
        return null;
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
