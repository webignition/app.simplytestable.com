<?php

namespace Tests\ApiBundle\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Symfony\Component\HttpFoundation\Response;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

abstract class AbstractJobConfigurationControllerTest extends AbstractBaseTestCase
{
    /**
     * @var JobConfigurationController
     */
    protected $jobConfigurationController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationController = $this->container->get(JobConfigurationController::class);
    }
}
