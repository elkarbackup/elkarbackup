<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Binovo\ElkarBackupBundle\Controller\DefaultController;

class BackupLocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $this->fs = $options['fs'];

        $builder->add('name'        , TextType::class    , array('label' => $t->trans('Name', array(), 'BinovoElkarBackup'),
                                                        'required' => true,
                                                        'attr' => array('class' => 'form-control')))
                ->add('host'        , TextType::class    , array('label' => $t->trans('Host', array(), 'BinovoElkarBackup'),
                                                        'required' => false,
                                                        'attr' => array('class' => 'form-control'),
                                                        'disabled' => !$this->fs))
                ->add('directory'   , TextType::class    , array('label' => $t->trans('Directory', array(), 'BinovoElkarBackup'),
                                                        'required' => true,
                                                        'attr' => array('class' => 'form-control')))
                ->add('maxParallelJobs', IntegerType::class, array('label' => $t->trans('Max parallel jobs', array(), 'BinovoElkarBackup'),
                                                         'attr'  => array('class'    => 'form-control'),
                                                         'required' => true));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class'         => 'Binovo\ElkarBackupBundle\Entity\BackupLocation',
          'translator'         => null,
          'scriptFileRequired' => false,
          'fs'                 => null
        ));
    }
}
