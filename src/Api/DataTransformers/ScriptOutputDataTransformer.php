<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\ScriptOutput;
use App\Entity\Script;
use Doctrine\ORM\EntityManagerInterface;

class ScriptOutputDataTransformer implements DataTransformerInterface
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
        return ScriptOutput::class === $to && $data instanceof Script;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new ScriptOutput();
        $output->setId($data->getId());
        $output->setName($data->getName());
        $output->setDescription($data->getDescription());
        $output->setIsClientPost($data->getIsClientPost());
        $output->setIsClientPre($data->getIsClientPre());
        $output->setIsJobPost($data->getIsJobPost());
        $output->setIsJobPre($data->getIsJobPre());
        return $output;
    }
}

