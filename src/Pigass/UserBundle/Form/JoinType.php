<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2015 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface;

/**
 * JoinType
 */
class JoinType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('method', 'choice', array(
                'choices' => array(
                    'offline' => 'Chèque',
                    'paypal'  => 'Paypal',
                ),
                'required' => true,
                'multiple' => false,
                'expanded' => true,
                'label'    => 'Moyen de paiement'
            ))
            ->add('Payer', 'submit')
        ;
    }

    public function getName()
    {
        return 'gesseh_userbundle_jointype';
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pigass\UserBundle\Entity\Membership',
        ));

        $resolver->setAllowedValues(array(
        ));
    }
}
