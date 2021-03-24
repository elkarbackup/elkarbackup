<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('username'    , TextType::class    , array('label' => $t->trans('Name'     , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('email'       , EmailType::class   , array('label' => $t->trans('Email'    , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('isActive'    , CheckboxType::class, array('label' => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                        'required' => false))
                ->add('roles'       , ChoiceType::class, array('choices' => array(
                                                                    'Admin' => 'ROLE_ADMIN',
                                                                    'User'  => 'ROLE_USER'
                                                                ),
                                                                'expanded' => false
                ))
                ->add('newPassword', RepeatedType::class, array('type' => PasswordType::class,
                    'options' => array('attr' => array('class' => 'password-field form-control')),
                    'required' => false,
                    'first_options'  => array('label' => $t->trans('New password' , array(), 'BinovoElkarBackup')),
                    'second_options' => array('label' => $t->trans('Confirm new password', array(), 'BinovoElkarBackup')),
                    'invalid_message' => 'The password fields must match.'
                ));
         $builder->get('roles')
                ->addModelTransformer(new CallbackTransformer(
                    function ($rolesArray) {
                        // transform the array to a string
                        return count($rolesArray)? $rolesArray[0]: null;
                    },
                    function ($rolesString) {
                        // transform the string back to an array
                        return [$rolesString];
                    }
                    ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'App\Entity\User',
          'translator' => null,
        ));
    }
}
