<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\FileType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * ReceiptType
 */
class ReceiptType extends AbstractType
{
    private $structure;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];

        $builder
            ->add('begin', DateType::class, array(
                'label' => 'Du',
                'help'  => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'attr'  => [ 'placeholder' => 'AAAA-MM-JJ' ],
            ))
            ->add('end', DateType::class, array(
                'label' => 'Au',
                'help'  => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'attr'  => [ 'placeholder' => 'AAAA-MM-JJ' ],
            ))
            ->add('person', EntityType::class, array(
                'label'         => 'Signataire',
                'class'         => 'App:Person',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->join('p.memberships', 'm')
                        ->where('m.structure = :structure_id')
                        ->setParameter('structure_id', $this->structure->getId());
                },
                'required'      => true,
                'multiple'      => false,
                'expanded'      => false,
            ))
            ->add('position', TextType::class, array(
                'label' => 'En qualité de'
            ))
            ->add('imageFile', VichImageType::class, array(
                'label'          => 'Signature (image)',
                'required'       => false,
                'allow_delete'   => false,
                'download_uri'   => false,
                'image_uri'      => true,
            ))
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'pigass_corebundle_structuretype';
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Receipt',
            'structure' => null,
        ));
    }
}
