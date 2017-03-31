<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\FileType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;

/**
 * ImportType
 */
class ImportType extends AbstractType
{
    private $choices;

    public function __construct()
    {
        $this->choices = array(
            '1re colonne (A)' => '0',
            '2e colonne (B)'  => '1',
            '3e colonne (C)'  => '2',
            '4e colonne (D)'  => '3',
            '5e colonne (E)'  => '4',
            '6e colonne (F)'  => '5',
            '7e colonne (G)'  => '6',
            '8e colonne (H)'  => '7',
            '9e colonne (I)'  => '8',
            '10e colonne (J)' => '9',
            '11e colonne (K)' => '10',
            '12e colonne (L)' => '11',
            '13e colonne (M)' => '12',
            '14e colonne (N)' => '13',
            '15e colonne (O)' => '14',
            '16e colonne (P)' => '15',
            '17e colonne (Q)' => '16',
            '18e colonne (R)' => '17',
            '19e colonne (S)' => '18',
            '20e colonne (T)' => '19',
            '21e colonne (U)' => '20',
            '22e colonne (V)' => '21',
            '23e colonne (W)' => '22',
            '24e colonne (X)' => '23',
            '25e colonne (Y)' => '24',
            '26e colonne (Z)' => '25',
            '27e colonne (AA)' => '26',
            '28e colonne (AB)' => '27',
            '29e colonne (AC)' => '28',
            '30e colonne (AD)' => '29',
            '31e colonne (AE)' => '30',
            '32e colonne (AF)' => '31',
            '33e colonne (AG)' => '32',
            '34e colonne (AH)' => '33',
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, array(
                'label'    => 'Fichier',
                'required' => true,
            ))
            ->add('first_row', CheckboxType::class, array(
                'label'    => 'Le fichier contient une ligne de titre de colonne',
                'required' => false,
                'data'     => true,
            ))
            ->add('rewrite', CheckboxType::class, [
                'label'    => 'Remplacer les données de contact existantes',
                'required' => false,
                'data'     => false,
            ])
        ;

        if (null != $options['fields']) {
            foreach ($options['fields'] as $field) {
                if ($field['required']) {
                    $builder->add($field['name'], ChoiceType::class, [
                        'label'    => $field['label'],
                        'required' => $field['required'],
                        'expanded' => false,
                        'multiple' => false,
                        'choices'  => $this->choices,
                    ]);
                } else {
                    $builder->add($field['name'], ChoiceType::class, [
                        'label'       => $field['label'],
                        'required'    => $field['required'],
                        'expanded'    => false,
                        'multiple'    => false,
                        'choices'     => $this->choices,
                        'placeholder' => 'aucune',
                        'empty_data'  => null,
                    ]);
                }
            }
        }

        if (null != $options['gateways']) {
            foreach ($options['gateways'] as $gateway) {
                $builder->add('gateway_' . $gateway->getId(), TextType::class, [
                    'label'    => $gateway->getLabel(),
                    'required' => true,
                ]);
            }
        }

        $builder->add('Save', SubmitType::class, array(
            'label'     => 'Importer',
            'attr'      => array(
                'class' => 'btn btn-primary pull-right',
            ),
        ));
    }

    public function getName()
    {
        return 'pigass_userbundle_importtype';
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'fields'   => null,
            'gateways' => null,
        ));
    }
}
