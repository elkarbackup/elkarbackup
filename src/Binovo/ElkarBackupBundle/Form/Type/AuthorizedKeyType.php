<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthorizedKeyType extends AbstractType
{
    private $translator;

    public function __construct($translator = null)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('publicKey', 'text', array('required' => true , 'label' => '',
                                                 'attr' => array('placeholder' => $t->trans('Key', array(), 'BinovoElkarBackup'))))
                ->add('comment'  , 'text', array('required' => false, 'label' => '',
                                                 'attr' => array('placeholder' => $t->trans('Comment', array(), 'BinovoElkarBackup'))));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'translator' => $this->translator,
        ));
    }

    public function getName()
    {
        return 'AuthorizedKey';
    }
}
