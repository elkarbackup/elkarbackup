<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'          , 'text'      , array('label' => $t->trans('Name', array(), 'BinovoTknikaBackups')))
                ->add('description'   , 'textarea'  , array('label' => $t->trans('Description', array(), 'BinovoTknikaBackups'),
                                                            'required' => false))
                ->add('url'           , 'text'      , array('label' => $t->trans('Url', array(), 'BinovoTknikaBackups')))
                ->add('preScript'     , 'hidden'    , array('label' => $t->trans('Pre script', array(), 'BinovoTknikaBackups')))
                ->add('preScriptFile' , 'file'      , array('label'    => $t->trans('Upload pre script', array(), 'BinovoTknikaBackups'),
                                                            'required' => false))
                ->add('postScript'    , 'hidden'    , array('label' => $t->trans('Post script', array(), 'BinovoTknikaBackups')))
                ->add('postScriptFile', 'file'      , array('label'    => $t->trans('Upload post script', array(), 'BinovoTknikaBackups'),
                                                            'required' => false))
                ->add('isActive'      , 'checkbox'  , array('label'    => $t->trans('Is active', array(), 'BinovoTknikaBackups'),
                                                            'required' => false))
                ->add('jobs'          , 'collection', array('type'         => new JobShortType(),
                                                            'allow_delete' => true,
                                                            'label'        => $t->trans('Jobs', array(), 'BinovoTknikaBackups')));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\Tknika\TknikaBackupsBundle\Entity\Client',
            'translator' => null,
        );
    }

    public function getName()
    {
        return 'Client';
    }
}