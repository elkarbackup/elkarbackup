<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name'        , 'text', array('label' => 'Name'))
                ->add('description' , 'textarea')
                ->add('url'         , 'text')
                ->add('preScript'   , 'text')
                ->add('postScript'  , 'text');
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\Tknika\TknikaBackupsBundle\Entity\Job',
        );
    }

    public function getName()
    {
        return 'Job';
    }
}