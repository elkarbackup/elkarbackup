<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class RestoreBackupType extends AbstractType
{

  public function __construct($actualuserid,$granted)
      {
          $this->actualuserid = $actualuserid;
          $this->granted = $granted;
      }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $actualuserid = $this->actualuserid;
        $granted = $this->granted;

        $t = $options['translator'];

        $builder->add('client'       ,'entity'    , array('label'    => $t->trans('Restore host', array(), 'BinovoElkarBackup'),
                                                          'property' => 'client',
                                                          'attr'     => array('class'    => 'form-control'),
                                                          'class'    => 'BinovoElkarBackupBundle:Client',
                                                          'query_builder' => function(EntityRepository $er ) use ( $actualuserid, $granted ) {
                                                                  return  $this->getFilteredClients($er,$actualuserid,$granted);
                                                            },
                                                          'choice_label' => 'name',
                                                          'required' => true))

                ->add('source'	      ,'text'      , array('label'    => $t->trans('Source path', array(), 'BinovoElkarBackup'),
                                                            'attr'     => array('class'     => 'form-control'),
                                                            'read_only' =>'true',
                                                            'required' => false))
                ->add('path'          ,'text'      , array('label'    => $t->trans('Remote path', array(), 'BinovoElkarBackup'),
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
        ));

    }
}
