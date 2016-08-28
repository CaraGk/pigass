<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * UserType
 */
class UserType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('email', EmailType::class);
  }

  public function getName()
  {
    return 'pigass_user';
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pigass\UserBundle\Entity\User',
        'cascade_validation' => true,
    ));
  }
}
