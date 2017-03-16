<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\FileType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Pigass\UserBundle\Form\AddressType;

/**
 * StructureType
 */
class StructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('fullname', TextType::class, array(
                'label' => 'Nom complet',
                'required' => false,
            ))
            ->add('area', TextType::class, array(
                'label' => 'Zone géographique',
                'required' => false,
            ))
            ->add('email')
            ->add('address', AddressType::class, array(
                'label' => 'Adresse postale',
                'label_render' => false,
            ))
            ->add('logo', FileType::class, array(
                'label'    => 'Logo (image)',
                'required' => false,
            ))
            ->add('activated', CheckboxType::class, array(
                'label'      => 'Activé ?',
                'required'   => false,
            ))
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'pigass_corebundle_structuretype';
    }
}
