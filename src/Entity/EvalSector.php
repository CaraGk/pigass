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
 * App\Entity\EvalSector
 *
 * @ORM\Table(name="eval_sector")
 * @ORM\Entity(repositoryClass="App\Repository\EvalSectorRepository")
 */
class EvalSector
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
     * @ORM\OneToOne(targetEntity="App\Entity\Sector")
     * @ORM\JoinColumn(name="sector_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $sector;

    /**
     * @ORM\ManyToOne(targetEntity="EvalForm")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $form;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    public function __toString()
    {
      return $sector . " : " . $form;
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
     * Set form
     *
     * @param App\Entity\EvalForm $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * Get form
     *
     * @return App\Entity\EvalForm
     */
    public function getForm()
    {
        return $this->form;
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
