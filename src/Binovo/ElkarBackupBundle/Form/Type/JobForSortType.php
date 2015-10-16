<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobForSortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'hidden');

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\Job',
        ));
    }

    public function getName()
    {
        return 'JobForSort';
    }
}
