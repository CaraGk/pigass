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
use Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * WishType
 */
class WishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rules = $options['rules'];

        $builder->add('department', EntityType::class, array(
            'class'         => 'App:Department',
            'query_builder' => function (\App\Repository\DepartmentRepository $er) use ($rules) { return $er->getAdaptedUserList($rules); },
            'label'         => ' ',
        ));
        $builder->add('Ajouter', SubmitType::class);
    }

    public function getName()
    {
        return 'app_type_wish';
    }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'App\Entity\Wish',
        'rules' => null,
    ));
  }
}
