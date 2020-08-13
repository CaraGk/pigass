<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\AddressType;

/**
 * GatewayConfigType
 */
class GatewayConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', TextType::class, array(
            'required' => false,
        ))
        ->add('password', TextType::class, array(
            'required' => false,
        ))
        ->add('signature', TextType::class, array(
            'required' => false,
        ))
        ->add('sandbox', CheckboxType::class, array(
            'required' => false,
            'label'    => 'Identifiants de test pour Paypal (à décocher pour permettre les transactions)',
            'value'    => false,
        ))
        ->add('payableTo', TextType::class, array(
            'label' => 'Ordre (chèque)',
            'required' => false,
        ))
        ->add('address', AddressType::class, array(
            'label' => 'Adresse d\'envoi (chèque)',
            'required' => false,
        ))
        ->add('iban', TextType::class, array(
            'required' => false,
        ))
        ->add('external', TextType::class, array(
            'label'    => 'Lien de paiement externe',
            'required' => false,
            'help'     => 'https://lien-vers-le-site-de-paiement',
        ))
        ;
    }

    public function getName()
    {
        return 'pigass_userbundle_gatewayconfigtype';
    }
}
