<?php

namespace Tests\AppBundle\Functional\Services\Job\Configuration;

use AppBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use AppBundle\Services\JobTypeService;
use AppBundle\Services\ScheduledJob\Service as ScheduledJobService;
use AppBundle\Services\TaskTypeService;
use AppBundle\Services\UserService;

class ConfigurationServiceDeleteTest extends AbstractConfigurationServiceTest
{
    public function testDeleteInvalidLabel()
    {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage('Configuration with label "foo" does not exist');
        $this->expectExceptionCode(JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION);

        $this->jobConfigurationService->delete('foo');
    }

    public function testDeleteInUseByScheduledJob()
    {
        $userService = self::$container->get(UserService::class);
        $scheduledJobService = self::$container->get(ScheduledJobService::class);

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

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage('Job configuration is in use by one or more scheduled jobs');
        $this->expectExceptionCode(JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB);

        $this->jobConfigurationService->delete($jobConfiguration->getLabel());
    }

    public function testDeleteSuccess()
    {
        $userService = self::$container->get(UserService::class);

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
