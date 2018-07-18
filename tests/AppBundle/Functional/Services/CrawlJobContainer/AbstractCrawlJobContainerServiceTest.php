<?php

namespace Tests\AppBundle\Functional\Services\CrawlJobContainer;

use AppBundle\Services\CrawlJobContainerService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

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

        $this->crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $this->jobFactory = new JobFactory(self::$container);
        $this->userFactory = new UserFactory(self::$container);
    }
}
