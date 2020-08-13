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
use Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\HiddenType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * PlacementType
 */
class PlacementType extends AbstractType
{
    private $person;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->person = $options['person'];

        $builder->add('period', EntityType::class, array(
            'class'    => 'App:Period',
            'label'    => 'Période de stage',
            'required' => true,
        ))
        ;
            if ($this->person) {
            $builder
                ->add('person', HiddenType::class, [
                    'data' => $this->person,
                ])
            ;
        } else {
            $builder
                ->add('person', EntityType::class, array(
                    'class'         => 'App:Person',
                    'label'         => 'Étudiant',
                    'query_builder' => function (\App\Repository\PersonRepository $er) {
                        return $er->createQueryBuilder('s')
                          ->addOrderBy('s.surname', 'ASC')
                          ->addOrderBy('s.name', 'ASC');
                    },
                    'attr'          => array('class' => 'ui-widget combobox'),
                    'placeholder'   => 'Choisissez un étudiant...',
                    'required'      => true,
                ))
            ;
        }
        $builder
            ->add('department', EntityType::class, array(
                'class'         => 'App:Department',
                'label'         => 'Terrain de stage',
                'query_builder' => function (\App\Repository\DepartmentRepository $er) {
                    return $er->createQueryBuilder('d')
                          ->join('d.hospital', 'h')
                          ->addOrderBy('h.name', 'ASC')
                          ->addOrderBy('d.name', 'ASC');
                },
                'attr'          => array('class' => 'ui-widget combobox'),
                'placeholder'   => 'Choisissez un terrain de stage...',
                'required'      => true,
            ))
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'person' => null,
        ]);
    }
}
