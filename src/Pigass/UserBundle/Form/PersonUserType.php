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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * PersonUserType
 */
class PersonUserType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
      $builder
            ->add('title', 'choice', array(
                'label' => 'Titre',
                'choices' => array(
                    'M.'   => 'M.',
                    'Mme'  => 'Mme',
                    'Mlle' => 'Mlle',
                ),
            ))
            ->add('surname', 'text', array(
                'label' => 'Nom',
            ))
            ->add('name', 'text', array(
                'label' => 'Prénom',
            ))
            ->add('birthday', 'birthday', array(
                'label'  => 'Date de naissance',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
            ))
            ->add('birthplace', 'text', array(
                'label' => 'Lieu de naissance',
            ))
            ->add('anonymous')
            ->add('user', new UserType('Pigass\UserBundle\Entity\User'), array(
                'label' => ' ',
            ))
            ->add('phone', 'text', array(
                'label' => 'Téléphone',
            ))
            ->add('address', new AddressType(), array(
                'label' => 'Adresse :'
            ))
            ->add('Enregistrer', 'submit')
    ;
  }

  public function getName()
  {
    return 'pigass_userbundle_persontype';
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pigass\UserBundle\Entity\Person',
        'cascade_validation' => true,
    ));

    $resolver->setAllowedValues(array(
    ));
  }
}