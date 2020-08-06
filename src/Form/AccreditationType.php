<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\UserType;

/**
 * Accreditation Type
 */
class AccreditationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('begin', DateType::class, [
                'label'  => 'Début de l\'agrément',
                'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
            ])
            ->add('end', DateType::class, [
                'label'  => 'Fin de l\'agrèment',
                'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
            ])
            ->add('revoked', CheckboxType::class, [
                'label'    => 'Révoquée ?',
                'required' => false,
            ])
            ->add('sector', EntityType::class, [
                'required' => true,
                'label'    => 'Type d\'agrément',
                'class'    => 'App\Entity\Sector',
            ])
            ->add('supervisor', TextType::class, [
                'label' => 'Nom du référent pédagogique',
            ])
            ->add('user', UserType::class, [
                'label' => ' ',
            ])
            ->add('comment', TextAreatype::class, [
                'label' => 'Remarques diverses',
                'required' => false,
            ])
            ->add('Enregister', SubmitType::class)
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Accreditation',
        ));
    }
}
