<?php

namespace App\Tests\Functional\Services\Job\Configuration;

use App\Services\JobTypeService;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Services\TaskTypeService;
use App\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use App\Services\UserService;
use App\Tests\Services\UserFactory;

class ConfigurationServiceRemoveAllTest extends AbstractConfigurationServiceTest
{
    /**
     * @dataProvider removeAllUserIsInTeamDataProvider
     *
     * @param string $userName
     */
    public function testRemoveAllUserIsInTeam($userName)
    {
        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $user = $users[$userName];
        $this->setUser($user);

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage('Unable to remove all; user is in a team');
        $this->expectExceptionCode(JobConfigurationServiceException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM);

        $this->jobConfigurationService->removeAll();
    }

    /**
     * @return array
     */
    public function removeAllUserIsInTeamDataProvider()
    {
        return [
            'leader' => [
                'userName' => 'leader',
            ],
            'member' => [
                'userName' => 'member1',
            ],
        ];
    }

    public function testRemoveAllInUseByScheduledJob()
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
                'parameters' => '[]',
            ],
        ]);

        $jobConfiguration = $jobConfigurationCollection[0];
        $scheduledJobService->create($jobConfiguration);

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage('One or more job configurations are in use by one or more scheduled jobs');
        $this->expectExceptionCode(JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB);

        $this->jobConfigurationService->removeAll();
    }

    public function testRemoveAllSuccess()
    {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $jobConfigurationCollection = $this->createJobConfigurationCollection([
            [
                'label' => 'foo',
                'website' => 'http://foo.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
                'parameters' => '[]',
            ],
            [
                'label' => 'bar',
                'website' => 'http://bar. example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
                'parameters' => '[]',
            ],
        ]);

        foreach ($jobConfigurationCollection as $jobConfiguration) {
            $this->assertNotNull($jobConfiguration->getId());

            foreach ($jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
                $this->assertNotNull($taskConfiguration->getId());
            }
        }

        $this->jobConfigurationService->removeAll();

        foreach ($jobConfigurationCollection as $jobConfiguration) {
            $this->assertNull($jobConfiguration->getId());

            foreach ($jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
                $this->assertNull($taskConfiguration->getId());
            }
        }
    }
}
