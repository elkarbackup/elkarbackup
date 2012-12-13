<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Form\Type;

use Binovo\Tknika\TknikaBackupsBundle\Entity\Job;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'              , 'text'    , array('label' => $t->trans('Name', array(), 'BinovoTknikaBackups')))
                ->add('description'       , 'textarea', array('label' => $t->trans('Description', array(), 'BinovoTknikaBackups'),
                                                              'required' => false))
                ->add('policy'            , 'entity'  , array('label' => $t->trans('Policy', array(), 'BinovoTknikaBackups'),
                                                              'required' => false,
                                                              'class'    => 'BinovoTknikaTknikaBackupsBundle:Policy',
                                                              'property' => 'name'))
                ->add('owner'             , 'entity'  , array('label'    => $t->trans('Owner', array(), 'BinovoTknikaBackups'),
                                                              'property' => 'username',
                                                              'class'    => 'BinovoTknikaTknikaBackupsBundle:User'))
                ->add('url'               , 'text'    , array('label' => $t->trans('Url', array(), 'BinovoTknikaBackups')))
                ->add('notificationsTo'   , 'choice'  , array('label'    => $t->trans('Send notices to', array(), 'BinovoTknikaBackups'),
                                                              'required' => false,
                                                              'choices'  => array(Job::NOTIFY_TO_ADMIN => $t->trans('Admin', array(), 'BinovoTknikaBackups'),
                                                                                  Job::NOTIFY_TO_OWNER => $t->trans('Owner', array(), 'BinovoTknikaBackups'),
                                                                                  Job::NOTIFY_TO_EMAIL => $t->trans('Email', array(), 'BinovoTknikaBackups')),
                                                              'multiple' => true,
                                                              'expanded' => true,))
                ->add('notificationsEmail', 'email'  , array('label'    => ' ',
                                                             'required' => false))
                ->add('minNotificationLevel', 'choice'  , array('label'       => $t->trans('Notify only', array(), 'BinovoTknikaBackups'),
                                                                'empty_value' => false,
                                                                'required'    => false,
                                                                'choices'      => array(Job::NOTIFICATION_LEVEL_ALL     => $t->trans('All messages'   , array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_INFO    => $t->trans('Notices and up' , array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_WARNING => $t->trans('Warnings and up', array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_ERROR   => $t->trans('Errors and up'  , array(), 'BinovoTknikaBackups'),
                                                                                        Job::NOTIFICATION_LEVEL_NONE    => $t->trans('None'           , array(), 'BinovoTknikaBackups'))))
                ->add('preScript'         , 'hidden'  , array('label' => $t->trans('Pre script', array(), 'BinovoTknikaBackups')))
                ->add('preScriptFile'     , 'file'    , array('label'    => $t->trans('Upload pre script', array(), 'BinovoTknikaBackups'),
                                                              'required' => false))
                ->add('postScript'        , 'hidden'  , array('label' => $t->trans('Post script', array(), 'BinovoTknikaBackups')))
                ->add('postScriptFile'    , 'file'    , array('label'    => $t->trans('Upload post script', array(), 'BinovoTknikaBackups'),
                                                              'required' => false))
                ->add('isActive'          , 'checkbox', array('label'    => $t->trans('Is active', array(), 'BinovoTknikaBackups'),
                                                              'required' => false));
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
}