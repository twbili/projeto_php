<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class NewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category')
            ->add('status',ChoiceType::class,[
                'choices'=>[
                    'Ativo'=>1,
                    'Inativo'=>0,
                ]
            ])
            ->add('highlighted',ChoiceType::class,[
                'choices'=>[
                    'Ativo'=>1,
                    'Inativo'=>0,
                ]
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('title')
            ->add('shortDescription')
            ->add('youtueVideoCode')
            ->add('fullDesxription')
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => false,
                'asset_helper' => false,
                'download_uri' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}
