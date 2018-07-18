<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation;

use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Services\TestHttpClientService;

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
