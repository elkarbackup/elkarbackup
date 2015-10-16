<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScriptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'        , 'text'    , array('label' => $t->trans('Name'     , array(), 'BinovoElkarBackup'),
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('description' , 'textarea', array('label' => $t->trans('Description' , array(), 'BinovoElkarBackup'),
                                                        'required' => false,
                                                        'attr'  => array('class'    => 'form-control','rows' => '3')))
                ->add('scriptFile'  , 'file'    , array('label' => $t->trans('File' , array(), 'BinovoElkarBackup'),
                                                        'required' => $options['scriptFileRequired'],
                                                        'attr'  => array('class'    => 'form-control')))
                ->add('isClientPre' , 'checkbox', array('label' => $t->trans('Runs as before client script', array(), 'BinovoElkarBackup'),
                                                        'required' => false))
                ->add('isClientPost', 'checkbox', array('label' => $t->trans('Runs as after client script', array(), 'BinovoElkarBackup'),
                                                        'required' => false))
                ->add('isJobPre'    , 'checkbox', array('label' => $t->trans('Runs as before job script', array(), 'BinovoElkarBackup'),
                                                        'required' => false))
                ->add('isJobPost'   , 'checkbox', array('label' => $t->trans('Runs as after job script', array(), 'BinovoElkarBackup'),
                                                        'required' => false));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class'         => 'Binovo\ElkarBackupBundle\Entity\Script',
          'translator'         => null,
          'scriptFileRequired' => false,
        ));
    }

    public function getName()
    {
        return 'Script';
    }
}
