<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobShortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name'        , 'text')
                ->add('description' , 'textarea', array('required' => false))
                ->add('url'         , 'text')
                ->add('isActive'    , 'checkbox', array('required' => false));

    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\Tknika\TknikaBackupsBundle\Entity\Job',
        );
    }

    public function getName()
    {
        return 'JobShort';
    }
}