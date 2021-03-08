<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
namespace App\Controller;

use \DateTime;
use \Exception;
use \PDOException;
use \RuntimeException;
use App\BinovoElkarBackupBundle;
use App\Entity\Client;
use App\Entity\BackupLocation;
use App\Entity\Job;
use App\Entity\Message;
use App\Entity\Policy;
use App\Entity\Queue;
use App\Entity\Script;
use App\Entity\User;
use App\Form\Type\AuthorizedKeyType;
use App\Form\Type\BackupLocationType;
use App\Form\Type\ClientType;
use App\Form\Type\JobForSortType;
use App\Form\Type\JobType;
use App\Form\Type\PolicyType;
use App\Form\Type\RestoreBackupType;
use App\Form\Type\ScriptType;
use App\Form\Type\UserType;
use App\Form\Type\PreferencesType;
use App\Lib\Globals;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use steevanb\SSH2Bundle\Entity\Profile;
use steevanb\SSH2Bundle\Entity\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
    private $security;
    private $translator;

    public function __construct(Security $security, TranslatorInterface $t)
    {
        $this->security = $security;
        $this->translator = $t;
    }
    protected function info($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->get('BnvWebLogger');
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $logger->info(
            $this->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
        );
    }
    
    protected function warn($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->get('BnvWebLogger');
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $logger->warning(
            $this->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
        );
    }
    
    protected function err($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->get('BnvWebLogger');
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $logger->error(
            $this->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
        );
    }
    
    protected function debug($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->get('BnvWebLogger');
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $logger->debug(
            $this->trans($msg, $translatorParams, 'BinovoElkarBackup'),
            $context
        );
    }

    protected function generateClientRoute($id)
    {
        return $this->generateUrl('editClient', array('id' => $id));
    }

    protected function generateJobRoute($idJob, $idClient)
    {
        return $this->generateUrl('editJob', array(
            'idClient' => $idClient,
            'idJob' => $idJob
        ));
    }

    protected function generatePolicyRoute($id)
    {
        return $this->generateUrl('editPolicy', array('id' => $id));
    }

    protected function generateScriptRoute($id)
    {
        return $this->generateUrl('editScript', array('id' => $id));
    }

    protected function generateBackupLocationRoute($id)
    {
        return $this->generateUrl('editBackupLocation', array('id' => $id));
    }

    protected function generateUserRoute($id)
    {
        return $this->generateUrl('editUser', array('id' => $id));
    }

    /*
     * Checks if autofs is installed and the builtin -hosts map activated
     */
    protected function isAutoFsAvailable()
    {
        $result = false;
        if (! file_exists('/etc/auto.master')) {
            return false;
        }
        $file = fopen('/etc/auto.master', 'r');
        if (! $file) {
            return false;
        }
        while ($line = fgets($file)) {
            if (preg_match('/^\s*\/net\s*-hosts/', $line)) {
                $result = true;
                break;
            }
        }
        fclose($file);
        return $result;
    }

    /**
     * Should be called after making changes to any of the parameters to make the changes effective.
     */
    protected function clearCache()
    {
        $realCacheDir = $this->container->getParameter('kernel.cache_dir');
        $this->container->get('cache_clearer')->clear($realCacheDir);
    }

    /**
     * @Route("/about", name="about")
     * @Template()
     */
    public function aboutAction(Request $request)
    {
        return $this->render('BinovoElkarBackupBundle:Default:about.html.twig');
    }

    /**
     * @Route("/config/publickey/get", name="downloadPublicKey")
     * @Template()
     */
    public function downloadPublicKeyAction(Request $request)
    {
        if (!file_exists($this->container->getParameter('public_key'))) {
            throw $this->createNotFoundException(
                $this->trans('Unable to find public key:')
            );
        }
        $headers = array(
            'Content-Type' => 'text/plain',
            'Content-Disposition' => sprintf('attachment; filename="Publickey.pub"')
        );
        
        return new Response(file_get_contents(
            $this->container->getParameter('public_key')),
            200,
            $headers
        );
    }

    /**
     * @Route("/config/publickey/generate", name="generatePublicKey")
     *
     * @method ("POST")
     *         @Template()
     */
    public function generatePublicKeyAction(Request $request)
    {
        $t = $this->translator;
        $db = $this->getDoctrine();
        $manager = $db->getManager();
        $msg = new Message(
            'DefaultController', 
            'TickCommand', 
            json_encode(array('command' => "elkarbackup:generate_keypair"))
        );
        $manager->persist($msg);
        $this->info('Public key generation requested');
        $manager->flush();
        $this->get('session')->getFlashBag()->add(
            'manageParameters', 
            $t->trans(
                'Wait for key generation. It should be available in less than 2 minutes. Check logs if otherwise', 
                array(), 
                'BinovoElkarBackup'
            )
        );
        
        return $this->redirect($this->generateUrl('manageParameters'));
    }

    public function trans($msg, $params = array(), $domain = 'BinovoElkarBackup')
    {
        return $this->translator->trans($msg, $params, $domain);
    }

    /**
     * @Route("/client/{id}/delete", name="deleteClient")
     *
     * @method ("POST")
     *         @Template()
     */
    public function deleteClientAction(Request $request, $id)
    {
        $access = $this->checkPermissions($id);
        if ($access == False) {
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        $db = $this->getDoctrine();
        $manager = $db->getManager();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Client');
        $manager = $db->getManager();
        $client = $repository->find($id);
        $queue = $db->getRepository('BinovoElkarBackupBundle:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getClient()->getId() == $id) {
                $response = new JsonResponse(array(
                    'error' => true,
                    'msg' => $t->trans(
                        'Could not delete client %clientName%, it has jobs enqueued.',
                        array('%clientName%' => $client->getName()),
                        'BinovoElkarBackup'
                    ),
                    'data' => array($id)
                ));
                $this->err(
                    'Could not delete client %clientName%, it has jobs enqueued.',
                    array('%clientName%' => $client->getName()),
                    array('link' => $this->generateClientRoute($id))
                );
                $manager->flush();
                return $response;
            }
        }
        
        try {
            $manager->remove($client);
            $msg = new Message(
                'DefaultController',
                'TickCommand',
                json_encode(array(
                    'command' => "elkarbackup:delete_job_backups",
                    'client' => (int) $id
                ))
            );
            $manager->persist($msg);
            $this->info(
                'Client "%clientid%" deleted',
                array('%clientid%' => $id),
                array('link' => $this->generateClientRoute($id))
            );
            $manager->flush();
            $response = new JsonResponse(array(
                'error' => false,
                'msg' => $t->trans(
                    'Client %clientName% deleted successfully.',
                    array('%clientName%' => $client->getName()),
                    'BinovoElkarBackup'
                ),
                'action' => 'deleteClientRow',
                'data' => array($id)
            ));
            
        } catch (Exception $e) {
            $response = new JsonResponse(array(
                'error' => false,
                'msg' => $t->trans(
                    'Unable to delete client: %extrainfo%',
                    array('%extrainfo%' => $e->getMessage()),
                    'BinovoElkarBackup'
                ),
            ));
        }
        return $response;
    }

    /**
     * @Route("/login", name="login")
     *
     * @method ("GET")
     *         @Template()
     */
    public function loginAction(Request $request)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $session = $request->getSession();
        $t = $this->translator;
        
        // get the login error if there is one
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        }
        $this->info(
            'Log in attempt with user: %username%',
            array('%username%' => $session->get(Security::LAST_USERNAME))
        );
        $this->getDoctrine()->getManager()->flush();
        $locales = $this->container->getParameter('supported_locales');
        $localesWithNames = array();
        foreach ($locales as $locale) {
            $localesWithNames[] = array(
                $locale,
                $t->trans("language_$locale", array(), 'BinovoElkarBackup')
            );
        }
        if ($this->container->hasParameter('disable_background')) {
            $disable_background = $this->container->getParameter('disable_background');
        } else {
            $disable_background = False;
        }
        
        // Warning for Rsnapshot 1.3.1-4 in Debian Jessie
        $rsnapshot_jessie_md5 = '7d9eb926a1c4d6fcbf81d939d9f400ea';
        if (is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')) {
            $rsnapshot_path = shell_exec(sprintf('which rsnapshot'));
            $sresult = explode(' ', shell_exec(sprintf('md5sum %s', $rsnapshot_path)));
            if (is_array($sresult)) {
                $rsnapshot_local_md5 = $sresult[0];
            } else {
                // PHP 5.3 or higher
                $rsnapshot_local_md5 = $sresult;
            }
        } else {
            $rsnapshot_local_md5 = "unknown";
            syslog(
                LOG_INFO,
                'Impossible to check rsnapshot version. More info: https://github.com/elkarbackup/elkarbackup/issues/88"'
            );
        }
        if ($rsnapshot_jessie_md5 == $rsnapshot_local_md5) {
            $alert = "WARNING! Change your Rsnapshot version <a href='https://github.com/elkarbackup/elkarbackup/wiki/JessieRsnapshotIssue'>More info</a>";
            syslog(LOG_INFO, 'Rsnapshot 1.3.1-4 not working with SSH args. Downgrade it or fix it. More info: https://github.com/elkarbackup/elkarbackup/issues/88"');
            $disable_background = True;
        } else {
            $alert = NULL;
        }
        
        return $this->render(
            'default/login.html.twig',
            array(
                'last_username' => $session->get(Security::LAST_USERNAME),
                'error' => $error,
                'supportedLocales' => $localesWithNames,
                'disable_background' => $disable_background,
                'alert' => $alert
            )
        );
    }

    /**
     * @Route("/client/{id}", name="editClient")
     *
     * @method ("GET")
     *         @Template()
     */
    public function editClientAction(Request $request, $id = 'new')
    {
        if ('new' === $id) {
            $client = new Client();
        } else {
            $access = $this->checkPermissions($id);
            if ($access == False) {
                return $this->redirect($this->generateUrl('showClients'));
            }
            
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
            $client = $repository->find($id);
            if (null == $client) {
                throw $this->createNotFoundException(
                    $this->trans('Unable to find Client entity:') . $id
                );
            }
        }
        
        $form = $this->createForm(
            ClientType::class,
            $client,
            array('translator' => $this->translator)
        );
        $this->debug(
            'View client %clientid%',
            array('%clientid%' => $id),
            array('link' => $this->generateClientRoute($id))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:client.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/client/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveClient")
     *
     * @method ("POST")
     *         @Template()
     */
    public function saveClientAction(Request $request, $id)
    {
        $user = $this->security->getToken()->getUser();
        $actualuserid = $user->getId();
        
        $t = $this->translator;
        if ("-1" === $id) {
            $client = new Client();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
            $client = $repository->find($id);
        }
        
        $form = $this->createForm(
            ClientType::class,
            $client,
            array('translator' => $t)
        );
        $form->handleRequest($request);
        if ($form->isValid()) {
            $client = $form->getData();
            $em = $this->getDoctrine()->getManager();
            try {
                if (isset($jobsToDelete)) {
                    foreach ($jobsToDelete as $idJob => $job) {
                        $client->getJobs()->removeElement($job);
                        $em->remove($job);
                        $msg = new Message(
                            'DefaultController', 
                            'TickCommand', 
                            json_encode(array(
                                'command' => "elkarbackup:delete_job_backups",
                                'client' => (int) $id,
                                'job' => $idJob
                            ))
                        );
                        $em->persist($msg);
                        $this->info(
                            'Delete client %clientid%, job %jobid%',
                            array(
                                '%clientid%' => $client->getId(),
                                '%jobid%' => $job->getId()
                            ), 
                            array('link' => $this->generateJobRoute(
                                $job->getId(),
                                $client->getId()
                            ))
                        );
                    }
                }
                if ($client->getOwner() == null) {
                    $client->setOwner($this->security->getToken()->getUser());
                }
                
                if ($client->getMaxParallelJobs() < 1) {
                    throw new Exception('Max parallel jobs parameter should be positive integer');
                }
                $em->persist($client);
                $this->info(
                    'Save client %clientid%',
                    array('%clientid%' => $client->getId()),
                    array('link' => $this->generateClientRoute($client->getId()))
                );
                $em->flush();
                
                return $this->redirect($this->generateUrl('showClients'));
            } catch (Exception $e) {
                $this->get('session')->getFlashBag()->add(
                    'client',
                    $t->trans('Unable to save your changes: %extrainfo%',
                        array('%extrainfo%' => $e->getMessage()),
                        'BinovoElkarBackup'
                    )
                );
                
                return $this->redirect($this->generateUrl(
                    'editClient',
                    array('id' => $client->getId() == null ? 'new' : $client->getId())
                ));
            }
        } else {
            
            return $this->render(
                'BinovoElkarBackupBundle:Default:client.html.twig',
                array('form' => $form->createView())
            );
        }
    }

    /**
     * @Route("/job/{id}/delete", name="deleteJob")
     * @Route("/client/{idClient}/job/{idJob}/delete", requirements={"idClient" = "\d+", "idJob" = "\d+"}, defaults={"idJob" = "-1"}, name="deleteJob")
     *
     * @method ("POST")
     *         @Template()
     */
    public function deleteJobAction(Request $request, $idClient, $idJob)
    {
        $access = $this->checkPermissions($idClient);
        if ($access == False) {
            return $this->redirect($this->generateUrl('showClients'));
        }
        $t = $this->translator;
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Job');
        $manager = $db->getManager();
        $job = $repository->find($idJob);
        $queue = $db->getRepository('BinovoElkarBackupBundle:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getId() == $idJob) {
                $response = new JsonResponse(array(
                    'error' => true,
                    'msg' => $t->trans(
                        'Could not delete job %jobName%, it is enqueued.',
                        array('%jobName%' => $job->getName()),
                        'BinovoElkarBackup'
                    ),
                    'data' => array($idJob)
                ));
                $this->err(
                    'Could not delete job %jobName%, it is enqueued.',
                    array('%jobName%' => $job->getName()),
                    array('link' => $this->generateJobRoute($idJob, $idClient))
                );
                $manager->flush();
                return $response;
            }
        }
        
        try {
            $manager->remove($job);
            $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
                'command' => "elkarbackup:delete_job_backups",
                'client' => (int) $idClient,
                'job' => (int) $idJob
            )));
            $manager->persist($msg);
            $this->info(
                'Client %clientid%, job "%jobid%" deleted successfully.',
                array('%clientid%' => $idClient,'%jobid%' => $idJob),
                array('link' => $this->generateJobRoute($idJob, $idClient))
            );
            $manager->flush();
            $response = new JsonResponse(array(
                'error' => false,
                'msg' => $t->trans(
                    'Client %clientid%, job "%jobid%" deleted successfully.',
                    array('%clientid%' => $idClient,'%jobid%' => $idJob),
                    'BinovoElkarBackup'
                    ),
                'action' => 'deleteJobRow',
                'data' => array($idJob)
            ));
        } catch (Exception $e) {
            $response = new JsonResponse(array(
                'error' => false,
                'msg' => $t->trans(
                    'Unable to delete job: %extrainfo%',
                    array('%extrainfo%' => $e->getMessage()),
                    'BinovoElkarBackup'
                ),
            ));
        }
        return $response;
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}", name="editJob")
     *
     * @method ("GET")
     *         @Template()
     */
    public function editJobAction(Request $request, $idClient, $idJob = 'new')
    {
        if ('new' === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Client')
                ->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($this->trans('Unable to find Client entity:') . $idClient);
            }
            $job->setClient($client);
        } else {
            $access = $this->checkPermissions($idClient, $idJob);
            if ($access == True) {
                $job = $this->getDoctrine()
                    ->getRepository('BinovoElkarBackupBundle:Job')
                    ->find($idJob);
            } else {
                return $this->redirect($this->generateUrl('showClients'));
            }
        }
        $form = $this->createForm(
            JobType::class,
            $job,
            array('translator' => $this->translator)
        );
        $this->debug(
            'View client %clientid%, job %jobid%',
            array('%clientid%' => $idClient,'%jobid%' => $idJob),
            array('link' => $this->generateJobRoute($idJob, $idClient))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:job.html.twig',
            array('form' => $form->createView())
        );
    }
    
    /**
     * @Route("/client/{idClient}/job/{idJob}/restore/{idBackupLocation}/{path}", requirements={"idClient" = "\d+", "idJob" = "\d+", "path" = ".*", "idBackupLocation" = "\d+"}, defaults={"path" = "/", "idBackupLocation" = 0}, name="restoreJobBackup")
     *
     * @method ("GET")
     */
    public function restoreJobBackupAction(Request $request, $idClient, $idJob, $idBackupLocation, $path)
    {
        $user = $this->security->getToken()->getUser();
        $actualuserid = $user->getId();
        $actualusername = $user->getUsername();
        
        $suggestedPath = mb_substr($path, mb_strpos($path, '/'));
        $suggestedPath = mb_substr($suggestedPath, 0, mb_strrpos($suggestedPath, '/'));

        $access = $this->checkPermissions($idClient);
        if ($access == False) {

            $this->err(
            'Unautorized access attempt by user: %username% into %path% by idclient: %clientid%. / idjob: %jobid%' ,
            array('%username%' => $actualusername, '%path%' => $path, '%clientid%' => $idClient,'%jobid%' => $idJob )
            );

            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('showClients'));
        }

        $granted = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');

        $form = $this->createForm(
            RestoreBackupType::class,
            array('path' => $suggestedPath,'source' => $path),
            array('translator' => $this->translator, 'actualuserid' => $actualuserid, 'granted' => $granted));
        return $this->render('BinovoElkarBackupBundle:Default:restorebackup.html.twig',array(
            'form' => $form->createView(),
            'idClient' => $idClient,
            'idJob' => $idJob,
            'idBackupLocation' => $idBackupLocation,
            'path' => $path));
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/restore/{idBackupLocation}/{path}", requirements={"idClient" = "\d+", "idJob" = "\d+", "path" = ".*", "idBackupLocation" = "\d+"}, defaults={"path" = "/", "idBackupLocation" = 0}, name="runRestoreJobBackup")
     *
     * @method ("POST")
     */
    public function runRestoreJobBackupAction(Request $request, $idClient, $idJob, $idBackupLocation, $path)
    {
       $t = $this->translator;
       $user = $this->security->getToken()->getUser();
       $actualuserid = $user->getId();
       $granted = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
       $suggestedPath = mb_substr($path, mb_strpos($path, '/'));
       $suggestedPath = mb_substr($suggestedPath, 0, mb_strrpos($suggestedPath, '/'));
      
       
        $form = $this->createForm(
            RestoreBackupType::class,
            array('path' => $suggestedPath,'source' => $path),
            array('translator' => $this->translator, 'actualuserid' => $actualuserid, 'granted' => $granted));
       $form->handleRequest($request);

       if (!$form->isSubmitted() || !$form->isValid()) {
           $this->get('session')->getFlashBag()->add('error',
           $t->trans('There was an error in your restore backup process', array(), 'BinovoElkarBackup'));
           return $this->redirect($this->generateUrl('showClients'));
       }
        
       $data = $form->getData();
       $targetPath = $data['path'];
       $targetIdClient = $data['client'];

       $backupLocation = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:BackupLocation')->find($idBackupLocation);
       $sourcePath = sprintf("%s/%s/%s/%s", $backupLocation->getDirectory(), sprintf('%04d', $idClient), sprintf('%04d', $idJob), $path);

       $clientRepo = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Client');
       $targetClient = $clientRepo->find($targetIdClient);
       $url = $targetClient->getUrl();
       $sshArgs = $targetClient->getSshArgs();

       $manager = $this->getDoctrine()->getManager();
       $msg = new Message(
              'DefaultController',
              'TickCommand',
              json_encode(array(
                  'command' => "elkarbackup:restore_backup",
                  'url' => $url,
                  'sourcePath' => $sourcePath,
                  'remotePath' => $targetPath,
                  'sshArgs'    => $sshArgs
              ))
        );
        $manager->persist($msg);
        $this->info(
              'Client "%clientid%" restore started',
              array('%clientid%' => $idClient),
              array('link' => $this->generateClientRoute($idClient))
        );
        $manager->flush();
        
        $this->get('session')->getFlashBag()->add('success',
              $t->trans('Your backup restore process has been enqueued', array(), 'BinovoElkarBackup'));
        return $this->redirect($this->generateUrl('showClients'));
    }
    
    /**
     * @Route("/client/{idClient}/job/{idJob}/run", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="enqueueJob")
     *
     * @method ("POST")
     *         @Template()
     */
    public function enqueueJobAction(Request $request, $idClient, $idJob)
    {
        $t = $this->translator;
        $user = $this->security->getToken();
        $trustable = false;
        
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Authenticated user
            $trustable = true;
        } else {
            // Anonymous access
            $token = $this->get('request')->request->get('token');
            if ('' == $token) {
                $response = new JsonResponse(array(
                    'status' => 'true',
                    'msg' => $t->trans(
                        'You need to login or send a token',
                        array(),
                        'BinovoElkarBackup'
                    )
                ));
                return $response;
            } else {
                $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
                $job = $repository->findOneById($idJob);
                if ($token == $job->getToken()) {
                    // Valid token, but let's require HTTPS
                    if ($this->container->get('request_stack')->getCurrentRequest()->isSecure()) {
                        $trustable = true;
                    } else {
                        $response = new JsonResponse(array(
                            'status' => 'false',
                            'msg' => $t->trans(
                                'Aborted: HTTPS required',
                                array(),
                                'BinovoElkarBackup'
                            )
                        ));
                        return $response;
                    }
                } else {
                    // Invalid token
                    $trustable = false;
                }
                
                if (! $trustable) {
                    $response = new JsonResponse(array(
                        'status' => 'false',
                        'msg' => $t->trans(
                            'You need to login or send properly values',
                            array(),
                            'BinovoElkarBackup'
                        )
                    ));
                    return $response;
                }
            }
        }
        
        if ($trustable) {
            if (! isset($job)) {
                $job = $this->getDoctrine()
                    ->getRepository('BinovoElkarBackupBundle:Job')
                    ->find($idJob);
                if (null == $job) {
                    throw $this->createNotFoundException($this->trans('Unable to find Job entity:') . $idJob);
                }
            }
            $em = $this->getDoctrine()->getManager();
            $context = array(
                'link' => $this->generateJobRoute($idJob, $idClient),
                'source' => Globals::STATUS_REPORT
            );
            $isQueueIn = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Queue')
            ->findBy(array('job' => $job));
            if(! $isQueueIn) {
                $status = 'QUEUED';
                $queue = new Queue($job);
                $em->persist($queue);
                $response = new JsonResponse(array(
                    'error' => false,
                    'msg' => $t->trans(
                        'Job queued successfully. It will start running in less than a minute!',
                        array(),
                        'BinovoElkarBackup'
                        ),
                    'data' => array($idJob)
                ));
                $this->info($status, array(), $context);
                
            } else {
                $status = 'The job has been already enqueued, it will not be enqueued again';
                $response = new JsonResponse(array(
                    'error' => true,
                    'msg' => $t->trans(
                        'One or more jobs were already enqueued, they will not be enqueued again',
                        array(),
                        'BinovoElkarBackup'
                        ),
                    'data' => array($idJob)
                ));
                $this->warn($status, array(), $context);
            }
            $em->flush();
            return $response;
        }
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/abort", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="abortJob")
     *
     * @method ("POST")
     *         @Template()
     */
    public function runAbortAction(Request $request, $idClient, $idJob)
    {
        $t = $this->translator;
        $manager = $this->getDoctrine()->getManager();
        
        $job = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Job')
            ->find($idJob);
        $queue = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Queue')
            ->findOneBy(array('job' => $job));
        if (null == $job) {
            throw $this->createNotFoundException($this->trans('Unable to find Job entity:') . $idJob);
        }
        if (null == $queue) {
            $response = new JsonResponse(array(
                'error' => true,
                'msg' => $t->trans(
                    'The requested job does not exists, it has probably ended.',
                    array(),
                    'BinovoElkarBackup'
                    ),
                'action' => 'callbackJobAborting',
                'data' => array($idJob)
            ));
        } else {
            $queue->setAborted(true);
            $queue->setPriority(0);
            
            $response = new JsonResponse(array(
                'error' => false,
                'msg' => $t->trans(
                    'Job stop requested: aborting job',
                    array(),
                    'BinovoElkarBackup'
                    ),
                'action' => 'callbackJobAborting',
                'data' => array($idJob)
            ));
        }
        

        $manager->flush();
        return $response;
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/config", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="showJobConfig")
     *
     * @method ("GET")
     *         @Template()
     */
    public function showJobConfigAction(Request $request, $idClient, $idJob)
    {
        $t = $this->translator;        
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
        $job = $repository->find($idJob);
        if (null == $job || $job->getClient()->getId() != $idClient) {
            throw $this->createNotFoundException(
                $t->trans(
                    'Unable to find Job entity: ',
                    array(),
                    'BinovoElkarBackup'
                ) 
                . $idClient . " " . $idJob
            );
        }
        $backupDir = $job->getBackupLocation()->getEffectiveDir();
        $client = $job->getClient();
        $logDir = $this->container->get('kernel')->getLogDir();
        $tmpDir = $this->container->getParameter('tmp_dir');
        $sshArgs = $client->getSshArgs();
        $rsyncShortArgs = $client->getRsyncShortArgs();
        $rsyncLongArgs = $client->getRsyncLongArgs();
        $url = $job->getUrl();
        $idJob = $job->getId();
        $policy = $job->getPolicy();
        $retains = $policy->getRetains();
        $includes = array();
        $include = $job->getInclude();
        if ($include) {
            $includes = explode("\n", $include);
            foreach ($includes as &$theInclude) {
                $theInclude = str_replace('\ ', '?', trim($theInclude));
            }
        }
        $excludes = array();
        $exclude = $job->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
            foreach ($excludes as &$theExclude) {
                $theExclude = str_replace('\ ', '?', trim($theExclude));
            }
        }
        $syncFirst = $policy->getSyncFirst();
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $this->info(
            'Show job config %clientid%, job %jobid%',
            array('%clientid%' => $idClient,'%jobid%' => $idJob),
            array('link' => $this->generateJobRoute($idJob, $idClient))
        );
        $this->getDoctrine()->getManager()->flush();
        $preCommand = '';
        $postCommand = '';
        foreach ($job->getPreScripts() as $script) {
            $preCommand = $preCommand . "\n" . sprintf('/usr/bin/env ELKARBACKUP_LEVEL="JOB" ELKARBACKUP_EVENT="PRE" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" sudo "%s" 2>&1', $url, $idJob, $job->getSnapshotRoot(), 0, $script->getScriptPath());
        }
        foreach ($job->getPostScripts() as $script) {
            $postCommand = $postCommand . "\n" . sprintf('/usr/bin/env ELKARBACKUP_LEVEL="JOB" ELKARBACKUP_EVENT="POST" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" sudo "%s" 2>&1', $url, $idJob, $job->getSnapshotRoot(), 0, $script->getScriptPath());
        }
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:rsnapshotconfig.txt.twig',
            array(
                'cmdPreExec' => $preCommand,
                'cmdPostExec' => $postCommand,
                'excludes' => $excludes,
                'idClient' => sprintf('%04d', $idClient),
                'idJob' => sprintf('%04d', $idJob),
                'includes' => $includes,
                'backupDir' => $backupDir,
                'retains' => $retains,
                'tmp' => $tmpDir,
                'snapshotRoot' => $job->getSnapshotRoot(),
                'syncFirst' => $syncFirst,
                'url' => $url,
                'useLocalPermissions' => $job->getUseLocalPermissions(),
                'sshArgs' => $sshArgs,
                'rsyncShortArgs' => $rsyncShortArgs,
                'rsyncLongArgs' => $rsyncLongArgs,
                'logDir' => $logDir
            ),
            $response
        );
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}", requirements={"idClient" = "\d+", "idJob" = "\d+"}, defaults={"idJob" = "-1"}, name="saveJob")
     *
     * @method ("POST")
     *         @Template()
     */
    public function saveJobAction(Request $request, $idClient, $idJob)
    {
        $t = $this->translator;
        if ("-1" === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Client')
                ->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($t->trans(
                    'Unable to find Client entity:',
                    array(),
                    'BinovoElkarBackup'
                ) . $idClient);
            }
            $job->setClient($client);
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
            $job = $repository->find($idJob);
        }
        $form = $this->createForm(
            JobType::class,
            $job,
            array('translator' => $t)
        );
        $form->handleRequest($request);
        if ($form->isValid()) {
            $job = $form->getData();
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($job);
                $this->info(
                    'Save client %clientid%, job %jobid%',
                    array(
                        '%clientid%' => $job->getClient()->getId(),
                        '%jobid%' => $job->getId()
                    ), 
                    array('link' => $this->generateJobRoute(
                        $job->getId(),
                        $job->getClient()->getId()
                    ))
                );
                $em->flush();
            } catch (Exception $e) {
                $this->get('session')->getFlashBag()->add('job', $t->trans(
                    'Unable to save your changes: %extrainfo%',
                    array('%extrainfo%' => $e->getMessage()),
                    'BinovoElkarBackup'
                ));
            }
            
            return $this->redirect($this->generateUrl('showClients'));
        } else {
            
            return $this->render(
                'BinovoElkarBackupBundle:Default:job.html.twig',
                array('form' => $form->createView())
            );
        }
    }

        /**
     * @Route("/client/{idClient}/job/{idJob}/backup/{action}/{idBackupLocation}/{path}", requirements={"idClient" = "\d+", "idJob" = "\d+", "path" = ".*", "action" = "view|download|downloadzip", "idBackupLocation" = "\d+"}, defaults={"path" = "/", "idBackupLocation" = 0}, name="showJobBackup")
     *
     * @method ("GET")
     */
    public function showJobBackupAction(Request $request, $idClient, $idJob, $action, $idBackupLocation, $path)
    {
        if ($this->checkPermissions($idClient) == False) {
            return $this->redirect($this->generateUrl('showClients'));
        }
        $t = $this->translator;
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
        $job = $repository->find($idJob);
        if ($job->getClient()->getId() != $idClient) {
            throw $this->createNotFoundException($t->trans(
                'Unable to find Job entity: ',
                array(),
                'BinovoElkarBackup'
            ) . $idClient . " " . $idJob);
        }
        if (0 != $idBackupLocation) {
            $backupLocation = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:BackupLocation')
            ->find($idBackupLocation);
            $backupDir = sprintf(
                '%s/%04d/%04d',
                $backupLocation->getEffectiveDir(),
                $job->getClient()->getId(),
                $job->getId()
            );
            
            $snapshotRoot = realpath($backupDir);
            $realPath = realpath($snapshotRoot . '/' . $path);
            $backupDirectories = array();
            $jobPath = array(
                $backupLocation,
                $backupDir
            );
            if (file_exists($jobPath[1])) {
                array_push($backupDirectories, $jobPath);
            }
            
            if (false == $realPath) {
                throw $this->createNotFoundException($t->trans(
                    'Path not found:',
                    array(),
                    'BinovoElkarBackup'
                    ) . $path);
            }
            if (0 !== strpos($realPath, $snapshotRoot)) {
                throw $this->createNotFoundException($t->trans(
                    'Path not found:',
                    array(),
                    'BinovoElkarBackup'
                    ) . $path);
            }
            if (is_dir($realPath)) {
                if ('download' == $action) {
                    $headers = array(
                        'Content-Type' => 'application/x-gzip',
                        'Content-Disposition' => sprintf(
                            'attachment; filename="%s.tar.gz"',
                            basename($realPath)
                            )
                    );
                    $f = function () use ($realPath) {
                        $command = sprintf(
                            'cd "%s"; tar zc "%s"',
                            dirname($realPath),
                            basename($realPath)
                            );
                        passthru($command);
                    };
                    $this->info(
                        'Download backup directory %clientid%, %jobid% %path%',
                        array('%clientid%' => $idClient,'%jobid%' => $idJob,'%path%' => $path),
                        array('link' => $this->generateUrl('showJobBackup', array(
                            'action' => $action,
                            'idClient' => $idClient,
                            'idJob' => $idJob,
                            'path' => $path
                        ))));
                    $this->getDoctrine()->getManager()->flush();
                    
                    return new StreamedResponse($f, 200, $headers);
                } elseif ('downloadzip' == $action) {
                    $headers = array(
                        'Content-Type' => 'application/zip',
                        'Content-Disposition' => sprintf(
                            'attachment; filename="%s.zip"',
                            basename($realPath)
                            )
                    );
                    $f = function () use ($realPath) {
                        $command = sprintf(
                            'cd "%s"; zip -r - "%s"',
                            dirname($realPath),
                            basename($realPath)
                            );
                        passthru($command);
                    };
                    $this->info(
                        'Download backup directory %clientid%, %jobid% %path%',
                        array('%clientid%' => $idClient,'%jobid%' => $idJob,'%path%' => $path),
                        array('link' => $this->generateUrl('showJobBackup', array(
                            'action' => $action,
                            'idClient' => $idClient,
                            'idJob' => $idJob,
                            'path' => $path
                        )))
                        );
                    $this->getDoctrine()->getManager()->flush();
                    
                    return new StreamedResponse($f, 200, $headers);
                } else {
                    // Check if Zip is in the user path
                    exec('which zip', $cmdretval);
                    $isZipInstalled = $cmdretval;
                    $dirContent = array();
                    $content = scandir($realPath);
                    if (false === $content) {
                        $content = array();
                    }
                    foreach ($content as &$aFile) {
                        $date = new \DateTime();
                        $date->setTimestamp(filemtime($realPath . '/' . $aFile));
                        $aFile = array(
                            $aFile,
                            $date,
                            is_dir($realPath . '/' . $aFile),
                            is_link($realPath . '/' . $aFile)
                        );
                    }
                    array_push($dirContent, $content);
                    $this->debug(
                        'View backup directory %clientid%, %jobid% %path%',
                        array('%clientid%' => $idClient,'%jobid%' => $idJob, '%idBackupLocation%' => $idBackupLocation, '%path%' => $path),
                        array('link' => $this->generateUrl('showJobBackup', array(
                            'action' => $action,
                            'idClient' => $idClient,
                            'idJob' => $idJob,
                            'idBackupLocation' => $idBackupLocation,
                            'path' => $path
                        )))
                        );
                    $this->getDoctrine()->getManager()->flush();
                    
                    $params = array(
                        'dirContent' => $dirContent,
                        'job' => $job,
                        'path' => $path,
                        'realPath' => $realPath,
                        'isZipInstalled' => $isZipInstalled,
                        'backupDirectories' => $backupDirectories,
                        'idBackupLocation' => $idBackupLocation
                    );
                    return $this->render('BinovoElkarBackupBundle:Default:directory.html.twig', $params);
                }
            } else {
                $response = new BinaryFileResponse($realPath);
                $this->info('Download backup file %clientid%, %jobid% %path%', array(
                    '%clientid%' => $idClient,
                    '%jobid%' => $idJob,
                    '%path%' => $path
                ), array('link' => $this->generateUrl('showJobBackup', array(
                    'action' => $action,
                    'idClient' => $idClient,
                    'idJob' => $idJob,
                    'path' => $path
                ))));
                $this->getDoctrine()->getManager()->flush();
                
                return $response;
            }
        } else {
            // Check if Zip is in the user path
            exec('which zip', $cmdretval);
            $isZipInstalled = $cmdretval;
            
            $backupDirectories = $this->findBackups($job);
            $dirContent = array();
            foreach ($backupDirectories as $backupDir) {
                $content = scandir($backupDir[1]);
                if (false === $content) {
                    $content = array();
                }
                foreach ($content as &$aFile) {
                    $date = new \DateTime();
                    $date->setTimestamp(filemtime($backupDir[1] . '/' . $aFile));
                    $aFile = array(
                        $aFile,
                        $date,
                        is_dir($backupDir[1] . '/' . $aFile),
                        is_link($backupDir[1] . '/' . $aFile)
                    );
                }
                array_push($dirContent, $content);
            }
            $this->debug(
                'View backup directory %clientid%, %jobid% %path%',
                array('%clientid%' => $idClient,'%jobid%' => $idJob),
                array('link' => $this->generateUrl('showJobBackup', array(
                    'action' => $action,
                    'idClient' => $idClient,
                    'idJob' => $idJob,
                )))
            );
            $this->getDoctrine()->getManager()->flush();
            
            $params = array(
                'dirContent' => $dirContent,
                'job' => $job,
                'isZipInstalled' => $isZipInstalled,
                'path' => '',
                'backupDirectories' => $backupDirectories
            );
            return $this->render('BinovoElkarBackupBundle:Default:directory.html.twig', $params);

        }
    }

    /**
     * @Route("/", name="home")
     * @Template()
     */
    public function homeAction(Request $request)
    {
        return $this->redirect($this->generateUrl('showClients'));
    }

    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name
        );
    }

    /**
     * @Route("/policy/{id}", name="editPolicy")
     *
     * @method ("GET")
     *         @Template()
     */
    public function editPolicyAction(Request $request, $id = 'new')
    {
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        if ('new' === $id) {
            $policy = new Policy();
        } else {
            $policy = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Policy')
                ->find($id);
        }
        $form = $this->createForm(
            PolicyType::class,
            $policy,
            array('translator' => $t)
        );
        $this->debug(
            'View policy %policyname%',
            array('%policyname%' => $policy->getName()), 
            array('link' => $this->generatePolicyRoute($policy->getId()))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:policy.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/policy/{id}/delete", name="deletePolicy")
     *
     * @method ("POST")
     *         @Template()
     */
    public function deletePolicyAction(Request $request, $id)
    {
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Policy');
        $manager = $db->getManager();
        $policy = $repository->find($id);
        try {
            $manager->remove($policy);
            $this->info(
                'Delete policy %policyname%',
                array('%policyname%' => $policy->getName()),
                array('link' => $this->generatePolicyRoute($id))
            );
            $manager->flush();
        } catch (PDOException $e) {
            $this->get('session')->getFlashBag()->add('showPolicies', $t->trans(
                'Removing the policy %name% failed. Check that it is not in use.',
                array('%name%' => $policy->getName()), 
                'BinovoElkarBackup'
            ));
        }
        
        return $this->redirect($this->generateUrl('showPolicies'));
    }

    /**
     * @Route("/policy/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="savePolicy")
     *
     * @method ("POST")
     *         @Template()
     */
    public function savePolicyAction(Request $request, $id)
    {
        $t = $this->translator;
        if ("-1" === $id) {
            $policy = new Policy();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Policy');
            $policy = $repository->find($id);
        }
        $form = $this->createForm(
            PolicyType::class,
            $policy,
            array('translator' => $t)
        );
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($policy);
            $this->info(
                'Save policy %policyname%',
                array('%policyname%' => $policy->getName()),
                array('link' => $this->generatePolicyRoute($id))
            );
            $em->flush();
            
            return $this->redirect($this->generateUrl('showPolicies'));
        } else {
            
            return $this->render(
                'BinovoElkarBackupBundle:Default:policy.html.twig',
                array('form' => $form->createView())
            );
        }
    }

    /**
     * @Route("/jobs/sort", name="sortJobs")
     * @Template()
     */
    public function sortJobsAction(Request $request)
    {
        $user = $this->security->getToken()->getUser();
        $actualuserid = $user->getId();
        
        $t = $this->translator;
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
        
        $query = $repository->createQueryBuilder('j')
            ->innerJoin('j.client', 'c')
            ->addOrderBy('j.priority', 'ASC');
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // Non-admin users only can sort their own jobs
            $query->where('j.isActive <> 0 AND c.isActive <> 0 AND c.owner = ?1'); // adding users and roles
            $query->setParameter(1, $actualuserid);
        }
        $jobs = $query->getQuery()->getResult();
        ;
        
        $formBuilder = $this->createFormBuilder(array('jobs' => $jobs));
        $formBuilder->add('jobs', CollectionType::class, array('entry_type' => JobForSortType::class));
        $form = $formBuilder->getForm();
        if ($request->isMethod('POST')) {
            $i = 1;
            foreach ($_POST['form']['jobs'] as $jobId) {
                $jobId = $jobId['id'];
                $job = $repository->findOneById($jobId);
                $job->setPriority($i);
                ++ $i;
            }
            $this->info(
                'Jobs reordered',
                array(),
                array('link' => $this->generateUrl('showClients'))
            );
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add(
                'sortJobs',
                $t->trans('Jobs prioritized', array(), 'BinovoElkarBackup')
            );
            $result = $this->redirect($this->generateUrl('sortJobs'));
        } else {
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:sortjobs.html.twig',
                array('form' => $form->createView())
            );
        }
        
        return $result;
    }

    public function getFsSize($path)
    {
        $size = (int) shell_exec(sprintf("df -k '%s' | tail -n1 | awk '{ print $2 }' | head -c -2", $path));
        return $size;
    }

    public function getFsUsed($path)
    {
        $size = (float) shell_exec(sprintf("df -k '%s' | tail -n1 | awk '{ print $3 }' | head -c -2", $path));
        return $size;
    }

    /**
     * @Route("/clients", name="showClients")
     * @Template()
     */
    public function showClientsAction(Request $request)
    {
        $user = $this->security->getToken()->getUser();
        $actualuserid = $user->getId();
        
        $fsDiskUsage = (int) round(
            $this->getFsUsed('/') * 100 / $this->getFsSize('/'),
            0,
            PHP_ROUND_HALF_UP
        );
        
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
        $query = $repository->createQueryBuilder('c')->addOrderBy('c.id', 'ASC');
        
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // Limited view for non-admin users
            $query->where('c.owner = ?1'); // adding users and roles
            $query->setParameter(1, $actualuserid);
        }
        $query->getQuery();
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
        );
        
        foreach ($pagination as $i => $client) {
            $client->setLogEntry($this->getLastLogForLink('%/client/' . $client->getId()));
            foreach ($client->getJobs() as $job) {
                $job->setLogEntry($this->getLastLogForLink(
                    '%/client/' . $client->getId() . '/job/' . $job->getId()
                ));
            }
        }
        $this->debug(
            'View clients',
            array(),
            array('link' => $this->generateUrl('showClients'))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:clients.html.twig',
            array('pagination' => $pagination,'fsDiskUsage' => $fsDiskUsage)
        );
    }
    
    /**
     * @Route("/status", name="showStatus")
     * @Template()
     */
    public function showStatusAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Queue');
        $query = $repository
            ->createQueryBuilder('c')
            ->orderBy('c.date ASC, c.priority')
            ->getQuery();
        $clients = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client')->findAll();
            
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
            );
        
        $this->debug(
            'View backup status',
            array(),
            array('link' => $this->generateUrl('showStatus'))
            );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:status.html.twig',
            array('pagination' => $pagination, 'clients' => $clients)
        );
    }

    /**
     * @Route("/scripts", name="showScripts")
     * @Template()
     */
    public function showScriptsAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Script');
        $query = $repository->createQueryBuilder('c')->getQuery();
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
        );
        $this->debug(
            'View scripts',
            array(),
            array('link' => $this->generateUrl('showScripts'))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:scripts.html.twig',
            array('pagination' => $pagination)
        );
    }

    public function getLastLogForLink($link)
    {
        $lastLog = null;
        $em = $this->getDoctrine()->getManager();
        // :WARNING: this call might end up slowing things too much.
        $dql = <<<EOF
SELECT l
FROM  BinovoElkarBackupBundle:LogRecord l
WHERE l.source = :source AND l.link LIKE :link
ORDER BY l.id DESC
EOF;
        $query = $em->createQuery($dql)
            ->setParameter('link', $link)
            ->setParameter('source', Globals::STATUS_REPORT)
            ->setMaxResults(1);
        $logs = $query->getResult();
        if (count($logs) > 0) {
            $lastLog = $logs[0];
        }
        
        return $lastLog;
    }

    /**
     * @Route("/logs", name="showLogs")
     * @Template()
     */
    public function showLogsAction(Request $request)
    {
        $formValues = array();
        $t = $this->translator;
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:LogRecord');
        $queryBuilder = $repository->createQueryBuilder('l')->addOrderBy('l.id', 'DESC');
        $queryParamCounter = 1;
        
        $filter = $request->get('filter');
        if (! $filter ){
            // Default log level = 200 (Notices and up)
            $filter['gte']['l.level'] = Job::NOTIFICATION_LEVEL_INFO;
        }
        $queryBuilder->where("1 = 1");
        foreach ($filter as $op => $filterValues) {
            if (! in_array($op, array('gte','eq','like'))) {
                $op = 'eq';
            }
            foreach ($filterValues as $columnName => $value) {
                if ($value) {
                    $queryBuilder->andWhere($queryBuilder->expr()->$op(
                        $columnName,
                        "?$queryParamCounter"
                    ));
                    if ('like' == $op) {
                        $queryBuilder->setParameter($queryParamCounter, '%' . $value . '%');
                    } else {
                        $queryBuilder->setParameter($queryParamCounter, $value);
                    }
                    ++ $queryParamCounter;
                    $formValues["filter[$op][$columnName]"] = $value;
                }
            }
        }
        
        $query = $queryBuilder->getQuery();
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
        );
        $this->debug(
            'View logs',
            array(),
            array('link' => $this->generateUrl('showLogs'))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:logs.html.twig',
            array(
                'pagination' => $pagination,
                'levels' => array(
                    'options' => array(
                        Job::NOTIFICATION_LEVEL_ALL => $t->trans(
                            'All messages',
                            array(),
                            'BinovoElkarBackup'
                        ),
                        Job::NOTIFICATION_LEVEL_INFO => $t->trans(
                            'Notices and up',
                            array(),
                            'BinovoElkarBackup'
                        ),
                        Job::NOTIFICATION_LEVEL_WARNING => $t->trans(
                            'Warnings and up',
                            array(),
                            'BinovoElkarBackup'
                        ),
                        Job::NOTIFICATION_LEVEL_ERROR => $t->trans(
                            'Errors and up',
                            array(),
                            'BinovoElkarBackup'
                        ),
                        Job::NOTIFICATION_LEVEL_NONE => $t->trans(
                            'None',
                            array(),
                            'BinovoElkarBackup'
                        )
                    ),
                    'value' => isset($formValues['filter[gte][l.level]']) ? $formValues['filter[gte][l.level]'] : null,'name' => 'filter[gte][l.level]'
                ),
                'object' => array(
                    'value' => isset($formValues['filter[like][l.link]']) ? $formValues['filter[like][l.link]'] : null,
                    'name' => 'filter[like][l.link]'
                ),
                'source' => array(
                    'options' => array(
                        '' => $t->trans('All', array(), 'BinovoElkarBackup'),
                        'DefaultController' => 'DefaultController',
                        'GenerateKeyPairCommand' => 'GenerateKeyPairCommand',
                        'RunJobCommand' => 'RunJobCommand',
                        Globals::STATUS_REPORT => 'StatusReport',
                        'TickCommand' => 'TickCommand',
                        'UpdateAuthorizedKeysCommand' => 'UpdateAuthorizedKeysCommand'
                    ),
                    'value' => isset($formValues['filter[eq][l.source]']) ? $formValues['filter[eq][l.source]'] : null,'name' => 'filter[eq][l.source]'
                )
            )
        );
    }

    /**
     * @Route("/policies", name="showPolicies")
     * @Template()
     */
    public function showPoliciesAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Policy');
        $query = $repository->createQueryBuilder('c')->getQuery();
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
        );
        $this->debug(
            'View policies',
            array(),
            array('link' => $this->generateUrl('showPolicies'))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:policies.html.twig',
            array('pagination' => $pagination)
        );
    }

    /**
     * @Route("/config/repositorybackupscript/download", name="getRepositoryBackupScript")
     * @method ("POST")
     * @Template()
     */
    public function getRepositoryBackupScriptAction(Request $request)
    {
        $result = $request->request->all();
        $backupLocationId = $result['form']['backup_script'];
        $backupLocation = $this
            ->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:BackupLocation')
            ->find($backupLocationId);
        $response = $this->render(
            'BinovoElkarBackupBundle:Default:copyrepository.sh.twig',
            array(
                'backupsroot'   => $backupLocation->getEffectiveDir(),
                'backupsuser'   => 'elkarbackup',
                'mysqldb'       => $this->container->getParameter('database_name'),
                'mysqlhost'     => $this->container->getParameter('database_host'),
                'mysqlpassword' => $this->container->getParameter('database_password'),
                'mysqluser'     => $this->container->getParameter('database_user'),
                'server'        => $request->getHttpHost(),
                'uploads'       => $this->container->getParameter('upload_dir')
            )
        );
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="copyrepository.sh"');
        
        return $response;
    }

    public function readKeyFileAsCommentAndRest($filename)
    {
        $keys = array();
        if (! file_exists($filename)) {
            return $keys;
        }
        foreach (explode("\n", file_get_contents($filename)) as $keyLine) {
            $matches = array();
            // the format of eacn non empty non comment line is "options keytype base64-encoded key comment" where key is one of ecdsa-sha2-nistp256, ecdsa-sha2-nistp384, ecdsa-sha2-nistp521, ssh-dss, ssh-rsa
            if (preg_match('/(.*(?:ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521|ssh-dss|ssh-rsa) *[^ ]*) *(.*)/', $keyLine, $matches)) {
                $keys[] = array('publicKey' => $matches[1],'comment' => $matches[2]);
            }
        }
        
        return $keys;
    }

    /**
     * @Route("/config/repositorybackupscript/manage", name="configureRepositoryBackupScript")
     * @Template()
     */
    public function configureRepositoryBackupScriptAction(Request $request)
    {
        $t = $this->translator;
        $params = array(
            'backup_script' => array(
                'entry_type' => EntityType::class,
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Backup Script', array(), 'BinovoElkarBackup'),
                'class'    => 'BinovoElkarBackupBundle:BackupLocation',
                'choice_label' => 'name'
            )
        );
        $defaultData = array();
        $backupScriptFormBuilder = $this->createFormBuilder($defaultData);
        foreach ($params as $paramName => $formField) {
            $backupScriptFormBuilder->add(
                $paramName,
                $formField['entry_type'],
                array_diff_key($formField, array('entry_type' => true))
            );
        }
        $backupScriptForm = $backupScriptFormBuilder->getForm();
        
        $authorizedKeysFile = dirname(
            $this->container->getParameter('public_key')
        ) . '/authorized_keys';
        $keys = $this->readKeyFileAsCommentAndRest($authorizedKeysFile);
        $authorizedKeysFormBuilder = $this->createFormBuilder(array('publicKeys' => $keys));
        $authorizedKeysFormBuilder->add(
            'publicKeys',
            CollectionType::class,
            array(
                'entry_type' => AuthorizedKeyType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'attr' => array('class' => 'form-control'),
                'entry_options' => array('required' => false,'attr' => array('class' => 'span10'), 'translator' => $t)
            )
        );
        $authorizedKeysForm = $authorizedKeysFormBuilder->getForm();
        if ($request->isMethod('POST')) {
            $authorizedKeysForm->handleRequest($request);
            $data = $authorizedKeysForm->getData();
            $serializedKeys = '';
            foreach ($data['publicKeys'] as $key) {
                $serializedKeys .= sprintf("%s %s\n", $key['publicKey'], $key['comment']);
            }
            $manager = $this->getDoctrine()->getManager();
            $msg = new Message(
                'DefaultController',
                'TickCommand',
                json_encode(array(
                    'command' => "elkarbackup:update_authorized_keys",
                    'content' => $serializedKeys
                ))
            );
            $manager->persist($msg);
            $this->info(
                'Updating key file %keys%',
                array('%keys%' => $serializedKeys)
            );
            $manager->flush();
            $this->get('session')->getFlashBag()->add(
                'backupScriptConfig',
                $t->trans(
                    'Key file updated. The update should be effective in less than 2 minutes.',
                    array(),
                    'BinovoElkarBackup'
                )
            );
            $result = $this->redirect($this->generateUrl('configureRepositoryBackupScript'));
        } else {
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:backupscriptconfig.html.twig',
                array('authorizedKeysForm' => $authorizedKeysForm->createView(), 'backupScriptForm' => $backupScriptForm->createView())
            );
        }
        
        return $result;
    }

    /**
     * @Route("/config/backupLocation/{id}", name="editBackupLocation")
     * @Template()
     */
    public function editBackupLocationAction(Request $request, $id = 'new')
    {
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        if ('new' === $id) {
            $backupLocation = new BackupLocation();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:BackupLocation');
            $backupLocation = $repository->find($id);
        }
        
        
        $form = $this->createForm(
            BackupLocationType::class,
            $backupLocation,
            array( 'fs' => $this->isAutoFsAvailable(), 'translator' => $t)
        );
        
        $this->debug(
            'View location %backupLocationName%.',
            array('%backupLocationName%' => $backupLocation->getName()),
            array('link' => $this->generateBackupLocationRoute($id))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:backuplocation.html.twig',
            array('form' => $form->createView(),'id' => $id)
        );
    }

    /**
     * @Route("/backupLocation/{id}/save", name="saveBackupLocation")
     *
     * @method ("POST")
     *         @Template()
     */
    public function saveBackupLocationAction(Request $request, $id = 'new')
    {
        $t = $this->translator;
        if ('new' === $id) {
            $backupLocation = new BackupLocation();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:BackupLocation');
            $backupLocation = $repository->find($id);
        }
        
        
        $form = $this->createForm(
            BackupLocationType::class,
            $backupLocation,
            array( 'fs' => $this->isAutoFsAvailable(), 'translator' => $t)
        );
        $form->handleRequest($request);
        $result = null;
        $location = $backupLocation->getEffectiveDir();
        if (! is_dir($location)) {
            $form->addError(new FormError($t->trans(
                'Warning: the directory does not exist',
                array(),
                'BinovoElkarBackup'
            )));
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:backuplocation.html.twig',
                array('form' => $form->createView(),'id' => $id)
            );
        } elseif ($backupLocation->getMaxParallelJobs() < 1) {
            $form->addError(new FormError($t->trans(
                'Max parallel jobs value must be a positive integer',
                array(),
                'BinovoElkarBackup'
            )));
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:backuplocation.html.twig',
                array('form' => $form->createView(),'id' => $id)
            );
            
        } elseif ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($backupLocation);
            $this->info(
                'Save backup location %locationName%.',
                array('%locationName%' => $backupLocation->getName()),
                array('link' => $this->generateBackupLocationRoute($id))
            );
            $em->flush();
            $result = $this->redirect($this->generateUrl('manageBackupLocations'));
        } else {
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:backuplocation.html.twig',
                array('form' => $form->createView(),'id' => $id)
            );
        }
        
        $this->clearCache();
        return $result;
    }

    /**
     * @Route("/backupLocation/{id}/delete", name="deleteBackupLocation")
     *
     * @method ("POST")
     *         @Template()
     */
    public function deleteBackupLocationAction(Request $request, $id)
    {
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:BackupLocation');
        $manager = $db->getManager();
        $backupLocation = $repository->find($id);
        try {
            $manager->remove($backupLocation);
            $this->info(
                'Delete backup location %locationName%',
                array('%locationName%' => $backupLocation->getName()),
                array('link' => $this->generateBackupLocationRoute($id))
            );
            $manager->flush();
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'manageBackupLocations',
                $t->trans(
                    'Removing %name% failed. Check that it is not in use.',
                    array('%name%' => $backupLocation->getName()),
                    'BinovoElkarBackup'
                )
            );
        }
        
        return $this->redirect($this->generateUrl('manageBackupLocations'));
    }

    /**
     * @Route("/config/backupLocations", name="manageBackupLocations")
     * @Template()
     */
    public function manageBackupLocationsAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:BackupLocation');
        $query = $repository->createQueryBuilder('c')->getQuery();
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
        );
        $this->debug(
            'View backup locations',
            array(),
            array('link' => $this->generateUrl('manageBackupLocations'))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:backuplocations.html.twig',
            array('pagination' => $pagination)
        );
    }

    /**
     * @Route("/config/params", name="manageParameters")
     * @Template()
     */
    public function manageParametersAction(Request $request)
    {
        $t = $this->translator;
        $params = array(
            'database_host' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('MySQL host', array(), 'BinovoElkarBackup')
            ),
            'database_port' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('MySQL port', array(), 'BinovoElkarBackup')
            ),
            'database_name' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('MySQL DB name', array(), 'BinovoElkarBackup')
            ),
            'database_user' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('MySQL user', array(), 'BinovoElkarBackup')
            ),
            'database_password' => array(
                'entry_type' => PasswordType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('MySQL password', array(), 'BinovoElkarBackup')
            ),
            'mailer_transport' => array(
                'entry_type' => ChoiceType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'choices' => array(
                    'gmail' => 'gmail',
                    'mail' => 'mail',
                    'sendmail' => 'sendmail',
                    'smtp' => 'smtp'
                ),
                'label' => $t->trans('Mailer transport', array(), 'BinovoElkarBackup')
            ),
            'mailer_host' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Mailer host', array(), 'BinovoElkarBackup')
            ),
            'mailer_user' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Mailer user', array(), 'BinovoElkarBackup')
            ),
            'mailer_password' => array(
                'entry_type' => PasswordType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Mailer password', array(), 'BinovoElkarBackup')
            ),
            'mailer_from' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Mailer from', array(), 'BinovoElkarBackup')
            ),
            'max_log_age' => array(
                'entry_type' => ChoiceType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'choices' => array(
                    $t->trans('One day', array(), 'BinovoElkarBackup') => 'P1D',
                    $t->trans('One week', array(), 'BinovoElkarBackup') => 'P1W',
                    $t->trans('Two weeks', array(), 'BinovoElkarBackup') => 'P2W',
                    $t->trans('Three weeks', array(), 'BinovoElkarBackup') => 'P3W',
                    $t->trans('A month', array(), 'BinovoElkarBackup') => 'P1M',
                    $t->trans('Six months', array(), 'BinovoElkarBackup') => 'P6M',
                    $t->trans('A year', array(), 'BinovoElkarBackup') => 'P1Y',
                    $t->trans('Two years', array(), 'BinovoElkarBackup') => 'P2Y',
                    $t->trans('Three years', array(), 'BinovoElkarBackup') => 'P3Y',
                    $t->trans('Four years', array(), 'BinovoElkarBackup') => 'P4Y',
                    $t->trans('Five years', array(), 'BinovoElkarBackup') => 'P5Y',
                    $t->trans('Never', array(), 'BinovoElkarBackup') => ''
                ),
                'label' => $t->trans('Remove logs older than', array(), 'BinovoElkarBackup')
            ),
            'warning_load_level' => array(
                'entry_type' => PercentType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Quota warning level', array(), 'BinovoElkarBackup')
            ),
            'pagination_lines_per_page' => array(
                'entry_type' => IntegerType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Records per page', array(), 'BinovoElkarBackup')
            ),
            'url_prefix' => array(
                'entry_type' => TextType::class,
                'required' => false,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Url prefix', array(), 'BinovoElkarBackup')
            ),
            'disable_background' => array(
                'entry_type' => CheckboxType::class,
                'required' => false,
                'label' => $t->trans('Disable background', array(), 'BinovoElkarBackup')
            ),
            'max_parallel_jobs' => array(
                'entry_type' => IntegerType::class,
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Max parallel jobs', array(), 'BinovoElkarBackup')
            ),
            'post_on_pre_fail' => array(
                'entry_type' => CheckboxType::class,
                'required' => false,
                'label' => $t->trans('Do post script on pre script failure', array(), 'BinovoElkarBackup')
            ),
            
        );
        $defaultData = array();
        foreach ($params as $paramName => $formField) {
            if (PasswordType::class != $formField['entry_type']) {
                $defaultData[$paramName] = $this->container->getParameter($paramName);
            }
        }
        $formBuilder = $this->createFormBuilder($defaultData);
        foreach ($params as $paramName => $formField) {
            $formBuilder->add(
                $paramName,
                $formField['entry_type'],
                array_diff_key($formField, array('entry_type' => true))
            );
        }
        $result = null;
        $form = $formBuilder->getForm();
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();
            $allOk = true;
            foreach ($data as $paramName => $paramValue) {
                $ok = true;
                if (PasswordType::class == $params[$paramName]['entry_type']) {
                    if (! empty($paramValue)) {
                        $ok = $this->setParameter(
                            $paramName,
                            $paramValue,
                            'manageParameters'
                        );
                    }
                } elseif (CheckboxType::class == $params[$paramName]['entry_type']) {
                    // Workaround to store value in boolean format
                    if (! empty($paramValue)) {
                        $ok = $this->setParameter(
                            $paramName,
                            'true',
                            'manageParameters'
                        );
                    } else {
                        $ok = $this->setParameter(
                            $paramName,
                            'false',
                            'manageParameters'
                        );
                    }
                } else {
                    if ($paramValue != $this->container->getParameter($paramName)) {
                        if ('max_parallel_jobs' == $paramName) {
                            if ($paramValue < 1) {
                                $ok = false;
                            } else {
                                $ok = $this->setParameter(
                                    $paramName,
                                    $paramValue,
                                    'manageParameters'
                                );
                            }
                        } else {
                            $ok = $this->setParameter(
                                $paramName,
                                $paramValue,
                                'manageParameters'
                            );
                        }
                    }
                }
                if (! $ok) {
                    $this->get('session')->getFlashBag()->add(
                        'manageParameters',
                        $t->trans(
                            'Error saving parameter "%param%"',
                            array('%param%' => $params[$paramName]['label']),
                            'BinovoElkarBackup'
                        )
                    );
                    $allOk = false;
                }
            }
            if ($allOk) {
                $this->get('session')->getFlashBag()->add(
                    'manageParameters',
                    $t->trans('Parameters updated', array(), 'BinovoElkarBackup')
                );
            }
            $result = $this->redirect($this->generateUrl('manageParameters'));
        } else {
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:params.html.twig',
                array(
                    'form' => $form->createView(),
                    'showKeyDownload' => file_exists(
                        $this->container->getParameter('public_key')
                    )
                )
            );
        }
        $this->getDoctrine()->getManager()->flush();
        $this->clearCache();
        
        return $result;
    }

    /**
     * Sets the value of a filed in the parameters.yml file to the given value
     */
    public function setParameter($name, $value, $from)
    {
        $paramsFilename = dirname(__FILE__) . '/../../../../app/config/parameters.yml';
        $paramsFile = file_get_contents($paramsFilename);
        if (false == $paramsFile) {
            return false;
        }
        $updated = preg_replace("/$name:.*/", "$name: $value", $paramsFile);
        $ok = file_put_contents($paramsFilename, $updated);
        if ($ok) {
            $this->info(
                'Set Parameter %paramname%',
                array('%paramname%' => $name),
                array('link' => $this->generateUrl($from))
            );
        } else {
            $this->info(
                'Warning: Parameter %paramname% not set',
                array('%paramname%' => $name),
                array('link' => $this->generateUrl($from))
            );
        }
        
        return $ok;
    }

    /**
     * @Route("/password", name="changePassword")
     * @Template()
     */
    public function changePasswordAction(Request $request)
    {
        $t = $this->translator;
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)->add(
            'oldPassword',
            PasswordType::class,
            array(
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Old password', array(), 'BinovoElkarBackup')
            )
        )
        ->add(
            'newPassword',
            PasswordType::class,
            array(
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('New password', array(), 'BinovoElkarBackup')
            )
        )
        ->add(
            'newPassword2',
            PasswordType::class,
            array(
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'label' => $t->trans('Confirm new password', array(), 'BinovoElkarBackup')
            )
        )
        ->getForm();
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();
            $user = $this->security->getToken()->getUser();
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $ok = true;
            if (empty($data['newPassword']) || $data['newPassword'] !== $data['newPassword2']) {
                $ok = false;
                $this->get('session')->getFlashBag()->add(
                    'changePassword',
                    $t->trans("Passwords do not match", array(), 'BinovoElkarBackup')
                );
                $this->info(
                    'Change password for user %username% failed. Passwords do not match.',
                    array('%username%' => $user->getUsername()),
                    array('link' => $this->generateUserRoute($user->getId()))
                );
            }
            if ($encoder->encodePassword($data['oldPassword'], $user->getSalt()) !== $user->getPassword()) {
                $ok = false;
                $this->get('session')->getFlashBag()->add(
                    'changePassword',
                    $t->trans("Wrong old password", array(), 'BinovoElkarBackup')
                );
                $this->info(
                    'Change password for user %username% failed. Wrong old password.',
                    array('%username%' => $user->getUsername()),
                    array('link' => $this->generateUserRoute($user->getId()))
                );
            }
            if ($ok) {
                $user->setPassword($encoder->encodePassword(
                    $data['newPassword'],
                    $user->getSalt()
                ));
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $this->get('session')->getFlashBag()->add(
                    'changePassword',
                    $t->trans("Password changed", array(), 'BinovoElkarBackup')
                );
                $this->info(
                    'Change password for user %username%.',
                    array('%username%' => $user->getUsername()),
                    array('link' => $this->generateUserRoute($user->getId()))
                );
                $manager->flush();
            }
            
            return $this->redirect($this->generateUrl('changePassword'));
        } else {
            
            return $this->render(
                'BinovoElkarBackupBundle:Default:password.html.twig',
                array('form' => $form->createView())
            );
        }
    }

    /**
     * @Route("/log/{id}/download", name="downloadLog")
     *
     * @method ("GET")
     *         @Template()
     */
    public function downloadLogAction(Request $request, $id)
    {
        $t = $this->translator;
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:LogRecord');
        $manager = $db->getManager();
        $log = $repository->findOneById($id);
        if (null == $log) {
            throw $this->createNotFoundException($t->trans(
                'Log "%id%" not found',
                array('%id%' => $id),
                'BinovoElkarBackup'
            ));
        }
        
        $response = new Response();
        if (is_file($log->getLogfilePath())) {
            // Logfile exists
            $this->info(
                'Download log %logfile%',
                array('%logfile%' => $log->getLogfile()),
                array('link' => $this->generateUrl('downloadLog', array('id' => $id)))
            );
            $manager->flush();
            
            $response->setContent(file_get_contents($log->getLogfilePath()));
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set(
                'Content-Disposition',
                $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $log->getLogfile()
                )
            );
        } elseif (is_file($log->getLogFilePath() . ".gz")) {
            // Logfile is compressed (gz)
            $this->info(
                'Download log %logfile%.gz',
                array('%logfile%' => $log->getLogfile()),
                array('link' => $this->generateUrl('downloadLog', array('id' => $id)))
            );
            $manager->flush();
            
            $response->setContent(file_get_contents($log->getLogfilePath() . ".gz"));
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set(
                'Content-Disposition',
                $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $log->getLogfile() . ".gz"
                )
            );
        } else {
            // Logfile does not exist
            $this->info(
                'Log not found: %logfile%',
                array('%logfile%' => $log->getLogfilePath()),
                array('link' => $this->generateUrl('downloadLog', array('id' => $id)))
            );
            $manager->flush();
            $response = $this->redirect($this->generateUrl('showLogs'));
        }
        
        return $response;
    }

    /**
     * @Route("/script/{id}/delete", name="deleteScript")
     *
     * @method ("POST")
     *         @Template()
     */
    public function deleteScriptAction(Request $request, $id)
    {
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Script');
        $manager = $db->getManager();
        $script = $repository->find($id);
        try {
            $manager->remove($script);
            $this->info(
                'Delete script %scriptname%',
                array('%scriptname%' => $script->getName()),
                array('link' => $this->generateScriptRoute($id))
            );
            $manager->flush();
        } catch (PDOException $e) {
            $this->get('session')->getFlashBag()->add(
                'showScripts',
                $t->trans(
                    'Removing the script %name% failed. Check that it is not in use.',
                    array('%name%' => $script->getName()),
                    'BinovoElkarBackup'
                )
            );
        }
        
        return $this->redirect($this->generateUrl('showScripts'));
    }

    /**
     * @Route("/script/{id}/download", name="downloadScript")
     *
     * @method ("GET")
     *         @Template()
     */
    public function downloadScriptAction(Request $request, $id)
    {
        $t = $this->translator;
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Script');
        $manager = $db->getManager();
        $script = $repository->findOneById($id);
        if (null == $script) {
            throw $this->createNotFoundException($t->trans(
                'Script "%id%" not found',
                array('%id%' => $id),
                'BinovoElkarBackup'
            ));
        }
        $this->info(
            'Download script %scriptname%',
            array('%scriptname%' => $script->getName()),
            array('link' => $this->generateScriptRoute($id))
        );
        $manager->flush();
        $response = new Response();
        $response->setContent(file_get_contents($script->getScriptPath()));
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $script->getName()
            )
        );
        
        return $response;
    }

    /**
     * @Route("/script/{id}", name="editScript")
     *
     * @method ("GET")
     *         @Template()
     */
    public function editScriptAction(Request $request, $id)
    {
        if (! $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }
        
        $t = $this->translator;
        if ('new' === $id) {
            $script = new Script();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Script');
            $script = $repository->find($id);
        }
        $form = $this->createForm(
            ScriptType::class,
            $script,
            array(
                'scriptFileRequired' => ! $script->getScriptFileExists(),
                'translator' => $t
            )
        );
        $this->debug(
            'View script %scriptname%.',
            array('%scriptname%' => $script->getName()),
            array('link' => $this->generateScriptRoute($id))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:script.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/script/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveScript")
     *
     * @method ("POST")
     *         @Template()
     */
    public function saveScriptAction(Request $request, $id)
    {
        $t = $this->translator;
        if ("-1" === $id) {
            $script = new Script();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Script');
            $script = $repository->find($id);
        }
        $form = $this->createForm(
            ScriptType::class,
            $script,
            array(
                'scriptFileRequired' => ! $script->getScriptFileExists(),
                'translator' => $t
            )
        );
        $form->handleRequest($request);
        $result = null;
        if ($form->isValid()) {
            if ("-1" == $id && null == $script->getScriptFile()) { // it is a new script but no file was uploaded
                $this->get('session')->getFlashBag()->add(
                    'editScript',
                    $t->trans(
                        'Uploading a script is mandatory for script creation.',
                        array(),
                        'BinovoElkarBackup'
                    )
                );
            } else {
                $em = $this->getDoctrine()->getManager();
                $script->setLastUpdated(new DateTime()); // we to this to force the PostPersist script to run.
                $em->persist($script);
                $this->info(
                    'Save script %scriptname%.',
                    array('%scriptname%' => $script->getScriptname()),
                    array('link' => $this->generateScriptRoute($id))
                );
                $em->flush();
                $result = $this->redirect($this->generateUrl('showScripts'));
            }
        }
        if (! $result) {
            $result = $this->render(
                'BinovoElkarBackupBundle:Default:script.html.twig',
                array('form' => $form->createView())
            );
        }
        
        return $result;
    }

    /**
     * @Route("/user/{id}/delete", name="deleteUser")
     *
     * @method ("POST")
     *         @Template()
     */
    public function deleteUserAction(Request $request, $id)
    {
        if (User::SUPERUSER_ID != $id) {
            $db = $this->getDoctrine();
            $repository = $db->getRepository('BinovoElkarBackupBundle:User');
            $manager = $db->getManager();
            $user = $repository->find($id);
            $manager->remove($user);
            $this->info(
                'Delete user %username%.',
                array('%username%' => $user->getUsername()),
                array('link' => $this->generateUserRoute($id))
            );
            $manager->flush();
        }
        
        return $this->redirect($this->generateUrl('showUsers'));
    }

    /**
     * @Route("/user/{id}", name="editUser")
     *
     * @method ("GET")
     *         @Template()
     */
    public function editUserAction(Request $request, $id)
    {
        $t = $this->translator;
        if ('new' === $id) {
            $user = new User();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:User');
            $user = $repository->find($id);
        }
        $form = $this->createForm(UserType::class, $user, array('translator' => $t));
        $this->debug(
            'View user %username%.',
            array('%username%' => $user->getUsername()),
            array('link' => $this->generateUserRoute($id))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:user.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/user/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveUser")
     *
     * @method ("POST")
     *         @Template()
     */
    public function saveUserAction(Request $request, $id)
    {
        $t = $this->translator;
        if ("-1" === $id) {
            $user = new User();
        } else {
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:User');
            $user = $repository->find($id);
        }
        $form = $this->createForm(UserType::class, $user, array('translator' => $t));
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($user->newPassword) {
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $password = $encoder->encodePassword($user->newPassword, $user->getSalt());
                $user->setPassword($password);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $this->info(
                'Save user %username%.',
                array('%username%' => $user->getUsername()),
                array('link' => $this->generateUserRoute($id))
            );
            $em->flush();
            
            return $this->redirect($this->generateUrl('showUsers'));
        } else {
            
            return $this->render(
                'BinovoElkarBackupBundle:Default:user.html.twig',
                array('form' => $form->createView())
            );
        }
    }

    /**
     * @Route("/setlocale/{locale}", name="setLocale")
     */
    public function setLanguage(Request $request, $locale)
    {
        $this->get('session')->set('_locale', $locale);
        $referer = $request->headers->get('referer');
        
        return $this->redirect($referer);
    }

    /**
     * @Route("/users", name="showUsers")
     * @Template()
     */
    public function showUsersAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:User');
        $query = $repository->createQueryBuilder('c')->getQuery();
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
        );
        $this->debug(
            'View users',
            array(),
            array('link' => $this->generateUrl('showUsers'))
        );
        $this->getDoctrine()->getManager()->flush();
        
        return $this->render(
            'BinovoElkarBackupBundle:Default:users.html.twig',
            array('pagination' => $pagination)
        );
    }

    protected function checkPermissions($idClient, $idJob = null)
    {
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
        $client = $repository->find($idClient);
        
        if ($client->getOwner() == $this->security->getToken()->getUser() || 
            $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return True;
        } else {
            return False;
        }
    }

    /**
     * @Route("/client/clone/{idClient}", requirements={"idClient" = "\d+"}, defaults={"id" = "-1"}, name="cloneClient")
     *
     * @method ("POST")
     *         @Template()
     */
    public function cloneClientAction(Request $request, $idClient)
    {
        $t = $this->translator;
        $idoriginal = $idClient;
        if (null == $idClient) {
            throw $this->createNotFoundException($t->trans(
                'Unable to find Client entity:',
                array(),
                'BinovoElkarBackup'
            ) . $idClient);
        }
        
        $clientrow = array();
        try {
            // CLONE CLIENT
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
            $client = $repository->find($idoriginal);
            if (null == $client) {
                throw $this->createNotFoundException($t->trans(
                    'Unable to find Client entity:',
                    array(),
                    'BinovoElkarBackup'
                ) . $client);
            }
            
            $newname = $client->getName() . "-cloned1";
            while ($repository->findOneByName($newname)) {
                $newname ++;
            }
            
            $new = clone $client;
            $new->setName($newname);
            $newem = $this->getDoctrine()->getManager();
            $newem->persist($new);
            $newem->flush();
            $newem->detach($new);
            
            $idnew = $new->getId();
            
            // CLONE JOBS
            
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
            $jobs = $repository->findBy(array('client' => $idoriginal
            ));
            
            foreach ($jobs as $job) {
                $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
                $client = $repository->find($idnew);
                
                $newjob = clone $job;
                $newjob->setClient($client);
                $newjob->setDiskUsage(0);
                $newjob->setLastResult('');
                $newem = $this->getDoctrine()->getManager();
                $newem->persist($newjob);
                $newem->flush();
                $newem->detach($newjob);
            }
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'clone',
                $t->trans(
                    'Unable to clone your client: %extrainfo%',
                    array('%extrainfo%' => $e->getMessage()),
                    'BinovoElkarBackup'
                )
            );
        }
        
        // $response = new Response($t->trans('Client cloned successfully', array(), 'BinovoElkarBackup'));
        // $response->headers->set('Content-Type', 'text/plain');
        
        // Custom normalizer
        // $normalizers[] = new ClientNormalizer();
        // $normalizer = new ObjectNormalizer();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
        $normalizers[] = $normalizer;
        $encoders[] = new JsonEncoder();
        $encoders[] = new XmlEncoder();
        $serializer = new Serializer($normalizers, $encoders);
        
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
        $client = $repository->find($idnew);
        // syslog(LOG_ERR, "Obtaining first job: ".$client->getJobs()[0]->getId());
        syslog(LOG_ERR, "Serializing object: " . $client->getName());
        $json = $serializer->serialize($client, 'json');
        syslog(LOG_ERR, "Output: " . print_r($json, TRUE));
        $response = new JsonResponse(array(
            'msg' => $t->trans('Client cloned successfully', array(), 'BinovoElkarBackup'),
            'action' => 'callbackClonedClient',
            'data' => array($json)
        ));
        return $response;
    }

    /**
     * @Route("/job/generate/token/", name="generateToken")
     *
     * @method ("POST")
     *         @Template()
     */
    public function generateTokenAction(Request $request)
    {
        $t = $this->translator;
        $randtoken = md5(uniqid(rand(), true));
        
        $response = new JsonResponse(array(
            'token' => $randtoken,
            'msg' => $t->trans('New Token have been generated', array(), 'BinovoElkarBackup')
        ));
        return $response;
    }

    /**
     * @Route("/config/preferences", name="managePreferences")
     * @Template()
     */
    public function managePreferencesAction(Request $request)
    {
        $t = $this->translator;
        // Get current user
        $user = $this->security->getToken()->getUser();
        $form = $this->createForm(
            PreferencesType::class,
            $user,
            array('translator' => $t,'validation_groups' => array('preferences'))
        );
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($data);
            $this->info(
                'Save preferences for user %username%.',
                array('%username%' => $user->getUsername()),
                array('link' => $this->generateUserRoute($user->getId()))
            );
            $em->flush();
            
            $language = $form['language']->getData();
            $this->setLanguage($request, $language);
            return $this->redirect($this->generateUrl('managePreferences'));
        } else {
            $this->info(
                'Manage preferences for user %username%.',
                array('%username%' => $user->getUsername()),
                array('link' => $this->generateUserRoute($user->getId()))
            );
            $this->getDoctrine()->getManager()->flush();
            
            return $this->render(
                'BinovoElkarBackupBundle:Default:preferences.html.twig',
                array('form' => $form->createView())
            );
        }
    }

    private function getUserPreference(Request $request, $param)
    {
        $response = null;
        $user = $this->security->getToken()->getUser();
        if ($param == 'language') {
            $response = $user->getLanguage();
        } elseif ($param == 'linesperpage') {
            $response = $user->getLinesperpage();
        }
        return $response;
    }
    
    public function findBackups($job)
    {
        $em = $this->getDoctrine()->getManager();
        $backupLocations = $em->getRepository('BinovoElkarBackupBundle:BackupLocation')
        ->findAll();
        $jobLocations = array();
        $actualLocation = $job->getBackupLocation()->getEffectiveDir();
        $jobPath = array(
            $job->getBackupLocation(),
            sprintf(
                '%s/%04d/%04d',
                $actualLocation,
                $job->getClient()->getId(),
                $job->getId()
                )
        );
        if (file_exists($jobPath[1]) and is_dir($jobPath[1])) {
            array_push($jobLocations, $jobPath);
        }
        foreach ($backupLocations as $backupLocation) {
            $directory = $backupLocation->getEffectiveDir();
            if (strcmp($directory, $actualLocation) !== 0) {
                $jobPath = array(
                    $backupLocation,
                    sprintf(
                        '%s/%04d/%04d',
                        $directory,
                        $job->getClient()->getId(),
                        $job->getId()
                    )
                );
                if (file_exists($jobPath[1]) and is_dir($jobPath[1])) {
                    array_push($jobLocations, $jobPath);
                }
            }
        }
        
        return $jobLocations;
    }
}
