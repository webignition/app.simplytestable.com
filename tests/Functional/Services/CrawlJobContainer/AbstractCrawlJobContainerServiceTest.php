<?php

namespace App\Tests\Functional\Services\CrawlJobContainer;

use App\Services\CrawlJobContainerService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

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
