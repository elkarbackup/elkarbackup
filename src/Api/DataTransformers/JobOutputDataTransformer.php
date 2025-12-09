<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\JobOutput;
use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;

class JobOutputDataTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
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
        return JobOutput::class === $to && $data instanceof Job;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new JobOutput();
        $output->setId($data->getId());
        $output->setName($data->getName());
        $output->setDescription($data->getDescription());
        $output->setDiskUsage((int) $data->getDiskUsage());
        $output->setClient($data->getClient()->getId());
        $output->setIsActive($data->getIsActive());
        $output->setNotificationsEmail($data->getNotificationsEmail());
        $output->setNotificationsTo($data->getNotificationsTo());
        $output->setMinNotificationLevel($data->getMinNotificationLevel());
        $output->setExclude($data->getExclude());
        $output->setInclude($data->getInclude());
        $output->setPolicy($data->getPolicy()->getId());
        $output->setPostScripts($this->getScriptsId($data->getPostScripts()));
        $output->setPreScripts($this->getScriptsId($data->getPreScripts()));
        $output->setPath($data->getPath());
        $output->setUseLocalPermissions($data->getUseLocalPermissions());
        $output->setToken($data->getToken());
        $output->setBackupLocation($data->getBackupLocation()->getId());
        $output->setLastResult($data->getLastResult());
        return $output;
    }
}

