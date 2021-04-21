<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\BackupLocationOutput;
use App\Entity\BackupLocation;
use Doctrine\ORM\EntityManagerInterface;

class BackupLocationOutputDataTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return BackupLocationOutput::class === $to && $data instanceof BackupLocation;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new BackupLocationOutput();
        $output->setId($data->getId());
        $output->setName($data->getName());
        $output->setHost($data->getHost());
        $output->setDirectory($data->getDirectory());
        $output->setMaxParallelJobs($data->getMaxParallelJobs());
        return $output;
    }
}
