<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * EvalCriteriaType
 */
class EvalCriteriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rank', IntegerType::class, [
                'label' => 'Rang',
            ])
            ->add('name', TextType::class, [
                'label' => 'Intitulé de la question',
            ])
            ->add('type', ChoiceType::class, [
              'choices' => array(
                'Choix unique pondéré'        => '1',
                'Choix unique non pondéré'    => '5',
                'Échelle visuelle analogique' => '7',
                'Choix multiple'              => '3',
                'Valeur numérique'            => '4',
                'Horaire'                     => '6',
                'Texte long'                  => '2',
              ),
              'required' => true,
              'multiple' => false,
              'expanded' => false,
            ])
            ->add('more')
            ->add('required')
            ->add('moderate')
            ->add('private')
    ;
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'App\Entity\EvalCriteria',
    ));
  }
}
