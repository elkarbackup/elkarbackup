<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobShortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name'        , TextType::class)
                ->add('description' , TextareaType::class, array('required' => false))
                ->add('path'        , TextType::class)
                ->add('policy'      , EntityType::class  , array('required' => false,
                                                        'class'    => 'App:Policy',
                                                        'choice_label' => 'name'))
                ->add('isActive'    , CheckboxType::class, array('required' => false));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
          'data_class' => 'App\Entity\Job',
        ));
    }
}
