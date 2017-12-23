<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Pigass\CoreBundle\Entity\Receipt
 *
 * @ORM\Table(name="receipt")
 * @ORM\Entity(repositoryClass="Pigass\CoreBundle\Entity\ReceiptRepository")
 * @Vich\Uploadable
 */
class Receipt
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(name="begin", type="date")
     *
     * @var date $begin
     */
    private $begin;

    /**
     * @ORM\Column(name="end", type="date")
     *
     * @var date $end
     */
    private $end;

    /**
     * @ORM\ManyToOne(targetEntity="\Pigass\UserBundle\Entity\Person", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     *
     * @var Person $person
     */
    private $person;

    /**
     * @ORM\Column(name="position", type="string", length=50, nullable=false)
     * @Assert\NotBlank()
     *
     * @var string $position
     */
    private $position;

    /**
     * @Vich\UploadableField(mapping="receipt_sign", fileNameProperty="imageName")
     *
     * @var File $image
     */
    private $image;

    /**
     * @ORM\Column(name="sign", type="string", length=255, nullable=true)
     * @Assert\Image()
     *
     * @var string $imageName
     */
    private $imageName;

    /**
     * @ORM\ManyToOne(targetEntity="\Pigass\CoreBundle\Entity\Structure", inversedBy="receipts", cascade={"persist"})
     * @ORM\JoinColumn(name="structure_id", referencedColumnName="id")
     *
     * @var Structure $structure
     */
    private $structure;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @var \DateTime $updatedAt
     */
    private $updatedAt;


    public function __toString()
    {
        return $this->structure->getName() . ' : ' . $this->begin->format('Y') . ' - ' . $this->end->format('Y');
    }

    /**
     * Set image file
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Receipt
     */
    public function setImage(File $image = null)
    {
        $this->image = $image;

        if ($image) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function setImageFile(File $image = null)
    {
        $this->image = $image;

        if ($image)
            $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Get image file
     *
     * @return File|null
     */
    public function getImage()
    {
        return $this->image;
    }

    public function getImageFile()
    {
        return $this->image;
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
     * Set begin
     *
     * @param \DateTime $begin
     *
     * @return Receipt
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;

        return $this;
    }

    /**
     * Get begin
     *
     * @return \DateTime
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     *
     * @return Receipt
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set position
     *
     * @param string $position
     *
     * @return Receipt
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set imageName
     *
     * @param string $imageName
     *
     * @return Receipt
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get imageName
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Receipt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set person
     *
     * @param \Pigass\UserBundle\Entity\Person $person
     *
     * @return Receipt
     */
    public function setPerson(\Pigass\UserBundle\Entity\Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return \Pigass\UserBundle\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set structure
     *
     * @param \Pigass\CoreBundle\Entity\Structure $structure
     *
     * @return Receipt
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
