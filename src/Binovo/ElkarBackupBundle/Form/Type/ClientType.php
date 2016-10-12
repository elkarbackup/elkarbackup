<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Modify 'quota' value
        // User will see the quota in GB (fields.html.twig), but we will save it in KBs
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
          $client = $event->getData();
          $form = $event->getForm();
          if ($client->getQuota() > 0) {
            $quota = $client->getQuota() * 1024 * 1024;
            $client->setQuota($quota);
            $event->setData($client);
          }
        }, 255);

        $t = $options['translator'];
        $builder->add('name'          , 'text'      , array('label' => $t->trans('Name', array(), 'BinovoElkarBackup'),
                                                            'attr'  => array('class'    => 'form-control')))
                ->add('description'   , 'textarea'  , array('label' => $t->trans('Description', array(), 'BinovoElkarBackup'),
                                                            'required' => false,
                                                            'attr'  => array('class'    => 'form-control','rows' => '3')))
                ->add('url'           , 'text'      , array('label'    => $t->trans('Url', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class' => 'form-control'),
                                                            'required' => false))
                ->add('quota'         , 'number'     , array('label' => $t->trans('Quota', array(), 'BinovoElkarBackup'),
                                                            'attr'  => array('class'    => 'form-control','min' => '-1', 'step' => 'any')))
                ->add('preScripts'    , 'entity'    , array('label' => $t->trans('Pre script', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class' => 'multiselect autoheight form-control'),
                                                            'required' => false,
                                                            'multiple' => true,
                                                            'expanded' => true,
                                                            'class'    => 'BinovoElkarBackupBundle:Script',
                                                            'query_builder' => function($er) {
                                                                return $er->createQueryBuilder('s')
                                                                    ->where('s.isClientPre = 1');
                                                            },
                                                            'property' => 'name'))
                ->add('postScripts'   , 'entity'    , array('label' => $t->trans('Post script', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class' => 'multiselect autoheight form-control'),
                                                            'required' => false,
                                                            'multiple' => true,
                                                            'expanded' => true,
                                                            'class'    => 'BinovoElkarBackupBundle:Script',
                                                            'query_builder' => function($er) {
                                                                return $er->createQueryBuilder('s')
                                                                    ->where('s.isClientPost = 1');
                                                            },
                                                            'class'    => 'BinovoElkarBackupBundle:Script',
                                                            'property' => 'name'))
                ->add('isActive'      , 'checkbox'  , array('label'    => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                            'required' => false))
                ->add('owner'         , 'entity'    , array('label'    => $t->trans('Owner', array(), 'BinovoElkarBackup'),
                                                                'property' => 'username',
                                                                'attr'     => array('class'    => 'form-control'),
                                                                'class'    => 'BinovoElkarBackupBundle:User'))
                ->add('sshArgs'	      , 'text'      , array('label'    => $t->trans('SSH args', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class'     => 'form-control advanced-form-item'),
                                                            'required' => false))
                ->add('rsyncShortArgs', 'text'      , array('label'    => $t->trans('Rsync short args', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class'     => 'form-control advanced-form-item'),
                                                            'required' => false))
                ->add('rsyncLongArgs'	, 'text'      , array('label'    => $t->trans('Rsync long args', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class'     => 'form-control advanced-form-item'),
                                                            'required' => false))
                ->add('jobs'          , 'collection', array('type'         => new JobShortType(),
                                                            'allow_delete' => true,
                                                            'label'        => $t->trans('Jobs', array(), 'BinovoElkarBackup')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\Client',
          'translator' => null,
        ));
    }

    public function getName()
    {
        return 'Client';
    }
}
