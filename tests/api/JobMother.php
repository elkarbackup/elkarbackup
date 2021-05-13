<?php
namespace App\Tests\api;

use App\Tests\Api\RequestObject;

class JobMother
{
    const JOB_CONTEXT = [
        "@context" => [
            "@vocab" => "http://127.0.0.1/api/docs.jsonld#",
            "hydra" => "http://www.w3.org/ns/hydra/core#",
            "backupLocation" => "JobOutput/backupLocation",
            "client" => "JobOutput/client",
            "description" => "JobOutput/description",
            "diskUsage" => "JobOutput/diskUsage",
            "exclude" => "JobOutput/exclude",
            "id" => "JobOutput/id",
            "include" => "JobOutput/include",
            "isActive" => "JobOutput/isActive",
            "minNotificationLevel" => "JobOutput/minNotificationLevel",
            "name" => "JobOutput/name",
            "notificationsEmail" => "JobOutput/notificationsEmail",
            "notificationsTo" => "JobOutput/notificationsTo",
            "path" => "JobOutput/path",
            "policy" => "JobOutput/policy",
            "postScripts" => "JobOutput/postScripts",
            "preScripts" => "JobOutput/preScripts",
            "token" => "JobOutput/token",
            "useLocalPermissions" => "JobOutput/useLocalPermissions"
        ],
        "@type" => "Job"
    ];
    const UNEXISTING_ID = 726358291635;

    public static function base(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    private function createJobName(): string
    {
        $time = new \DateTime();
        $jobName = 'job_'.$time->getTimestamp().'_'.rand(1000, 9999);
        return $jobName;
    }

    public static function getNonExistentIri(): string
    {
        return '/api/jobs/'.self::UNEXISTING_ID;
    }

    public static function named(string $jobName): RequestObject
    {
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withAllParameters(
        int $backupLocationId, 
        int $client, 
        string $description, 
        string $exclude, 
        string $include, 
        bool $isActive, 
        int $notificationsLevel, 
        string $notificationsEmail,
        array $notificationsTo, 
        string $path, 
        int $policy, 
        array $postScripts, 
        array $preScripts, 
        string $token, 
        bool $localPermissions
    ): RequestObject {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => $backupLocationId,
            'client' => $client,
            'description' => $description,
            'exclude' => $exclude,
            'include' => $include,
            'isActive' => true,
            'minNotificationLevel' => $notificationsLevel,
            'name' => $jobName,
            'notificationsEmail' => $notificationsEmail,
            'notificationsTo' => $notificationsTo,
            'path' => $path,
            'policy' => $policy,
            'postScripts' => $postScripts,
            'preScripts' => $preScripts,
            'token' => $token,
            'useLocalPermissions' => $localPermissions
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withBackupLocation(int $backupLocationId): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => $backupLocationId,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withClient (int $clientId): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => $clientId,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withInvalidNotificationLevel(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => rand(0,199),
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withInvalidNotificationsEmail(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsEmail' => "This is not an email",
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withInvalidNotificationsTo(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['invalid'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNonExistentBackupLocation(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => self::UNEXISTING_ID,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNonExistentClient (): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => self::UNEXISTING_ID,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNonExistentPolicy(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => self::UNEXISTING_ID,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNonExistentPostScripts(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'postScripts' => [self::UNEXISTING_ID],
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNonExistentPreScripts(): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'preScripts' => [self::UNEXISTING_ID],
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNotificationLevel(int $minNotificationLevel): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => $minNotificationLevel,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNotificationsEmail(string $notificationsEmail): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsEmail' => $notificationsEmail,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withNotificationsTo(array $notificationsTo): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => $notificationsTo,
            'path' => '/some/default/path',
            'policy' => 1,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withPolicy(int $policyId): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => $policyId,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }
    public static function withPostScripts(array $postScripts): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'postScripts' => $postScripts,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }

    public static function withPreScripts(array $preScripts): RequestObject
    {
        $jobName = self::createJobName();
        $data = [
            'backupLocation' => 1,
            'client' => 1,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'preScripts' => $preScripts,
            'useLocalPermissions' => true
        ];
        $response = new RequestObject(self::JOB_CONTEXT, $data);
        return $response;
    }
}
