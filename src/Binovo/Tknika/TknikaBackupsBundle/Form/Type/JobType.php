<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Form\Type;

// use Symfony\Component\DependencyInjection\ContainerAwareInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobType extends AbstractType // implements ContainerAwareInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'        , 'text',     array('label' => $t->trans('Name', array(), 'BinovoTknikaBackups')))
                ->add('description' , 'textarea', array('label' => $t->trans('Description', array(), 'BinovoTknikaBackups')))
                ->add('url'         , 'text',     array('label' => $t->trans('Url', array(), 'BinovoTknikaBackups')))
                ->add('preScript'   , 'text',     array('label' => $t->trans('Pre script', array(), 'BinovoTknikaBackups')))
                ->add('postScript'  , 'text',     array('label' => $t->trans('Post script', array(), 'BinovoTknikaBackups')));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\Tknika\TknikaBackupsBundle\Entity\Job',
            'translator' => null,
        );
    }

    public function getName()
    {
        return 'Job';
    }

    /* public function setContainer(ContainerInterface $container = null) */
    /* { */
    /*     echo $container; die(); */
    /* } */
}