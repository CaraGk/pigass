<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Parameter
 *
 * @ORM\Table(name="parameter")
 * @ORM\Entity(repositoryClass="App\Repository\ParameterRepository")
 */
class Parameter
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
     * @var integer $name
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string $label
     *
     * @ORM\Column(name="label", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min = 5)
     */
    private $label;

    /**
     * @var string $category
     *
     * @ORM\Column(name="category", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min = 5)
     */
    private $category;

    /**
     * @var smallint $type
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var array more
     *
     * @ORM\Column(name="more", type="array", nullable=true)
     */
    private $more;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure")
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     */
    private $structure;

    /**
     * @var string value
     *
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * @var boolean active
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    public function __toString()
    {
      return $this->label;
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
     * Set category
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set type
     *
     * @param smallint $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
      if ($this->type == 1)
        return 'string';
      elseif ($this->type == 2)
          return 'boolean';
      elseif ($this->type == 3)
          return 'choice';
      else
        return $this->type;
    }

    /**
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set more
     *
     * @param array $more
     * @return Parameter
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
     * Set structure
     *
     * @param \App\Entity\Structure $structure
     *
     * @return Parameter
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
     * Set name
     *
     * @param string $name
     * @return Parameter
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Parameter
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Parameter
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }
}
