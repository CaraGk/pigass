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
use Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\NumberType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * GradeType
 */
class GradeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la promotion',
            ])
            ->add('rank', NumberType::class, [
                'label' => 'Rang',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active ?',
                'required' => false,
            ])
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'app_type_grade';
    }
}
