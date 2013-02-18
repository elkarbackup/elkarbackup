<?php

namespace Binovo\Tknika\BackupsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'          , 'text'      , array('label' => $t->trans('Name', array(), 'BinovoTknikaBackups'),
                                                            'attr'  => array('class'    => 'span12')))
                ->add('description'   , 'textarea'  , array('label' => $t->trans('Description', array(), 'BinovoTknikaBackups'),
                                                            'required' => false,
                                                            'attr'  => array('class'    => 'span12')))
                ->add('url'           , 'text'      , array('label'    => $t->trans('Url', array(), 'BinovoTknikaBackups'),
                                                            'attr'     => array('class' => 'span12'),
                                                            'required' => false))
                ->add('quota'         , 'integer'   , array('label' => $t->trans('Quota', array(), 'BinovoTknikaBackups'),
                                                            'attr'  => array('class'    => 'span12')))
                ->add('preScript'     , 'entity'    , array('label' => $t->trans('Pre script', array(), 'BinovoTknikaBackups'),
                                                            'attr'     => array('class' => 'span12'),
                                                            'required' => false,
                                                            'class'    => 'BinovoTknikaBackupsBundle:Script',
                                                            'query_builder' => function($er) {
                                                                return $er->createQueryBuilder('s')
                                                                    ->where('s.isClientPre = 1');
                                                            },
                                                            'property' => 'name'))
                ->add('postScript'    , 'entity'    , array('label' => $t->trans('Post script', array(), 'BinovoTknikaBackups'),
                                                            'attr'     => array('class' => 'span12'),
                                                            'required' => false,
                                                            'class'    => 'BinovoTknikaBackupsBundle:Script',
                                                            'query_builder' => function($er) {
                                                                return $er->createQueryBuilder('s')
                                                                    ->where('s.isClientPost = 1');
                                                            },
                                                            'class'    => 'BinovoTknikaBackupsBundle:Script',
                                                            'property' => 'name'))
                ->add('isActive'      , 'checkbox'  , array('label'    => $t->trans('Is active', array(), 'BinovoTknikaBackups'),
                                                            'required' => false))
                ->add('jobs'          , 'collection', array('type'         => new JobShortType(),
                                                            'allow_delete' => true,
                                                            'label'        => $t->trans('Jobs', array(), 'BinovoTknikaBackups')));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\Tknika\BackupsBundle\Entity\Client',
            'translator' => null,
        );
    }

    public function getName()
    {
        return 'Client';
    }
}