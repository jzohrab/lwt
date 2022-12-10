<?php

namespace App\Form;

use App\Entity\Text;
use App\Entity\Language;
use App\Form\DataTransformer\TagsToCollectionTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType as SymfTextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\Common\Persistence\ObjectManager;


class TextType extends AbstractType
{

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $audioHelp = 'YouTube, Dailymotion, Vimeo, or file in /public/media';
        $builder
            ->add('language',
                  EntityType::class,
                  [ 'class' => Language::class, 'choice_label' => 'lgName' ]
            )
            ->add('Title',
                  SymfTextType::class,
                  [ 'attr' => [ 'class' => 'form-text' ] ])
            ->add('Text',
                  TextareaType::class,
                  [ 'label' => 'Text', 'help' => 'max 65,000 bytes', 'attr' => [ 'class' => 'form-largetextarea' ] ])
            ->add('AudioURI',
                  SymfTextType::class,
                  [ 'label' => 'Media URI', 'help' => $audioHelp, 'attr' => [ 'class' => 'form-text' ], 'required' => false ])
            ->add('SourceURI',
                  SymfTextType::class,
                  [ 'label' => 'Source URI', 'attr' => [ 'class' => 'form-text' ], 'required' => false ])
            ->add('textTags',
                  CollectionType::class,
                  [
                    'entry_type' => TextTagType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'required' => false
                  ])
            ;

        // Data Transformer
        $builder
            ->get('textTags')
            ->addModelTransformer(new TagsToCollectionTransformer($this->manager));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Text::class,
        ]);
    }
}
