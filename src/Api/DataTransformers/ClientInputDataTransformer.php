<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Entity\Client;
use App\Entity\User;
use App\Exception\PermissionException;
use Doctrine\ORM\EntityManagerInterface;
use \Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class ClientInputDataTransformer implements DataTransformerInterface
{
    private $authChecker;
    private $entityManager;
    private $security;
    
    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, Security $security)
    {
        $this->authChecker   = $authChecker;
        $this->entityManager = $em;
        $this->security      = $security;
    }

    private function setOwner ($client, $owner)
    {
        if (null != $owner) {
            $repository = $this->entityManager->getRepository('App:User');
            $query = $repository->createQueryBuilder('c');
            $query->where($query->expr()->eq('c.id', $owner));
            if (null == $query->getQuery()->getOneOrNullResult()) {
                throw new InvalidArgumentException ("Incorrect owner id");
            } else {
                if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
                    $user = $this->security->getToken()->getUser()->getId();
                    if ($user == $owner){
                        $client->setOwner($this->security->getToken()->getUser());
                    } else{
                        throw new PermissionException("Permission denied.");
                    }
                } else {
                    $client->setOwner($query->getQuery()->getOneOrNullResult());
                }
            }
        }
    }

    private function setPostScripts($client, $postScripts): void
    {
        foreach ($client->getPostScripts() as $script) {
            $client->removePostScript($script);
        }
        $repository = $this->entityManager->getRepository('App:Script');
        foreach ($postScripts as $script) {
            $result = $repository->find($script);
            if (null != $result) {
                if ($result->getIsClientPost()) {
                    $client->addPostScript($result);
                }else {
                    throw new InvalidArgumentException(sprintf('Script "%s" is not a client post script', $result->getId()));
                }
            } else {
                throw new InvalidArgumentException(sprintf('Script "%s" does not exist', $script));
            }
        }
    }

    private function setPreScripts(Client $client, $preScripts): void
    {
        foreach ($client->getPreScripts()->toArray() as $script) {
            $client->removePreScript($script);
        }
        $repository = $this->entityManager->getRepository('App:Script');
        foreach ($preScripts as $script) {
            $result = $repository->find($script);
            if (null != $result) {
                if ($result->getIsClientPre()) {
                    $client->addPreScript($result);
                }else {
                    throw new InvalidArgumentException(sprintf('Script "%s" is not a client pre script', $result->getId()));
                }
            } else {
                throw new InvalidArgumentException(sprintf('Script "%s" does not exist', $script));
            }
        }
    }
    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Client) {
            return false;
        }
        
        return Client::class === $to && null !== ($context['input']['class'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        if (isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            $client = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        } else {
            $client = new Client();
        }
        $client->setName($data->getName());
        $client->setUrl($data->getUrl());
        $client->setQuota($data->getQuota());
        $client->setDescription($data->getDescription());
        $client->setIsActive($data->getIsActive());
        $this->setPreScripts($client, $data->getPreScripts());
        $this->setPostScripts($client, $data->getPostScripts());
        $client->setMaxParallelJobs($data->getMaxParallelJobs());
        $this->setOwner($client, $data->getOwner());
        $client->setSshArgs($data->getSshArgs());
        $client->setRsyncShortArgs($data->getRsyncShortArgs());
        $client->setRsyncLongArgs($data->getRsyncLongArgs());
        return $client;
    }
}
