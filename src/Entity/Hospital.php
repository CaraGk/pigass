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
 * App\Entity\Hospital
 *
 * @ORM\Table(name="hospital")
 * @ORM\Entity(repositoryClass="App\Repository\HospitalRepository")
 */
class Hospital
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
     * @Assert\Length(min = 5)
     */
    private $name;

    /**
     * @var string $address
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string $web
     *
     * @ORM\Column(name="web", type="string", length=255, nullable=true)
     * @Assert\Url(protocols={"http", "https"}))
     */
    private $web;

    /**
     * @var string $phone
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @var text $description
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Department", mappedBy="hospital", cascade={"remove", "persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"name" = "asc"})
     */
    private $departments;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __construct()
    {
        $this->departments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set web
     *
     * @param string $web
     */
    public function setWeb($web)
    {
        $this->web = $web;
    }

    /**
     * Get web
     *
     * @return string
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Set phone
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add departments
     *
     * @param App\Entity\Department $department
     */
    public function addDepartment(\App\Entity\Department $department)
    {
        $this->departments[] = $department;
        $department->setHospital($this);
    }

    /**
     * Remove department
     *
     * @param App\Entity\Departement $department
     */
    public function removeDepartment(\App\Entity\Department $department)
    {
        $this->departments->removeElement($department);
    }

    /**
     * Set departments
     *
     * @param Doctrine\Common\Collections\Collection $departments
     */
    public function setDepartments(\Doctrine\Common\Collections\Collection $departments)
    {
      $this->departments = $departments;
      foreach($departments as $department)
        $department->setHospital($this);
    }

    /**
     * Get departments
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getDepartments()
    {
        return $this->departments;
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
