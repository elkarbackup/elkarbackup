<?php

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobShortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name'        , 'text')
                ->add('description' , 'textarea', array('required' => false))
                ->add('path'        , 'text')
                ->add('policy'      , 'entity'  , array('required' => false,
                                                        'class'    => 'BinovoElkarBackupBundle:Policy',
                                                        'property' => 'name'))
                ->add('isActive'    , 'checkbox', array('required' => false));

    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\ElkarBackupBundle\Entity\Job',
        );
    }

    public function getName()
    {
        return 'JobShort';
    }
}