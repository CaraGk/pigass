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
 * App\Entity\Placement
 *
 * @ORM\Table(name="placement")
 * @ORM\Entity(repositoryClass="App\Repository\PlacementRepository")
 */
class Placement
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Person")
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="Repartition", inversedBy="placements", cascade={"persist"})
     * @ORM\JoinColumn(name="repartition_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Assert\Type(type="App\Entity\Repartition")
     */
    private $repartition;

    public function __toString()
    {
      return $this->repartition->getDepartment()->getName() . " à " . $this->repartition->getDepartment()->getHospital()->getName();
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
    /**
     * Set repartition
     *
     * @param App\Entity\Repartition $repartition
     */
    public function setRepartition(\App\Entity\Repartition $repartition)
    {
        $this->repartition = $repartition;
    }

    /**
     * Get repartition
     *
     * @return App\Entity\Repartition
     */
    public function getRepartition()
    {
        return $this->repartition;
    }
}
