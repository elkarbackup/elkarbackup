<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Binovo\ElkarBackupBundle\Controller\DefaultController;
use Binovo\ElkarTahoeBundle\Utils\TahoeBackup;

class BackupLocationType extends AbstractType
{
    private $fs;
    private $tahoeInstalled;
    
    
    public function __construct($fs, $tahoeInstalled)
    {
        $this->tahoeInstalled = $tahoeInstalled;
        $this->fs = $fs;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];

        $builder->add('name'        , 'text'    , array('label' => $t->trans('Name', array(), 'BinovoElkarBackup'),
                                                        'required' => true,
                                                        'attr' => array('class' => 'form-control')))
                ->add('host'        , 'text'    , array('label' => $t->trans('Host', array(), 'BinovoElkarBackup'),
                                                        'required' => false,
                                                        'attr' => array('class' => 'form-control'),
                                                        'disabled' => !$this->fs))
                ->add('directory'   , 'text'    , array('label' => $t->trans('Directory', array(), 'BinovoElkarBackup'),
                                                        'required' => true,
                                                        'attr' => array('class' => 'form-control')))
                ->add('tahoe'       , 'checkbox', array('required' => false,
                                                        'label' => $t->trans('Turn on Tahoe storage', array(), 'BinovoElkarTahoe'),
                                                        'disabled' => !$this->tahoeInstalled))
                ->add('maxParallelJobs', 'integer', array('label' => $t->trans('Max parallel jobs', array(), 'BinovoElkarBackup'),
                                                         'attr'  => array('class'    => 'form-control'),
                                                         'required' => true));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class'         => 'Binovo\ElkarBackupBundle\Entity\BackupLocation',
          'translator'         => null,
          'scriptFileRequired' => false,
        ));
    }

    public function getName()
    {
        return 'BackupLocation';
    }
}
