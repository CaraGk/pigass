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
use App\Entity\Structure;

/**
 * App\Entity\Grade
 *
 * @ORM\Table(name="grade")
 * @ORM\Entity(repositoryClass="App\Repository\GradeRepository")
 */
class Grade
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
   * @Assert\Length(min = 2)
   */
  private $name;

  /**
   * @var integer $rank
   *
   * @ORM\Column(name="rank", type="integer")
   */
  private $rank;

  /**
   * @var boolean $isActive
   *
   * @ORM\Column(name="is_active", type="boolean", nullable=true)
   */
  private $isActive;

  /**
   * @ORM\OneToMany(targetEntity="Person", mappedBy="grade")
   */
  private $persons;

    /**
     * @ORM\ManyToOne(targetEntity="Structure", inversedBy="grades", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

  public function __construct()
  {
    $this->persons = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set isActive
     *
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add persons
     *
     * @param App\Entity\Person $persons
     */
    public function addPerson(\App\Entity\Person $persons)
    {
        $this->persons[] = $persons;
    }

    /**
     * Remove persons
     *
     * @param App\Entity\Person $persons
     */
    public function removePerson(\App\Entity\Person $persons)
    {
    }

    /**
     * Get persons
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPersons()
    {
        return $this->persons;
    }
    /**
     * Set structure
     *
     * @param \App\Entity\Structure $structure
     *
     * @return Grade
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
