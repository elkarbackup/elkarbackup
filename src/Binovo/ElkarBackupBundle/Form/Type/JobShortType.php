<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobShortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name'        , 'text')
                ->add('description' , 'textarea', array('required' => false))
                ->add('path'        , 'text')
                ->add('policy'      , 'entity'  , array('required' => false,
                                                        'class'    => 'BinovoElkarBackupBundle:Policy',
                                                        'property' => 'name'))
                ->add('isActive'    , 'checkbox', array('required' => false));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\Job',
        ));
    }

    public function getName()
    {
        return 'JobShort';
    }
}
