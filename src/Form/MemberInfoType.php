<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\OptionsResolver\OptionsResolver,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\TimeType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\IntegerType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * MemberInfoType
 */
class MemberInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->questions = $options['questions'];

        foreach($this->questions as $question) {
            if($question->getType() == 1) {
                $builder->add('value', ChoiceType::class, array(
                    'choices'  => $this->getQuestionSubjectiveChoiceOptions($question->getMore()),
                    'required' => $question->isRequired()?true:false,
                    'multiple' => false,
                    'expanded' => true,
                    'label'    => $question->getName(),
                 ));
            } elseif($question->getType() == 2) {
                $builder->add('value', TextareaType::class, array(
                    'required' => $question->isRequired()?true:false,
                    'trim'       => true,
                    'label'      => $question->getName(),
                ));
            } elseif ($question->getType() == 3) {
                $builder->add('value', ChoiceType::class, array(
                    'choices' => $question->getMore(),
                    'choice_label' => function ($value, $key, $index) { return $value; },
                    'required' => $question->isRequired()?true:false,
                    'multiple' => true,
                    'expanded' => true,
                    'label'    => $question->getName(),
                ));
            } elseif ($question->getType() == 4) {
                $builder->add('value', IntegerType::class, array(
                    'scale'     => (int) $question->getMore(),
                    'required' => $question->isRequired()?true:false,
                    'label'     => $question->getName(),
                ));
            } elseif ($question->getType() == 5) {
                $builder->add('value', ChoiceType::class, array(
                    'choices' => $question->getMore(),
                    'choice_label' => function ($value, $key, $index) { return $value; },
                    'required' => $question->isRequired()?true:false,
                    'multiple' => false,
                    'expanded' => true,
                    'label'    => $question->getName(),
                ));
            } elseif ($question->getType() == 6) {
                $builder->add('value', TimeType::class, array(
                    'input'        => 'string',
                    'widget'       => 'single_text',
                    'with_seconds' => false,
                    'timepicker'   => true,
                    'required' => $question->isRequired()?true:false,
                    'label'        => $question->getName(),
                ));
            } elseif ($question->getType() == 7) {
                $builder->add('value', DateType::class, array(
                    'input'        => 'string',
                    'widget'       => 'single_text',
                    'html5'        => true,
                    'required' => $question->isRequired()?true:false,
                    'label'        => $question->getName(),
                ));
            } elseif ($question->getType() == 8) {
                $builder->add('value', ChoiceType::class, array(
                    'choices' => $question->getMore(),
                    'choice_label' => function ($value, $key, $index) { return $value; },
                    'required' => $question->isRequired()?true:false,
                    'multiple' => false,
                    'expanded' => false,
                    'label'    => $question->getName(),
                ));
            } elseif ($question->getType() == 9) {
                $builder->add('value', TextType::class, array(
                    'required' => $question->isRequired()?true:false,
                    'label'        => $question->getName(),
                ));
            }
            $builder->add('question', HiddenType::class, array(
                'data' => $question->getId(),
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'questions' => null,
        ));
    }

    public function getQuestionSubjectiveChoiceOptions($options)
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

}
