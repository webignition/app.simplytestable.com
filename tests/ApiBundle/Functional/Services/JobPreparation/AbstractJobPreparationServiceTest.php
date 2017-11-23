<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation;

use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobPreparationService = $this->container->get(JobPreparationService::class);
        $this->crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);

        $taskTypeService = $this->container->get(TaskTypeService::class);
        $cssValidationTaskType = $taskTypeService->getCssValidationTaskType();

        $this->jobPreparationService->setPredefinedDomainsToIgnore($cssValidationTaskType, [
            'predefined',
        ]);

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }
}
