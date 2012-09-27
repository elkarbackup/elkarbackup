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

class DefaultController extends Controller
{
    /**
     * @Route("/about", name="about")
     * @Template()
     */
    public function aboutAction(Request $request)
    {

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
        $form = $this->createForm(new ClientType(), $client);
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
        $form = $this->createForm(new ClientType(), $client);
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
        if ('new' === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException('Unable to find Client entity ' . $idClient);
            }
            $job->setClient($client);
        } else {
            $job = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Job')->find($idJob);
        }
        $form = $this->createForm(new JobType(), $job);
        return $this->render('BinovoTknikaTknikaBackupsBundle:Default:job.html.twig',
                             array('form' => $form->createView()));
    }


    /**
     * @Route("/client/{idClient}/job/{idJob}", requirements={"idClient" = "\d+", "idJob" = "\d+"}, defaults={"idJob" = "-1"}, name="saveJob")
     * @Method("POST")
     * @Template()
     */
    public function saveJobAction(Request $request, $idClient, $idJob)
    {
        if ("-1" === $idJob) {
            $job = new Job();
            $client = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Client')->find($idClient);
            if (null == $client) {
                throw $this->createNotFoundException('Unable to find Client entity ' . $idClient);
            }
            $job->setClient($client);
            // $job->setPolicy(new Policy());
        } else {
            $repository = $this->getDoctrine()
                ->getRepository('BinovoTknikaTknikaBackupsBundle:Job');
            $job = $repository->find($idJob);
        }
        $form = $this->createForm(new JobType(), $job);
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

    }

}
