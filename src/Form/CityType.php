<?php

namespace App\Form;

use App\Entity\City;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom de la ville',
                'attr' => ['class' => 'form form-control', 'placeholder' => "nom de la ville"]
            ])
            ->add('shippingCost', null, [
                'label' => 'Frais de livraison',
                'attr' => ['class' => 'form form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }
}
