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
use App\Form\SimulPeriodType;

/**
 * PeriodType
 */
class PeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Étiquette',
                'help'  => 'Un texte court permettant d\'identifier facilement la session de stage',
            ])
            ->add('begin', DateType::class, [
                'label'  => 'Début de la session',
                'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
            ])
            ->add('end', DateType::class, [
                'label'  => 'Fin de la session',
                'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
            ])
        ;

        if ($options['withSimul']) {
            $builder
                ->add('simul_begin', DateType::class, [
                    'label'  => 'Début des simulations',
                    'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                    'widget' => 'single_text',
                    'html5'  => true,
                    'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
                ])
                ->add('simul_end', DateType::class, [
                    'label'  => 'Fin des simulations',
                    'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                    'widget' => 'single_text',
                    'html5'  => true,
                    'attr'   => ['placeholder' => 'AAAA-MM-JJ'],
                ])
            ;
        }
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Period',
            'withSimul'  => false,
        ));
    }
}
