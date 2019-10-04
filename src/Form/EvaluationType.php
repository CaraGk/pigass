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
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\IntegerType,
    Symfony\Component\Form\Extension\Core\Type\TimeType,
    Symfony\Component\Form\Extension\Core\Type\RangeType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Evaluationtype
 */
class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['eval_forms'] as $eval_form)
        {
            foreach ($eval_form->getCriterias() as $criteria) {
                    $label = $criteria->getName();
                if ($criteria->isPrivate() == true) {
                    $tooltip = array(
                        'title' => 'Consultation restreinte aux étudiants',
                        'text'  => 'accès restreint',
                        'icon'  => 'eye-close',
                    );
                } else {
                    $tooltip = array(
                        'title' => 'Consultation ouverte aux enseignants après pondération sur plusieurs évaluations pour garantir l\'anonymat',
                        'text'  => 'visible',
                        'icon'  => 'eye-open',
                    );
                }

                if ($criteria->getType() == 1) {
                    $builder->add('criteria_' . $criteria->getId(), ChoiceType::class, array(
                        'choices'    => $this->getCriteriaSubjectiveChoiceOptions($criteria->getMore()),
                        'required'   => $criteria->isRequired(),
                        'multiple'   => false,
                        'expanded'   => true,
                        'label'      => $label,
                    ));
                } elseif ($criteria->getType() == 2) {
                    $builder->add('criteria_' . $criteria->getId(), TextareaType::class, array(
                        'required'   => $criteria->isRequired(),
                        'trim'       => true,
                        'label'      => $label,
                    ));
                } elseif ($criteria->getType() == 3) {
                    $builder->add('criteria_' . $criteria->getId(), ChoiceType::class, array(
                        'choices'    => $this->getCriteriaChoiceOptions($criteria->getMore()),
                        'required'   => $criteria->isRequired(),
                        'multiple'   => true,
                        'expanded'   => true,
                        'label'      => $label,
                    ));
                } elseif ($criteria->getType() == 4) {
                    $builder->add('criteria_' . $criteria->getId(), IntegerType::class, array(
                        'required'   => $criteria->isRequired(),
                        'label'      => $label,
                    ));
                } elseif ($criteria->getType() == 5) {
                    $builder->add('criteria_' . $criteria->getId(), ChoiceType::class, array(
                        'choices'    => $this->getCriteriaChoiceOptions($criteria->getMore(), array(0)),
                        'required'   => $criteria->isRequired(),
                        'multiple'   => false,
                        'expanded'   => true,
                        'label'      => $label,
                    ));
                } elseif ($criteria->getType() == 6) {
                    $builder->add('criteria_' . $criteria->getId(), TimeType::class, array(
                        'input'        => 'string',
//                        'input_format' => 'H:i',
                        'widget'       => 'text',
//                        'with_seconds' => false,
                        'required'     => $criteria->isRequired(),
                        'label'      => $label,
                        'model_timezone' => 'Europe/Paris',
                        'view_timezone' => 'Europe/Paris',
                    ));
                } elseif ($criteria->getType() == 7) {
                    $options = explode('|', $criteria->getMore());
                    $legend = '<span>' . $options[1] . '</span><span>' . $options[2] . '</span>';
                    $builder->add('criteria_' . $criteria->getId(), RangeType::class, array(
                        'required'   => $criteria->isRequired(),
                        'label'      => $label,
                        'attr'       => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'data'       => $options[0],
                        'help'       => $legend,
                        'help_html'  => true,
                        'help_attr'  => [
                            'class' => 'd-flex justify-content-between align-items-center',
                        ],
                    ));
                }
            }
        }
        $builder->add('Enregister', SubmitType::class);
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'eval_forms' => null,
        ]);
    }

  public function getCriteriaSubjectiveChoiceOptions($options)
  {
    $opt = explode("|", $options);
    $label = explode(",", $opt[1]);
    $choices = array();

    for ($i = 0 ; $i < (int) $opt[0] ; $i ++) {
      $j = $i + 1;
      $choices[$j] = (string) $j;
      if ($label[$i] != null)
        $choices[$j] .= ' (' . $label[$i] . ')';
    }

    return $choices;
  }

    public function getCriteriaChoiceOptions($options, $except = array())
    {
        $opt = explode("|", $options);
        $choice = array();
        foreach($opt as $key => $value) {
            if (!in_array($key, $except)) {
                $choice[$value] = $value;
            }
        }
        return $choice;
  }
}
