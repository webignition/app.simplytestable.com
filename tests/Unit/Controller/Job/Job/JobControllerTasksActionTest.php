<?php

namespace App\Tests\Unit\Controller\Job\Job;

use App\Services\Job\RetrievalService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

/**
 * @group Controller/Job/JobController
 */
class JobControllerTasksActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';
    const JOB_ID = 1;

    public function testTasksActionRetrievalFailure()
    {
        $taskService = MockFactory::createTaskService();

        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'throw' => new JobRetrievalServiceException(),
            ],
        ]);

        $jobController = $this->createJobController([
            RetrievalService::class => $jobRetrievalService,
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->tasksAction(
            $taskService,
            new Request(),
            self::JOB_ID
        );
    }
}
