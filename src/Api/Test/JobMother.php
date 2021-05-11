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
}

// {
//     "backupLocation": 1,
//     "client": 0,
//     "description": "string",
//     "exclude": "string",
//     "include": "string",
//     "isActive": true,
//     "minNotificationLevel": 400,
//     "name": "string",
//     "notificationsEmail": "string",
//     "notificationsTo": [
//         "owner"
//     ],
//     "path": "string",
//     "policy": 1,
//     "postScripts": [
//         "string"
//     ],
//     "preScripts": [
//         "string"
//     ],
//     "token": "string",
//     "useLocalPermissions": true
// }