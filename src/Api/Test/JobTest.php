<?php
namespace App\Api\Test;

use App\Api\Test\BaseApiTestCase;

class JobTest extends BaseApiTestCase
{
    public function testCreateJob(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::named($jobName);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($jobJson);
        $this->assertJobHydraContext();
    }

    //public function testCreateJobAllParameters(): void

    public function testCreateJobInvalidBackupLocation(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withBackupLocation($jobName, self::UNEXISTING_ID);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect backup location id');
    }
    public function testCreateJobInvalidClient(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withClient($jobName, self::UNEXISTING_ID);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect client id');
    }

    public function testCreateJobInvalidNotificationLevel(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withNotificationLevel($jobName, 333);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification level (0, 200, 300, 400, 1000)');
    }

    public function testCreateJobInvalidNotificationsEmail(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withNotificationsEmail($jobName, 'invalid email');
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification email address');
    }

    public function testCreateJobInvalidNotificationsTo(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withNotificationsTo($jobName, ['invalid']);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notifications to argument (owner, admin, email)');
    }

    public function testCreateJobInvalidPolicy(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withPolicy($jobName, self::UNEXISTING_ID);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect policy id');
    }

    public function testCreateJobInvalidPostScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_post');
        $jobName = $this->createJobName();
        $jobJson = JobMother::withPostScripts($jobName, [$scriptId]);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job post script');
    }

    public function testCreateJobInvalidPreScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_pre');
        $jobName = $this->createJobName();
        $jobJson = JobMother::withPreScripts($jobName, [$scriptId]);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job pre script');
    }

    public function testCreateJobUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withPostScripts($jobName, [self::UNEXISTING_ID]);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }

    public function testCreateJobUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::withPreScripts($jobName, [self::UNEXISTING_ID]);
        $this->postJob($httpClient, $jobJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }
    
}

