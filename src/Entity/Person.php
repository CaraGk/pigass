<?php

/**
 * This file is part of PIGASS project
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
 * App\Entity\Person
 *
 * @ORM\Table(name="person")
 * @ORM\Entity(repositoryClass="App\Repository\PersonRepository")
 */
class Person
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
   * @var string $title
   *
   * @ORM\Column(name="title", type="string", length=5)
   */
  private $title = "M.";

  /**
   * @var string $surname
   *
   * @ORM\Column(name="surname", type="string", length=255)
   * @Assert\NotBlank()
   * @Assert\Length(min = 2)
   */
  private $surname;

  /**
   * @var string $name
   *
   * @ORM\Column(name="name", type="string", length=255)
   * @Assert\NotBlank()
   * @Assert\Length(min = 2)
   */
  private $name;

  /**
   * @var date $birthday
   *
   * @ORM\Column(name="birthday", type="date", nullable=true)
   */
  private $birthday;

  /**
   * @var string $birthplace
   *
   * @ORM\Column(name="birthplace", type="string", nullable=true)
   */
  private $birthplace;

  /**
   * @var string $phone
   *
   * @ORM\Column(name="phone", type="string", length=18, nullable=true)
   * @Assert\Length(min = 10)
   */
  private $phone;

  /**
   * @var array $address
   *
   * @ORM\Column(name="address", type="array", nullable=true)
   */
  private $address;

  /**
   * @ORM\OneToOne(targetEntity="User", cascade={"persist", "remove"})
   * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
   */
  private $user;

  /**
   * @var boolean $anonymous
   *
   * @ORM\Column(name="anonymous", type="boolean", nullable=true)
   */
  private $anonymous;

  /**
   * @ORM\ManyToOne(targetEntity="Grade", inversedBy="persons")
   * @ORM\JoinColumn(name="grade_id", referencedColumnName="id")
   */
  private $grade;

  /**
   * @var smallint $ranking
   *
   * @ORM\Column(name="ranking", type="smallint", nullable=true)
   */
  private $ranking;

  /**
   * @var smallint $graduate
   *
   * @ORM\Column(name="graduate", type="smallint", nullable=true)
   */
  private $graduate;

    /**
     * @ORM\OneToMany(targetEntity="Membership", mappedBy="person", cascade={"remove", "persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"end" = "asc"})
     */
    private $memberships;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    private function upperFirst($data)
    {
        return preg_replace_callback(
            '#([- ]?)(\p{L}*)([- ]?)#u',
            function ($data) {
                return in_array($data[0], array('d', 'de', 'des', 'du', 'en', 'l', 'la', 'le')) ? $data[0] : ucfirst($data[0]);
            },
            strtolower($data)
        );
    }

    public function __toString()
    {
        if ($this->isAnonymous())
            return "*** anonyme ***";
        else
            return $this->upperFirst($this->name) . ' ' . $this->upperFirst($this->surname);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->memberships = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set surname
     *
     * @param string $surname
     */
    public function setSurname($surname)
    {
        $this->surname = $this->upperFirst($surname);

        return $this;
    }

    /**
     * Get surname
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->upperFirst($this->surname);
    }

    /**
     * Get anonymized surname
     * @return string
     */
    public function getAnonSurname()
    {
      if ($this->isAnonymous())
        return "***";
      else
        return $this->surname;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $this->upperFirst($name);

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->upperFirst($this->name);
    }

    /**
     * Get anonymized name
     *
     * @return string
     */
    public function getAnonName()
    {
      if ($this->isAnonymous())
        return "***";
      else
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
     * Set phone
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
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
     * Set user
     *
     * @param App\Entity\User $user
     */
    public function setUser(\App\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get anonymous
     *
     * @return boolean
     */
    public function getAnonymous()
    {
      return $this->anonymous;
    }

    /**
     * Is anonymous?
     *
     * @return boolean
     */
    public function isAnonymous()
    {
      if ($this->anonymous)
        return true;
      else
        return false;
    }

    /**
     * Set anonymous
     *
     * @param boolean
     */
    public function setAnonymous($anonymous)
    {
      $this->anonymous = $anonymous;
    }

    /**
     * Set address
     *
     * @param  string  $address
     * @return Person
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
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
     * Get printable address
     *
     * @return string
     */
    public function getPrintableAddress($html = false)
    {
        $address = $this->address['number'] . ' ' . $this->address['type'] . ' ' . $this->address['street'];
        if ($complement = $this->address['complement']) {
            $address .= ($html?'<br />':', ') . $complement;
        }
        $address .= ($html?'<br />':', ') . $this->address['code'] . ', ' . $this->address['city'] . ', ' . $this->address['country'];

        return $address;
    }

    /**
     * Set title
     *
     * @param $title
     * @return Person
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set birthday
     *
     * @param $birthday
     * @return Person
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return $birthday
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set birthplace
     *
     * @param $birthplace
     * @return Person
     */
    public function setBirthplace($birthplace)
    {
        $this->birthplace = $this->upperFirst($birthplace);

        return $this;
    }

    /**
     * Get birthplace
     *
     * @return $birthplace
     */
    public function getBirthplace()
    {
        return $this->upperFirst($this->birthplace);
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
     * Set ranking
     *
     * @param smallint $ranking
     */
    public function setRanking($ranking)
    {
        $this->ranking = $ranking;
    }

    /**
     * Get ranking
     *
     * @return smallint
     */
    public function getRanking()
    {
        return $this->ranking;
    }

    /**
     * Set graduate
     *
     * @param smallint $graduate
     */
    public function setGraduate($graduate)
    {
        $this->graduate = $graduate;
    }

    /**
     * Get graduate
     *
     * @return smallint
     */
    public function getGraduate()
    {
        return $this->graduate;
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
