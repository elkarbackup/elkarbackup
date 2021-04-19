<?php
namespace App\Service;

use App\Entity\Message;
use App\Service\LoggerService;
use App\Service\RouterService;
use App\Exception\PermissionException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use \Exception;

class JobService
{
    private $authChecker;
    private $em;
    private $logger;
    private $router;
    private $security;
    
    public function __construct(AuthorizationCheckerInterface $authChecker, EntityManagerInterface $em, LoggerService $logger, RouterService $router, 
        Security $security)
    {
        $this->authChecker = $authChecker;
        $this->em          = $em;
        $this->logger      = $logger;
        $this->router      = $router;
        $this->security    = $security;
    }
    
    public function delete ($id)
    {
//         $access = $this->checkPermissions($idClient);
//         if ($access == False) {
//             return $this->redirect($this->generateUrl('showClients'));
//         }
//         $t = $this->translator;
//         $db = $this->getDoctrine();
//         $repository = $db->getRepository('App:Job');
//         $manager = $db->getManager();
        $job = $this->em->getRepository('App:Job')->find($id);
        $idClient = $job->getClient()->getId();
        $this->assertPermission($idClient);
        $queue = $this->em->getRepository('App:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getId() == $id) {
//                 $response = new JsonResponse(array(
//                     'error' => true,
//                     'msg' => $t->trans(
//                         'Could not delete job %jobName%, it is enqueued.',
//                         array('%jobName%' => $job->getName()),
//                         'BinovoElkarBackup'
//                         ),
//                     'data' => array($idJob)
//                 ));
                $this->logger->err(
                    'Could not delete job %jobName%, it is enqueued.',
                    array('%jobName%' => $job->getName()),
                    array('link' => $this->router->generateJobRoute($id, $idClient))
                    );
                $this->em->flush();
                throw new Exception(sprintf('Could not delete job %s, it is enqueued.', $job->getName()));
//                 return $response;
            }
        }
        
//         try {
            $this->em->remove($job);
            $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
                'command' => "elkarbackup:delete_job_backups",
                'client' => (int) $idClient,
                'job' => (int) $id
            )));
            $this->em->persist($msg);
            $this->logger->info(
                'Client %clientid%, job "%jobid%" deleted successfully.',
                array('%clientid%' => $idClient,'%jobid%' => $id),
                array('link' => $this->router->generateJobRoute($id, $idClient))
                );
            $this->em->flush();
//             $response = new JsonResponse(array(
//                 'error' => false,
//                 'msg' => $t->trans(
//                     'Client %clientid%, job "%jobid%" deleted successfully.',
//                     array('%clientid%' => $idClient,'%jobid%' => $idJob),
//                     'BinovoElkarBackup'
//                     ),
//                 'action' => 'deleteJobRow',
//                 'data' => array($idJob)
//             ));
//         } catch (Exception $e) {
//             $response = new JsonResponse(array(
//                 'error' => false,
//                 'msg' => $t->trans(
//                     'Unable to delete job: %extrainfo%',
//                     array('%extrainfo%' => $e->getMessage()),
//                     'BinovoElkarBackup'
//                     ),
//             ));
//         }
//         return $response;
    }

    public function save ($job)
    {
//         $t = $this->translator;
//         if ("-1" === $idJob) {
//             $job = new Job();
//             $client = $this->getDoctrine()
//             ->getRepository('App:Client')
//             ->find($idClient);
//             if (null == $client) {
//                 throw $this->createNotFoundException($t->trans(
//                     'Unable to find Client entity:',
//                     array(),
//                     'BinovoElkarBackup'
//                     ) . $idClient);
//             }
//             $job->setClient($client);
//         } else {
//             $repository = $this->getDoctrine()->getRepository('App:Job');
//             $job = $repository->find($idJob);
//        }
//         $form = $this->createForm(
//             JobType::class,
//             $job,
//             array('translator' => $t)
//             );
//         $form->handleRequest($request);
//         if ($form->isValid()) {
//             $job = $form->getData();
//             try {
                $this->em->persist($job);
                $this->logger->info(
                    'Save client %clientid%, job %jobid%',
                    array(
                        '%clientid%' => $job->getClient()->getId(),
                        '%jobid%' => $job->getId()
                    ),
                    array('link' => $this->router->generateJobRoute(
                        $job->getId(),
                        $job->getClient()->getId()
                        ))
                    );
                $this->em->flush();
//             } catch (Exception $e) {
//                 $this->get('session')->getFlashBag()->add('job', $t->trans(
//                     'Unable to save your changes: %extrainfo%',
//                     array('%extrainfo%' => $e->getMessage()),
//                     'BinovoElkarBackup'
//                     ));
//             }
            
//             return $this->redirect($this->generateUrl('showClients'));
//         } else {
            
//             return $this->render(
//                 'default/job.html.twig',
//                 array('form' => $form->createView())
//                 );
//         }
    }
    
    private function assertPermission($idClient)
    {
        $repository = $this->em->getRepository('App:Client');
        $client = $repository->find($idClient);
        
        if ($client->getOwner() == $this->security->getToken()->getUser() ||
            $this->authChecker->isGranted('ROLE_ADMIN'))
        {
            return;
        }
        throw new PermissionException("Unable to delete client: Permission denied.");
    }
}

