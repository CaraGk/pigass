<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\MoneyType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;

/**
 * FeeType
 */
class FeeType extends AbstractType
{
    private $structure;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Étiquette',
            ])
            ->add('amount', MoneyType::class, [
                'label'    => 'Montant',
                'currency' => 'EUR',
                'divisor'  => 100,
            ])
            ->add('help', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('default', CheckboxType::class, [
                'label' => 'Montant par défaut',
                'required' => false,
            ])
            ->add('counted', CheckboxType::class, [
                'label'    => 'Comptabilisé comme adhérent',
                'required' => false,
            ])
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'pigass_corebundle_feetype';
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Fee',
            'structure' => null,
        ]);
    }
}
