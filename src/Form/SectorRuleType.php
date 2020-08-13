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

/**
 * SectorRuleType
 */
class SectorRuleType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
      $builder->add('grade')
              ->add('relation', 'choice', array(
                  'choices' => array('NOT' => 'ne doit pas faire de stage de', 'FULL' => 'doit compléter les stage de'),
                  'required' => true,
                  'multiple' => false,
                  'expanded' => false,
              ))
              ->add('sector')
              ->add('Enregistrer', 'submit')
      ;
  }

  public function getName()
  {
    return 'app_type_sectorrule';
  }

  public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'App\Entity\SectorRule',
    ));
  }
}
