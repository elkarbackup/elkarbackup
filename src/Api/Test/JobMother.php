<?php
namespace App\Api\Test;

class JobMother
{
    public static function named(string $jobName): array
    {
        $json = [
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
        return $json;
    }

    public static function withAllParameters(
        string $jobName, 
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
    ): array {
        $json = [
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
        return $json;
    }

    public static function withBackupLocation(string $jobName, int $backupLocationId): array
    {
        $json = [
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
        return $json;
    }

    public static function withClient (string $jobName, int $clientId): array
    {
        $json = [
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
        return $json;
    }
    
    public static function withNotificationLevel(string $jobName, int $minNotificationLevel): array
    {
        $json = [
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
        return $json;
    }

    public static function withNotificationsEmail(string $jobName, string $notificationsEmail): array
    {
        $json = [
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
        return $json;
    }

    public static function withNotificationsTo(string $jobName, array $notificationsTo): array
    {
        $json = [
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
        return $json;
    }

    public static function withPolicy(string $jobName, int $policyId): array
    {
        $json = [
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
        return $json;
    }
    public static function withPostScripts(String $jobName, array $postScripts): array
    {
        $json = [
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
        return $json;
    }

    public static function withPreScripts(String $jobName, array $preScripts): array
    {
        $json = [
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
        return $json;
    }
}
