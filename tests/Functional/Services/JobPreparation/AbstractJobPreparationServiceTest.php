<?php

namespace App\Tests\Functional\Services\JobPreparation;

use App\Services\CrawlJobContainerService;
use App\Services\HttpClientService;
use App\Services\JobPreparationService;
use App\Services\TaskTypeService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestHttpClientService;

/**
 * @group Services/JobPreparationService
 */
abstract class AbstractJobPreparationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var JobPreparationService
     */
    protected $jobPreparationService;

    /**
     * @var CrawlJobContainerService
     */
    protected $crawlJobContainerService;

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var TestHttpClientService
     */
    protected $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobPreparationService = self::$container->get(JobPreparationService::class);
        $this->crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $taskTypeService = self::$container->get(TaskTypeService::class);
        $cssValidationTaskType = $taskTypeService->getCssValidationTaskType();

        $this->jobPreparationService->setPredefinedDomainsToIgnore($cssValidationTaskType, [
            'predefined',
        ]);

        $this->jobFactory = new JobFactory(self::$container);
        $this->userFactory = new UserFactory(self::$container);

        $this->httpClientService = self::$container->get(HttpClientService::class);
    }
}
