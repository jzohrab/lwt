<?php

namespace App\Form;

use App\Entity\Text;
use App\Entity\Language;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType as SymfTextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('language', EntityType::class, [ 'class' => Language::class, 'choice_label' => 'lgName' ])
            ->add('Title', SymfTextType::class, [ 'attr' => [ 'class' => 'form-text' ] ])
            ->add('Text', TextareaType::class, [ 'attr' => [ 'class' => 'form-largetextarea' ] ])
            ->add('AudioURI', SymfTextType::class, [ 'attr' => [ 'class' => 'form-text' ] ])
            ->add('SourceURI', SymfTextType::class, [ 'attr' => [ 'class' => 'form-text' ] ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Text::class,
        ]);
    }
}
