<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractScheduledJobControllerTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobController
     */
    protected $scheduledJobController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobController = $this->container->get(ScheduledJobController::class);
    }
}
