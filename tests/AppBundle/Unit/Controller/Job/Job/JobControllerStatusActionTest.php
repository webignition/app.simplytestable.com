<?php

namespace Tests\AppBundle\Unit\Controller\Job\Job;

use Mockery\Mock;
use AppBundle\Entity\Job\Job;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\AppBundle\Factory\MockFactory;
use AppBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

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

        $jobController = $this->createJobController($jobRetrievalService);

        $response = $jobController->statusAction(
            $jobSummaryFactory,
            self::CANONICAL_URL,
            self::JOB_ID
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $responseData);
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

        $jobController = $this->createJobController($jobRetrievalService);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->statusAction(
            $jobSummaryFactory,
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }
}
