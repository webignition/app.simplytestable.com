<?php

namespace Tests\ApiBundle\Unit\Controller\Job\Job;

use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\ApiBundle\Factory\MockFactory;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

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
