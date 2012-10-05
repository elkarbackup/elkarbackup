<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Controller;

use Binovo\Tknika\TknikaBackupsBundle\Entity\Client;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Job;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Policy;
use Binovo\Tknika\TknikaBackupsBundle\Form\Type\ClientType;
use Binovo\Tknika\TknikaBackupsBundle\Form\Type\JobType;
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
    /**
     * @Route("/about", name="about")
     * @Template()
     */
    public function aboutAction(Request $request)
    {
        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:about.html.twig');
    }

    /**
     * @Route("/client/{id}/delete", name="deleteClient")
     * @Method("POST")
     * @Template()
     */
    public function deleteClientAction(Request $request, $id)
    {
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
        $manager = $db->getManager();
        $client = $repository->find($id);
        $manager->remove($client);
        $manager->flush();
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

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:login.html.twig', array(
                                 'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                                 'error'         => $error,
                                 ));
    }

    /**
     * @Route("/client/{id}", name="editClient")
     * @Method("GET")
     * @Template()
     */
    public function editClientAction(Request $request, $id)
    {
        $t = $this->get('translator');
        if ('new' === $id) {
            $client = new Client();
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client');
            $client = $repository->find($id);
        }
        $form = $this->createForm(new ClientType(), $client, array('translator' => $t));
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
            foreach ($jobsToDelete as $job) {
                $client->getJobs()->removeElement($job);
                $em->remove($job);
            }
            $em->persist($client);
            $em->flush();

            return $this->redirect($this->generateUrl('showClients'));
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
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
        $manager = $db->getManager();
        $job = $repository->find($id);
        $manager->remove($job);
        $manager->flush();
        return $this->redirect($this->generateUrl('editClient', array('id' => $idClient)));
    }

    /**
     * @Route("/client/{idClient}/job/{idJob}", name="editJob")
     * @Method("GET")
     * @Template()
     */
    public function editJobAction(Request $request, $idClient, $idJob)
    {
        $t = $this->get('translator');
        if ('new' === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException($t->trans('Unable to find Client entity: ') . $idClient, array(), 'BinovoTknikaBackups');
            }
            $job->setClient($client);
        } else {
            $job = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Job')->find($idJob);
        }
        $form = $this->createForm(new JobType(), $job, array('translator' => $t));
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
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:rsnapshotconfig.txt.twig',
                             array('cmdPreExec'    => $job->getPreScript()  ? $job->getScriptPath('pre') : '',
                                   'cmdPostExec'   => $job->getPostScript() ? $job->getScriptPath('post'): '',
                                   'idClient'      => sprintf('%04d', $idClient),
                                   'idJob'         => sprintf('%04d', $idJob),
                                   'backupDir'     => $this->container->getParameter('backup_dir'),
                                   'tmp'           => '/tmp',
                                   'snapshotRoot'  => $job->getSnapshotRoot(),
                                   'url'           => $job->getUrl()),
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
                throw $this->createNotFoundException($t->trans('Unable to find Client entity: ', array(), 'BinovoTknikaBackups') . $idClient);
            }
            $job->setClient($client);
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
            $job = $repository->find($idJob);
        }
        $form = $this->createForm(new JobType(), $job, array('translator' => $t));
        $form->bind($request);

        if ($form->isValid())
        {
            $job = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush();

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
            throw $this->createNotFoundException($t->trans('Path not found: ', array(), 'BinovoTknikaBackups') . $path);
        }
        if (0 !== strpos($realPath, $job->getSnapshotRoot())) {
            throw $this->createNotFoundException($t->trans('Path not found: ', array(), 'BinovoTknikaBackups') . $path);
        }
        if (is_dir($realPath)) {
            if ('download' == $action) {
                $headers = array('Content-Type'        => 'application/x-gzip',
                                 'Content-Disposition' => sprintf('attachment; filename="%s.tar.gz"', basename($realPath)));
                $f = function() use ($realPath){
                    $command = sprintf('cd %s; tar zc %s', dirname($realPath), basename($realPath));
                    passthru($command);
                };
                return new StreamedResponse($f, 200, $headers);
            } else {
                $content = scandir($realPath);
                if (false === $content) {
                    $content = array();
                }

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
     * @Route("/policy/new", name="newPolicy")
     * @Template()
     */
    public function newPolicyAction(Request $request)
    {
        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:policy.html.twig');
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

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:clients.html.twig',
                             array('pagination' => $pagination));
    }

    /**
     * @Route("/policies", name="showPolicies")
     * @Template()
     */
    public function showPoliciesAction(Request $request)
    {
        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:policies.html.twig');
    }

}
