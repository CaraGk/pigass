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
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * GatewayType
 */
class GatewayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('factoryName', ChoiceType::class, array(
                'label'   => 'Type',
                'choices' => array(
                    'Paiement hors-ligne' => 'offline',
                    'Paypal'              => 'paypal_express_checkout',
                ),
                'multiple' => false,
                'expanded' => false,
            ))
            ->add('label', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('config', GatewayConfigType::class, array(
                'label'   => 'Configuration',
                'required' => false,
            ))
            ->add('active', CheckboxType::class, [
                'label' => 'Active ?',
                'required' => false,
            ])
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'pigass_userbundle_gatewaytype';
    }
}
