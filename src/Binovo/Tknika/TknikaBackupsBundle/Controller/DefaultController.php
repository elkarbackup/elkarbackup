<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Controller;

use Binovo\Tknika\TknikaBackupsBundle\Entity\Client;
use Binovo\Tknika\TknikaBackupsBundle\Entity\Job;
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

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:clients.html.twig',
                             array('pagination' => $pagination));
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

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:policies.html.twig',
                             array('pagination' => $pagination));
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
            ->add('oldPassword' , 'password')
            ->add('newPassword' , 'password')
            ->add('newPassword2', 'password')
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
            }
            if ($encoder->encodePassword($data['oldPassword'], $user->getSalt()) !== $user->getPassword()) {
                $ok = false;
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Wrong old password", array(), 'BinovoTknikaBackups'));
            }
            if ($ok) {
                $user->setPassword($encoder->encodePassword($data['newPassword'], $user->getSalt()));
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $manager->flush();
                $this->get('session')->getFlashBag()->add('changePassword',
                                                          $t->trans("Password changed", array(), 'BinovoTknikaBackups'));
            }

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
        $db = $this->getDoctrine();
        $repository = $db->getRepository('BinovoTknikaTknikaBackupsBundle:User');
        $manager = $db->getManager();
        $user = $repository->find($id);
        $manager->remove($user);
        $manager->flush();

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
            $em->flush();

            return $this->redirect($this->generateUrl('showUsers'));
        } else {

            return $this->render('BinovoTknikaTknikaBackupsBundle:Default:user.html.twig',
                                 array('form' => $form->createView()));
        }
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

        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:users.html.twig',
                             array('pagination' => $pagination));
    }
}
