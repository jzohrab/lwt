<?php

namespace App\Form;

use App\Entity\Term;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TermType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Text',
                  TextType::class,
                  [ 'label' => 'Text',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('Romanization',
                  TextType::class,
                  [ 'label' => 'Romanization',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('Translation',
                  TextType::class,
                  [ 'label' => 'Translation',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('Status',
                  ChoiceType::class,
                  [ 'choices'  => [
                      '1' => 1,
                      '2' => 2,
                      '3' => 3,
                      '4' => 4,
                      '5' => 5,
                      'Wkn' => 99,
                      'Ign' => 98
                  ],
                    'label' => 'Status',
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true
                  ]
            )
            ->add('Sentence',
                  TextType::class,
                  [ 'label' => 'Sentence',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            // ->add('WordCount')
            // ->add('language')
            // ->add('termTags')
            ->add('parent',
                  TextType::class,
                  [ 'label' => 'Language',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Term::class,
        ]);
    }
}
