<?php
namespace App\Api\Test;

use App\Api\Test\BaseApiTestCase;
use App\Entity\Job;

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

    public function testDeleteJob(): void
    {
        $httpClient = $this->authenticate();
        $iri = $this->findIriBy(Job::class, [
            'name' => 'job_to_delete'
        ]);
        $response = $httpClient->request('DELETE', $iri);
        $this->assertResponseIsSuccessful();
        $response = $httpClient->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteJobNotFound(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('DELETE', 'api/jobs/'.self::UNEXISTING_ID);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The job "'.self::UNEXISTING_ID.'" does not exist.');
    }

    public function testGetJob(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $jobJson = JobMother::named($jobName);
        $this->postJob($httpClient, $jobJson);
        $iri = $this->findIriBy(Job::class, [
            'name' => $jobName
        ]);
        $response = $httpClient->request('GET', $iri);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($jobJson);
        $this->assertJobHydraContext();
    }

    public function testGetJobs(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('GET', '/api/jobs');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Job',
            '@type' => 'hydra:Collection',
            '@id' => '/api/jobs',
        ]);
    }

    public function testGetJobsUnauthenticated(): void
    {
        $response = static::createClient()->request('GET', '/api/jobs');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetNonexistentJob(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('GET', '/api/jobs/'.self::UNEXISTING_ID);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The job "'.self::UNEXISTING_ID.'" does not exist.');
    }

    public function testUpdateJob(): void
    {
        $httpClient = $this->authenticate();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_1']);
        $updatedName = $this->createJobName();
        $updateJobJson = JobMother::named($updatedName);
        $httpClient->request('PUT', $iri, [
            'json' => $updateJobJson
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($updateJobJson);
        $this->assertJobHydraContext();
    }

    public function testUpdateJobInvalidBackupLocation(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_2']);
        $updateJobJson = JobMother::withBackupLocation($jobName, self::UNEXISTING_ID);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect backup location id');
    }

    public function testUpdateJobInvalidClient(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_3']);
        $updateJobJson = JobMother::withClient($jobName, self::UNEXISTING_ID);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect client id');
    }

    public function testUpdateJobInvalidNotificationLevel(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_4']);
        $updateJobJson = JobMother::withNotificationLevel($jobName, 333);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification level (0, 200, 300, 400, 1000)');
    }

    public function testUpdateJobInvalidNotificationsEmail(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_5']);
        $updateJobJson = JobMother::withNotificationsEmail($jobName, 'invalid email');
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification email address');
    }

    public function testUpdateJobInvalidNotificationsTo(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_6']);
        $updateJobJson = JobMother::withNotificationsTo($jobName, ['invalid']);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notifications to argument (owner, admin, email)');
    }

    public function testUpdateJobInvalidPolicy(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_7']);
        $updateJobJson = JobMother::withPolicy($jobName, self::UNEXISTING_ID);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testUpdateJobInvalidPostScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_post');
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_8']);
        $updateJobJson = JobMother::withPostScripts($jobName, [$scriptId]);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job post script');
    }
    
    public function testUpdateJobInvalidPreScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_pre');
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_9']);
        $updateJobJson = JobMother::withPreScripts($jobName, [$scriptId]);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job pre script');
    }

    public function testUpdateJobNotFound(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $updateJobJson = JobMother::named($jobName);
        $httpClient->request('PUT', 'api/jobs/'.self::UNEXISTING_ID, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The job "'.self::UNEXISTING_ID.'" does not exist.');
    }
    public function testUpdateJobUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_10']);
        $updateJobJson = JobMother::withPostScripts($jobName, [self::UNEXISTING_ID]);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }
    
    public function testUpdateJobUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $jobName = $this->createJobName();
        $iri = $this->findIriBy(Job::class, ['name' => 'job_to_update_11']);
        $updateJobJson = JobMother::withPreScripts($jobName, [self::UNEXISTING_ID]);
        $httpClient->request('PUT', $iri, ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }
}

