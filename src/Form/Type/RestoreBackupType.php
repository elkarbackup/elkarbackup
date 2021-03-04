<?php
namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class RestoreBackupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $actualuserid = $options['actualuserid'];
        $granted = $options['granted'];

        $t = $options['translator'];

        $builder->add('client'       ,EntityType::class    , array('label'    => $t->trans('Restore host', array(), 'BinovoElkarBackup'),
                                                          'choice_label' => 'client',
                                                          'attr'     => array('class'    => 'form-control'),
                                                          'class'    => 'BinovoElkarBackupBundle:Client',
                                                          'query_builder' => function(EntityRepository $er ) use ( $actualuserid, $granted ) {
                                                                  return  $this->getFilteredClients($er,$actualuserid,$granted);
                                                            },
                                                          'choice_label' => 'name',
                                                          'required' => true))

                ->add('source'	      ,TextType::class      , array('label'    => $t->trans('Source path', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class'     => 'form-control', 'read_only' =>'true'),
                                                            'required' => false))
                ->add('path'          ,TextType::class      , array('label'    => $t->trans('Remote path', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class'     => 'form-control'),
                                                            'required' => true));




    }

    private function getFilteredClients(EntityRepository $er, $userId, $granted) {
        if($granted){
            return null;
        }

        return $er->createQueryBuilder('c')
            ->where('c.owner = ?1')
            ->setParameter(1, $userId);
    }

    public function configureOptions(OptionsResolver $resolver)
    {

         $resolver->setDefaults(array(
            'translator' => null,
            'actualuserid' => null,
            'granted' => null
        ));

    }
}
