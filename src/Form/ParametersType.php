<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\OptionsResolver\OptionsResolver,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * ParametersType
 */
class ParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->parameters = $options['parameters'];

        foreach ($this->parameters as $parameter) {
            if ($parameter->getType() == "string") {
                $builder->add($parameter->getName(), TextType::class, array(
                    'required' => false,
                    'label'    => $parameter->getLabel(),
                    'data'     => $parameter->getValue(),
                ));
            } elseif ($parameter->getType() == "boolean") {
                $builder->add($parameter->getName(), CheckboxType::class, array(
                    'required' => false,
                    'value'    => false,
                    'label'    => $parameter->getLabel(),
                    'data'     => (bool) $parameter->getValue(),
                ));
            } elseif ($parameter->getType() == "choice") {
                $builder->add($parameter->getName(), ChoiceType::class, array(
                    'required'    => false,
                    'choices'     => $parameter->getMore(),
                    'multiple'    => false,
                    'expanded'    => false,
                    'label'       => $parameter->getLabel(),
                    'data'        => $parameter->getValue(),
                ));
            }
        }
        $builder->add('Enregistrer', SubmitType::class);
    }

    public function getName()
    {
        return 'pigass_parameterbundle_parameterstype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'parameters' => null,
        ));
    }
}
