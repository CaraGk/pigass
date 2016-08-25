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

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

/**
 * UserType
 */
class UserType extends BaseType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('email', 'email');
  }

  public function getName()
  {
    return 'pigass_user';
  }
}
