<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction\Success;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobTaskConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction\DeleteTest;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class SuccessTest extends DeleteTest {

    /**
     * @var Response
     */
    private $response;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();
        $this->setUser($user);

        $methodName = $this->getActionNameFromRouter();

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $user,
            JobConfigurationFactory::KEY_LABEL => self::LABEL,
            JobConfigurationFactory::KEY_WEBSITE_URL => 'http://example.com/',
            JobConfigurationFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
            JobConfigurationFactory::KEY_TASK_CONFIGURATIONS => [
                [
                    JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                ],
            ],
        ]);

        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $jobConfigurationService->setUser($user);

        $this->jobConfiguration = $jobConfigurationService->get(self::LABEL);

        $this->response = $this->getCurrentController()->$methodName(self::LABEL);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testJobConfigurationIsRemoved() {
        $this->assertNull($this->jobConfiguration->getId());
    }
}