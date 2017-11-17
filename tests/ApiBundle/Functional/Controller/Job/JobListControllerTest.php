<?php

namespace Tests\ApiBundle\Functional\Controller\Job;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class JobListControllerTest extends AbstractBaseTestCase
{
    public function testCountActionGetRequest()
    {
        $requestUrl = $this->container->get('router')->generate('job_joblist_count');

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent());

        $this->assertInternalType('int', $responseData);
    }

    public function testListActionGetRequest()
    {
        $requestUrl = $this->container->get('router')->generate(
            'job_joblist_list',
            [
                'limit' => 0,
                'offset' => 0,
            ]
        );

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $responseData);
        $this->assertEquals([
            'max_results',
            'limit',
            'offset',
            'jobs',
        ], array_keys($responseData));

        $responseJobs = $responseData['jobs'];

        $this->assertInternalType('array', $responseJobs);
    }

    public function testWebsitesActionGetRequest()
    {
        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_joblist_websites'),
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $responseData);
    }
}
