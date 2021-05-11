<?php
namespace App\Api\Test;

class ClientMother
{
    public static function named(string $clientName): array
    {
        $json = [
        'isActive' => true,
        'maxParallelJobs' => 1,
        'name' => $clientName,
        'owner' => 1,
        'quota' => -1
        ];
        return $json;
    }
    
    public static function withAllParameters(
        string $clientName, 
        int $owner, 
        string $description, 
        bool $isActive, 
        int $maxParallelJobs, 
        array $postScripts, 
        array $preScripts, 
        int $quota, 
        string $rsyncLongArgs, 
        string $rsyncShortArgs, 
        string $sshArgs, 
        string $url
    ): array {
        $json = [
            'description' => $description,
            'isActive' => $isActive,
            'maxParallelJobs' => $maxParallelJobs,
            'name' => $clientName,
            'owner' => $owner,
            'postScripts' => $postScripts,
            'preScripts' => $preScripts,
            'quota' => $quota,
            'rsyncLongArgs' => $rsyncLongArgs,
            'rsyncShortArgs' => $rsyncShortArgs,
            'sshArgs' => $sshArgs,
            'url' => $url
        ];
        return $json;
    }
    
    public static function withMaxParallelJobs(string $clientName, int $maxParallelJobs): array
    {
        $json = [
            'isActive' => true,
            'maxParallelJobs' => $maxParallelJobs,
            'name' => $clientName,
            'owner' => 1,
            'quota' => -1
        ];
        return $json;
    }
    
    public static function withOwner (string $clientName, int $owner): array
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
    public static function withPostScripts(string $clientName, array $postScripts): array
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

    public static function withPreScripts(string $clientName, array $preScripts): array
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

