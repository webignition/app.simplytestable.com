<?php

namespace Tests\AppBundle\Unit\Controller\Job\Job;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\AppBundle\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

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

        $jobController = $this->createJobController($jobRetrievalService);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->tasksAction(
            $taskService,
            new Request(),
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }
}