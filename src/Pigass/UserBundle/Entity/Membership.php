<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Membership
 *
 * @ORM\Table(name="membership")
 * @ORM\Entity(repositoryClass="Pigass\UserBundle\Entity\MembershipRepository")
 */
class Membership
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
     * @ORM\ManyToOne(targetEntity="\Pigass\UserBundle\Entity\Person", cascade={"persist"})
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="decimal", precision=2)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Gateway", cascade={"persist"})
     * @ORM\JoinColumn(name="method_id", referencedColumnName="id")
     */
    private $method;

    /**
     * @ORM\ManyToOne(targetEntity="\Pigass\CoreBundle\Entity\Structure", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payed_on", type="datetime", nullable=true)
     */
    private $payedOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_on", type="datetime")
     */
    private $expiredOn;

    /**
     * @ORM\OneToOne(targetEntity="\Pigass\UserBundle\Entity\Payment")
     * @ORM\JoinColumn(name="payment_id")
     */
    private $payment;

    /**
     * @ORM\OneToMany(targetEntity="MemberInfo", mappedBy="membership", cascade={"remove", "persist"}, orphanRemoval=true)
     */
    private $infos;


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
     * @param Pigass\UserBundle\Entity\Person $person
     * @return Membership
     */
    public function setPerson(\Pigass\UserBundle\Entity\Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return Pigass\UserBundle\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return Membership
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set method
     *
     * @param Gateway $method
     * @return Membership
     */
    public function setMethod(Gateway $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return Gateway
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set payedOn
     *
     * @param \DateTime $payedOn
     * @return Membership
     */
    public function setPayedOn($payedOn)
    {
        $this->payedOn = $payedOn;

        return $this;
    }

    /**
     * Get payedOn
     *
     * @return \DateTime
     */
    public function getPayedOn()
    {
        return $this->payedOn;
    }

    /**
     * Set expiredOn
     *
     * @param \DateTime $expiredOn
     * @return Membership
     */
    public function setExpiredOn($expiredOn)
    {
        $this->expiredOn = $expiredOn;

        return $this;
    }

    /**
     * Get expiredOn
     *
     * @return \DateTime
     */
    public function getExpiredOn()
    {
        return $this->expiredOn;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
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
     * @param \Pigass\UserBundle\Entity\MemberInfo $infos
     * @return Membership
     */
    public function addInfo(\Pigass\UserBundle\Entity\MemberInfo $infos)
    {
        $this->infos[] = $infos;

        return $this;
    }

    /**
     * Remove infos
     *
     * @param \Pigass\UserBundle\Entity\MemberInfo $infos
     */
    public function removeInfo(\Pigass\UserBundle\Entity\MemberInfo $infos)
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
     * @param \Pigass\CoreBundle\Entity\Structure $structure
     *
     * @return Membership
     */
    public function setStructure(\Pigass\CoreBundle\Entity\Structure $structure = null)
    {
        $this->structure = $structure;

        return $this;
    }

    /**
     * Get structure
     *
     * @return \Pigass\CoreBundle\Entity\Structure
     */
    public function getStructure()
    {
        return $this->structure;
    }
}
