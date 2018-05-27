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

class RestoreBackupType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('client'         ,'entity'    , array('label'    => 'Client',
                                                                'property' => 'client',
                                                                'attr'     => array('class'    => 'form-control'),
                                                                'class'    => 'BinovoElkarBackupBundle:Client',
                                                                'choice_label' => 'name',
                                                                'required' => true))
                ->add('path'	      , 'text'      , array('label'    => 'Path',
                                                            'attr'     => array('class'     => 'form-control'),
                                                            'required' => true));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'translator'         => null,
            'scriptFileRequired' => false,
        ));
    }
}
