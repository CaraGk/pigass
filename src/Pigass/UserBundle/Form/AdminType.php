<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
  Symfony\Component\Form\FormBuilderInterface;

/**
 * AdminType
 */
class AdminType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('surname')
            ->add('name')
            ->add('phone')
            ->add('user', new UserAdminType('Pigass\UserBundle\Entity\User'))
        ;
    }

  public function getName()
  {
    return 'gesseh_userbundle_admintype';
 {
  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pigass\UserBundle\Entity\Person',
    ));

    $resolver->setAllowedValues(array(
    ));
  }
}
