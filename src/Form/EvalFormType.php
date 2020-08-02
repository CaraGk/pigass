<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType,
    Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\EvalCriteriaType,
    App\Form\EvalSectorType;

/**
 * EvalFormType
 */
class EvalFormType extends AbstractType
{
    private $exclude_sectors = null;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Titre du formulaire (pour identification)',
            ])
            ->add('sectors', CollectionType::class, [
                'label'        => 'Agréments',
                'entry_type'   => EvalSectorType::class,
                'entry_options' => ['exclude_sectors' => $this->exclude_sectors],
                'allow_add'    => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype'    => true,
                'by_reference' => false,
            ])
            ->add('criterias', CollectionType::class, [
                'label'        => 'Questions du formulaire',
                'entry_type'   => EvalCriteriaType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype'    => true,
                'by_reference' => false,
                'attr'         => ['class' => 'criterias_collection'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
            ])
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EvalForm',
            'exclude_sectors' => null,
        ));
    }
}
