<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthorizedKeyType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $t = $options['translator'];
        $builder->add('publicKey', TextType::class, array('required' => true , 'label' => '',
                                                 'attr' => array('placeholder' => $t->trans('Key', array(), 'BinovoElkarBackup'))))
                ->add('comment'  , TextType::class, array('required' => false, 'label' => '',
                                                 'attr' => array('placeholder' => $t->trans('Comment', array(), 'BinovoElkarBackup'))));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'translator' => null,
        ));
    }
}
