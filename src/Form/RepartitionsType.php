<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\RepartitionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType,
    Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * Repartitions Type
 */
class RepartitionsType extends AbstractType
{
    private $type = 'period';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->type=$options['type'];

        foreach($options['repartitions'] as $repartition) {
            if ($this->type == 'period')
                $label = $repartition->getDepartment()->getName();
            elseif ($this->type == 'department')
                $label = $repartition->getPeriod();
            $id = $repartition->getId();

            $builder
                ->add('number_' . $id, IntegerType::class, array(
                    'label'    => $label,
                    'required' => true,
                    'data'     => $repartition->getNumber(),
                ))
                ->add('cluster_' . $id, TextType::class, array(
                    'label'    => 'Stage couplé',
                    'required' => false,
                    'data'     => $repartition->getCluster(),
                ))
            ;
        }

        $builder->add('Enregistrer', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'type' => 'period',
            'repartitions' => null,
        ));
    }
}
