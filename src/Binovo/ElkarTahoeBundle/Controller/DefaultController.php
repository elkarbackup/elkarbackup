<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Controller;

use \Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Binovo\ElkarBackupBundle\Entity\Message;

class DefaultController extends Controller
{

    /**
     * @Route("/tahoe/config", name="tahoeConfig")
     * @Template()
     */
    public function tahoeConfigAction(Request $request)
    {
        $context = array('source' => 'DefaultController');

        $t = $this->get('translator');
        $manager = $this->getDoctrine()->getManager();
        $tahoe = $this->container->get('Tahoe');

        if (!$tahoe->isInstalled()) { //it is supossed not to happend though
            return $this->redirect($this->generateUrl('manageBackupsLocation'));
        } 

        //obtain data from the node's config file
        $fields['nickname']='nickname';
        $fields['introducerfurl']='introducer.furl';
        $fields['sharesneeded']='shares.needed';
        $fields['shareshappy']='shares.happy';
        $fields['sharestotal']='shares.total';

        $data['nickname'] = 'elkarbackup_node';
        $data['introducerfurl'] = '';
        $data['sharesneeded'] = 3;  //K
        $data['shareshappy'] = 7;   //H
        $data['sharestotal'] = 10;  //N default values

        $nodeConfigFile = '/var/lib/elkarbackup/.tahoe/tahoe.cfg';
        if (file_exists($nodeConfigFile)) {
            try {
                $content = file_get_contents($nodeConfigFile);
                foreach ($fields as $key => $field) {
                    $data[$key]='';
                    $line = $field . ' = ';
                    $i=strpos($content, $line);                    
                    if ('#' == $content[$i-1]) {
                        break;                      
                    }
                    $i+=strlen($line);
                    while ("\n"!=$content[$i] and $i<strlen($content)) {
                        $data[$key].=$content[$i];
                        $i++;
                    }
                    $data[$key]=rtrim($data[$key]);
                }
            } catch (Exception $e) {
                $this->warn('Warning: the file could not be read. Default settings will be displayed', $context);
            }
        }

        $formBuilder = $this->createFormBuilder($data);
        $formBuilder->add('nickname'      , 'text'    , array('required' => false,
                                                              'label'    => $t->trans('Node nickname', array(), 'BinovoElkarTahoe'),
                                                              'attr'     => array('class'    => 'form-control')));
        $formBuilder->add('introducerfurl', 'textarea', array('required' => true,
                                                              'label'    => $t->trans('Introducer node furl', array(), 'BinovoElkarTahoe'),
                                                              'attr'     => array('class'   => 'form-control',
                                                                                  'style'   => 'resize: vertical; min-height: 74px; max-height: 114px;')));
        $formBuilder->add('sharesneeded'  , 'text'    , array('required' => false,
                                                              'label'    => 'K',
                                                              'attr'     => array('class'   => 'form-control',
                                                                                  'style'   => 'width: 52px;',
                                                                                  'maxlength' => 3)));
        $formBuilder->add('shareshappy'   , 'text' , array('required' => false,
                                                              'label'    => 'H',
                                                              'attr'     => array('class'   => 'form-control',
                                                                                  'style'   => 'width: 52px;',
                                                                                  'maxlength' => 3)));
        $formBuilder->add('sharestotal'   , 'text'    , array('required' => false,
                                                              'label'    => 'N',
                                                              'attr'     => array('class'   => 'form-control',
                                                                                  'style'   => 'width: 52px;',
                                                                                  'maxlength' => 3)));
        $form = $formBuilder->getForm();


        if ($request->isMethod('POST')) {

            foreach ($data as $key => $value) {
              $oldData[$key] = $value;
            }

            $form->bind($request);   
            $data = $form->getData();

            //0 = no change, 1 = change and ok, 2 = change but warning, 3 = change but error
            $changes[0] = 0;
            $data['nickname'] = str_replace(' ', '_', $data['nickname']);
            if ($oldData['nickname'] == $data['nickname']) {
                $changes[1] = 0;
            } else {
                if (''==$data['nickname']) {
                    $data['nickname']='elkarbackup_node';
                    $changes[1]=2;
                } else {
                    $changes[1]=1;
                }
            }
            if ($changes[1]>$changes[0]) {
                $changes[0]=$changes[1];
            }

            $data['introducerfurl'] = str_replace(' ', '', $data['introducerfurl']);
            if ($oldData['introducerfurl'] == $data['introducerfurl']) {
                $changes[2] = 0;
            } else {
                $expression = '#^pb:\/\/([a-z0-9]{32})@([a-z0-9\.]{1,})\.([a-z0-9\.]{1,}):([0-9]{5})([a-z0-9:,\.]*)\/([a-z0-9]{1,})#';
                if (preg_match($expression, $data['introducerfurl']) ) {
                    $changes[2] = 1;
                } else {
                    $data['introducerfurl'] = $oldData['introducerfurl'];
                    $changes[2] = 3;
                    $trans_msg = $t->trans('ERROR: Invalid introducer`s furl, try again', array(), 'BinovoElkarTahoe');
                    $form->addError(new FormError($trans_msg));
                }
            }
            if ($changes[2]>$changes[0]) {
                $changes[0]=$changes[2];
            }

            //KHN
            $data['sharesneeded'] = str_replace(' ', '', $data['sharesneeded']);
            $data['shareshappy'] = str_replace(' ', '', $data['shareshappy']);
            $data['sharestotal'] = str_replace(' ', '', $data['sharestotal']);
            if ($oldData['sharesneeded'] == $data['sharesneeded'] and
                $oldData['shareshappy'] == $data['shareshappy'] and
                $oldData['sharestotal'] == $data['sharestotal']) {
                $changes[3] = 0;
            } else {
                if (is_numeric($data['sharesneeded']) and is_numeric($data['sharestotal'])) { 
                    if (!is_numeric($data['shareshappy'])) {
                        if (0 < $data['sharesneeded'] and $data['sharesneeded'] <= $data['sharestotal']) {
                            $changes[3]=3;
                        } else {
                            $changes[3]=2;
                        }
                    } else {
                        if (0 < $data['sharesneeded'] and  $data['sharesneeded'] <= $data['sharestotal']) {
                            if ($data['sharesneeded'] <= $data['shareshappy'] and $data['shareshappy'] <= $data['sharestotal']) {
                                //everything ok ( 1 >= K >= H >= N )
                                $changes[3]=1;
                            } else {
                                $changes[3]=2;
                            }
                        } else {
                            $changes[3]=3;
                        }
                    }
                } else {
                    $changes[3] = 3;
                }
            }
            if (2==$changes[3]) { //catch problem
              //find a new H
              if ($data['sharesneeded']==$data['sharestotal']) {
                  $data['shareshappy'] = $data['sharesneeded'];
              } else {
                  $data['shareshappy'] = $data['sharestotal'] - $data['sharesneeded'];
                  if ($data['shareshappy'] < $data['sharesneeded']) {
                      $data['shareshappy'] = $data['shareshappy'] + 1;  //I trust servers' disponibility
                      //$data['shareshappy'] = $data['sharestotal'];    //I trust no-one (I should also use smaller K)
                  }
              }
            }
            if (3==$changes[3]) { //catch error
              $data['sharesneeded'] = $oldData['sharesneeded'];
              $data['shareshappy'] = $oldData['shareshappy'];
              $data['sharestotal'] = $oldData['sharestotal'];
              $trans_msg = $t->trans('Warning: wrong KHN parameters', array(), 'BinovoElkarTahoe');
              $form->addError(new FormError($trans_msg));
            }
            if ($changes[3]>$changes[0]) {
                $changes[0]=$changes[3];
            }


            if (3 > $changes[0]) {
                $msg = new Message('DefaultController', 'TickCommand',
                                     json_encode(array( 'command' => 'tahoe:config_node',
                                                        'i.furl' => $data['introducerfurl'],
                                                        's.K' => $data['sharesneeded'],
                                                        's.H' => $data['shareshappy'],
                                                        's.N' => $data['sharestotal'],
                                                        'nname' => $data['nickname']
                                                        )));
                $manager->persist($msg);
                $manager->flush();

                $seconds = 65-date("s"); //1 min + 5sec delay
                return $this->render('BinovoElkarTahoeBundle:Default:configuring.html.twig',
                                     array( 'form' => $form->createView(),
                                            'seconds' => $seconds));
            }

            foreach ($form->getErrors() as $error) {
                $errors[] = $error;
            }
            $formBuilder->setData($data);
            $form = $formBuilder->getForm();
            foreach ($errors as $error) {
                $form->addError($error);
            }
        }
        
        if (file_exists('/var/lib/elkarbackup/.tahoe/imReady.txt')) {
            $trans_msg = $t->trans('Tahoe storage is successfully configured', array(), 'BinovoElkarTahoe');
        } else {
            $trans_msg = $t->trans('ERROR: Tahoe storage is actually not configured properly or configured at all', array(), 'BinovoElkarTahoe');
        }
        $this->get('session')->getFlashBag()->add('tahoeConfiguration', $trans_msg);

        return $this->render('BinovoElkarTahoeBundle:Default:configurenode.html.twig',
                                    array('form' => $form->createView()));
    }

}
