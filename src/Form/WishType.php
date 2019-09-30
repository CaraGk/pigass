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

/**
 * WishType
 */
class WishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rules = $options['rules'];

        $builder->add('department', 'entity', array(
            'class'         => 'App:Department',
            'query_builder' => function (\App\Entity\DepartmentRepository $er) use ($rules) { return $er->getAdaptedUserList($rules); },
            'label'         => ' ',
        ));
        $builder->add('Ajouter', 'submit');
    }

    public function getName()
    {
        return 'app_type_wish';
    }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'App\Entity\Wish',
    ));
  }
}
