<?php

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobForSortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'hidden');

    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\ElkarBackupBundle\Entity\Job',
        );
    }

    public function getName()
    {
        return 'JobForSort';
    }
}