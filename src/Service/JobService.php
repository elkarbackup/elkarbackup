<?php
namespace App\Service;

use App\Entity\Message;
use App\Service\LoggerService;
use App\Service\RouterService;
use App\Exception\PermissionException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class JobService
{

    private $authChecker;
    private $em;
    private $logger;
    private $router;
    private $security;

    public function __construct(AuthorizationCheckerInterface $authChecker, EntityManagerInterface $em, LoggerService $logger, RouterService $router, Security $security)
    {
        $this->authChecker = $authChecker;
        $this->em = $em;
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
        $job = $this->em->getRepository('App:Job')->find($id);
        $idClient = $job->getClient()->getId();
        $this->assertPermission($idClient);
        $queue = $this->em->getRepository('App:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getId() == $id) {
                $this->logger->err('Could not delete job %jobName%, it is enqueued.', array(
                    '%jobName%' => $job->getName()
                ), array(
                    'link' => $this->router->generateJobRoute($id, $idClient)
                ));
                $this->em->flush();
                throw new Exception(sprintf('Could not delete job %s, it is enqueued.', $job->getName()));
            }
        }
        $this->em->remove($job);
        $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
            'command' => "elkarbackup:delete_job_backups",
            'client' => (int) $idClient,
            'job' => (int) $id
        )));
        $this->em->persist($msg);
        $this->logger->info('Client %clientid%, job "%jobid%" deleted successfully.', array(
            '%clientid%' => $idClient,
            '%jobid%' => $id
        ), array(
            'link' => $this->router->generateJobRoute($id, $idClient)
        ));
        $this->em->flush();
    }

    public function save($job)
    {
        $this->em->persist($job);
        $this->logger->info('Save client %clientid%, job %jobid%', array(
            '%clientid%' => $job->getClient()
                ->getId(),
            '%jobid%' => $job->getId()
        ), array(
            'link' => $this->router->generateJobRoute($job->getId(), $job->getClient()
                ->getId())
        ));
        $this->em->flush();
    }
}

