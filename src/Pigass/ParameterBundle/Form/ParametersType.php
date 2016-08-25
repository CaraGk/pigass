<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\ParameterBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * ParametersType
 */
class ParametersType extends AbstractType
{
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->getType() == "string") {
                $builder->add($parameter->getName(), 'text', array(
                    'required' => false,
                    'label'    => $parameter->getLabel(),
                    'data'     => $parameter->getValue(),
                ));
            } elseif ($parameter->getType() == "boolean") {
                $builder->add($parameter->getName(), 'checkbox', array(
                    'required' => false,
                    'value'    => false,
                    'label'    => $parameter->getLabel(),
                    'data'     => (bool) $parameter->getValue(),
                ));
            } elseif ($parameter->getType() == "choice") {
                $builder->add($parameter->getName(), 'choice', array(
                    'required'    => false,
                    'choices'     => $parameter->getMore(),
                    'multiple'    => false,
                    'expanded'    => false,
                    'label'       => $parameter->getLabel(),
                    'data'        => $parameter->getValue(),
                ));
            }
        }
        $builder->add('Enregistrer', 'submit');
    }

  public function getName()
  {
    return 'pigass_parameterbundle_parameterstype';
  }
}
