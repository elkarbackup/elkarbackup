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
            'description' => null,
            'exclude' => null,
            'include' => null,
            'isActive' => true,
            'minNotificationLevel' => 400,
            'name' => $jobName,
            'notificationsEmail' => null,
            'notificationsTo' => ['owner'],
            'path' => '/some/default/path',
            'policy' => 1,
            'postScripts' => [],
            'preScripts' => [],
            'token' => null,
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
        $response = self::base();
        $data = $response->getData();
        $data['backupLocation'] = $backupLocationId;
        $response->setData($data);
        return $response;
    }

    public static function withClient (int $clientId): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['client'] = $clientId;
        $response->setData($data);
        return $response;
    }

    public static function withInvalidNotificationLevel(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['minNotificationLevel'] = rand(0,199);
        $response->setData($data);
        return $response;
    }

    public static function withInvalidNotificationsEmail(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['notificationsEmail'] = "This is not a email";
        $response->setData($data);
        return $response;
    }

    public static function withInvalidNotificationsTo(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['notificationsTo'] = ['invalid'];
        $response->setData($data);
        return $response;
    }

    public static function withNonExistentBackupLocation(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['backupLocation'] = self::UNEXISTING_ID;
        $response->setData($data);
        return $response;
    }

    public static function withNonExistentClient (): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['client'] = self::UNEXISTING_ID;
        $response->setData($data);
        return $response;
    }

    public static function withNonExistentPolicy(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['policy'] = self::UNEXISTING_ID;
        $response->setData($data);
        return $response;
    }

    public static function withNonExistentPostScripts(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['postScripts'] = [self::UNEXISTING_ID];
        $response->setData($data);
        return $response;
    }

    public static function withNonExistentPreScripts(): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['preScripts'] = [self::UNEXISTING_ID];
        $response->setData($data);
        return $response;
    }

    public static function withNotificationLevel(int $minNotificationLevel): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['minNotificationLevel'] = $minNotificationLevel;
        $response->setData($data);
        return $response;
    }

    public static function withNotificationsEmail(string $notificationsEmail): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['notificationsEmail'] = $notificationsEmail;
        $response->setData($data);
        return $response;
    }

    public static function withNotificationsTo(array $notificationsTo): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['notificationsTo'] = $notificationsTo;
        $response->setData($data);
        return $response;
    }

    public static function withPolicy(int $policyId): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['policy'] = $policyId;
        $response->setData($data);
        return $response;
    }
    public static function withPostScripts(array $postScripts): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['postScripts'] = $postScripts;
        $response->setData($data);
        return $response;
    }

    public static function withPreScripts(array $preScripts): RequestObject
    {
        $response = self::base();
        $data = $response->getData();
        $data['preScripts'] = $preScripts;
        $response->setData($data);
        return $response;
    }
}
