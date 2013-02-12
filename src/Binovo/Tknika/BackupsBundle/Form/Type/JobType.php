<?php

namespace Binovo\Tknika\BackupsBundle\Form\Type;

use Binovo\Tknika\BackupsBundle\Entity\Job;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'                , 'text'    , array('label' => $t->trans('Name', array(), 'BinovoTknikaBackups'),
                                                                'attr'  => array('class'    => 'span12')))
                ->add('description'         , 'textarea', array('label' => $t->trans('Description', array(), 'BinovoTknikaBackups'),
                                                                'required' => false,
                                                                'attr'  => array('class'    => 'span12')))
                ->add('policy'              , 'entity'  , array('label' => $t->trans('Policy', array(), 'BinovoTknikaBackups'),
                                                                'attr'     => array('class'    => 'span12'),
                                                                'required' => false,
                                                                'class'    => 'BinovoTknikaBackupsBundle:Policy',
                                                                'property' => 'name'))
                ->add('useLocalPermissions' , 'checkbox', array('label'    => $t->trans('Use local permissions', array(), 'BinovoTknikaBackups'),
                                                                'required' => false))
                ->add('exclude'             , 'textarea', array('label' => $t->trans('Exclude', array(), 'BinovoTknikaBackups'),
                                                                'required' => false,
                                                                'attr' => array('class'    => 'span12')))
                ->add('include'             , 'textarea', array('label' => $t->trans('Include', array(), 'BinovoTknikaBackups'),
                                                                'required' => false,
                                                                'attr' => array('class'    => 'span12')))
                ->add('owner'               , 'entity'  , array('label'    => $t->trans('Owner', array(), 'BinovoTknikaBackups'),
                                                                'property' => 'username',
                                                                'attr'     => array('class'    => 'span12'),
                                                                'class'    => 'BinovoTknikaBackupsBundle:User'))
                ->add('path'                , 'text'    , array('label' => $t->trans('Path', array(), 'BinovoTknikaBackups'),
                                                                'attr'  => array('class'    => 'span12')))
                ->add('notificationsTo'     , 'choice'  , array('label'    => $t->trans('Send notices to', array(), 'BinovoTknikaBackups'),
                                                                'required' => false,
                                                                'attr'     => array('class'    => 'span12'),
                                                                'choices'  => array(Job::NOTIFY_TO_ADMIN => $t->trans('Admin', array(), 'BinovoTknikaBackups'),
                                                                                    Job::NOTIFY_TO_OWNER => $t->trans('Owner', array(), 'BinovoTknikaBackups'),
                                                                                    Job::NOTIFY_TO_EMAIL => $t->trans('Email', array(), 'BinovoTknikaBackups')),
                                                                'multiple' => true,
                                                                'expanded' => true,))
                ->add('notificationsEmail'  , 'email'  , array('label'    => ' ',
                                                               'required' => false))
                ->add('minNotificationLevel', 'choice'  , array('label'       => $t->trans('Notify only', array(), 'BinovoTknikaBackups'),
                                                                'attr'        => array('class'    => 'span12'),
                                                                'empty_value' => false,
                                                                'required'    => false,
                                                                'choices'      => array(Job::NOTIFICATION_LEVEL_ALL     => $t->trans('All messages'   , array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_INFO    => $t->trans('Notices and up' , array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_WARNING => $t->trans('Warnings and up', array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_ERROR   => $t->trans('Errors and up'  , array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_NONE    => $t->trans('None'           , array(), 'BinovoTknikaBackups'))))
                ->add('preScript'           , 'entity'  , array('label' => $t->trans('Pre script', array(), 'BinovoTknikaBackups'),
                                                                'attr'     => array('class'    => 'span12'),
                                                                'required' => false,
                                                                'class'    => 'BinovoTknikaBackupsBundle:Script',
                                                                'query_builder' => function($er) {
                                                                    return $er->createQueryBuilder('s')
                                                                        ->where('s.isJobPre = 1');
                                                                },
                                                                'property' => 'name'))
                ->add('postScript'          , 'entity'  , array('label' => $t->trans('Post script', array(), 'BinovoTknikaBackups'),
                                                                'attr'     => array('class'    => 'span12'),
                                                                'required' => false,
                                                                'class'    => 'BinovoTknikaBackupsBundle:Script',
                                                                'query_builder' => function($er) {
                                                                    return $er->createQueryBuilder('s')
                                                                        ->where('s.isJobPost = 1');
                                                                },
                                                                'class'    => 'BinovoTknikaBackupsBundle:Script',
                                                                'property' => 'name'))
                ->add('isActive'            , 'checkbox', array('label'    => $t->trans('Is active', array(), 'BinovoTknikaBackups'),
                                                                'required' => false));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\Tknika\BackupsBundle\Entity\Job',
            'translator' => null,
        );
    }

    public function getName()
    {
        return 'Job';
    }
}