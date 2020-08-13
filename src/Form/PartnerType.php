<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\CollectionType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\UserType,
    App\Form\FilterType;

/**
 * PartnerType
 */
class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $questions = $options['questions'];

        $builder
            ->add('name', TextType::class, array(
                'label'   => 'Nom',
            ))
            ->add('user', UserType::class, array(
                'label'   => ' ',
            ))
            ->add('filters', CollectionType::class, array(
                'label'        => 'Filtres',
                'entry_type'   => FilterType::class,
                'entry_options' => array(
                    'questions' => $questions,
                ),
                'allow_add'    => true,
                'allow_delete' => true,
                'delete_empty' => true,
            ))
            ->add('limits', ChoiceType::class, array(
                'label' => 'Restrictions d\'accès',
                'choices' => array(
                    'Téléphone' => 'telephone',
                    'Adresse'   => 'address',
                ),
                'multiple' => true,
                'expanded' => true,
            ))
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'app_type_partner';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Partner',
            'questions'  => null,
        ));
    }
}

