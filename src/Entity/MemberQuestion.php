<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MemberQuestion
 *
 * @ORM\Table(name="member_question")
 * @ORM\Entity(repositoryClass="App\Repository\MemberQuestionRepository")
 */
class MemberQuestion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var smallint
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var array
     *
     * @ORM\Column(name="more", type="array", nullable=true)
     */
    private $more;

    /**
     * @var smallint
     *
     * @ORM\Column(name="rank", type="smallint")
     */
    private $rank;

    /**
     * @ORM\OneToMany(targetEntity="MemberInfo", mappedBy="question", cascade={"remove", "persist"}, orphanRemoval=true)
     */
    private $infos;

    /**
     * @var boolean
     *
     * @ORM\Column(name="required", type="boolean", nullable=true)
     */
    private $required;

    /**
     * @var string
     *
     * @ORM\Column(name="short", type="string", length=40)
     */
    private $short;

    /**
     * @var Structure
     *
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure")
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     */
    private $structure;

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
     * @return MemberQuestion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * Set type
     *
     * @param smallint $type
     * @return MemberQuestion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return smallint
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get readable type
     *
     * @return string
     */
    public function getReadableType()
    {
        if ($this->type == 1) {
            return "Choix unique pondéré";
        } elseif ($this->type == 2) {
            return "Texte long";
        } elseif ($this->type == 3) {
            return "Choix multiple";
        } elseif ($this->type == 4) {
            return "Valeur numérique";
        } elseif ($this->type == 5) {
            return "Choix unique non pondéré";
        } elseif ($this->type == 6) {
            return "Horaire";
        } elseif ($this->type == 7) {
            return "Date";
        } elseif ($this->type == 8) {
            return "Menu déroulant";
        } elseif ($this->type == 9) {
            return "Texte court";
        } else {
            return "Type inconnu";
        }
    }

    /**
     * Set more
     *
     * @param array $more
     * @return MemberQuestion
     */
    public function setMore($more)
    {
        $this->more = $more;

        return $this;
    }

    /**
     * Get more
     *
     * @return array
     */
    public function getMore()
    {
        return $this->more;
    }

    /**
     * Set rank
     *
     * @param smallint $rank
     * @return MemberQuestion
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return smallint
     */
    public function getRank()
    {
        return $this->rank;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->infos = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add infos
     *
     * @param \App\Entity\MemberInfo $infos
     * @return MemberQuestion
     */
    public function addInfo(\App\Entity\MemberInfo $infos)
    {
        $this->infos[] = $infos;

        return $this;
    }

    /**
     * Remove infos
     *
     * @param \App\Entity\MemberInfo $infos
     */
    public function removeInfo(\App\Entity\MemberInfo $infos)
    {
        $this->infos->removeElement($infos);
    }

    /**
     * Get infos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInfos()
    {
        return $this->infos;
    }

    /**
     * Set structure
     *
     * @param \App\Entity\Structure $structure
     *
     * @return MemberQuestion
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

    /**
     * Set required
     *
     * @param  boolean  $required
     * @return Question
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Is required
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Get required
     *
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set short
     *
     * @param string $short
     *
     * @return MemberQuestion
     */
    public function setShort($short)
    {
        $this->short = $short;

        return $this;
    }

    /**
     * Get short
     *
     * @return string
     */
    public function getShort()
    {
        return $this->short;
    }
}
