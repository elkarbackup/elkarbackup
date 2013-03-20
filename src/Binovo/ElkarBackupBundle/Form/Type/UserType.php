<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('username'    , 'text'    , array('label' => $t->trans('Name'     , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'span12')))
                ->add('newPassword' , 'password', array('label' => $t->trans('Password' , array(), 'BinovoElkarBackup'),
                                                        'required' => false,
                                                        'attr'  => array('class'    => 'span12')))
                ->add('email'       , 'email'   , array('label' => $t->trans('Email'    , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'span12')))
                ->add('isActive'    , 'checkbox', array('label' => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                        'required' => false));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\ElkarBackupBundle\Entity\User',
            'translator' => null,
        );
    }

    public function getName()
    {
        return 'User';
    }
}