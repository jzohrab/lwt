<?php

namespace App\Form;

use App\Entity\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('TxLgID')
            ->add('TxTitle')
            ->add('TxText')
            ->add('TxAnnotatedText')
            ->add('TxAudioURI')
            ->add('TxSourceURI')
            ->add('TxPosition')
            ->add('TxAudioPosition')
            ->add('TxArchived')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Text::class,
        ]);
    }
}
