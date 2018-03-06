<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PolicyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $t = $options['translator'];
        $builder->add('name'              , TextType::class    , array('label' => $t->trans('Name'       , array(), 'BinovoElkarBackup'),
                                                              'attr' => array('class'    => 'form-control')))
                ->add('description'       , TextareaType::class, array('label' => $t->trans('Description', array(), 'BinovoElkarBackup'),
                                                              'required' => false,
                                                              'attr' => array('class'    => 'form-control','rows' => '3')))
                ->add('hourlyHours'       , HiddenType::class  , array('required' => false))
                ->add('hourlyDaysOfMonth' , HiddenType::class  , array('required' => false))
                ->add('hourlyDaysOfWeek'  , HiddenType::class  , array('required' => false))
                ->add('hourlyMonths'      , HiddenType::class  , array('required' => false))
                ->add('hourlyCount'       , HiddenType::class  , array('required' => false))
                ->add('dailyHours'        , HiddenType::class  , array('required' => false))
                ->add('dailyDaysOfMonth'  , HiddenType::class  , array('required' => false))
                ->add('dailyDaysOfWeek'   , HiddenType::class  , array('required' => false))
                ->add('dailyMonths'       , HiddenType::class  , array('required' => false))
                ->add('dailyCount'        , HiddenType::class  , array('required' => false))
                ->add('weeklyHours'       , HiddenType::class  , array('required' => false))
                ->add('weeklyDaysOfMonth' , HiddenType::class  , array('required' => false))
                ->add('weeklyDaysOfWeek'  , HiddenType::class  , array('required' => false))
                ->add('weeklyMonths'      , HiddenType::class  , array('required' => false))
                ->add('weeklyCount'       , HiddenType::class  , array('required' => false))
                ->add('monthlyHours'      , HiddenType::class  , array('required' => false))
                ->add('monthlyDaysOfMonth', HiddenType::class  , array('required' => false))
                ->add('monthlyDaysOfWeek' , HiddenType::class  , array('required' => false))
                ->add('monthlyMonths'     , HiddenType::class  , array('required' => false))
                ->add('monthlyCount'      , HiddenType::class  , array('required' => false))
                ->add('yearlyHours'       , HiddenType::class  , array('required' => false))
                ->add('yearlyDaysOfMonth' , HiddenType::class  , array('required' => false))
                ->add('yearlyDaysOfWeek'  , HiddenType::class  , array('required' => false))
                ->add('yearlyMonths'      , HiddenType::class  , array('required' => false))
                ->add('yearlyCount'       , HiddenType::class  , array('required' => false))
                ->add('exclude'           , TextareaType::class, array('label' => $t->trans('Exclude', array(), 'BinovoElkarBackup'),
                                                              'required' => false,
                                                              'attr' => array('class'    => 'form-control','rows' => '3')))
                ->add('include'           , TextareaType::class, array('label' => $t->trans('Include', array(), 'BinovoElkarBackup'),
                                                              'required' => false,
                                                              'attr' => array('class'    => 'form-control','rows' => '3')))
                ->add('syncFirst'         , CheckboxType::class, array('label' => $t->trans('Sync first', array(), 'BinovoElkarBackup'),
                                                              'required' => false))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'Binovo\ElkarBackupBundle\Entity\Policy',
          'translator' => null,
        ));
    }

    public function getName()
    {
        return 'Policy';
    }
}
