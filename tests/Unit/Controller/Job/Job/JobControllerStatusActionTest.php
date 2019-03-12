<?php

namespace App\Tests\Unit\Controller\Job\Job;

use App\Services\Job\RetrievalService;
use Mockery\Mock;
use App\Entity\Job\Job;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Tests\Factory\MockFactory;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

/**
 * @group Controller/Job/JobController
 */
class JobControllerStatusActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';
    const JOB_ID = 1;

    public function testStatusActionSuccess()
    {
        /* @var Mock|Job $job */
        $job = \Mockery::mock(Job::class);
        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'return' => $job,
            ],
        ]);

        $jobSummaryFactory = MockFactory::createJobSummaryFactory([
            'create' => [
                'with' => $job,
                'return' => [],
            ],
        ]);

        $jobController = $this->createJobController([
            RetrievalService::class => $jobRetrievalService,
        ]);

        $response = $jobController->statusAction(
            $jobSummaryFactory,
            self::JOB_ID
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertIsArray($responseData);
    }

    public function testStatusActionAccessDenied()
    {
        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'throw' => new JobRetrievalServiceException(),
            ],
        ]);

        $jobSummaryFactory = MockFactory::createJobSummaryFactory();

        $jobController = $this->createJobController([
            RetrievalService::class => $jobRetrievalService,
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->statusAction(
            $jobSummaryFactory,
            self::JOB_ID
        );
    }
}
