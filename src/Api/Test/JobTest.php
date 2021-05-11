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
}

