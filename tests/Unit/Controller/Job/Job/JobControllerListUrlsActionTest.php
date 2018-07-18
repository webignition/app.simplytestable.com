<?php

namespace App\Tests\Unit\Controller\Job\Job;

use Mockery\Mock;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Tests\Factory\MockFactory;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

/**
 * @group Controller/Job/JobController
 */
class JobControllerListUrlsActionTest extends AbstractJobControllerTest
{
    const JOB_ID = 1;

    public function testListUrlsActionJobRetrievalServiceException()
    {
        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'throw' => new JobRetrievalServiceException(),
            ],
        ]);

        $jobController = $this->createJobController($jobRetrievalService);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->listUrlsAction(
            'http://example.com/',
            self::JOB_ID
        );
    }

    public function testListUrlsActionSuccess()
    {
        /* @var Mock|Job $job */
        $job = \Mockery::mock(Job::class);
        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'return' => $job,
            ],
        ]);

        $urlResultSet = [
            [
                'url' => 'foo',
            ],
        ];

        $taskRepository = MockFactory::createTaskRepository([
            'findUrlsByJob' => [
                'with' => $job,
                'return' => $urlResultSet,
            ],
        ]);

        $entityManager = MockFactory::createEntityManager([
            'getRepository' => [
                'with' => Task::class,
                'return' => $taskRepository,
            ],
        ]);

        $jobController = $this->createJobController($jobRetrievalService, $entityManager);

        $response = $jobController->listUrlsAction('http://example.com/', self::JOB_ID);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals($urlResultSet, $responseData);
    }
}
