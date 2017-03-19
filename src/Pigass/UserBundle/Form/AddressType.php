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
    Symfony\Component\Form\Extension\Core\Type\IntegerType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\CountryType;

/**
 * AddressType
 */
class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('number', TextType::class, array(
                'label' => 'Nº dans la voie',
            ))
            ->add('type', ChoiceType::class, array(
                'label' => 'Type de voie',
                'choices' => array(
                    'allée'     => 'allée',
                    'avenue'    => 'avenue',
                    'boulevard' => 'boulevard',
                    'bourg'     => 'bourg',
                    'chemin'    => 'chemin',
                    'cité '     => 'cité ',
                    'clos'      => 'clos',
                    'cote'      => 'cote',
                    'cours'     => 'cours',
                    'domaine'   => 'domaine',
                    'espace'    => 'espace',
                    'esplanade' => 'esplanade',
                    'faubourg'  => 'faubourg',
                    'grande rue' => 'grande rue',
                    'impasse'   => 'impasse',
                    'lieu dit'  => 'lieu dit',
                    'lot'       => 'lot',
                    'montée'    => 'montée',
                    'parvis'    => 'parvis',
                    'passage'   => 'passage',
                    'pavillon'  => 'pavillon',
                    'place'     => 'place',
                    'plan'      => 'plan',
                    'quai'      => 'quai',
                    'résidence' => 'résidence',
                    'route'     => 'route',
                    'rue'       => 'rue',
                    'ruelle'    => 'ruelle',
                    'square'    => 'square',
                    'ter'       => 'ter',
                    'traverse'  => 'traverse',
                ),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'horizontal_input_wrapper_class' => 'col-lg-4',
            ))
            ->add('street', textType::class, array(
                'label'    => 'Nom de voie',
                'required' => false,
            ))
            ->add('complement', TextType::class, array(
                'label'    => 'Complément d\'adresse',
                'required' => false,
            ))
            ->add('code', IntegerType::class, array(
                'label' => 'Code postal',
                'horizontal_input_wrapper_class' => 'col-lg-4',
            ))
            ->add('city', TextType::class, array(
                'label' => 'Ville',
            ))
            ->add('country', CountryType::class, array(
                'label' => 'Pays',
                'preferred_choices' => array('FR')
            ))
        ;
    }

    public function getName()
    {
        return 'pigass_userbundle_addresstype';
    }

}
