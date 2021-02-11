<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $languages = array(
          'English' => 'en',
          'Español' => 'es',
          'Basque'  => 'eu',
	  'Deutsch'  => 'de',
        );

        $builder->add('language'    , ChoiceType::class      , array('label'   => $t->trans('Language', array(), 'BinovoElkarBackup'),
                                                          'attr'    => array('class'    => 'form-control'),
                                                          'choices' => $languages,
                                                          'choices_as_values' => true,
                                                          ))
	              ->add('linesperpage', IntegerType::class   , array('label'   => $t->trans('Records per page', array(), 'BinovoElkarBackup'),
                                                          'attr'    => array('class'    => 'form-control')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\User',
          'validation_groups' => array('preferences'),
          'translator' => null,
        ));
    }
}
