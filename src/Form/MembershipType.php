<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\PersonUserType;
use Doctrine\ORM\EntityRepository;
use App\Repository\FeeRepository;

/**
 * MembershipType
 */
class MembershipType extends AbstractType
{
    private $structure, $fees, $withPerson;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];
        $this->withPerson = $options['withPerson'];
        $this->withPayment = $options['withPayment'];

        if ($this->withPerson) {
            $builder
                ->add('person', PersonUserType::class, [
                    'label' => 'Identité'
                ]);
        }

        $builder
            ->add('fee', EntityType::class, [
                'class'         => 'App:Fee',
                'choice_label'  => function ($fee) {
                    return $fee;
                },
                'query_builder' => function (FeeRepository $er) {
                    return $er->getForStructureQuery($this->structure);
                },
                'required'      => true,
                'multiple'      => false,
                'expanded'      => true,
                'label'         => 'Montant',
            ])
            ->add('method', EntityType::class, [
                'class'         => 'App:Gateway',
                'choice_label'  => 'label',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.structure = :structure_id')
                        ->setParameter('structure_id', $this->structure->getId());
                },
                'required'      => true,
                'multiple'      => false,
                'expanded'      => true,
                'label'         => 'Moyen de paiement',
            ])
        ;

        if ($this->withPayment) {
            $builder
                ->add('payedOn', DateType::class, [
                    'label'  => 'Date d\'encaissement ou de réception',
                    'widget' => 'single_text',
                    'html5'  => true,
                    'horizontal_input_wrapper_class' => 'col-lg-4',
                ])
                ->add('ref', TextType::class, [
                    'label' => 'Référence du paiement',
                ])
            ;
        }

        $builder
            ->add('Save', SubmitType::class, [
                'label' => 'Continuer',
                'attr'  => [
                    'class' => 'btn btn-primary pull-right',
                ],
            ])
        ;
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'  => 'App\Entity\Membership',
            'structure'   => null,
            'withPerson'  => null,
            'withPayment' => null,
        ]);
    }
}
