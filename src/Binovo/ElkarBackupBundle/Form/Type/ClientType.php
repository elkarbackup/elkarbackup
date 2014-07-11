<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'          , 'text'      , array('label' => $t->trans('Name', array(), 'BinovoElkarBackup'),
                                                            'attr'  => array('class'    => 'form-control')))
                ->add('description'   , 'textarea'  , array('label' => $t->trans('Description', array(), 'BinovoElkarBackup'),
                                                            'required' => false,
                                                            'attr'  => array('class'    => 'form-control','rows' => '3')))
                ->add('url'           , 'text'      , array('label'    => $t->trans('Url', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class' => 'form-control'),
                                                            'required' => false))
                ->add('quota'         , 'integer'   , array('label' => $t->trans('Quota', array(), 'BinovoElkarBackup'),
                                                            'attr'  => array('class'    => 'form-control')))
                ->add('preScripts'    , 'entity'    , array('label' => $t->trans('Pre script', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class' => 'form-control'),
                                                            'required' => false,
                                                            'multiple' => true,
                                                            'class'    => 'BinovoElkarBackupBundle:Script',
                                                            'query_builder' => function($er) {
                                                                return $er->createQueryBuilder('s')
                                                                    ->where('s.isClientPre = 1');
                                                            },
                                                            'property' => 'name'))
                ->add('postScripts'   , 'entity'    , array('label' => $t->trans('Post script', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class' => 'form-control'),
                                                            'required' => false,
                                                            'multiple' => true,
                                                            'class'    => 'BinovoElkarBackupBundle:Script',
                                                            'query_builder' => function($er) {
                                                                return $er->createQueryBuilder('s')
                                                                    ->where('s.isClientPost = 1');
                                                            },
                                                            'class'    => 'BinovoElkarBackupBundle:Script',
                                                            'property' => 'name'))
                ->add('isActive'      , 'checkbox'  , array('label'    => $t->trans('Is active', array(), 'BinovoElkarBackup'),
                                                            'required' => false))
                ->add('jobs'          , 'collection', array('type'         => new JobShortType(),
                                                            'allow_delete' => true,
                                                            'label'        => $t->trans('Jobs', array(), 'BinovoElkarBackup')));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Binovo\ElkarBackupBundle\Entity\Client',
            'translator' => null,
        );
    }

    public function getName()
    {
        return 'Client';
    }
}
