<?php

namespace App\Tests\Functional\Services\Job\Configuration;

use App\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Services\UserService;

class ConfigurationServiceDeleteTest extends AbstractConfigurationServiceTest
{
    public function testDeleteInvalidId()
    {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage('Configuration does not exist');
        $this->expectExceptionCode(JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION);

        $this->jobConfigurationService->delete(0);
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
                'parameters' => '[]',
            ],
        ]);

        $jobConfiguration = $jobConfigurationCollection[0];

        $this->assertNotNull($jobConfiguration->getId());

        foreach ($jobConfiguration->getTaskConfigurationCollection() as $taskConfiguration) {
            $this->assertNotNull($taskConfiguration->getId());
        }

        $this->jobConfigurationService->delete($jobConfiguration->getId());

        $this->assertNull($jobConfiguration->getId());

        foreach ($jobConfiguration->getTaskConfigurationCollection() as $taskConfiguration) {
            $this->assertNull($taskConfiguration->getId());
        }
    }
}
