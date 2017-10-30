<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;

class ConfigurationServiceDeleteTest extends AbstractConfigurationServiceTest
{
    public function testDeleteInvalidLabel()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $this->setExpectedException(
            JobConfigurationServiceException::class,
            'Configuration with label "foo" does not exist',
            JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION
        );

        $this->jobConfigurationService->delete('foo');
    }

    public function testDeleteInUseByScheduledJob()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        $this->setUser($userService->getPublicUser());

        $jobConfigurationCollection = $this->createJobConfigurationCollection([
            [
                'label' => 'foo',
                'website' => 'http://example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],
        ]);

        $jobConfiguration = $jobConfigurationCollection[0];
        $scheduledJobService->create($jobConfiguration);

        $this->setExpectedException(
            JobConfigurationServiceException::class,
            'Job configuration is in use by one or more scheduled jobs',
            JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
        );

        $this->jobConfigurationService->delete($jobConfiguration->getLabel());
    }

    public function testDeleteSuccess()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->setUser($userService->getPublicUser());

        $jobConfigurationCollection = $this->createJobConfigurationCollection([
            [
                'label' => 'foo',
                'website' => 'http://example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],
        ]);

        $jobConfiguration = $jobConfigurationCollection[0];

        $this->assertNotNull($jobConfiguration->getId());

        foreach ($jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertNotNull($taskConfiguration->getId());
        }

        $this->jobConfigurationService->delete($jobConfiguration->getLabel());

        $this->assertNull($jobConfiguration->getId());

        foreach ($jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertNull($taskConfiguration->getId());
        }
    }
}
