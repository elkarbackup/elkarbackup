<?php
namespace App\Api\Test;

class ClientMother
{
    public static function base(string $clientName, int $owner): array
    {
        $json = [
        'isActive' => true,
        'maxParallelJobs' => 1,
        'name' => $clientName,
        'owner' => $owner,
        'quota' => -1
        ];
        return $json;
    }
    
    public static function withMaxParallelJobs(string $clientName, int $owner, int $maxParallelJobs): array
    {
        $json = [
            'isActive' => true,
            'maxParallelJobs' => $maxParallelJobs,
            'name' => $clientName,
            'owner' => $owner,
            'quota' => -1
        ];
        return $json;
    }
    
    public static function withPostScripts(string $clientName, int $owner, array $postScripts): array
    {
        $json = [
        'isActive'        => true,
        'maxParallelJobs' => 1,
        'name'            => $clientName,
        'owner'           => 1,
        'postScripts'     => [self::UNEXISTING_ID],
        'quota'           => -1
        ];
        return $json;
    }

    public static function withPreScripts(string $clientName, int $owner, array $preScripts): array
    {
        $json = [
            'isActive'        => true,
            'maxParallelJobs' => 1,
            'name'            => $clientName,
            'owner'           => 1,
            'preScripts'     => [self::UNEXISTING_ID],
            'quota'           => -1
        ];
        return $json;
    }
}

