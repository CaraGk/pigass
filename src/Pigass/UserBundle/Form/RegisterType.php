<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\SubmitType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Pigass\UserBundle\Form\UserAdminType,
    Pigass\UserBundle\Form\AddressType;

/**
 * RegisterType
 */
class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', ChoiceType::class, array(
                'label' => 'Titre',
                'choices' => array(
                    'M.'   => 'M.',
                    'Mme'  => 'Mme',
                    'Mlle' => 'Mlle',
                ),
            ))
            ->add('surname', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('name', TextType::class, array(
                'label' => 'Prénom',
            ))
            ->add('birthday', BirthdayType::class, array(
                'label'  => 'Date de naissance',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'horizontal_input_wrapper_class' => 'col-lg-4',
                'datepicker'   => true,
            ))
            ->add('birthplace', TextType::class, array(
                'label' => 'Lieu de naissance',
            ))
            ->add('user', UserAdminType::class, array(
                'label' => ' ',
            ))
            ->add('phone', TextType::class, array(
                'label' => 'Téléphone',
            ))
            ->add('address', AddressType::class, array(
                'label' => 'Adresse :'
            ))
            ->add('Enregistrer', SubmitType::class)
        ;

    }

  public function getName()
  {
    return 'pigass_userbundle_Registertype';
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pigass\UserBundle\Entity\Person',
        'cascade_validation' => true,
    ));
  }
}
