<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * SimulPeriodType
 */
class SimulPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('begin', DateType::class, [
                'label'  => 'Début des simulations',
                'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'input'  => 'string',
                'widget' => 'single_text',
                'html5'  => true,
                'mapped' => false,
                'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
            ])
            ->add('end', DateType::class, [
                'label'  => 'Fin des simulations',
                'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'input'  => 'string',
                'widget' => 'single_text',
                'html5'  => true,
                'mapped' => false,
                'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
            ])
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\SimulPeriod',
        ));
    }
}
