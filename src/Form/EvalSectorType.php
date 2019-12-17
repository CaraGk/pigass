<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * EvalSectorType
 */
class EvalSectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $exclude_sectors = $options['exclude_sectors'];

        $builder
            ->add('sector', EntityType::class, [
                'class'         => 'App:Sector',
                'query_builder' => function (\App\Repository\SectorRepository $er) use ($exclude_sectors) {
                    return $er->listOtherSectors($exclude_sectors);
                },
                'label'         => 'Catégorie de stage',
            ])
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EvalSector',
            'exclude_sectors' => null,
        ));
    }
}
