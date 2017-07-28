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
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;

/**
 * JoinType
 */
class JoinType extends AbstractType
{
    private $structure, $fees;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];
        $this->fees = $options['fees'];

        $builder
            ->add('amount', ChoiceType::class, array(
                'label'        => 'Montant',
                'choices'      => $this->fees,
                'choice_label' => function ($fee) {
                    return $fee;
                },
                'expanded'     => true,
                'multiple'     => false,
              ))
            ->add('method', EntityType::class, array(
                'class'         => 'PigassUserBundle:Gateway',
                'choice_label'  => 'label',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.structure = :structure_id')
                        ->setParameter('structure_id', $this->structure->getId());
                },
                'required'      => true,
                'multiple'      => false,
                'expanded'      => true,
                'label'         => 'Moyen de paiement'
            ))
            ->add('Save', SubmitType::class, array(
                'label' => 'Payer',
                'attr'  => array(
                    'class' => 'btn btn-primary pull-right',
                ),
            ))
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
            'structure'  => null,
            'fees'       => null,
        ));
    }
}
