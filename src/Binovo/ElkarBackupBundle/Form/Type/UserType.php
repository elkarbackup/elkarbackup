<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
/*        $builder->add('username'    , 'text'    , array('label' => $t->trans('Name'     , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('newPassword' , 'password', array('label' => $t->trans('Password' , array(), 'BinovoElkarBackup'),
                                                        'required' => false,
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('email'       , 'email'   , array('label' => $t->trans('Email'    , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('isActive'    , 'checkbox', array('label' => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                        'required' => false));
*/

$builder->add('username'    , 'text'    , array('label' => $t->trans('Name'     , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
	->add('email'       , 'email'   , array('label' => $t->trans('Email'    , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
        ->add('isActive'    , 'checkbox', array('label' => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                        'required' => false))
        ->add('roles'      , 'collection', array('type' => 'choice',
                                             //'label' => 'Profile type',
                                             //'attr' => array('class' => 'form-control'),
                                             'options' => array(
                                                'expanded' => false,
                                                'choices' => array(
                                                        'ROLE_ADMIN' => 'Admin',
                                                        'ROLE_USER' => 'User',
                                                ),
                                             ),
                                          ))


	->add('newPassword', 'repeated', array('type' => 'password',
			     'options' => array('attr' => array('class' => 'password-field form-control')),
			     'required' => true,
			     'first_options'  => array('label' => $t->trans('New password' , array(), 'BinovoElkarBackup')),
			     'second_options' => array('label' => $t->trans('Confirm new password', array(), 'BinovoElkarBackup')),
    			     'invalid_message' => 'The password fields must match.',

				));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\User',
          'translator' => null,
        ));
    }

    public function getName()
    {
        return 'User';
    }
}
