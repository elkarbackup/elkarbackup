<?php
namespace App\Tests\api;

use App\Tests\api\BaseApiTestCase;
use App\Tests\api\JobMother;
use App\Entity\Job;

class JobTest extends BaseApiTestCase
{
    public function testCreateJob(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $this->postJob($httpClient, $job);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($job->getData());
        $this->assertJsonContains($job->getContext());
    }

    public function testCreateJobAllParameters(): void
    {
        $httpClient = $this->authenticate();
        $preScript1 = $this->getScriptId($httpClient, 'script_job_pre_1');
        $postScript1 = $this->getScriptId($httpClient, 'script_job_post_1');
        $preScript2 = $this->getScriptId($httpClient, 'script_job_pre_2');
        $postScript2 = $this->getScriptId($httpClient, 'script_job_post_2');
        $job = JobMother::withAllParameters(
            1,
            1,
            "some description",
            "exclude pattern",
            "include pattern",
            true,
            400,
            "example@example.com",
            ["owner", "email"],
            "/some/random/path",
            1,
            [$postScript1, $postScript2],
            [$preScript1, $preScript2],
            "randomtoken",
            true
        );
        $this->postJob($httpClient, $job);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($job->getData());
        $this->assertJsonContains($job->getContext());
    }

    public function testCreateJobInvalidNotificationLevel(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withInvalidNotificationLevel();
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification level (0, 200, 300, 400, 1000)');
    }

    public function testCreateJobInvalidNotificationsEmail(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withInvalidNotificationsEmail();
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification email address');
    }

    public function testCreateJobInvalidNotificationsTo(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withInvalidNotificationsTo();
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notifications to argument (owner, admin, email)');
    }

    public function testCreateJobInvalidPostScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_post');
        $job = JobMother::withPostScripts([$scriptId]);
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job post script');
    }

    public function testCreateJobInvalidPreScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_pre');
        $job = JobMother::withPreScripts([$scriptId]);
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job pre script');
    }

    public function testCreateJobNonExistentBackupLocation(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withNonExistentBackupLocation();
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect backup location id');
    }

    public function testCreateJobNonExistentClient(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withNonExistentClient();
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect client id');
    }

    

    public function testCreateJobNonExistentPolicy(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withNonExistentPolicy();
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect policy id');
    }

    

    public function testCreateJobNonExistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withNonExistentPostScripts();
        $jobJson = $job->getData();
        $scriptId = $jobJson['postScripts'][0];
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" does not exist');
    }

    public function testCreateJobNonExistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::withNonExistentPreScripts();
        $jobJson = $job->getData();
        $scriptId = $jobJson['preScripts'][0];
        $this->postJob($httpClient, $job);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" does not exist');
    }

    public function testDeleteJob(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $this->postJob($httpClient, $job);
        $iri = $job->getIri();
        $response = $httpClient->request('DELETE', $iri);
        $this->assertResponseIsSuccessful();
        $response = $httpClient->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteJobNotFound(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('DELETE', JobMother::getNonExistentIri());
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The job "'.JobMother::UNEXISTING_ID.'" does not exist.');
    }

    public function testGetJob(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $this->postJob($httpClient, $job);
        $iri = $this->findIriBy(Job::class, [
            'name' => $job->getName()
        ]);
        $response = $httpClient->request('GET', $iri);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($job->getData());
        $this->assertJsonContains($job->getContext());
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

    public function testGetNonExistentJob(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('GET', JobMother::getNonExistentIri());
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The job "'.JobMother::UNEXISTING_ID.'" does not exist.');
    }

    public function testUpdateJob(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $preScript1 = $this->getScriptId($httpClient, 'script_job_pre_1');
        $postScript1 = $this->getScriptId($httpClient, 'script_job_post_1');
        $preScript2 = $this->getScriptId($httpClient, 'script_job_pre_2');
        $postScript2 = $this->getScriptId($httpClient, 'script_job_post_2');
        $updateJob = JobMother::withAllParameters(
            1,
            1,
            "description updated",
            "updated exclude pattern",
            "updated include pattern",
            true,
            400,
            "example@example.com",
            ["owner", "email"],
            "/some/random/path",
            1,
            [$postScript1, $postScript2],
            [$preScript1, $preScript2],
            "randomtoken",
            true
            );
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), [
            'json' => $updateJobJson
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($updateJobJson);
        $this->assertJsonContains($updateJob->getContext());
    }

    public function testUpdateJobInvalidNotificationLevel(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withInvalidNotificationLevel();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification level (0, 200, 300, 400, 1000)');
    }

    public function testUpdateJobInvalidNotificationsEmail(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withInvalidNotificationsEmail();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notification email address');
    }

    public function testUpdateJobInvalidNotificationsTo(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withInvalidNotificationsTo();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect notifications to argument (owner, admin, email)');
    }

    public function testUpdateJobInvalidPostScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_post');
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withPostScripts([$scriptId]);
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job post script');
    }

    public function testUpdateJobInvalidPreScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_job_pre');
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withPreScripts([$scriptId]);
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a job pre script');
    }

    public function testUpdateJobNonExistentBackupLocation(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withNonExistentBackupLocation();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect backup location id');
    }

    public function testUpdateJobNonExistentClient(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withNonExistentClient();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect client id');
    }

    public function testUpdateJobNonExistentPolicy(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job = $this->postJob($httpClient, $job);
        $updateJob = JobMother::withNonExistentPolicy();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testUpdateJobNotFound(): void
    {
        $httpClient = $this->authenticate();
        $updateJob = JobMother::base();
        $updateJobJson = $updateJob->getData();
        $httpClient->request('PUT', JobMother::getNonExistentIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The job "'.JobMother::UNEXISTING_ID.'" does not exist.');
    }

    public function testUpdateJobNonExistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job =$this->postJob($httpClient, $job);
        $updateJob = JobMother::withNonExistentPostScripts();
        $updateJobJson = $updateJob->getData();
        $scriptId = $updateJobJson['postScripts'][0];
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" does not exist');
    }

    public function testUpdateJobNonExistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $job = JobMother::base();
        $job =$this->postJob($httpClient, $job);
        $updateJob = JobMother::withNonExistentPreScripts();
        $updateJobJson = $updateJob->getData();
        $scriptId = $updateJobJson['preScripts'][0];
        $httpClient->request('PUT', $job->getIri(), ['json' => $updateJobJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" does not exist');
    }
}

