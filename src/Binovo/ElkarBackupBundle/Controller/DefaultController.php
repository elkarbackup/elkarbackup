<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Controller;

use \DateTime;
use \Exception;
use \PDOException;
use \RuntimeException;
use Binovo\ElkarBackupBundle\Entity\Client;
use Binovo\ElkarBackupBundle\Entity\Job;
use Binovo\ElkarBackupBundle\Entity\Message;
use Binovo\ElkarBackupBundle\Entity\Policy;
use Binovo\ElkarBackupBundle\Entity\Script;
use Binovo\ElkarBackupBundle\Entity\User;
use Binovo\ElkarBackupBundle\Form\Type\AuthorizedKeyType;
use Binovo\ElkarBackupBundle\Form\Type\ClientType;
use Binovo\ElkarBackupBundle\Form\Type\JobForSortType;
use Binovo\ElkarBackupBundle\Form\Type\JobType;
use Binovo\ElkarBackupBundle\Form\Type\PolicyType;
use Binovo\ElkarBackupBundle\Form\Type\ScriptType;
use Binovo\ElkarBackupBundle\Form\Type\UserType;
use Binovo\ElkarBackupBundle\Form\Type\PreferencesType;
use Binovo\ElkarBackupBundle\Lib\Globals;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\SecurityContext;
use steevanb\SSH2Bundle\Entity\Profile;
use steevanb\SSH2Bundle\Entity\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;



class DefaultController extends Controller
{
    protected function info($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->get('BnvWebLogger');
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $logger->info($this->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }

    protected function generateClientRoute($id)
    {
        return $this->generateUrl('editClient',
                                  array('id' => $id));
    }

    protected function generateJobRoute($idJob, $idClient)
    {
        return $this->generateUrl('editJob',
                                  array('idClient' => $idClient,
                                        'idJob'    => $idJob));
    }

    protected function generatePolicyRoute($id)
    {
        return $this->generateUrl('editPolicy',
                                  array('id' => $id));
    }

    protected function generateScriptRoute($id)
    {
        return $this->generateUrl('editScript',
                                  array('id' => $id));
    }

    protected function generateUserRoute($id)
    {
        return $this->generateUrl('editUser',
                                  array('id' => $id));
    }

    /*
     * Checks if autofs is installed and the builtin -hosts map activated
     */
    protected function isAutoFsAvailable()
    {
        $result = false;
        if (!file_exists('/etc/auto.master')) {
            return false;
        }
        $file = fopen('/etc/auto.master', 'r');
        if (!$file) {
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
        $oldCacheDir  = $realCacheDir.'_old';
        $this->container->get('cache_clearer')->clear($realCacheDir);
        rename($realCacheDir, $oldCacheDir);
        $this->container->get('filesystem')->remove($oldCacheDir);
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
            throw $this->createNotFoundException($this->trans('Unable to find public key:'));
        }
        $headers = array('Content-Type'        => 'text/plain',
                         'Content-Disposition' => sprintf('attachment; filename="Publickey.pub"'));

        return new Response(file_get_contents($this->container->getParameter('public_key')), 200, $headers);
    }

    /**
     * @Route("/config/publickey/generate", name="generatePublicKey")
     * @Method("POST")
     * @Template()
     */
    public function generatePublicKeyAction(Request $request)
    {
        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $manager = $db->getManager();
        $msg = new Message('DefaultController', 'TickCommand',
                           json_encode(array('command' => "elkarbackup:generate_keypair")));
        $manager->persist($msg);
        $this->info('Public key generation requested');
        $manager->flush();
        $this->get('session')->getFlashBag()->add('manageParameters',
                                                  $t->trans('Wait for key generation. It should be available in less than 2 minutes. Check logs if otherwise',
                                                            array(),
                                                            'BinovoElkarBackup'));

        return $this->redirect($this->generateUrl('manageParameters'));
    }

    public function trans($msg, $params = array(), $domain = 'BinovoElkarBackup')
    {
        return $this->get('translator')->trans($msg, $params, $domain);
    }

    /**
     * @Route("/client/{id}/delete", name="deleteClient")
     * @Method("POST")
     * @Template()
     */
    public function deleteClientAction(Request $request, $id)
    {
       $access = $this->checkPermissions($id);
                if ($access == False) {
	                return $this->redirect($this->generateUrl('showClients'));
        }

        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Client');
        $manager = $db->getManager();
        $client = $repository->find($id);
        try {
            $manager->remove($client);
            $msg = new Message('DefaultController', 'TickCommand',
                               json_encode(array('command' => "elkarbackup:delete_job_backups",
                                                 'client'  => (int)$id)));
            $manager->persist($msg);
            $this->info('Client "%clientid%" deleted', array('%clientid%' => $id), array('link' => $this->generateClientRoute($id)));
            $manager->flush();
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->add('clients',
                                                      $t->trans('Unable to delete client: %extrainfo%',
                                                                array('%extrainfo%' => $e->getMessage()), 'BinovoElkarBackup'));
        }

        return $this->redirect($this->generateUrl('showClients'));
    }

    /**
     * @Route("/login", name="login")
     * @Method("GET")
     * @Template()
     */
    public function loginAction(Request $request)
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $t = $this->get('translator');

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        $this->info('Log in attempt with user: %username%', array('%username%' => $session->get(SecurityContext::LAST_USERNAME)));
        $this->getDoctrine()->getManager()->flush();
        $locales = $this->container->getParameter('supported_locales');
        $localesWithNames = array();
        foreach ($locales as $locale) {
            $localesWithNames[] = array($locale, $t->trans("language_$locale", array(), 'BinovoElkarBackup'));
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
            # PHP 5.3 or higher
            $rsnapshot_local_md5 = $sresult;
          }
        } else {
          $rsnapshot_local_md5 = "unknown";
          syslog(LOG_INFO, 'Impossible to check rsnapshot version. More info: https://github.com/elkarbackup/elkarbackup/issues/88"');
        }
        if ( $rsnapshot_jessie_md5 == $rsnapshot_local_md5 ) {
          $alert = "WARNING! Change your Rsnapshot version <a href='https://github.com/elkarbackup/elkarbackup/wiki/JessieRsnapshotIssue'>More info</a>";
          syslog(LOG_INFO, 'Rsnapshot 1.3.1-4 not working with SSH args. Downgrade it or fix it. More info: https://github.com/elkarbackup/elkarbackup/issues/88"');
          $disable_background = True;
        } else {
	  $alert = NULL;
	}

        return $this->render('BinovoElkarBackupBundle:Default:login.html.twig', array(
                                 'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                                 'error'         => $error,
                                 'supportedLocales' => $localesWithNames,
                                 'disable_background' => $disable_background,
                                 'alert' => $alert));
    }

    /**
     * @Route("/client/{id}", name="editClient")
     * @Method("GET")
     * @Template()
     */
    public function editClientAction(Request $request, $id)
    {
        if ('new' === $id) {
            $client = new Client();
        } else {
	    $access = $this->checkPermissions($id);
                if ($access == False) {
	                return $this->redirect($this->generateUrl('showClients'));
                }

            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Client');
            $client = $repository->find($id);
            if (null == $client) {
                throw $this->createNotFoundException($this->trans('Unable to find Client entity:') . $id);
            }
        }

        $form = $this->createForm(new ClientType(), $client, array('translator' => $this->get('translator')));
        $this->info('View client %clientid%',
                    array('%clientid%' => $id),
                    array('link' => $this->generateClientRoute($id)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:client.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/client/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveClient")
     * @Method("POST")
     * @Template()
     */
    public function saveClientAction(Request $request, $id)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $actualuserid = $user->getId();

        $t = $this->get('translator');
        if ("-1" === $id) {
            $client = new Client();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Client');
            $client = $repository->find($id);
        }

        $form = $this->createForm(new ClientType(), $client, array('translator' => $t));
        $form->bind($request);
        if ($form->isValid()) {
	    $client = $form->getData();
	    $em = $this->getDoctrine()->getManager();
            try {
                if (isset($jobsToDelete)){
                    foreach ($jobsToDelete as $idJob => $job) {
                        $client->getJobs()->removeElement($job);
                        $em->remove($job);
                        $msg = new Message('DefaultController', 'TickCommand',
                                           json_encode(array('command' => "elkarbackup:delete_job_backups",
                                                             'client'  => (int)$id,
                                                             'job'     => $idJob)));
                        $em->persist($msg);
                        $this->info('Delete client %clientid%, job %jobid%',
                                    array('%clientid%' => $client->getId(),
                                          '%jobid%' => $job->getId()),
                                    array('link' => $this->generateJobRoute($job->getId(), $client->getId())));
                    }
                }
		if ($client->getOwner() == null) {
                	$client->setOwner($this->get('security.context')->getToken()->getUser());
            	}
	        $em->persist($client);
                $this->info('Save client %clientid%',
                        array('%clientid%' => $client->getId()),
                            array('link' => $this->generateClientRoute($client->getId()))
                    );
                $em->flush();

                return $this->redirect($this->generateUrl('showClients'));
            } catch (Exception $e) {
                $this->get('session')->getFlashBag()->add('client',
                                                          $t->trans('Unable to save your changes: %extrainfo%',
                                                                    array('%extrainfo%' => $e->getMessage()),
                                                                    'BinovoElkarBackup'));

                return $this->redirect($this->generateUrl('editClient', array('id' => $client->getId() == null ? 'new' : $client->getId())));
            }
        } else {

            return $this->render('BinovoElkarBackupBundle:Default:client.html.twig',
                                 array('form' => $form->createView()));
        }
    }

    /**
     * @Route("/job/{id}/delete", name="deleteJob")
     * @Route("/client/{idClient}/job/{idJob}/delete", requirements={"idClient" = "\d+", "idJob" = "\d+"}, defaults={"idJob" = "-1"}, name="deleteJob")
     * @Method("POST")
     * @Template()
     */
    public function deleteJobAction(Request $request, $idClient, $idJob)
    {
       $access = $this->checkPermissions($idClient);
                if ($access == False) {
	                return $this->redirect($this->generateUrl('showClients'));
                }

        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Job');
        $manager = $db->getManager();
        $job = $repository->find($idJob);
        try {
            $manager->remove($job);
            $msg = new Message('DefaultController', 'TickCommand',
                               json_encode(array('command' => "elkarbackup:delete_job_backups",
                                                 'client'  => (int)$idClient,
                                                 'job'     => (int)$idJob)));
            $manager->persist($msg);
            $this->info('Delete client %clientid%, job "%jobid%"', array('%clientid%' => $idClient, '%jobid%' => $idJob), array('link' => $this->generateJobRoute($idJob, $idClient)));
            $manager->flush();
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->add('client',
                                                      $t->trans('Unable to delete job: %extrainfo%',
                                                                array('%extrainfo%' => $e->getMessage()), 'BinovoElkarBackup'));
        }

        return $this->redirect($this->generateUrl('showClients'));
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}", name="editJob")
     * @Method("GET")
     * @Template()
     */
    public function editJobAction(Request $request, $idClient, $idJob)
    {
        if ('new' === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($this->trans('Unable to find Client entity:') . $idClient);
            }
            $job->setClient($client);
        } else {
	          $access = $this->checkPermissions($idClient, $idJob);
            if($access == True){
                $job = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job')->find($idJob);
            } else {
                return $this->redirect($this->generateUrl('showClients'));
            }
        }
        $form = $this->createForm(new JobType(), $job, array('translator' => $this->get('translator')));
        $this->info('View client %clientid%, job %jobid%',
                    array('%clientid%' => $idClient, '%jobid%' => $idJob),
                    array('link' => $this->generateJobRoute($idJob, $idClient)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:job.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/run", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="runJob")
     * @Method("POST")
     * @Template()
     */
    public function runJobAction(Request $request, $idClient, $idJob)
    {
        $t = $this->get('translator');
        $user = $this->get('security.context')->getToken();
        $trustable = false;

        if($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')){
          // Authenticated user
          $trustable = true;
        } else {
          // Anonymous access
          $token = $this->get('request')->request->get('token');
          if ('' == $token) {
            $response = new JsonResponse(array('status'  => 'true',
                                               'msg'    => $t->trans('You need to login or send a token', array(), 'BinovoElkarBackup')));
            return $response;
          } else {
            $repository = $this->getDoctrine()
              ->getRepository('BinovoElkarBackupBundle:Job');
              $job = $repository->findOneById($idJob);
              if ($token == $job->getToken()){
                  // Valid token, but let's require HTTPS
                  if ($this->getRequest()->isSecure()) {
                    $trustable = true;
                  } else {
                    $response = new JsonResponse(array('status'  => 'false',
                                                       'msg'    => $t->trans('Aborted: HTTPS required', array(), 'BinovoElkarBackup')));
                    return $response;
                  }
              } else {
                  // Invalid token
                  $trustable = false;
              }

              if (!$trustable){
                $response = new JsonResponse(array('status'  => 'false',
                                                   'msg'    => $t->trans('You need to login or send properly values', array(), 'BinovoElkarBackup')));
                return $response;
              }
          }
        }

        if($trustable){
          if (!isset($job)){
            $job = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Job')->find($idJob);
            if (null == $job) {
                throw $this->createNotFoundException($this->trans('Unable to find Job entity:') . $idJob);
            }
          }

          $em = $this->getDoctrine()->getManager();
          $msg = new Message('DefaultController', 'TickCommand',
                             json_encode(array('command' => 'elkarbackup:run_job',
                                               'client'  => $idClient,
                                               'job'     => $idJob)));

          $context = array('link'   => $this->generateJobRoute($idJob, $idClient),
                           'source' => Globals::STATUS_REPORT);
          $status = 'QUEUED';
          $job->setStatus($status);
          $this->info($status, array(), $context);
          $em->persist($msg);
          $em->flush();
          $response = new Response($t->trans('Job execution requested successfully', array(), 'BinovoElkarBackup'));
          $response->headers->set('Content-Type', 'text/plain');
          // TODO: change the response from text plain to JSON

          return $response;
        }

    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/abort", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="abortJob")
     * @Method("POST")
     * @Template()
     */
    public function runAbortAction(Request $request, $idClient, $idJob)
    {
        $t = $this->get('translator');

        $job = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Job')->find($idJob);
        if (null == $job) {
            throw $this->createNotFoundException($this->trans('Unable to find Job entity:') . $idJob);
        }

        if ($job->getStatus() == 'RUNNING' or $job->getStatus() == 'QUEUED'){
            $em = $this->getDoctrine()->getManager();
            $msg = new Message('DefaultController', 'TickCommand',
                               json_encode(array('command' => 'elkarbackup:stop_job',
                                                 'client'  => $idClient,
                                                 'job'     => $idJob)));

            if ($job->getStatus() == "RUNNING"){
                // Job is running, next TickCommand will kill the process
                $newstatus = "ABORTING";
            } else {
                // Job is queued, next TickCommand will ignore it
                $newstatus = "ABORTED";
            }
            $context = array('link'   => $this->generateJobRoute($idJob, $idClient),
                             'source' => Globals::STATUS_REPORT);
            $job->setStatus($newstatus);
            $this->info($newstatus, array(), $context);
            $em->persist($msg);
            $em->flush();
            $response = new JsonResponse(array('error'  =>  false,
                                               'msg'    => $t->trans('Job stop requested: aborting job', array(), 'BinovoElkarBackup'),
                                               'action' => 'callbackJobAborting',
                                               'data'   =>  array($idJob)));
            return $response;

        } else {
            $response = new JsonResponse(array('error'  => true,
                                               'msg'    => $t->trans('Cannot abort job: not running', array(), 'BinovoElkarBackup')));
            return $response;
        }

        return $response;
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/config", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="showJobConfig")
     * @Method("GET")
     * @Template()
     */
    public function showJobConfigAction(Request $request, $idClient, $idJob)
    {
        $t = $this->get('translator');
        $backupDir  = $this->container->getParameter('backup_dir');
        $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Job');
        $job = $repository->find($idJob);
        if (null == $job || $job->getClient()->getId() != $idClient) {
            throw $this->createNotFoundException($t->trans('Unable to find Job entity: ', array(), 'BinovoElkarBackup') . $idClient . " " . $idJob);
        }
        $client = $job->getClient();
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
	          foreach($includes as &$theInclude) {
		            $theInclude = str_replace('\ ', '?', trim($theInclude));
	          }
        }
        $excludes = array();
        $exclude = $job->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
	          foreach($excludes as &$theExclude) {
		            $theExclude = str_replace('\ ', '?', trim($theExclude));
	          }
        }
        $syncFirst = $policy->getSyncFirst();
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $this->info('Show job config %clientid%, job %jobid%',
                    array('%clientid%' => $idClient, '%jobid%' => $idJob),
                    array('link' => $this->generateJobRoute($idJob, $idClient)));
        $this->getDoctrine()->getManager()->flush();
        $preCommand  = '';
        $postCommand = '';
        foreach ($job->getPreScripts() as $script) {
            $preCommand = $preCommand . "\n" . sprintf('/usr/bin/env ELKARBACKUP_LEVEL="JOB" ELKARBACKUP_EVENT="PRE" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" sudo "%s" 2>&1',
                                                       $url,
                                                       $idJob,
                                                       $job->getSnapshotRoot(),
                                                       0,
                                                       $script->getScriptPath());
        }
        foreach ($job->getPostScripts() as $script) {
            $postCommand = $postCommand . "\n" . sprintf('/usr/bin/env ELKARBACKUP_LEVEL="JOB" ELKARBACKUP_EVENT="POST" ELKARBACKUP_URL="%s" ELKARBACKUP_ID="%s" ELKARBACKUP_PATH="%s" ELKARBACKUP_STATUS="%s" sudo "%s" 2>&1',
                                                       $url,
                                                       $idJob,
                                                       $job->getSnapshotRoot(),
                                                       0,
                                                       $script->getScriptPath());
        }

        return $this->render('BinovoElkarBackupBundle:Default:rsnapshotconfig.txt.twig',
                             array('cmdPreExec'          => $preCommand,
                                   'cmdPostExec'         => $postCommand,
                                   'excludes'            => $excludes,
                                   'idClient'            => sprintf('%04d', $idClient),
                                   'idJob'               => sprintf('%04d', $idJob),
                                   'includes'            => $includes,
                                   'backupDir'           => $backupDir,
                                   'retains'             => $retains,
                                   'tmp'                 => $tmpDir,
                                   'snapshotRoot'        => $job->getSnapshotRoot(),
                                   'syncFirst'           => $syncFirst,
                                   'url'                 => $url,
                                   'useLocalPermissions' => $job->getUseLocalPermissions(),
                                   'sshArgs'             => $sshArgs,
                                   'rsyncShortArgs'      => $rsyncShortArgs,
                                   'rsyncLongArgs'       => $rsyncLongArgs),
                             $response);
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}", requirements={"idClient" = "\d+", "idJob" = "\d+"}, defaults={"idJob" = "-1"}, name="saveJob")
     * @Method("POST")
     * @Template()
     */
    public function saveJobAction(Request $request, $idClient, $idJob)
    {
        $t = $this->get('translator');
        if ("-1" === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($t->trans('Unable to find Client entity:', array(), 'BinovoElkarBackup') . $idClient);
            }
            $job->setClient($client);
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Job');
            $job = $repository->find($idJob);
        }
        $form = $this->createForm(new JobType(), $job, array('translator' => $t));
        $form->bind($request);
        if ($form->isValid()) {
            $job = $form->getData();
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($job);
                $this->info('Save client %clientid%, job %jobid%',
                            array('%clientid%' => $job->getClient()->getId(),
                                  '%jobid%'    => $job->getId()),
                            array('link' => $this->generateJobRoute($job->getId(), $job->getClient()->getId())));
                $em->flush();
            } catch (Exception $e) {
                $this->get('session')->getFlashBag()->add('job',
                                                          $t->trans('Unable to save your changes: %extrainfo%',
                                                                    array('%extrainfo%' => $e->getMessage()),
                                                                    'BinovoElkarBackup'));
            }

            return $this->redirect($this->generateUrl('showClients'));
        } else {

            return $this->render('BinovoElkarBackupBundle:Default:job.html.twig',
                                 array('form' => $form->createView()));
        }
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/backup/{action}/{path}", requirements={"idClient" = "\d+", "idJob" = "\d+", "path" = ".*", "action" = "view|download|downloadzip"}, defaults={"path" = "/"}, name="showJobBackup")
     * @Method("GET")
     */
    public function showJobBackupAction(Request $request, $idClient, $idJob, $action, $path)
    {
	if($this->checkPermissions($idClient)==False){
                return $this->redirect($this->generateUrl('showClients'));
        }

        $t = $this->get('translator');
        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Job');
        $job = $repository->find($idJob);
        if ($job->getClient()->getId() != $idClient) {
            throw $this->createNotFoundException($t->trans('Unable to find Job entity: ', array(), 'BinovoElkarBackup') . $idClient . " " . $idJob);
        }

        $snapshotRoot = realpath($job->getSnapshotRoot());
        $realPath = realpath($snapshotRoot . '/' . $path);
        if (false == $realPath) {
            throw $this->createNotFoundException($t->trans('Path not found:', array(), 'BinovoElkarBackup') . $path);
        }
        if (0 !== strpos($realPath, $snapshotRoot)) {
            throw $this->createNotFoundException($t->trans('Path not found:', array(), 'BinovoElkarBackup') . $path);
        }
        if (is_dir($realPath)) {
            if ('download' == $action) {
                $headers = array('Content-Type'        => 'application/x-gzip',
                                 'Content-Disposition' => sprintf('attachment; filename="%s.tar.gz"', basename($realPath)));
                $f = function() use ($realPath){
                    $command = sprintf('cd "%s"; tar zc "%s"', dirname($realPath), basename($realPath));
                    passthru($command);
                };
                $this->info('Download backup directory %clientid%, %jobid% %path%',
                            array('%clientid%' => $idClient,
                                  '%jobid%'    => $idJob,
                                  '%path%'     => $path),
                            array('link' => $this->generateUrl('showJobBackup',
                                                               array('action'   => $action,
                                                                     'idClient' => $idClient,
                                                                     'idJob'    => $idJob,
                                                                     'path'     => $path))));
                $this->getDoctrine()->getManager()->flush();

                return new StreamedResponse($f, 200, $headers);
            } elseif ('downloadzip' == $action) {
                $headers = array('Content-Type'        => 'application/zip',
                                 'Content-Disposition' => sprintf('attachment; filename="%s.zip"', basename($realPath)));
                $f = function() use ($realPath){
                    $command = sprintf('cd "%s"; zip -r - "%s"', dirname($realPath), basename($realPath));
                    passthru($command);
                };
                $this->info('Download backup directory %clientid%, %jobid% %path%',
                            array('%clientid%' => $idClient,
                                  '%jobid%'    => $idJob,
                                  '%path%'     => $path),
                            array('link' => $this->generateUrl('showJobBackup',
                                                               array('action'   => $action,
                                                                     'idClient' => $idClient,
                                                                     'idJob'    => $idJob,
                                                                     'path'     => $path))));
                $this->getDoctrine()->getManager()->flush();

                return new StreamedResponse($f, 200, $headers);
            } else {
                // Check if Zip is in the user path
                system('which zip', $cmdretval);
                $isZipInstalled = !$cmdretval;

                $content = scandir($realPath);
                if (false === $content) {
                    $content = array();
                }
                foreach ($content as &$aFile) {
                    $date = new \DateTime();
                    $date->setTimestamp(filemtime($realPath . '/' . $aFile));
                    $aFile = array($aFile, $date, is_dir($realPath . '/' . $aFile));
                }
                $this->info('View backup directory %clientid%, %jobid% %path%',
                            array('%clientid%' => $idClient,
                                  '%jobid%'    => $idJob,
                                  '%path%'     => $path),
                            array('link' => $this->generateUrl('showJobBackup',
                                                               array('action'   => $action,
                                                                     'idClient' => $idClient,
                                                                     'idJob'    => $idJob,
                                                                     'path'     => $path))));
                $this->getDoctrine()->getManager()->flush();

                return $this->render('BinovoElkarBackupBundle:Default:directory.html.twig',
                                     array('content'  => $content,
                                           'job'      => $job,
                                           'path'     => $path,
                                           'realPath' => $realPath,
                                           'isZipInstalled' => $isZipInstalled));
            }
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mimeType = finfo_file($finfo, $realPath);
            finfo_close($finfo);
            $headers = array('Content-Type' => $mimeType,
                             'Content-Disposition' => sprintf('attachment; filename="%s"', basename($realPath)));
            $this->info('Download backup file %clientid%, %jobid% %path%',
                        array('%clientid%' => $idClient,
                              '%jobid%'    => $idJob,
                              '%path%'     => $path),
                        array('link' => $this->generateUrl('showJobBackup',
                                                           array('action'   => $action,
                                                                 'idClient' => $idClient,
                                                                 'idJob'    => $idJob,
                                                                 'path'     => $path))));
            $this->getDoctrine()->getManager()->flush();

            return new Response(file_get_contents($realPath), 200, $headers);
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
        return array('name' => $name);
    }

    /**
     * @Route("/policy/{id}", name="editPolicy")
     * @Method("GET")
     * @Template()
     */
    public function editPolicyAction(Request $request, $id)
    {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
	       //only allow to admins to do this task
         return $this->redirect($this->generateUrl('showClients'));
            }

        $t = $this->get('translator');
        if ('new' === $id) {
            $policy = new Policy();
        } else {
            $policy = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Policy')->find($id);
        }
        $form = $this->createForm(new PolicyType(), $policy, array('translator' => $t));
        $this->info('View policy %policyname%',
                    array('%policyname%' => $policy->getName()),
                    array('link' => $this->generatePolicyRoute($policy->getId())));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:policy.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/policy/{id}/delete", name="deletePolicy")
     * @Method("POST")
     * @Template()
     */
    public function deletePolicyAction(Request $request, $id)
    {
       if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
          //only allow to admins to do this task
            return $this->redirect($this->generateUrl('showClients'));
        }

        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Policy');
        $manager = $db->getManager();
        $policy = $repository->find($id);
        try{
            $manager->remove($policy);
            $this->info('Delete policy %policyname%',
                        array('%policyname%' => $policy->getName()),
                        array('link' => $this->generatePolicyRoute($id)));
            $manager->flush();
        } catch (PDOException $e) {
            $this->get('session')->getFlashBag()->add('showPolicies',
                                                      $t->trans('Removing the policy %name% failed. Check that it is not in use.', array('%name%' => $policy->getName()), 'BinovoElkarBackup'));
        }

        return $this->redirect($this->generateUrl('showPolicies'));
    }

    /**
     * @Route("/policy/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="savePolicy")
     * @Method("POST")
     * @Template()
     */
    public function savePolicyAction(Request $request, $id)
    {
        $t = $this->get('translator');
        if ("-1" === $id) {
            $policy = new Policy();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Policy');
            $policy = $repository->find($id);
        }
        $form = $this->createForm(new PolicyType(), $policy, array('translator' => $t));
        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($policy);
            $this->info('Save policy %policyname%',
                        array('%policyname%' => $policy->getName()),
                        array('link' => $this->generatePolicyRoute($id)));
            $em->flush();

            return $this->redirect($this->generateUrl('showPolicies'));
        } else {

            return $this->render('BinovoElkarBackupBundle:Default:policy.html.twig',
                                 array('form' => $form->createView()));
        }
    }

    /**
     * @Route("/jobs/sort", name="sortJobs")
     * @Template()
     */
    public function sortJobsAction(Request $request)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $actualuserid = $user->getId();

        $t = $this->get('translator');
        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Job');

        $query = $repository->createQueryBuilder('j')->innerJoin('j.client','c')->addOrderBy('j.priority', 'ASC');
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            // Non-admin users only can sort their own jobs
            $query->where('j.isActive <> 0 AND c.isActive <> 0 AND c.owner = ?1'); //adding users and roles
            $query->setParameter(1, $actualuserid);
        }
        $jobs = $query->getQuery()->getResult();;


        $formBuilder = $this->createFormBuilder(array('jobs' => $jobs));
        $formBuilder->add('jobs', 'collection',
                          array('type' => new JobForSortType()));
        $form = $formBuilder->getForm();
        if ($request->isMethod('POST')) {
            $i = 1;
            foreach ($_POST['form']['jobs'] as $jobId) {
                $jobId = $jobId['id'];
                $job = $repository->findOneById($jobId);
                $job->setPriority($i);
                ++$i;
            }
            $this->info('Jobs reordered',
                        array(),
                        array('link' => $this->generateUrl('showClients')));
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add('sortJobs',
                                                      $t->trans('Jobs prioritized',
                                                                array(),
                                                                'BinovoElkarBackup'));
            $result = $this->redirect($this->generateUrl('sortJobs'));
        } else {
            $result = $this->render('BinovoElkarBackupBundle:Default:sortjobs.html.twig',
                                    array('form' => $form->createView()));
        }

        return $result;
    }

    public function getFsSize( $path )
    {
        $size = (int)shell_exec(sprintf("df -k '%s' | tail -n1 | awk '{ print $2 }' | head -c -2", $path));
        return $size;
    }

    public function getFsUsed( $path)
    {
        $size = (float)shell_exec(sprintf("df -k '%s' | tail -n1 | awk '{ print $3 }' | head -c -2", $path));
        return $size;
    }

    /**
     * @Route("/clients", name="showClients")
     * @Template()
     */
    public function showClientsAction(Request $request)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $actualuserid = $user->getId();

        $fsDiskUsage = (int)round($this->getFsUsed( Globals::getBackupDir() ) * 100 / $this->getFsSize( Globals::getBackupDir() ), 0, PHP_ROUND_HALF_UP);

        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Client');
        $query = $repository->createQueryBuilder('c')->addOrderBy('c.id', 'ASC');

        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            // Limited view for non-admin users
            $query->where('c.owner = ?1'); //adding users and roles
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
                $job->setLogEntry($this->getLastLogForLink('%/client/' . $client->getId() . '/job/' . $job->getId()));
            }
        }
        $this->info('View clients',
                    array(),
                    array('link' => $this->generateUrl('showClients')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:clients.html.twig',
                             array('pagination' => $pagination,
                                   'fsDiskUsage' => $fsDiskUsage));
    }

    /**
     * @Route("/scripts", name="showScripts")
     * @Template()
     */
    public function showScriptsAction(Request $request)
    {

        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Script');
        $query = $repository->createQueryBuilder('c')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
            );
        $this->info('View scripts',
                    array(),
                    array('link' => $this->generateUrl('showScripts')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:scripts.html.twig',
                             array('pagination' => $pagination));
    }

    public function getLastLogForLink($link)
    {
        $lastLog = null;
        $em = $this->getDoctrine()->getManager();
        // :WARNING: this call might end up slowing things too much.
        $dql =<<<EOF
SELECT l
FROM  BinovoElkarBackupBundle:LogRecord l
WHERE l.source = :source AND l.link LIKE :link
ORDER BY l.id DESC
EOF;
        $query = $em->createQuery($dql)->setParameter('link'  , $link)
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
        $t = $this->get('translator');
        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:LogRecord');
        $queryBuilder = $repository->createQueryBuilder('l')
            ->addOrderBy('l.id', 'DESC');
        $queryParamCounter = 1;
        if ($request->get('filter')) {
            $queryBuilder->where("1 = 1");
            foreach ($request->get('filter') as $op => $filterValues) {
                if (!in_array($op, array('gte', 'eq', 'like'))) {
                    $op = 'eq';
                }
                foreach ($filterValues as $columnName => $value) {
                    if ($value) {
                        $queryBuilder->andWhere($queryBuilder->expr()->$op($columnName, "?$queryParamCounter"));
                        if ('like' == $op) {
                            $queryBuilder->setParameter($queryParamCounter, '%' . $value . '%');
                        } else {
                            $queryBuilder->setParameter($queryParamCounter, $value);
                        }
                        ++$queryParamCounter;
                        $formValues["filter[$op][$columnName]"] = $value;
                    }
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
        $this->info('View logs',
                    array(),
                    array('link' => $this->generateUrl('showLogs')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:logs.html.twig',
                             array('pagination' => $pagination,
                                   'levels' => array('options' => array(Job::NOTIFICATION_LEVEL_ALL     => $t->trans('All messages'   , array(), 'BinovoElkarBackup'),
                                                                        Job::NOTIFICATION_LEVEL_INFO    => $t->trans('Notices and up' , array(), 'BinovoElkarBackup'),
                                                                        Job::NOTIFICATION_LEVEL_WARNING => $t->trans('Warnings and up', array(), 'BinovoElkarBackup'),
                                                                        Job::NOTIFICATION_LEVEL_ERROR   => $t->trans('Errors and up'  , array(), 'BinovoElkarBackup'),
                                                                        Job::NOTIFICATION_LEVEL_NONE    => $t->trans('None'           , array(), 'BinovoElkarBackup')),
                                                     'value'   => isset($formValues['filter[gte][l.level]']) ? $formValues['filter[gte][l.level]'] : null,
                                                     'name'    => 'filter[gte][l.level]'),
                                   'object' => array('value'   => isset($formValues['filter[like][l.link]']) ? $formValues['filter[like][l.link]'] : null,
                                                     'name'    => 'filter[like][l.link]'),
                                   'source' => array('options' => array(''                            => $t->trans('All', array(), 'BinovoElkarBackup'),
                                                                        'DefaultController'           => 'DefaultController',
                                                                        'GenerateKeyPairCommand'      => 'GenerateKeyPairCommand',
                                                                        'RunJobCommand'               => 'RunJobCommand',
                                                                        Globals::STATUS_REPORT        => 'StatusReport',
                                                                        'TickCommand'                 => 'TickCommand',
                                                                        'UpdateAuthorizedKeysCommand' => 'UpdateAuthorizedKeysCommand'),
                                                     'value'   => isset($formValues['filter[eq][l.source]']) ? $formValues['filter[eq][l.source]'] : null,
                                                     'name'    => 'filter[eq][l.source]')));
    }

    /**
     * @Route("/policies", name="showPolicies")
     * @Template()
     */
    public function showPoliciesAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:Policy');
        $query = $repository->createQueryBuilder('c')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
            );
        $this->info('View policies',
                    array(),
                    array('link' => $this->generateUrl('showPolicies')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:policies.html.twig',
                             array('pagination' => $pagination));
    }

    /**
     * @Route("/config/repositorybackupscript/download", name="getRepositoryBackupScript")
     * @Template()
     */
    public function getRepositoryBackupScriptAction(Request $request)
    {
        $response = $this->render('BinovoElkarBackupBundle:Default:copyrepository.sh.twig',
                                  array('backupsroot'   => $this->container->getParameter('backup_dir'),
                                        'backupsuser'   => 'elkarbackup',
                                        'mysqldb'       => $this->container->getParameter('database_name'),
                                        'mysqlhost'     => $this->container->getParameter('database_host'),
                                        'mysqlpassword' => $this->container->getParameter('database_password'),
                                        'mysqluser'     => $this->container->getParameter('database_user'),
                                        'server'        => $request->getHttpHost(),
                                        'uploads'       => $this->container->getParameter('upload_dir')));
        $response->headers->set('Content-Type'       , 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="copyrepository.sh"');

        return $response;
    }

    public function readKeyFileAsCommentAndRest($filename)
    {
        $keys = array();
        if (!file_exists($filename)) {
            return $keys;
        }
        foreach (explode("\n", file_get_contents($filename)) as $keyLine) {
            $matches = array();
            // the format of eacn non empty non comment line is "options keytype base64-encoded key comment" where key is one of ecdsa-sha2-nistp256, ecdsa-sha2-nistp384, ecdsa-sha2-nistp521, ssh-dss, ssh-rsa
            if (preg_match('/(.*(?:ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521|ssh-dss|ssh-rsa) *[^ ]*) *(.*)/', $keyLine, $matches)) {
                $keys[] = array('publicKey' => $matches[1], 'comment' => $matches[2]);
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
        $t = $this->get('translator');
        $authorizedKeysFile = dirname($this->container->getParameter('public_key')) . '/authorized_keys';
        $keys = $this->readKeyFileAsCommentAndRest($authorizedKeysFile);
        $formBuilder = $this->createFormBuilder(array('publicKeys' => $keys));
        $formBuilder->add('publicKeys', 'collection',
                          array('type'         => new AuthorizedKeyType($t),
                                'allow_add'    => true,
                                'allow_delete' => true,
                                'attr'         => array('class'    => 'form-control'),
                                'options'      => array('required' => false,
                                                        'attr'     => array('class' => 'span10'))));
        $form = $formBuilder->getForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            $data = $form->getData();
            $serializedKeys = '';
            foreach ($data['publicKeys'] as $key) {
                $serializedKeys .= sprintf("%s %s\n", $key['publicKey'], $key['comment']);
            }
            $manager = $this->getDoctrine()->getManager();
            $msg = new Message('DefaultController', 'TickCommand',
                               json_encode(array('command' => "elkarbackup:update_authorized_keys",
                                                 'content'  => $serializedKeys)));
            $manager->persist($msg);
            $this->info('Updating key file %keys%',
                        array('%keys%' => $serializedKeys));
            $manager->flush();
            $this->get('session')->getFlashBag()->add('backupScriptConfig',
                                                      $t->trans('Key file updated. The update should be effective in less than 2 minutes.',
                                                                array(),
                                                                'BinovoElkarBackup'));
            $result = $this->redirect($this->generateUrl('configureRepositoryBackupScript'));
        } else {
            $result = $this->render('BinovoElkarBackupBundle:Default:backupscriptconfig.html.twig',
                                    array('form'            => $form->createView()));
        }

        return $result;
    }

    /**
     * @Route("/config/backupslocation", name="manageBackupsLocation")
     * @Template()
     */
    public function manageBackupsLocationAction(Request $request)
    {
        $t = $this->get('translator');
        $backupDir = $this->container->getParameter('backup_dir');
        $hostAndDir = array();
        if (preg_match('/^\/net\/([^\/]+)(\/.*)$/', $backupDir, $hostAndDir)) {
            $data = array('host'      => $hostAndDir[1],
                          'directory' => $hostAndDir[2]);
        } else {
            $data = array('host'      => '',
                          'directory' => $backupDir);
        }
        $tahoe = $this->container->get('Tahoe');
        $tahoeInstalled = $tahoe->isInstalled();
        $tahoeOn = $this->container->getParameter('tahoe_active');
        if (!$tahoeInstalled && $tahoeOn) {
            $tahoeOn = false;
            $this->setParameter('tahoe_active', 'false', 'manageBackupsLocation');
        }
        $data['tahoe_active'] = $tahoeOn;

        $formBuilder = $this->createFormBuilder($data);
        $formBuilder->add('host'      , 'text'  , array('required' => false,
                                                        'label'    => $t->trans('Host', array(), 'BinovoElkarBackup'),
                                                        'attr'     => array('class'    => 'form-control'),
                                                        'disabled' => !$this->isAutoFsAvailable()));
        $formBuilder->add('directory' , 'text'  , array('required' => false,
                                                        'label'    => $t->trans('Directory', array(), 'BinovoElkarBackup'),
                                                        'attr'     => array('class' => 'form-control')));
        $formBuilder->add('tahoe_active', 'checkbox', array('required' => false,
                                                            'label'    => $t->trans('Turn on Tahoe storage', array(), 'BinovoElkarTahoe'),
                                                            'disabled' => !$tahoeInstalled ));

        $result = null;
        $form = $formBuilder->getForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            $data = $form->getData();
            if ('' != $data['host']) {
                $backupDir = sprintf('/net/%s%s', $data['host'], $data['directory']);
            } else {
                $backupDir = $data['directory'];
            }
            $ok = true;
            $result = $this->redirect($this->generateUrl('manageBackupsLocation'));
            if ($this->container->getParameter('backup_dir') != $backupDir) {
                if ($this->setParameter('backup_dir', $backupDir, 'manageBackupsLocation')) {
                    $this->get('session')->getFlashBag()->add('manageParameters',
                                                              $t->trans('Parameters updated',
                                                                        array(),
                                                                        'BinovoElkarBackup'));
                }
                if (!is_dir($backupDir)) {
                    $form->addError(new FormError($t->trans('Warning: the directory does not exist',
                                                            array(),
                                                            'BinovoElkarBackup')));
                    $result = $this->render('BinovoElkarBackupBundle:Default:backupslocation.html.twig',
                                            array('form' => $form->createView()));
                }
            }
            if ($data['tahoe_active'] != $tahoeOn) {
                if ($data['tahoe_active']) {
                  $strvalue = 'true';
                } else {
                  $strvalue = 'false';
                }
                if ($this->setParameter('tahoe_active', $strvalue, 'manageBackupsLocation')) {
                    $this->get('session')->getFlashBag()->add('manageParameters', $t->trans('Parameters updated',
                                                                                            array(),
                                                                                            'BinovoElkarBackup'));
                }
            }
        } else {
            $result = $this->render('BinovoElkarBackupBundle:Default:backupslocation.html.twig',
                                    array('form' => $form->createView()));
        }

        if (!$tahoe->isReady() and $data['tahoe_active']) {
            $this->get('session')->getFlashBag()->add('manageParameters',
                                                      $t->trans('Warning: tahoe is not properly configured and will not work',
                                                                array(),
                                                                'BinovoElkarTahoe'));
            $result = $this->render('BinovoElkarBackupBundle:Default:backupslocation.html.twig',
                                    array('form' => $form->createView()));
        }

        $this->getDoctrine()->getManager()->flush();
        $this->clearCache();

        return $result;
    }
    /**
     * @Route("/config/params", name="manageParameters")
     * @Template()
     */
    public function manageParametersAction(Request $request)
    {
        $t = $this->get('translator');
        $params = array('database_host'             => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('MySQL host'            , array(), 'BinovoElkarBackup')),
                        'database_port'             => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('MySQL port'            , array(), 'BinovoElkarBackup')),
                        'database_name'             => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('MySQL DB name'         , array(), 'BinovoElkarBackup')),
                        'database_user'             => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('MySQL user'            , array(), 'BinovoElkarBackup')),
                        'database_password'         => array('type' => 'password', 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('MySQL password'        , array(), 'BinovoElkarBackup')),
                        'mailer_transport'          => array('type' => 'choice'  , 'required' => false, 'attr' => array('class' => 'form-control'), 'choices' => array('gmail'    => 'gmail',
                                                                                                                                                                 'mail'     => 'mail',
                                                                                                                                                                 'sendmail' => 'sendmail',
                                                                                                                                                                 'smtp'     => 'smtp'),
                                                             'label' => $t->trans('Mailer transport'       , array(), 'BinovoElkarBackup')),
                        'mailer_host'               => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Mailer host'           , array(), 'BinovoElkarBackup')),
                        'mailer_user'               => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Mailer user'           , array(), 'BinovoElkarBackup')),
                        'mailer_password'           => array('type' => 'password', 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Mailer password'       , array(), 'BinovoElkarBackup')),
                        'mailer_from'               => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Mailer from'           , array(), 'BinovoElkarBackup')),
                        'max_log_age'               => array('type' => 'choice'  , 'required' => false, 'attr' => array('class' => 'form-control'), 'choices' => array('P1D' => $t->trans('One day'    , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P1W' => $t->trans('One week'   , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P2W' => $t->trans('Two weeks'  , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P3W' => $t->trans('Three weeks', array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P1M' => $t->trans('A month'    , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P6M' => $t->trans('Six months' , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P1Y' => $t->trans('A year'     , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P2Y' => $t->trans('Two years'  , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P3Y' => $t->trans('Three years', array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P4Y' => $t->trans('Four years' , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 'P5Y' => $t->trans('Five years' , array(), 'BinovoElkarBackup'),
                                                                                                                                                                 ''    => $t->trans('Never'      , array(), 'BinovoElkarBackup')),
                                                             'label' => $t->trans('Remove logs older than', array(), 'BinovoElkarBackup')),
                        'warning_load_level'        => array('type' => 'percent' , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Quota warning level', array(), 'BinovoElkarBackup')),
                        'pagination_lines_per_page' => array('type' => 'integer' , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Records per page'   , array(), 'BinovoElkarBackup')),
                        'url_prefix'                => array('type' => 'text'    , 'required' => false, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Url prefix'         , array(), 'BinovoElkarBackup')),
                        'disable_background'        => array('type' => 'checkbox', 'required' => false, 'label' => $t-> trans('Disable background', array(), 'BinovoElkarBackup')),
            );
        $defaultData = array();
        foreach ($params as $paramName => $formField) {
            if ('password' != $formField['type']) {
                $defaultData[$paramName] = $this->container->getParameter($paramName);
            }
        }
        $formBuilder = $this->createFormBuilder($defaultData);
        foreach ($params as $paramName => $formField) {
            $formBuilder->add($paramName, $formField['type'], array_diff_key($formField, array('type' => true)));
        }
        $result = null;
        $form = $formBuilder->getForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            $data = $form->getData();
            $allOk = true;
            foreach ($data as $paramName => $paramValue) {
                $ok = true;
                if ('password' == $params[$paramName]['type']) {
                    if (!empty($paramValue)) {
                        $ok = $this->setParameter($paramName, $paramValue, 'manageParameters');
                    }
                } elseif ('checkbox' == $params[$paramName]['type']) {
                    // Workaround to store value in boolean format
                    if (!empty($paramValue)) {
                        $ok = $this->setParameter($paramName, 'true', 'manageParameters');
                    } else {
                        $ok = $this->setParameter($paramName, 'false', 'manageParameters');
                    }
                } else {
                    if ($paramValue != $this->container->getParameter($paramName)) {
                        $ok = $this->setParameter($paramName, $paramValue, 'manageParameters');
                    }
                }
                if (!$ok) {
                    $this->get('session')->getFlashBag()->add('manageParameters',
                                                              $t->trans('Error saving parameter "%param%"',
                                                                        array('%param%' => $params[$paramName]['label']),
                                                                        'BinovoElkarBackup'));
                    $allOk = false;
                }
            }
            if ($allOk) {
                $this->get('session')->getFlashBag()->add('manageParameters',
                                                          $t->trans('Parameters updated',
                                                                    array(),
                                                                    'BinovoElkarBackup'));
            }
            $result = $this->redirect($this->generateUrl('manageParameters'));
        } else {
            $result = $this->render('BinovoElkarBackupBundle:Default:params.html.twig',
                                    array('form'            => $form->createView(),
                                          'showKeyDownload' => file_exists($this->container->getParameter('public_key'))));
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
            $this->info('Set Parameter %paramname%',
                        array('%paramname%' => $name),
                        array('link' => $this->generateUrl($from)));
        } else {
            $this->info('Warning: Parameter %paramname% not set',
                        array('%paramname%' => $name),
                        array('link' => $this->generateUrl($from)));
        }

        return $ok;
    }

    /**
     * @Route("/password", name="changePassword")
     * @Template()
     */
    public function changePasswordAction(Request $request)
    {
        $t = $this->get('translator');
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->add('oldPassword' , 'password', array('required' => true, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Old password'        , array(), 'BinovoElkarBackup')))
            ->add('newPassword' , 'password', array('required' => true, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('New password'        , array(), 'BinovoElkarBackup')))
            ->add('newPassword2', 'password', array('required' => true, 'attr' => array('class' => 'form-control'), 'label' => $t->trans('Confirm new password', array(), 'BinovoElkarBackup')))
            ->getForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            $data = $form->getData();
            $user = $this->get('security.context')->getToken()->getUser();
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $ok = true;
            if (empty($data['newPassword']) || $data['newPassword'] !== $data['newPassword2']) {
                $ok = false;
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Passwords do not match", array(), 'BinovoElkarBackup'));
                $this->info('Change password for user %username% failed. Passwords do not match.',
                            array('%username%' => $user->getUsername()),
                            array('link' => $this->generateUserRoute($user->getId())));
            }
            if ($encoder->encodePassword($data['oldPassword'], $user->getSalt()) !== $user->getPassword()) {
                $ok = false;
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Wrong old password", array(), 'BinovoElkarBackup'));
                $this->info('Change password for user %username% failed. Wrong old password.',
                            array('%username%' => $user->getUsername()),
                            array('link' => $this->generateUserRoute($user->getId())));

            }
            if ($ok) {
                $user->setPassword($encoder->encodePassword($data['newPassword'], $user->getSalt()));
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Password changed", array(), 'BinovoElkarBackup'));
                $this->info('Change password for user %username%.',
                            array('%username%' => $user->getUsername()),
                            array('link' => $this->generateUserRoute($user->getId())));
                $manager->flush();
            }


            return $this->redirect($this->generateUrl('changePassword'));
        } else {

            return $this->render('BinovoElkarBackupBundle:Default:password.html.twig',
                                 array('form'    => $form->createView()));
        }
    }

    /**
     * @Route("/script/{id}/delete", name="deleteScript")
     * @Method("POST")
     * @Template()
     */
    public function deleteScriptAction(Request $request, $id)
    {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
	         //only allow to admins to do this task
        return $this->redirect($this->generateUrl('showClients'));
          }

        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Script');
        $manager = $db->getManager();
        $script = $repository->find($id);
        try{
            $manager->remove($script);
            $this->info('Delete script %scriptname%',
                        array('%scriptname%' => $script->getName()),
                        array('link' => $this->generateScriptRoute($id)));
            $manager->flush();
        } catch (PDOException $e) {
            $this->get('session')->getFlashBag()->add('showScripts',
                                                      $t->trans('Removing the script %name% failed. Check that it is not in use.', array('%name%' => $script->getName()), 'BinovoElkarBackup'));
        }

        return $this->redirect($this->generateUrl('showScripts'));
    }

    /**
     * @Route("/script/{id}/download", name="downloadScript")
     * @Method("GET")
     * @Template()
     */
    public function downloadScriptAction(Request $request, $id)
    {
        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoElkarBackupBundle:Script');
        $manager = $db->getManager();
        $script = $repository->findOneById($id);
        if (null == $script) {
            throw $this->createNotFoundException($t->trans('Script "%id%" not found', array('%id%' => $id), 'BinovoElkarBackup'));
        }
        $this->info('Download script %scriptname%',
                    array('%scriptname%' => $script->getName()),
                    array('link' => $this->generateScriptRoute($id)));
        $manager->flush();
        $response = new Response();
        $response->setContent(file_get_contents($script->getScriptPath()));
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $script->getName()));

        return $response;
    }

    /**
     * @Route("/script/{id}", name="editScript")
     * @Method("GET")
     * @Template()
     */
    public function editScriptAction(Request $request, $id)
    {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
	         //only allow to admins to do this task
        return $this->redirect($this->generateUrl('showClients'));
            }

        $t = $this->get('translator');
        if ('new' === $id) {
            $script = new Script();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Script');
            $script = $repository->find($id);
        }
        $form = $this->createForm(new ScriptType(), $script, array('scriptFileRequired' => !$script->getScriptFileExists(),
                                                                   'translator' => $t));
        $this->info('View script %scriptname%.',
                    array('%scriptname%' => $script->getName()),
                    array('link' => $this->generateScriptRoute($id)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:script.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/script/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveScript")
     * @Method("POST")
     * @Template()
     */
    public function saveScriptAction(Request $request, $id)
    {
        $t = $this->get('translator');
        if ("-1" === $id) {
            $script = new Script();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:Script');
            $script = $repository->find($id);
        }
        $form = $this->createForm(new ScriptType(), $script, array('scriptFileRequired' => !$script->getScriptFileExists(),
                                                                   'translator' => $t));
        $form->bind($request);
        $result = null;
        if ($form->isValid()) {
            if ("-1" == $id && null == $script->getScriptFile()) { // it is a new script but no file was uploaded
                $this->get('session')->getFlashBag()->add('editScript',
                                                          $t->trans('Uploading a script is mandatory for script creation.',
                                                                    array(),
                                                                    'BinovoElkarBackup'));
            } else {
                $em = $this->getDoctrine()->getManager();
                $script->setLastUpdated(new DateTime()); // we to this to force the PostPersist script to run.
                $em->persist($script);
                $this->info('Save script %scriptname%.',
                            array('%scriptname%' => $script->getScriptname()),
                            array('link' => $this->generateScriptRoute($id)));
                $em->flush();
                $result = $this->redirect($this->generateUrl('showScripts'));
            }
        }
        if (!$result) {
            $result = $this->render('BinovoElkarBackupBundle:Default:script.html.twig',
                                    array('form' => $form->createView()));
        }

        return $result;
    }

    /**
     * @Route("/user/{id}/delete", name="deleteUser")
     * @Method("POST")
     * @Template()
     */
    public function deleteUserAction(Request $request, $id)
    {
        if (User::SUPERUSER_ID != $id) {
            $db = $this->getDoctrine();
            $repository = $db->getRepository('BinovoElkarBackupBundle:User');
            $manager = $db->getManager();
            $user = $repository->find($id);
            $manager->remove($user);
            $this->info('Delete user %username%.',
                        array('%username%' => $user->getUsername()),
                        array('link' => $this->generateUserRoute($id)));
            $manager->flush();
        }

        return $this->redirect($this->generateUrl('showUsers'));
    }

    /**
     * @Route("/user/{id}", name="editUser")
     * @Method("GET")
     * @Template()
     */
    public function editUserAction(Request $request, $id)
    {
        $t = $this->get('translator');
        if ('new' === $id) {
            $user = new User();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:User');
            $user = $repository->find($id);
        }
        $form = $this->createForm(new UserType(), $user, array('translator' => $t));
        $this->info('View user %username%.',
                    array('%username%' => $user->getUsername()),
                    array('link' => $this->generateUserRoute($id)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:user.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/user/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveUser")
     * @Method("POST")
     * @Template()
     */
    public function saveUserAction(Request $request, $id)
    {
        $t = $this->get('translator');
        if ("-1" === $id) {
            $user = new User();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoElkarBackupBundle:User');
            $user = $repository->find($id);
        }
        $form = $this->createForm(new UserType(), $user, array('translator' => $t));
        $form->bind($request);
        if ($form->isValid()) {
            if ($user->newPassword) {
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $password = $encoder->encodePassword($user->newPassword, $user->getSalt());
                $user->setPassword($password);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $this->info('Save user %username%.',
                        array('%username%' => $user->getUsername()),
                        array('link' => $this->generateUserRoute($id)));
            $em->flush();

            return $this->redirect($this->generateUrl('showUsers'));
        } else {

            return $this->render('BinovoElkarBackupBundle:Default:user.html.twig',
                                 array('form' => $form->createView()));
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
        $repository = $this->getDoctrine()
            ->getRepository('BinovoElkarBackupBundle:User');
        $query = $repository->createQueryBuilder('c')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            $request->get('lines', $this->getUserPreference($request, 'linesperpage'))
            );
        $this->info('View users',
                    array(),
                    array('link' => $this->generateUrl('showUsers')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoElkarBackupBundle:Default:users.html.twig',
                             array('pagination' => $pagination));
    }

protected function checkPermissions($idClient, $idJob = null){

        $repository = $this->getDoctrine()
                        ->getRepository('BinovoElkarBackupBundle:Client');
        $client = $repository->find($idClient);

        if($client->getOwner() == $this->get('security.context')->getToken()->getUser() || $this->get('security.context')->isGranted('ROLE_ADMIN')){
                        return True;
                } else {
                        return False;
                }

        }


    /**
     * @Route("/client/clone/{idClient}", requirements={"idClient" = "\d+"}, defaults={"id" = "-1"}, name="cloneClient")
     * @Method("POST")
     * @Template()
     */
    public function cloneClientAction(Request $request, $idClient)
    {
        $t = $this->get('translator');
        $idoriginal = $idClient;
        if (null == $idClient) {
          throw $this->createNotFoundException($t->trans('Unable to find Client entity:', array(), 'BinovoElkarBackup') . $idClient);
        }

        $clientrow = array();
	      try {
    	      // CLONE CLIENT
            $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
            $client = $repository->find($idoriginal);
    	      if (null == $client) {
                throw $this->createNotFoundException($t->trans('Unable to find Client entity:', array(), 'BinovoElkarBackup') . $client);
            }

            $newname = $client->getName()."-cloned1";
            while ($repository->findOneByName($newname)){
                $newname++;
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
            $jobs = $repository->findBy(array('client' => $idoriginal));


          	foreach($jobs as $job) {
                $repository = $this->getDoctrine()->getRepository('BinovoElkarBackupBundle:Client');
              	$client = $repository->find($idnew);

              	$newjob = clone $job;
              	$newjob->setClient($client);
                $newjob->setDiskUsage(0);
                $newjob->setStatus('');
              	$newem = $this->getDoctrine()->getManager();
              	$newem->persist($newjob);
              	$newem->flush();
                $newem->detach($newjob);
          	}

	      } catch (Exception $e) {
	           $this->get('session')->getFlashBag()->add('clone',
                                                          $t->trans('Unable to clone your client: %extrainfo%',
                                                                    array('%extrainfo%' => $e->getMessage()),
                                                                    'BinovoElkarBackup'));
	      }

        //$response = new Response($t->trans('Client cloned successfully', array(), 'BinovoElkarBackup'));
        //$response->headers->set('Content-Type', 'text/plain');

        // Custom normalizer
        //$normalizers[] = new ClientNormalizer();
        //$normalizer = new ObjectNormalizer();
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
        //syslog(LOG_ERR, "Obtaining first job: ".$client->getJobs()[0]->getId());
        syslog(LOG_ERR, "Serializing object: ".$client->getName());
        $json = $serializer->serialize($client, 'json');
        syslog(LOG_ERR, "Output: ".print_r($json, TRUE));
        $response = new JsonResponse(array('msg'    => $t->trans('Client cloned successfully', array(), 'BinovoElkarBackup'),
                                           'action' => 'callbackClonedClient',
                                           'data'   =>  array($json)));
        return $response;
    }

    /**
     * @Route("/job/generate/token/", name="generateToken")
     * @Method("POST")
     * @Template()
     */

    public function generateTokenAction(Request $request)
    {
      $t = $this->get('translator');
      $randtoken = md5(uniqid(rand(), true));


      $response = new JsonResponse(array('token'  => $randtoken,
                                         'msg'    => $t->trans('New Token have been generated', array(), 'BinovoElkarBackup')));
      return $response;


    }

    /**
     * @Route("/config/preferences", name="managePreferences")
     * @Template()
     */
    public function managePreferencesAction(Request $request)
    {
        $t = $this->get('translator');
        // Get current user
        $user = $this->get('security.context')->getToken()->getUser();
        $form = $this->createForm(new PreferencesType(), $user, array('translator' => $t,
                                                                      'validation_groups' => array('preferences')
                                                                    ));

        if ($request->isMethod('POST')) {
              $form->bind($request);
              $data = $form->getData();
              $em = $this->getDoctrine()->getManager();
              $em->persist($data);
              $this->info('Save preferences for user %username%.',
                          array('%username%' => $user->getUsername()),
                          array('link' => $this->generateUserRoute($user->getId())));
              $em->flush();

              $language = $form['language']->getData();
              $this->setLanguage($request, $language);
              return $this->redirect($this->generateUrl('managePreferences'));

        } else {
          $this->info('Manage preferences for user %username%.',
                      array('%username%' => $user->getUsername()),
                      array('link' => $this->generateUserRoute($user->getId())));
          $this->getDoctrine()->getManager()->flush();

          return $this->render('BinovoElkarBackupBundle:Default:preferences.html.twig',
                               array('form' => $form->createView()));
        }
    }

    private function getUserPreference(Request $request, $param){
        $response = null;
        $user = $this->get('security.context')->getToken()->getUser();
        if ($param == 'language'){
            $response = $user->getLanguage();
        } elseif ($param == 'linesperpage'){
            $response = $user->getLinesperpage();
        }
        return $response;
    }



}
