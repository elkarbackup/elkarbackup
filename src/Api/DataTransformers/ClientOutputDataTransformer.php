<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\ClientOutput;
use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ClientOutputDataTransformer implements DataTransformerInterface
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
    }

    private function getOwnerId($data): ?int
    {
        if (null != $data->getOwner())
        {
            return $data->getOwner()->getId();
        }
        return $data->getOwner();
    }

    private function getQuota($quota): int
    {
        if (0 < $quota) {
            return $quota/1024;
        }
        return $quota;
    }

    private function getScriptsId ($scripts): array
    {
        $result = array();
        if(null != $scripts){
            foreach ($scripts as $script) {
                $result[]=$script->getId();
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ClientOutput::class === $to && $data instanceof Client;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new ClientOutput();
        $output->setId($data->getId());
        $output->setName($data->getName());
        $output->setUrl($data->getUrl());
        $output->setQuota($this->getQuota($data->getQuota()));
        $output->setDescription($data->getDescription());
        $output->setDiskUsage((int) $data->getDiskUsage());
        $output->setIsActive($data->getIsActive());
        $output->setPreScripts($this->getScriptsId($data->getPreScripts()));
        $output->setPostScripts($this->getScriptsId($data->getPostScripts()));
        $output->setMaxParallelJobs($data->getMaxParallelJobs());
        $output->setOwner($this->getOwnerId($data));
        $output->setSshArgs($data->getSshArgs());
        $output->setRsyncShortArgs($data->getRsyncShortArgs());
        $output->setRsyncLongArgs($data->getRsyncLongArgs());
        $output->setState($data->getState());
        return $output;
    }
}

