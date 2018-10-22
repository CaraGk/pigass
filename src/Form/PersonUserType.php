<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\BirthdayType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\AddressType,
    App\Form\UserType;

/**
 * PersonUserType
 */
class PersonUserType extends AbstractType
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
                'html5'  => true,
                'help_block'   => 'En cas de souci lié à l\'utilisation de Safari ou d\'un navigateur ancien, indiquez la date au format "AAAA-MM-JJ"',
            ))
            ->add('birthplace', TextType::class, array(
                'label' => 'Lieu de naissance',
            ))
            ->add('user', UserType::class, array(
                'label' => 'Identifiant',
            ))
            ->add('phone', TextType::class, array(
                'label' => 'Téléphone',
            ))
            ->add('address', AddressType::class, array(
                'label' => 'Adresse professionnelle :'
            ))
        ;
  }

  public function getName()
  {
    return 'pigass_userbundle_persontype';
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'App\Entity\Person',
        'cascade_validation' => true,
    ));
  }
}
