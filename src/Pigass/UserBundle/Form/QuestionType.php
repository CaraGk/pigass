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
    Symfony\Component\OptionsResolver\OptionsResolver,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\TimeType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\IntegerType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * QuestionType
 */
class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->questions = $options['questions'];

        foreach($this->questions as $question) {
            if($question->getType() == 1) {
                $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                    'choices'  => $this->getQuestionSubjectiveChoiceOptions($question->getMore()),
                    'required' => true,
                    'multiple' => false,
                    'expanded' => true,
                    'label'    => $question->getName(),
                 ));
            } elseif($question->getType() == 2) {
                $builder->add('question_' . $question->getId(), TextareaType::class, array(
                    'required'   => true,
                    'trim'       => true,
                    'max_length' => 250,
                    'label'      => $question->getName(),
                ));
            } elseif ($question->getType() == 3) {
                $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                    'choices' => $question->getMore(),
                    'choice_label' => function ($value, $key, $index) { return $value; },
                    'required' => true,
                    'multiple' => true,
                    'expanded' => true,
                    'label'    => $question->getName(),
                ));
            } elseif ($question->getType() == 4) {
                $builder->add('question_' . $question->getId(), IntegerType::class, array(
                    'precision' => $question->getMore(),
                    'required'  => true,
                    'label'     => $question->getName(),
                ));
            } elseif ($question->getType() == 5) {
                $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                    'choices' => $question->getMore(),
                    'choice_label' => function ($value, $key, $index) { return $value; },
                    'required' => true,
                    'multiple' => false,
                    'expanded' => true,
                    'label'    => $question->getName(),
                ));
            } elseif ($question->getType() == 6) {
                $builder->add('question_' . $question->getId(), TimeType::class, array(
                    'input'        => 'string',
                    'widget'       => 'single_text',
                    'with_seconds' => false,
                    'required'     => true,
                    'label'        => $question->getName(),
                ));
            } elseif ($question->getType() == 7) {
                $builder->add('question_' . $question->getId(), DateType::class, array(
                    'input'        => 'string',
                    'widget'       => 'single_text',
                    'format'       => 'dd/MM/yyyy',
                    'required'     => true,
                    'label'        => $question->getName(),
                ));
            } elseif ($question->getType() == 8) {
                $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                    'choices' => $question->getMore(),
                    'choice_label' => function ($value, $key, $index) { return $value; },
                    'required' => true,
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'label'    => $question->getName(),
                ));
            } elseif ($question->getType() == 9) {
                $builder->add('question_' . $question->getId(), TextType::class, array(
                    'required'     => true,
                    'label'        => $question->getName(),
                ));
            }
        }
        $builder->add('Enregistrer', SubmitType::class);
    }

    public function getName()
    {
        return 'pigass_userbundle_questiontype';
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
