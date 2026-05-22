<?php

namespace App\Form;

use App\Entity\Category;
use App\Service\CategoryCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = array_combine(CategoryCatalog::allowedNames(), CategoryCatalog::allowedNames());

        $builder->add('name', ChoiceType::class, [
            'label' => 'Category',
            'choices' => $choices,
            'placeholder' => 'Select a category',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
