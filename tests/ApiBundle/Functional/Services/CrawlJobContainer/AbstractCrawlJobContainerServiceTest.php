<?php

namespace Tests\ApiBundle\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractCrawlJobContainerServiceTest extends AbstractBaseTestCase
{
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

        $this->crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }
}
