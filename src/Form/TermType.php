<?php

namespace App\Form;

use App\Entity\Term;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TermType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('WoText')
            ->add('WoTextLC')
            ->add('WoStatus')
            ->add('WoTranslation')
            ->add('WoRomanization')
            ->add('WoSentence')
            ->add('WoWordCount')
            ->add('language')
            ->add('termTags')
            ->add('parents')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Term::class,
        ]);
    }
}
