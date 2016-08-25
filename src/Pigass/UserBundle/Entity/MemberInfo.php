<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MemberInfo
 *
 * @ORM\Table(name="member_info")
 * @ORM\Entity(repositoryClass="Pigass\UserBundle\Entity\MemberInfoRepository")
 */
class MemberInfo
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
     * @ORM\ManyToOne(targetEntity="Membership", inversedBy="infos", cascade={"persist"})
     * @ORM\JoinColumn(name="membership_id", referencedColumnName="id")
     */
    private $membership;

    /**
     * @ORM\ManyToOne(targetEntity="MemberQuestion")
     * @ORM\JoinColumn(name="memberquestion_id", referencedColumnName="id")
     */
    private $question;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;


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
     * Set membership
     *
     * @param Pigass\UserBundle\Entity\Membership $membership
     * @return MemberInfo
     */
    public function setMembership(\Pigass\UserBundle\Entity\Membership $membership)
    {
        $this->membership = $membership;

        return $this;
    }

    /**
     * Get membership
     *
     * @return Pigass\UserBundle\Entity\Membership
     */
    public function getMembership()
    {
        return $this->membership;
    }

    /**
     * Set question
     *
     * @param Pigass\UserBundle\Entity\MemberQuestion $question
     * @return MemberInfo
     */
    public function setQuestion(\Pigass\UserBundle\Entity\MemberQuestion $question)
    {
        $this->question = $question;

        return $this;
    }
    /**
     * Get question
     *
     * @return Pigass\UserBundle\Entity\MemberQuestion
     */
    public function getQuestion()
    {
        return $this->question;
    }


    /**
     * Set value
     *
     * @param string $value
     * @return MemberInfo
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
}
