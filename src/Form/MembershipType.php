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
    Symfony\Bridge\Doctrine\Form\Type\EntityType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\TimeType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType,
    Symfony\Component\Form\Extension\Core\Type\IntegerType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\PersonUserType;
use Doctrine\ORM\EntityRepository;
use App\Repository\FeeRepository;

/**
 * MembershipType
 */
class MembershipType extends AbstractType
{
    private $structure, $fees, $withPerson, $questions, $admin, $withQuestions;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->structure = $options['structure'];
        $this->withPerson = $options['withPerson'];
        $this->withPayment = $options['withPayment'];
        $this->withPrivacy = $options['withPrivacy'];
        $this->withQuestions = $options['withQuestions'];
        $this->questions = $options['questions'];
        $this->admin = $options['admin'];

        if ($this->withPerson) {
            $builder
                ->add('person', PersonUserType::class, [
                    'label' => 'Identité'
                ])
            ;
        }

        if ($this->withPrivacy) {
            $builder
                ->add('privacy', CheckboxType::class, [
                    'label' => 'J\'accepte que mes informations personnelles soient traitées dans le cadre défini par la politique de confidentialité du site.',
                    'required' => true,
                ])
            ;
        }

        if ($this->withQuestions) {
            foreach($this->questions as $question) {
                if($question->getType() == 1) {
                    $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                        'choices'  => $this->getQuestionSubjectiveChoiceOptions($question->getMore()),
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'multiple' => false,
                        'expanded' => true,
                        'label'    => $question->getName(),
                        'mapped'        => false,
                     ));
                } elseif($question->getType() == 2) {
                    $builder->add('question_' . $question->getId(), TextareaType::class, array(
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'trim'       => true,
                        'label'      => $question->getName(),
                        'mapped'        => false,
                    ));
                } elseif ($question->getType() == 3) {
                    $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                        'choices' => $question->getMore(),
                        'choice_label' => function ($value, $key, $index) { return $value; },
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'multiple' => true,
                        'expanded' => true,
                        'label'    => $question->getName(),
                        'mapped'        => false,
                        'attr'     => ['class' => 'form-check-inline'],
                    ));
                } elseif ($question->getType() == 4) {
                    $builder->add('question_' . $question->getId(), IntegerType::class, array(
                        'scale'     => (int) $question->getMore(),
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'label'     => $question->getName(),
                        'mapped'        => false,
                    ));
                } elseif ($question->getType() == 5) {
                    $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                        'choices' => $question->getMore(),
                        'choice_label' => function ($value, $key, $index) { return $value; },
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'multiple' => false,
                        'expanded' => (count($question->getMore()) > 4) ? false : true,
                        'label'    => $question->getName(),
                        'mapped'        => false,
                        'attr'     => ['class' => 'form-check-inline'],
                    ));
                } elseif ($question->getType() == 6) {
                    $builder->add('question_' . $question->getId(), TimeType::class, array(
                        'input'        => 'string',
                        'widget'       => 'single_text',
                        'with_seconds' => false,
                        'html5'        => true,
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'label'        => $question->getName(),
                        'mapped'        => false,
                    ));
                } elseif ($question->getType() == 7) {
                    $builder->add('question_' . $question->getId(), DateType::class, array(
                        'input'    => 'string',
                        'widget'   => 'single_text',
                        'html5'    => true,
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'label'    => $question->getName(),
                        'mapped'   => false,
                        'help'     => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                        'attr'     => [ 'placeholder' => 'AAAA-MM-JJ' ],
                    ));
                } elseif ($question->getType() == 8) {
                    $builder->add('question_' . $question->getId(), ChoiceType::class, array(
                        'choices' => $question->getMore(),
                        'choice_label' => function ($value, $key, $index) { return $value; },
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'multiple' => false,
                        'expanded' => false,
                        'label'    => $question->getName(),
                        'mapped'   => false,
                        'attr'     => ['class' => 'form-check-inline'],
                    ));
                } elseif ($question->getType() == 9) {
                    $builder->add('question_' . $question->getId(), TextType::class, array(
                        'required' => $this->admin?false:($question->isRequired()?true:false),
                        'label'        => $question->getName(),
                        'mapped'        => false,
                    ));
                }
            }
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
                        ->setParameter('structure_id', $this->structure->getId())
                        ->andWhere('u.active = true')
                    ;
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
                    'help'   => 'En l\'absence de calendrier, indiquez la date au format "AAAA-MM-JJ"',
                ])
                ->add('ref', TextType::class, [
                    'label' => 'Référence du paiement',
                ])
            ;
        }

        $builder
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr'  => [
                    'class' => 'btn btn-primary',
                ]
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
            'withPrivacy' => null,
            'withQuestions' => null,
            'questions'   => null,
            'admin'       => false,
        ]);
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
