<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Binovo\ElkarBackupBundle\Entity\Job;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'                , TextType::class    , array('label' => $t->trans('Name', array(), 'BinovoElkarBackup'),
                                                                'attr'  => array('class'    => 'form-control')))
                ->add('description'         , TextareaType::class, array('label' => $t->trans('Description', array(), 'BinovoElkarBackup'),
                                                                'required' => false,
                                                                'attr'  => array('class'    => 'form-control','rows' => '3')))
                ->add('policy'              , EntityType::class  , array('label' => $t->trans('Policy', array(), 'BinovoElkarBackup'),
                                                                'attr'     => array('class'    => 'form-control'),
                                                                'required' => true,
                                                                'class'    => 'BinovoElkarBackupBundle:Policy',
                                                                'property' => 'name'))
                ->add('useLocalPermissions' , CheckboxType::class, array('label'    => $t->trans('Use local permissions', array(), 'BinovoElkarBackup'),
                                                                'required' => false))
                ->add('exclude'             , TextareaType::class, array('label' => $t->trans('Exclude', array(), 'BinovoElkarBackup'),
                                                                'required' => false,
                                                                'attr' => array('class'    => 'form-control','rows' => '3')))
                ->add('include'             , TextareaType::class, array('label' => $t->trans('Include', array(), 'BinovoElkarBackup'),
                                                                'required' => false,
                                                                'attr' => array('class'    => 'form-control','rows' => '3')))
                ->add('path'                , TextType::class    , array('label' => $t->trans('Path', array(), 'BinovoElkarBackup'),
                                                                'attr'  => array('class'    => 'form-control')))
                ->add('notificationsTo'     , ChoiceType::class  , array('label'    => $t->trans('Send notices to', array(), 'BinovoElkarBackup'),
                                                                'required' => false,
                                                                'attr'     => array('class'    => 'form-control-no'),
                                                                'choices'  => array(Job::NOTIFY_TO_ADMIN => $t->trans('Admin', array(), 'BinovoElkarBackup'),
                                                                                    Job::NOTIFY_TO_OWNER => $t->trans('Client owner', array(), 'BinovoElkarBackup'),
                                                                                    Job::NOTIFY_TO_EMAIL => $t->trans('Email', array(), 'BinovoElkarBackup')),
                                                                'multiple' => true,
                                                                'expanded' => true,))
                ->add('notificationsEmail'  , EmailType::class , array('label'    => ' ',
                                                               'required' => false))
                ->add('minNotificationLevel', ChoiceType::class  , array('label'       => $t->trans('Notify only', array(), 'BinovoElkarBackup'),
                                                                'attr'        => array('class'    => 'form-control'),
                                                                'empty_value' => false,
                                                                'required'    => false,
                                                                'choices'      => array(Job::NOTIFICATION_LEVEL_ALL     => $t->trans('All messages'   , array(), 'BinovoElkarBackup'),
                                                                                        Job::NOTIFICATION_LEVEL_WARNING => $t->trans('Warnings and up', array(), 'BinovoElkarBackup'),
                                                                                        Job::NOTIFICATION_LEVEL_ERROR   => $t->trans('Errors and up'  , array(), 'BinovoElkarBackup'),
                                                                                        Job::NOTIFICATION_LEVEL_NONE    => $t->trans('None'           , array(), 'BinovoElkarBackup'))))
                ->add('preScripts'          , EntityType::class  , array('label' => $t->trans('Pre script', array(), 'BinovoElkarBackup'),
                                                                'attr'     => array('class' => 'autoheight form-control','data-dojo-type' => 'dojox.form.CheckedMultiSelect'),
                                                                'required' => false,
                                                                'multiple' => true,
                                                                'class'    => 'BinovoElkarBackupBundle:Script',
                                                                'query_builder' => function($er) {
                                                                    return $er->createQueryBuilder('s')
                                                                        ->where('s.isJobPre = 1');
                                                                },
                                                                'property' => 'name'))
                ->add('postScripts'         , EntityType::class  , array('label' => $t->trans('Post script', array(), 'BinovoElkarBackup'),
                                                                'attr'     => array('class' => 'autoheight form-control','data-dojo-type' => 'dojox.form.CheckedMultiSelect'),
                                                                'required' => false,
                                                                'multiple' => true,
                                                                'class'    => 'BinovoElkarBackupBundle:Script',
                                                                'query_builder' => function($er) {
                                                                    return $er->createQueryBuilder('s')
                                                                        ->where('s.isJobPost = 1');
                                                                },
                                                                'class'    => 'BinovoElkarBackupBundle:Script',
                                                                'property' => 'name'))
                ->add('isActive'            , CheckboxType::class, array('label'    => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                                'required' => false))
                ->add('token'               , TextType::class    , array('label' => $t->trans('Token', array(), 'BinovoElkarBackup'),
                                                                'required'  => false,
                                                                'attr'  => array('class'    => 'form-control', 'read_only' => true)))
                ->add('backupLocation'       , EntityType::class    , array('label' => $t->trans('Backup location', array(), 'BinovoElkarBackup'),
                                                                 'required' => true,
                                                                 'attr'     => array('class'    => 'form-control'),
                                                                 'class'    => 'BinovoElkarBackupBundle:BackupLocation',
                                                                 'property' => 'name'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\Job',
          'translator' => null,
        ));
    }

    public function getName()
    {
        return 'Job';
    }
}
