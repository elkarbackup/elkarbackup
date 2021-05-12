<?php
namespace App\Tests\api;

class ClientMother
{
    const CLIENT_CONTEXT = [
        '@context' => [
            '@vocab' => 'http://127.0.0.1/api/docs.jsonld#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'description' => 'ClientOutput/description',
            'id' => 'ClientOutput/id',
            'isActive' => 'ClientOutput/isActive',
            'maxParallelJobs' => 'ClientOutput/maxParallelJobs',
            'name' => 'ClientOutput/name',
            'owner' => 'ClientOutput/owner',
            'postScripts' => 'ClientOutput/postScripts',
            'preScripts' => 'ClientOutput/preScripts',
            'quota' => 'ClientOutput/quota',
            'rsyncLongArgs' => 'ClientOutput/rsyncLongArgs',
            'rsyncShortArgs' => 'ClientOutput/rsyncShortArgs',
            'sshArgs' => 'ClientOutput/sshArgs',
            'url' => 'ClientOutput/url'
        ],
        '@type' => 'Client'
    ];

    public static function named(string $clientName): RequestObject
    {
        $data = [
        'isActive' => true,
        'maxParallelJobs' => 1,
        'name' => $clientName,
        'owner' => 1,
        'quota' => -1
        ];
        $response = new RequestObject(self::CLIENT_CONTEXT, $data);
        return $response;
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
    ): RequestObject {
        $data = [
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
        $response = new RequestObject(self::CLIENT_CONTEXT, $data);
        return $response;
    }
    
    public static function withMaxParallelJobs(string $clientName, int $maxParallelJobs): RequestObject
    {
        
        $data = [
            'isActive' => true,
            'maxParallelJobs' => $maxParallelJobs,
            'name' => $clientName,
            'owner' => 1,
            'quota' => -1
        ];
        $response = new RequestObject(self::CLIENT_CONTEXT, $data);
        return $response;
    }
    
    public static function withOwner (string $clientName, int $owner): RequestObject
    {
        $data = [
            'isActive' => true,
            'maxParallelJobs' => 1,
            'name' => $clientName,
            'owner' => $owner,
            'quota' => -1
        ];
        $response = new RequestObject(self::CLIENT_CONTEXT, $data);
        return $response;
    }
    public static function withPostScripts(string $clientName, array $postScripts): RequestObject
    {
        $data = [
        'isActive'        => true,
        'maxParallelJobs' => 1,
        'name'            => $clientName,
        'owner'           => 1,
        'postScripts'     => $postScripts,
        'quota'           => -1
        ];
        $response = new RequestObject(self::CLIENT_CONTEXT, $data);
        return $response;
    }

    public static function withPreScripts(string $clientName, array $preScripts): RequestObject
    {
        $data = [
            'isActive'        => true,
            'maxParallelJobs' => 1,
            'name'            => $clientName,
            'owner'           => 1,
            'preScripts'     => $preScripts,
            'quota'           => -1
        ];
        $response = new RequestObject(self::CLIENT_CONTEXT, $data);
        return $response;
    }
}

