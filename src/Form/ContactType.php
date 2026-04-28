<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Votre nom',
                'constraints' => [new NotBlank(), new Length(min: 2, max: 100)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre email',
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'constraints' => [new NotBlank(), new Length(min: 3, max: 150)],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'constraints' => [new NotBlank(), new Length(min: 10, max: 5000)],
                'attr' => ['rows' => 6],
            ])
            // Honeypot anti-spam — champ caché qui doit rester vide
            ->add('website', TextType::class, [
                'required' => false,
                'mapped'   => false,
                'label'    => false,
                'attr'     => ['tabindex' => '-1', 'autocomplete' => 'off', 'style' => 'position:absolute;left:-9999px'],
            ])
            ->add('consent', CheckboxType::class, [
                'label' => 'J\'accepte que mes données soient utilisées pour répondre à ma demande (cf. politique de confidentialité).',
                'constraints' => [new IsTrue(message: 'Consentement requis.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
