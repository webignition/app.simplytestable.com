<?php

namespace App\Tests\Unit\Controller\Job;

use App\Repository\JobRepository;
use App\Controller\Job\StartController;
use App\Services\ApplicationStateService;
use App\Services\Job\StartService;
use App\Services\JobConfigurationFactory;
use App\Services\JobService;
use App\Services\Request\Factory\Job\StartRequestFactory;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Tests\Factory\ModelFactory;

/**
 * @group Controller/Job/StartController
 */
class JobStartControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testStartActionInMaintenanceReadOnlyMode()
    {
        $jobStartController = $this->createJobStartController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $jobStartController->startAction(new Request());
    }

    public function testRetestActionInvalidJobId()
    {
        $jobRepository = MockFactory::createJobRepository([
            'find' => [
                'with'=> 1,
                'return' => null,
            ],
        ]);

        $jobStartController = $this->createJobStartController([
            JobRepository::class => $jobRepository,
        ]);

        $this->expectException(BadRequestHttpException::class);

        $jobStartController->retestAction(new Request(), 1);
    }

    public function testRetestActionForUnfinishedJob()
    {
        $job = ModelFactory::createJob();

        $jobRepository = MockFactory::createJobRepository([
            'find' => [
                'with'=> 1,
                'return' => $job,
            ],
        ]);

        $jobService = MockFactory::createJobService([
            'isFinished' => [
                'with' => $job,
                'return' => false,
            ],
        ]);

        $jobStartController = $this->createJobStartController([
            JobRepository::class => $jobRepository,
            JobService::class => $jobService,
        ]);

        $this->expectException(BadRequestHttpException::class);

        $jobStartController->retestAction(new Request(), 1);
    }

    /**
     * @param array $services
     *
     * @return StartController
     */
    private function createJobStartController($services = [])
    {
        if (!isset($services['router'])) {
            /* @var RouterInterface|MockInterface $router */
            $router = \Mockery::mock(RouterInterface::class);

            $services['router'] = $router;
        }

        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[StartService::class])) {
            $services[StartService::class] = MockFactory::createJobStartService();
        }

        if (!isset($services[StartRequestFactory::class])) {
            $services[StartRequestFactory::class] = MockFactory::createJobStartRequestFactory();
        }

        if (!isset($services[JobConfigurationFactory::class])) {
            $services[JobConfigurationFactory::class] = MockFactory::createJobConfigurationFactory();
        }

        if (!isset($services[JobService::class])) {
            $services[JobService::class] = MockFactory::createJobService();
        }

        if (!isset($services[JobRepository::class])) {
            $services[JobRepository::class] = \Mockery::mock(JobRepository::class);
        }

        $jobStartController = new StartController(
            $services['router'],
            $services[ApplicationStateService::class],
            $services[StartService::class],
            $services[StartRequestFactory::class],
            $services[JobConfigurationFactory::class],
            $services[JobService::class],
            $services[JobRepository::class]
        );

        return $jobStartController;
    }
}
