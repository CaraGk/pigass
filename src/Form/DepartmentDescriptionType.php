<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

/**
 * DepartmentDescriptionType
 */
class DepartmentDescriptionType extends AbstractType
{
    private $structure;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Titre du service',
            ])
            ->add('hospital', EntityType::class, [
                'label' => 'Établissement',
                'class' => 'App:Hospital',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('h')
                        ->where('h.structure = :structure')
                        ->setParameter('structure', $this->structure->getId())
                    ;
                },
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('description', TextareaType::class, array(
                'label' => 'Description (texte libre)',
                'attr' => array(
                    'class'      => 'tinymce',
                    'data-theme' => 'medium'
                ),
                'required' => false,
            ))
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Department',
            'structure' => null,
        ));
    }
}
