<?php

namespace App\Tests\Functional\Services\CrawlJobContainer;

use App\Services\CrawlJobContainerService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;

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
        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->userFactory = self::$container->get(UserFactory::class);
    }
}
