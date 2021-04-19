<?php
namespace App\Service;

use App\Entity\Message;
use App\Service\LoggerService;
use \Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Exception\PermissionException;

class ClientService
{
    private $em;
    private $authChecker;
    private $logger;
    private $router;
    private $security;
    
    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, LoggerService $logger, Security $security, UrlGeneratorInterface $router){
        $this->em = $em;
        $this->authChecker = $authChecker;
        $this->logger = $logger;
        $this->router = $router;
        $this->security = $security;
    }
    public function delete($id)
    {
        $this->assertPermission($id);
//         $t = $this->translator;
        $repository = $this->em->getRepository('App:Client');
        $client = $repository->find($id);
        $queue = $this->em->getRepository('App:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getClient()->getId() == $id) {
                $this->em->flush();
                $this->logger->err(
                    'Could not delete client %clientName%, it has jobs enqueued.',
                    array('%clientName%' => $client->getName()),
                    array('link' => $this->generateClientRoute($id))
                    );
                throw new Exception("Could not delete client, it has jobs enqueued.");
//                 $response = new JsonResponse(array(
//                     'error' => true,
//                     'msg' => $t->trans(
//                         'Could not delete client %clientName%, it has jobs enqueued.',
//                         array('%clientName%' => $client->getName()),
//                         'BinovoElkarBackup'
//                         ),
//                     'data' => array($id)
//                 ));
            }
        }

//         try {
            $this->em->remove($client);
            $msg = new Message(
                'DefaultController',
                'TickCommand',
                json_encode(array(
                    'command' => "elkarbackup:delete_job_backups",
                    'client' => (int) $id
                ))
                );
            $this->em->persist($msg);
            $this->logger->info(
                'Client "%clientid%" deleted',
                array('%clientid%' => $id),
                array('link' => $this->generateClientRoute($id))
                );
            $this->em->flush();
//             $response = new JsonResponse(array(
//                 'error' => false,
//                 'msg' => $t->trans(
//                     'Client %clientName% deleted successfully.',
//                     array('%clientName%' => $client->getName()),
//                     'BinovoElkarBackup'
//                     ),
//                 'action' => 'deleteClientRow',
//                 'data' => array($id)
//             ));

//         } catch (Exception $e) {
//             $response = new JsonResponse(array(
//                 'error' => false,
//                 'msg' => $t->trans(
//                     'Unable to delete client: %extrainfo%',
//                     array('%extrainfo%' => $e->getMessage()),
//                     'BinovoElkarBackup'
//                     ),
//             ));
//         }
//         return $response;
    }

    public function save($client)
    {
//         $user = $this->security->getToken()->getUser();
//         $actualuserid = $user->getId();
//         if ("-1" === $id) {
//             $client = new Client();
//         } else {
//             $repository = $this->em->getRepository('App:Client');
//             $client = $repository->find($id);
//         }

//         $form = $this->createForm(
//             ClientType::class,
//             $client,
//             array('translator' => $t)
//             );
//         $form->handleRequest($request);
//         if ($form->isValid()) {
//             $client = $form->getData();
//             try {
                if (isset($jobsToDelete)) {
                    foreach ($jobsToDelete as $idJob => $job) {
                        $client->getJobs()->removeElement($job);
                        $this->em->remove($job);
                        $msg = new Message(
                            'DefaultController',
                            'TickCommand',
                            json_encode(array(
                                'command' => "elkarbackup:delete_job_backups",
                                'client' => (int) $id,
                                'job' => $idJob
                            ))
                            );
                        $this->em->persist($msg);
                        $this->logger->info(
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
                $this->em->persist($client);
                $this->logger->info(
                    'Save client %clientid%',
                    array('%clientid%' => $client->getId()),
                    array('link' => $this->generateClientRoute($client->getId()))
                    );
                $this->em->flush();
//                 return $this->redirect($this->generateUrl('showClients'));
//             } catch (Exception $e) {
//                 $this->get('session')->getFlashBag()->add(
//                     'client',
//                     $t->trans('Unable to save your changes: %extrainfo%',
//                         array('%extrainfo%' => $e->getMessage()),
//                         'BinovoElkarBackup'
//                         )
//                     );
                
//                 return $this->redirect($this->generateUrl(
//                     'editClient',
//                     array('id' => $client->getId() == null ? 'new' : $client->getId())
//                     ));
//             }
//         } else {
            
//             return $this->render(
//                 'default/client.html.twig',
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

    private function generateClientRoute($id)
    {
        return $this->router->generate('editClient', array('id' => $id));
    }
    
    private function generateJobRoute($idJob, $idClient)
    {
        return $this->router->generate('editJob', array(
            'idClient' => $idClient,
            'idJob' => $idJob
        ));
    }

}

