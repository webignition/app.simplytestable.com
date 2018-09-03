<?php

namespace App\Tests\Unit\Controller\Job\Job;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Tests\Factory\MockFactory;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

/**
 * @group Controller/Job/JobController
 */
class JobControllerTaskIdsActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';
    const JOB_ID = 1;

    public function testTasksIdsActionRetrievalFailure()
    {
        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'throw' => new JobRetrievalServiceException(),
            ],
        ]);

        $jobController = $this->createJobController($jobRetrievalService);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->taskIdsAction(
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }
}