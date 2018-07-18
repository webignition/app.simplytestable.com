<?php

namespace App\Tests\Unit\Controller\Job\Job;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use App\Controller\Job\JobController;
use App\Services\Job\RetrievalService;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractJobControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param Mock|RetrievalService $jobRetrievalService
     * @param Mock|EntityManagerInterface $entityManager
     *
     * @return JobController
     */
    protected function createJobController($jobRetrievalService = null, $entityManager = null)
    {
        /* @var Mock|RouterInterface $router */
        $router = \Mockery::mock(RouterInterface::class);

        if (empty($jobRetrievalService)) {
            $jobRetrievalService = \Mockery::mock(RetrievalService::class);
        }

        if (empty($entityManager)) {
            $entityManager = \Mockery::mock(EntityManagerInterface::class);
        }

        return new JobController(
            $router,
            $jobRetrievalService,
            $entityManager
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
