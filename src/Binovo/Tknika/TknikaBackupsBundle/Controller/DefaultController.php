<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Controller;

use \Exception;
use \RuntimeException;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Client;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Job;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Message;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Policy;
use Binovo\Tknika\TknikaBackupsBundle\Entity\User;
use Binovo\Tknika\TknikaBackupsBundle\Form\Type\ClientType;
use Binovo\Tknika\TknikaBackupsBundle\Form\Type\JobType;
use Binovo\Tknika\TknikaBackupsBundle\Form\Type\PolicyType;
use Binovo\Tknika\TknikaBackupsBundle\Form\Type\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\SecurityContext;

class DefaultController extends Controller
{

    const PUBLIC_KEY_FILE  = '/var/lib/tknikabackups/.ssh/id_rsa.pub';

    protected function info($msg, $translatorParams = array(), $context = array())
    {
        $logger = $this->get('BnvWebLogger');
        $context = array_merge(array('source' => 'DefaultController'), $context);
        $logger->info($this->trans($msg, $translatorParams, 'BinovoTknikaBackups'), $context);
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

    protected function generateUserRoute($id)
    {
        return $this->generateUrl('editUser',
                                  array('id' => $id));
    }

    /**
     * @Route("/about", name="about")
     * @Template()
     */
    public function aboutAction(Request $request)
    {
        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:about.html.twig');
    }

    /**
     * @Route("/config/publickey", name="downloadPublicKey")
     * @Template()
     */
    public function downloadPublicKeyAction(Request $request)
    {
        if (!file_exists(self::PUBLIC_KEY_FILE)) {
            throw $this->createNotFoundException($this->trans('Unable to find public key:'));
        }
        $headers = array('Content-Type'        => 'text/plain',
                         'Content-Disposition' => sprintf('attachment; filename="Publickey.pub"'));

        return new Response(file_get_contents(self::PUBLIC_KEY_FILE), 200, $headers);
    }

    public function trans($msg, $params = array(), $domain = 'BinovoTknikaBackups')
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
        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
        $manager = $db->getManager();
        $client = $repository->find($id);
        try {
            $manager->remove($client);
            $msg = new Message('DefaultController', 'TickCommand',
                               json_encode(array('command' => "tknikabackups:delete_job_backups",
                                                 'client'  => (int)$id)));
            $manager->persist($msg);
            $this->info('Client "%clientid%" deleted', array('%clientid%' => $id), array('link' => $this->generateClientRoute($id)));
            $manager->flush();
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->add('clients',
                                                      $t->trans('Unable to delete client: %extrainfo%',
                                                                array('%extrainfo%' => $e->getMessage()), 'BinovoTknikaBackups'));
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

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        $this->info('Log in attempt with user: %username%', array('%username%' => $session->get(SecurityContext::LAST_USERNAME)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:login.html.twig', array(
                                 'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                                 'error'         => $error,
                                 'supportedLocales' => array(array('es', 'Español'),
                                                             array('en', 'English'),
                                                             array('eu', 'Euskara'))));
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
            $repository = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
            $client = $repository->find($id);
        }
        foreach ($client->getJobs() as $job) {
            $job->setLogEntry($this->getLastLogForLink(sprintf('%%/client/%d/job/%d', $client->getId(), $job->getId())));
        }
        $form = $this->createForm(new ClientType(), $client, array('translator' => $this->get('translator')));
        $this->info('View client %clientid%',
                    array('%clientid%' => $id),
                    array('link' => $this->generateClientRoute($id)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:client.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/client/{id}", requirements={"id" = "\d+"}, defaults={"id" = "-1"}, name="saveClient")
     * @Method("POST")
     * @Template()
     */
    public function saveClientAction(Request $request, $id)
    {
        $t = $this->get('translator');
        if ("-1" === $id) {
            $client = new Client();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
            $client = $repository->find($id);
        }
        $jobsToDelete = array(); // we store here the jobs that are missing in the form
        foreach ($client->getJobs() as $job) {
            $jobsToDelete[$job->getId()] = $job;
        }
        $form = $this->createForm(new ClientType(), $client, array('translator' => $t));
        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($client->getJobs() as $job) {
                if (isset($jobsToDelete[$job->getid()])) {
                    unset($jobsToDelete[$job->getid()]);
                }
            }
            try {
                foreach ($jobsToDelete as $idJob => $job) {
                    $client->getJobs()->removeElement($job);
                    $em->remove($job);
                    $msg = new Message('DefaultController', 'TickCommand',
                                       json_encode(array('command' => "tknikabackups:delete_job_backups",
                                                         'client'  => (int)$id,
                                                         'job'     => $idJob)));
                    $em->persist($msg);
                    $this->info('Delete client %clientid%, job %jobid%',
                                array('%clientid%' => $client->getId(),
                                      '%jobid%' => $job->getId()),
                                array('link' => $this->generateJobRoute($job->getId(), $client->getId())));
                }
                $em->persist($client);
                $this->info('Save client %clientid%',
                        array('%clientid%' => $client->getId()),
                            array('link' => $this->generateClientRoute($client->getId()))
                    );
                $em->flush();

                return $this->redirect($this->generateUrl('showClients'));
            } catch (Exception $e) {
                throw $e;
                $this->get('session')->getFlashBag()->add('client',
                                                          $t->trans('Unable to save your changes: %extrainfo%',
                                                                    array('%extrainfo%' => $e->getMessage()),
                                                                    'BinovoTknikaBackups'));

                return $this->redirect($this->generateUrl('editClient', array('id' => $client->getId())));
            }
        } else {

            return $this->render('BinovoTknikaTknikaBackupsBundle:Default:client.html.twig',
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
        $t = $this->get('translator');
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
        $manager = $db->getManager();
        $job = $repository->find($id);
        try {
            $manager->remove($job);
            $this->info('Delete client %clientid%, job "%jobid%"', array('%clientid%' => $idClient, '%jobid%' => $idJob), array('link' => $this->generateJobRoute($idJob, $idClient)));
            $manager->flush();
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->add('client',
                                                      $t->trans('Unable to delete job: %extrainfo%',
                                                                array('%extrainfo%' => $e->getMessage()), 'BinovoTknikaBackups'));
        }

        return $this->redirect($this->generateUrl('editClient', array('id' => $idClient)));
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
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($this->trans('Unable to find Client entity:') . $idClient);
            }
            $job->setClient($client);
            $job->setOwner($this->get('security.context')->getToken()->getUser());
        } else {
            $job = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Job')->find($idJob);
        }
        $form = $this->createForm(new JobType(), $job, array('translator' => $this->get('translator')));
        $this->info('View client %clientid%, job %jobid%',
                    array('%clientid%' => $idClient, '%jobid%' => $idJob),
                    array('link' => $this->generateJobRoute($idJob, $idClient)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:job.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/config", requirements={"idClient" = "\d+", "idJob" = "\d+"}, name="showJobConfig")
     * @Method("GET")
     * @Template()
     */
    public function showJobConfigAction(Request $request, $idClient, $idJob)
    {
        $t = $this->get('translator');
        $repository = $this->getDoctrine()->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
        $job = $repository->find($idJob);
        if (null == $job || $job->getClient()->getId() != $idClient) {
            throw $this->createNotFoundException($t->trans('Unable to find Job entity: ', array(), 'BinovoTknikaBackups') . $idClient . " " . $idJob);
        }
        $policy = $job->getPolicy();
        $retains = $policy->getRetains();
        $includes = array();
        $include = $policy->getInclude();
        if ($include) {
            $includes = explode("\n", $include);
        }
        $excludes = array();
        $exclude = $policy->getExclude();
        if ($exclude) {
            $excludes = explode("\n", $exclude);
        }
        $syncFirst = $policy->getSyncFirst();
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $this->info('Show job config %clientid%, job %jobid%',
                    array('%clientid%' => $idClient, '%jobid%' => $idJob),
                    array('link' => $this->generateJobRoute($idJob, $idClient)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:rsnapshotconfig.txt.twig',
                             array('cmdPreExec'   => $job->getPreScript()  ? $job->getScriptPath('pre') : '',
                                   'cmdPostExec'  => $job->getPostScript() ? $job->getScriptPath('post'): '',
                                   'excludes'     => $excludes,
                                   'idClient'     => sprintf('%04d', $idClient),
                                   'idJob'        => sprintf('%04d', $idJob),
                                   'includes'     => $includes,
                                   'backupDir'    => $this->container->getParameter('backup_dir'),
                                   'retains'      => $retains,
                                   'tmp'          => '/tmp',
                                   'snapshotRoot' => $job->getSnapshotRoot(),
                                   'syncFirst'    => $syncFirst,
                                   'url'          => $job->getUrl()),
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
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($t->trans('Unable to find Client entity:', array(), 'BinovoTknikaBackups') . $idClient);
            }
            $job->setClient($client);
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
            $job = $repository->find($idJob);
        }
        $storedOwner = $job->getOwner();
        $form = $this->createForm(new JobType(), $job, array('translator' => $t));
        $form->bind($request);
        if ($form->isValid()) {
            $job = $form->getData();
            if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) { // only allow chown to admin
                $job->setOwner($storedOwner);
            }
            if ($job->getOwner() == null) {
                $job->setOwner($this->get('security.context')->getToken()->getUser());
            }
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
                                                                    'BinovoTknikaBackups'));

                return $this->redirect($this->generateJobRoute($job->getId(), $job->getClient()->getId()));
            }

            return $this->redirect($this->generateUrl('editClient', array('id' => $idClient)));
        } else {

            return $this->render('BinovoTknikaTknikaBackupsBundle:Default:job.html.twig',
                                 array('form' => $form->createView()));
        }
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}/backup/{action}/{path}", requirements={"idClient" = "\d+", "idJob" = "\d+", "path" = ".*", "action" = "view|download"}, defaults={"path" = "/"}, name="showJobBackup")
     * @Method("GET")
     */
    public function showJobBackupAction(Request $request, $idClient, $idJob, $action, $path)
    {
        $t = $this->get('translator');
        $repository = $this->getDoctrine()
            ->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
        $job = $repository->find($idJob);
        if ($job->getClient()->getId() != $idClient) {
            throw $this->createNotFoundException($t->trans('Unable to find Job entity: ', array(), 'BinovoTknikaBackups') . $idClient . " " . $idJob);
        }

        $realPath = realpath($job->getSnapshotRoot() . '/' . $path);
        if (false == $realPath) {
            throw $this->createNotFoundException($t->trans('Path not found:', array(), 'BinovoTknikaBackups') . $path);
        }
        if (0 !== strpos($realPath, $job->getSnapshotRoot())) {
            throw $this->createNotFoundException($t->trans('Path not found:', array(), 'BinovoTknikaBackups') . $path);
        }
        if (is_dir($realPath)) {
            if ('download' == $action) {
                $headers = array('Content-Type'        => 'application/x-gzip',
                                 'Content-Disposition' => sprintf('attachment; filename="%s.tar.gz"', basename($realPath)));
                $f = function() use ($realPath){
                    $command = sprintf('cd %s; tar zc %s', dirname($realPath), basename($realPath));
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
                $content = scandir($realPath);
                if (false === $content) {
                    $content = array();
                }
                foreach ($content as &$aFile) {
                    $date = new \DateTime();
                    $date->setTimestamp(filemtime($realPath . '/' . $aFile));
                    $aFile = array($aFile, $date);
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

                return $this->render('BinovoTknikaTknikaBackupsBundle:Default:directory.html.twig',
                                     array('content'  => $content,
                                           'job'      => $job,
                                           'path'     => $path,
                                           'realPath' => $realPath));
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
        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:home.html.twig');
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
        $t = $this->get('translator');
        if ('new' === $id) {
            $policy = new Policy();
        } else {
            $policy = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Policy')->find($id);
        }
        $form = $this->createForm(new PolicyType(), $policy, array('translator' => $t));
        $this->info('View policy %policyname%',
                    array('%policyname%' => $policy->getName()),
                    array('link' => $this->generatePolicyRoute($policy->getId())));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:policy.html.twig',
                             array('form' => $form->createView()));
    }

    /**
     * @Route("/policy/{id}/delete", name="deletePolicy")
     * @Method("POST")
     * @Template()
     */
    public function deletePolicyAction(Request $request, $id)
    {
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:Policy');
        $manager = $db->getManager();
        $policy = $repository->find($id);
        $manager->remove($policy);
        $this->info('Delete policy %policyname%',
                    array('%policyname%' => $policy->getName()),
                    array('link' => $this->generatePolicyRoute($id)));
        $manager->flush();

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
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Policy');
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

            return $this->render('BinovoTknikaTknikaBackupsBundle:Default:policy.html.twig',
                                 array('form' => $form->createView()));
        }
    }

    /**
     * @Route("/clients", name="showClients")
     * @Template()
     */
    public function showClientsAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
        $query = $repository->createQueryBuilder('c')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            10/*limit per page*/
            );
        foreach ($pagination as $i => $client) {
            $client->setLogEntry($this->getLastLogForLink('%/client/' . $client->getId()));
        }
        $this->info('View clients',
                    array(),
                    array('link' => $this->generateUrl('showClients')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:clients.html.twig',
                             array('pagination' => $pagination));
    }

    public function getLastLogForLink($link)
    {
        $lastLog = null;
        $em = $this->getDoctrine()->getManager();
        // :WARNING: this call might end up slowing things too much.
        $dql =<<<EOF
SELECT l
FROM  BinovoTknikaTknikaBackupsBundle:LogRecord l
WHERE l.source = 'TickCommand' AND l.link LIKE :link
ORDER BY l.id DESC
EOF;
        $query = $em->createQuery($dql)->setParameter('link', $link);
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
            ->getRepository('BinovoTknikaTknikaBackupsBundle:LogRecord');
        $queryBuilder = $repository->createQueryBuilder('l')
            ->addOrderBy('l.dateTime', 'DESC');
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
            10/*limit per page*/
            );
        $this->info('View logs',
                    array(),
                    array('link' => $this->generateUrl('showLogs')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:logs.html.twig',
                             array('pagination' => $pagination,
                                   'levels' => array('options' => array(Job::NOTIFICATION_LEVEL_ALL     => $t->trans('All messages'   , array(), 'BinovoTknikaBackups'),
                                                                        Job::NOTIFICATION_LEVEL_INFO    => $t->trans('Notices and up' , array(), 'BinovoTknikaBackups'),
                                                                        Job::NOTIFICATION_LEVEL_WARNING => $t->trans('Warnings and up', array(), 'BinovoTknikaBackups'),
                                                                        Job::NOTIFICATION_LEVEL_ERROR   => $t->trans('Errors and up'  , array(), 'BinovoTknikaBackups'),
                                                                        Job::NOTIFICATION_LEVEL_NONE    => $t->trans('None'           , array(), 'BinovoTknikaBackups')),
                                                     'value'   => isset($formValues['filter[gte][l.level]']) ? $formValues['filter[gte][l.level]'] : null,
                                                     'name'    => 'filter[gte][l.level]'),
                                   'object' => array('value'   => isset($formValues['filter[like][l.link]']) ? $formValues['filter[like][l.link]'] : null,
                                                     'name'    => 'filter[like][l.link]'),
                                   'source' => array('options' => array(''                  => $t->trans('All', array(), 'BinovoTknikaBackups'),
                                                                        'DefaultController' => 'DefaultController',
                                                                        'TickCommand'       => 'TickCommand'),
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
            ->getRepository('BinovoTknikaTknikaBackupsBundle:Policy');
        $query = $repository->createQueryBuilder('c')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            10/*limit per page*/
            );
        $this->info('View policies',
                    array(),
                    array('link' => $this->generateUrl('showPolicies')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:policies.html.twig',
                             array('pagination' => $pagination));
    }

    /**
     * @Route("/config/repositorybackupscript", name="getRepositoryBackupScript")
     * @Template()
     */
    public function getRepositoryBackupScriptAction(Request $request)
    {
        $response = $this->render('BinovoTknikaTknikaBackupsBundle:Default:copyrepository.sh.twig',
                                  array('backupsroot'   => $this->container->getParameter('backup_dir'),
                                        'backupsuser'   => 'tknikabackups',
                                        'mysqldb'       => $this->container->getParameter('database_name'),
                                        'mysqlhost'     => $this->container->getParameter('database_host'),
                                        'mysqlpassword' => $this->container->getParameter('database_password'),
                                        'mysqluser'     => $this->container->getParameter('database_user'),
                                        'server'        => $request->getHttpHost()));
        $response->headers->set('Content-Type'       , 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="copyrepository.sh"');

        return $response;
    }

    /**
     * @Route("/config/params", name="manageParameters")
     * @Template()
     */
    public function manageParametersAction(Request $request)
    {
        $t = $this->get('translator');
        $params = array('database_host'     => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('MySQL host'            , array(), 'BinovoTknikaBackups')),
                        'database_port'     => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('MySQL port'            , array(), 'BinovoTknikaBackups')),
                        'database_name'     => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('MySQL DB name'         , array(), 'BinovoTknikaBackups')),
                        'database_user'     => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('MySQL user'            , array(), 'BinovoTknikaBackups')),
                        'database_password' => array('type' => 'password', 'required' => false, 'label' => $t->trans('MySQL password'        , array(), 'BinovoTknikaBackups')),
                        'mailer_transport'  => array('type' => 'choice'  , 'required' => false, 'choices' => array('gmail'    => 'gmail',
                                                                                                                   'mail'     => 'mail',
                                                                                                                   'sendmail' => 'sendmail',
                                                                                                                   'smtp'     => 'smtp'),
                                                                                                'label' => $t->trans('Mailer transpor'       , array(), 'BinovoTknikaBackups')),
                        'mailer_host'       => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('Mailer host'           , array(), 'BinovoTknikaBackups')),
                        'mailer_user'       => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('Mailer user'           , array(), 'BinovoTknikaBackups')),
                        'mailer_password'   => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('Mailer password'       , array(), 'BinovoTknikaBackups')),
                        'upload_dir'        => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('Upload dir'            , array(), 'BinovoTknikaBackups')),
                        'max_log_age'       => array('type' => 'choice'  , 'required' => false, 'choices' => array('P1D' => $t->trans('One day'    , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P1W' => $t->trans('One week'   , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P2W' => $t->trans('Two weeks'  , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P3W' => $t->trans('Three weeks', array(), 'BinovoTknikaBackups'),
                                                                                                                   'P1M' => $t->trans('A month'    , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P6M' => $t->trans('Six months' , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P1Y' => $t->trans('A year'     , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P2Y' => $t->trans('Two years'  , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P3Y' => $t->trans('Three years', array(), 'BinovoTknikaBackups'),
                                                                                                                   'P4Y' => $t->trans('Four years' , array(), 'BinovoTknikaBackups'),
                                                                                                                   'P5Y' => $t->trans('Five years' , array(), 'BinovoTknikaBackups'),
                                                                                                                   ''    => $t->trans('Never')     , array(), 'BinovoTknikaBackups'),
                                                                                                'label' => $t->trans('Remove logs older than', array(), 'BinovoTknikaBackups')),
                        'backup_dir'        => array('type' => 'text'    , 'required' => false, 'label' => $t->trans('Backups dir'           , array(), 'BinovoTknikaBackups')),
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
                        $ok = $this->setParameter($paramName, $paramValue);
                    }
                } else {
                    if ($paramValue != $this->container->getParameter($paramName)) {
                        $ok = $this->setParameter($paramName, $paramValue);
                    }
                }
                if (!$ok) {
                    $this->get('session')->getFlashBag()->add('manageParameters',
                                                              $t->trans('Error saving parameter "%param%"',
                                                                        array('%param%' => $params[$paramName]['label']),
                                                                        'BinovoTknikaBackups'));
                    $allOk = false;
                }
            }
            if ($allOk) {
                $this->get('session')->getFlashBag()->add('manageParameters',
                                                          $t->trans('Parameters updated',
                                                                    array(),
                                                                    'BinovoTknikaBackups'));
            }
            $result = $this->redirect($this->generateUrl('manageParameters'));
        } else {
            $result = $this->render('BinovoTknikaTknikaBackupsBundle:Default:params.html.twig',
                                    array('form'            => $form->createView(),
                                          'showKeyDownload' => file_exists(self::PUBLIC_KEY_FILE)));
        }
        $this->getDoctrine()->getManager()->flush();
        // clear cache so that changes take effect
        $realCacheDir = $this->container->getParameter('kernel.cache_dir');
        $oldCacheDir  = $realCacheDir.'_old';
        $this->container->get('cache_clearer')->clear($realCacheDir);
        rename($realCacheDir, $oldCacheDir);
        $this->container->get('filesystem')->remove($oldCacheDir);

        return $result;
    }

    /**
     * Sets the value of a filed in the parameters.yml file to the given value
     */
    public function setParameter($name, $value)
    {
        $paramsFilename = dirname(__FILE__) . '/../../../../../app/config/parameters.yml';
        $paramsFile = file_get_contents($paramsFilename);
        if (false == $paramsFile) {
            return false;
        }
        $updated = preg_replace("/$name:.*/", "$name: $value", $paramsFile);
        $ok = file_put_contents($paramsFilename, $updated);
        if ($ok) {
            $this->info('Set Parameter %paramname%',
                        array('%paramname%' => $name),
                        array('link' => $this->generateUrl('showPolicies')));
        } else {
            $this->info('Set Parameter %paramname%',
                        array('%paramname%' => $name),
                        array('link' => $this->generateUrl('showPolicies')));
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
            ->add('oldPassword' , 'password', array('label' => $t->trans('Old password'        , array(), 'BinovoTknikaBackups')))
            ->add('newPassword' , 'password', array('label' => $t->trans('New password'        , array(), 'BinovoTknikaBackups')))
            ->add('newPassword2', 'password', array('label' => $t->trans('Confirm new password', array(), 'BinovoTknikaBackups')))
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
                                                          $t->trans("Passwords do not match", array(), 'BinovoTknikaBackups'));
                $this->info('Change password for user %username% failed. Passwords do not match.',
                            array('%username%' => $user->getUsername()),
                            array('link' => $this->generateUserRoute($user->getId())));
            }
            if ($encoder->encodePassword($data['oldPassword'], $user->getSalt()) !== $user->getPassword()) {
                $ok = false;
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Wrong old password", array(), 'BinovoTknikaBackups'));
                $this->info('Change password for user %username% failed. Wrong old password.',
                            array('%username%' => $user->getUsername()),
                            array('link' => $this->generateUserRoute($user->getId())));
            }
            if ($ok) {
                $user->setPassword($encoder->encodePassword($data['newPassword'], $user->getSalt()));
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Password changed", array(), 'BinovoTknikaBackups'));
                $this->info('Change password for user %username%.',
                            array('%username%' => $user->getUsername()),
                            array('link' => $this->generateUserRoute($user->getId())));
            }
            $manager->flush();

            return $this->redirect($this->generateUrl('changePassword'));
        } else {

            return $this->render('BinovoTknikaTknikaBackupsBundle:Default:password.html.twig',
                                 array('form'    => $form->createView()));
        }
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
            $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:User');
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
                ->getRepository('BinovoTknikaTknikaBackupsBundle:User');
            $user = $repository->find($id);
        }
        $form = $this->createForm(new UserType(), $user, array('translator' => $t));
        $this->info('View user %username%.',
                    array('%username%' => $user->getUsername()),
                    array('link' => $this->generateUserRoute($id)));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:user.html.twig',
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
                ->getRepository('BinovoTknikaTknikaBackupsBundle:User');
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

            return $this->render('BinovoTknikaTknikaBackupsBundle:Default:user.html.twig',
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
            ->getRepository('BinovoTknikaTknikaBackupsBundle:User');
        $query = $repository->createQueryBuilder('c')
            ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            10/*limit per page*/
            );
        $this->info('View users',
                    array(),
                    array('link' => $this->generateUrl('showUsers')));
        $this->getDoctrine()->getManager()->flush();

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:users.html.twig',
                             array('pagination' => $pagination));
    }
}
