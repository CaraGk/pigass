<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Pigass\UserBundle\Entity\User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="Pigass\UserBundle\Entity\UserRepository")
 * @UniqueEntity(
 *      fields={"emailCanonical", "email", "username", "usernameCanonical"},
 *      errorPath="email",
 *      message="Cette adresse e-mail est déjà utilisée.")
 */
class User extends BaseUser
{
  /**
   * @var integer $id
   *
   * @ORM\Column(name="id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  public function __construct()
  {
    parent::__construct();
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
   * Set email
   *
   * @return User
   */
  public function setEmail($email)
  {
      $this->email = $email;
      $this->username = $email;

      return $this;
  }

  /**
   * Set emailCanonical
   *
   * @return User
   */
  public function setEmailCanonical($emailCanonical)
  {
    $this->emailCanonical = $emailCanonical;
    $this->usenameCanonical = $emailCanonical;

    return $this;
  }
}