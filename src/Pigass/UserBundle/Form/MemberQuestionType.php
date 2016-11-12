<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\OptionsResolver\OptionsResolver,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\CollectionType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * MemberQuestionType
 */
class MemberQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];

        $builder
            ->add('rank')
            ->add('name')
            ->add('type', ChoiceType::class, array(
              'choices' => array(
                'Choix unique non pondéré' => 5,
                'Choix multiple'           => 3,
                'Valeur numérique'         => 4,
                'Horaire'                  => 6,
                'Date'                     => 7,
                'Texte long'               => 2,
              ),
              'required' => true,
              'multiple' => false,
              'expanded' => false,
            ))
            ->add('more', CollectionType::class, array(
                'label'        => 'Réponses',
                'entry_type'   => TextType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype'    => true,
            ))
        ;

        if (null == $this->structure)
            $builder->add('structure');

        $builder->add('Enregistrer', SubmitType::class);
    }

    public function getName()
    {
        return 'pigass_userbundle_memberquestiontype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'=> 'Pigass\UserBundle\Entity\MemberQuestion',
            'structure' => null,
        ));
    }
}
