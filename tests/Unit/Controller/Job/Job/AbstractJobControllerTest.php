<?php

namespace App\Tests\Unit\Controller\Job\Job;

use App\Repository\JobRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use App\Controller\Job\JobController;
use App\Services\Job\RetrievalService;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractJobControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function createJobController(
        ?RetrievalService $jobRetrievalService = null,
        ?TaskRepository $taskRepository = null
    ): JobController {
        /* @var Mock|RouterInterface $router */
        $router = \Mockery::mock(RouterInterface::class);

        if (empty($jobRetrievalService)) {
            $jobRetrievalService = \Mockery::mock(RetrievalService::class);
        }

        if (empty($taskRepository)) {
            $taskRepository = \Mockery::mock(TaskRepository::class);
        }

        return new JobController(
            $router,
            $jobRetrievalService,
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(JobRepository::class),
            $taskRepository
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
