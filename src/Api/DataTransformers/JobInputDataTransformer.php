<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Entity\Job;
use App\Exception\PermissionException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class JobInputDataTransformer implements DataTransformerInterface
{
    private $authChecker;
    private $entityManager;
    private $security;
    
    public function __construct(AuthorizationCheckerInterface $authChecker, EntityManagerInterface $em, Security $security)
    {
        $this->authChecker   = $authChecker;
        $this->entityManager = $em;
        $this->security      = $security;
    }

    private function setBackupLocation (Job $job, $backupLocationId)
    {
        $repository = $this->entityManager->getRepository('App:BackupLocation');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $backupLocationId));
        if (null == $query->getQuery()->getOneOrNullResult()) {
            throw new InvalidArgumentException ("Incorrect backup location id");
        } else {
            $job->setBackupLocation($query->getQuery()->getOneOrNullResult());
        }
    }

    private function setClient (Job $job, $clientId) {
        $repository = $this->entityManager->getRepository('App:Client');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $clientId));
        if (null == $query->getQuery()->getOneOrNullResult()) {
            throw new InvalidArgumentException ("Incorrect client id");
        } else {
            if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
                $query->andWhere($query->expr()->eq('c.owner', $this->security->getToken()->getUser()->getId()));
                if (null == $query->getQuery()->getOneOrNullResult()) {
                    throw new PermissionException(sprintf("Permission denied to create job of client %s", $clientId));
                }
            }
            $job->setClient($query->getQuery()->getOneOrNullResult());
        }
    }

    private function setMinNotificationLevel (Job $job, $minNotificationLevel)
    {
        if (0 == $minNotificationLevel || 200 == $minNotificationLevel || 300 == $minNotificationLevel || 
            400 == $minNotificationLevel || 1000 == $minNotificationLevel) {
            $job->setMinNotificationLevel($minNotificationLevel);
        } else {
            throw new InvalidArgumentException("Incorrect notification level (0, 200, 300, 400, 1000)");
        }
    }

    private function setNotificationsEmail (Job $job, $notificationsEmail)
    {
        if (isset($notificationsEmail) && !filter_var($notificationsEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Incorrect notification email address");
        }
        
        $job->setNotificationsEmail($notificationsEmail);
    }

    private function setNotificationsTo (Job $job, $notificationsTo)
    {
        foreach ($notificationsTo as $to) {
            if ("admin" != $to && "owner"!=$to && "email"!=$to) {
                throw new InvalidArgumentException("Incorrect notifications to argument (owner, admin, email)");
            }
        }
        $job->setNotificationsTo($notificationsTo);
    }

    private function setPolicy (Job $job, $policyId)
    {
        $repository = $this->entityManager->getRepository('App:Policy');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $policyId));
        if (null == $query->getQuery()->getOneOrNullResult()) {
            throw new InvalidArgumentException ("Incorrect policy id");
        } else {
            $job->setPolicy($query->getQuery()->getOneOrNullResult());
        }
    }

    private function setPostScripts (Job $job, $postScripts)
    {
        if (null != $job->getPostScripts()) {
            foreach ($job->getPostScripts() as $script) {
                $job->removePostScript($script);
            }
        }
        if (null != $postScripts){
            $repository = $this->entityManager->getRepository('App:Script');
            $query = $repository->createQueryBuilder('s');
            foreach ($postScripts as $script) {
                $query->where($query->expr()->eq('s.id', $script));
                $result = $query->getQuery()->getOneOrNullResult();
                if (null != $result) {
                    if ($result->getIsJobPost()) {
                        $job->addPostScript($result);
                    }else {
                        throw new InvalidArgumentException(sprintf('Script "%s" is not a job post script', $result->getId()));
                    }
                } else {
                    throw new InvalidArgumentException(sprintf('Script "%s" does not exist', $script));
                }
            }
        }
    }

    private function setPreScripts (Job $job, $preScripts)
    {
        if (null != $job->getPreScripts()) {
            foreach ($job->getPreScripts() as $script) {
                $job->removePreScript($script);
            }
        }
        if (null != $preScripts){
            $repository = $this->entityManager->getRepository('App:Script');
            $query = $repository->createQueryBuilder('s');
            foreach ($preScripts as $script) {
                $query->where($query->expr()->eq('s.id', $script));
                $result = $query->getQuery()->getOneOrNullResult();
                if (null != $result) {
                    if ($result->getIsJobPre()) {
                        $job->addPreScript($result);
                    }else {
                        throw new InvalidArgumentException(sprintf('Script "%s" is not a job pre script', $result->getId()));
                    }
                } else {
                    throw new InvalidArgumentException(sprintf('Script "%s" does not exist', $script));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Job) {
            return false;
        }
        
        return Job::class === $to && null !== ($context['input']['class'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        if (isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            $job = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        } else {
            $job = new Job();
        }
        $job->setName($data->getName());
        $job->setDescription($data->getDescription());
        $job->setDiskUsage($data->getDiskUsage());
        $this->setClient($job, $data->getClient());
        $job->setIsActive($data->getIsActive());
        $this->setNotificationsEmail($job, $data->getNotificationsEmail());
        $this->setNotificationsTo($job, $data->getNotificationsTo());
        $this->setMinNotificationLevel($job, $data->getMinNotificationLevel());
        $job->setExclude($data->getExclude());
        $job->setInclude($data->getInclude());
        $this->setPolicy($job, $data->getPolicy());
        $this->setPostScripts($job, $data->getPostScripts());
        $this->setPreScripts($job, $data->getPreScripts());
        $job->setPath($data->getPath());
        $job->setUseLocalPermissions($data->getUseLocalPermissions());
        $job->setToken($data->getToken());
        $this->setBackupLocation($job, $data->getBackupLocation());
        return $job;
    }
}

