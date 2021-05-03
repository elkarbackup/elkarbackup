<?php
namespace App\Service;

use App\Entity\Message;
use App\Exception\PermissionException;
use App\Service\LoggerService;
use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ClientService
{
    private $authChecker;
    private $em;
    private $logger;
    private $router;
    private $security;

    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, LoggerService $logger, Security $security, RouterService $router)
    {
        $this->em = $em;
        $this->authChecker = $authChecker;
        $this->logger = $logger;
        $this->router = $router;
        $this->security = $security;
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

    public function delete($id)
    {
        $this->assertPermission($id);
        $repository = $this->em->getRepository('App:Client');
        $client = $repository->find($id);
        $queue = $this->em->getRepository('App:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getClient()->getId() == $id) {
                $this->logger->err('Could not delete client %clientName%, it has jobs enqueued.', array(
                    '%clientName%' => $client->getName()
                ), array(
                    'link' => $this->router->generateClientRoute($id)
                ));
                throw new Exception("Could not delete client, it has jobs enqueued.");
            }
        }

        // try {
        $this->em->remove($client);
        $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
            'command' => "elkarbackup:delete_job_backups",
            'client' => (int) $id
        )));
        $this->em->persist($msg);
        $this->em->flush();
        $this->logger->info('Client "%clientid%" deleted', array(
            '%clientid%' => $id
        ), array(
            'link' => $this->router->generateClientRoute($id)
        ));
    }

    public function save($client)
    {
        if (isset($jobsToDelete)) {
            foreach ($jobsToDelete as $idJob => $job) {
                $client->getJobs()->removeElement($job);
                $this->em->remove($job);
                $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
                    'command' => "elkarbackup:delete_job_backups",
                    'client' => (int) $client->getId(),
                    'job' => $idJob
                )));
                $this->em->persist($msg);
                $this->logger->info('Delete client %clientid%, job %jobid%', array(
                    '%clientid%' => $client->getId(),
                    '%jobid%' => $job->getId()
                ), array(
                    'link' => $this->router->generateJobRoute($job->getId(), $client->getId())
                ));
            }
        }
        if ($client->getOwner() == null) {
            $client->setOwner($this->security->getToken()
                ->getUser());
        }

        if ($client->getMaxParallelJobs() < 1) {
            throw new Exception('Max parallel jobs parameter should be positive integer');
        }
        $this->em->persist($client);
        $this->em->flush();
        $this->logger->info('Save client %clientid%', array(
            '%clientid%' => $client->getId()
        ), array(
            'link' => $this->router->generateClientRoute($client->getId())
        ));
    }
}

