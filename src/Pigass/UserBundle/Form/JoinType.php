<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * JoinType
 */
class JoinType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('method', EntityType::class, array(
                'class'        => 'PigassUserBundle:Gateway',
                'choice_label' => 'description',
                'required'     => true,
                'multiple'     => false,
                'expanded'     => true,
                'label'        => 'Moyen de paiement'
            ))
            ->add('Payer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'pigass_userbundle_jointype';
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pigass\UserBundle\Entity\Membership',
        ));
    }
}
