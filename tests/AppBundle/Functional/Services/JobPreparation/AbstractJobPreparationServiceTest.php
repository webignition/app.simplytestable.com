<?php

namespace Tests\AppBundle\Functional\Services\JobPreparation;

use AppBundle\Services\CrawlJobContainerService;
use AppBundle\Services\HttpClientService;
use AppBundle\Services\JobPreparationService;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Services\TestHttpClientService;

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
