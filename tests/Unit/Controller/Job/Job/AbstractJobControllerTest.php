<?php

namespace App\Tests\Unit\Controller\Job\Job;

use App\Repository\JobRepository;
use App\Repository\TaskRepository;
use App\Services\Job\AuthorisationService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use App\Controller\Job\JobController;
use App\Services\Job\RetrievalService;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractJobControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function createJobController(array $constructorServices = []): JobController
    {
        $constructorServiceIds = [
            RouterInterface::class,
            RetrievalService::class,
            EntityManagerInterface::class,
            JobRepository::class,
            TaskRepository::class,
            AuthorisationService::class
        ];

        foreach ($constructorServiceIds as $serviceId) {
            if (!array_key_exists($serviceId, $constructorServices)) {
                $constructorServices[$serviceId] = \Mockery::mock($serviceId);
            }
        }

        return new JobController(
            $constructorServices[RouterInterface::class],
            $constructorServices[RetrievalService::class],
            $constructorServices[EntityManagerInterface::class],
            $constructorServices[JobRepository::class],
            $constructorServices[TaskRepository::class],
            $constructorServices[AuthorisationService::class]
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
