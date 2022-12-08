<?php

namespace App\Form;

use App\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('LgName')
            ->add('LgDict1URI')
            ->add('LgDict2URI')
            ->add('LgGoogleTranslateURI')
            ->add('LgExportTemplate')
            ->add('LgTextSize')
            ->add('LgCharacterSubstitutions')
            ->add('LgRegexpSplitSentences')
            ->add('LgExceptionsSplitSentences')
            ->add('LgRegexpWordCharacters')
            ->add('LgRemoveSpaces')
            ->add('LgSplitEachChar')
            ->add('LgRightToLeft')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Language::class,
        ]);
    }
}
