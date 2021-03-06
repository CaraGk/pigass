<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * SimulationType
 */
class SimulationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('department', 'entity', array(
                    'class'    => 'App:Department',
                    'query_builder' => function (\App\Entity\DepartmentRepository $er) { return $er->getAvailableQuery(); },
                    'required' => false,
                    'attr'     => array('class' => 'inline'),
                ))
                ->add('is_excess', null, array(
                    'required' => false,
                    'label'    => 'Surnombre',
                    'attr'     => array('class' => 'inline'),
                ))
                ->add('active', null, array(
                    'required' => false,
                    'label'    => 'Actif',
                    'attr'     => array('class' => 'inline'),
                ))
                ->add('Valider', 'submit', array(
                    'attr' => array('class' => 'inline'),
                ))
        ;
    }

    public function getName()
    {
        return 'app_type_simulation';
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Simulation',
        ));
    }
}
