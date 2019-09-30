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

/**
 * EvalSectorType
 */
class EvalSectorType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
      $exclude_sectors = $options['exclude_sectors'];
      $eval_form = $options['eval_form'];

    $builder
        ->add('sector', 'entity', array(
            'class'         => 'App:Sector',
            'query_builder' => function (\App\Entity\SectorRepository $er) use ($exclude_sectors) { return $er->listOtherSectors($exclude_sectors); },
            'label'         => 'Lier une catégorie de stage : ',
        ))
        ->add('form', 'hidden', array(
            'data' => $eval_form->getId(),
        ))
        ->add('Enregistrer', 'submit')
    ;
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'App\Entity\EvalSector',
    ));
  }
}
