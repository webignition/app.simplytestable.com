<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ConfigurationServiceRemoveAllTest extends AbstractConfigurationServiceTest
{
    /**
     * @dataProvider removeAllUserIsInTeamDataProvider
     *
     * @param string $userName
     */
    public function testRemoveAllUserIsInTeam($userName)
    {
        $userFactory = new UserFactory($this->container);
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

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage('One or more job configurations are in use by one or more scheduled jobs');
        $this->expectExceptionCode(JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB);

        $this->jobConfigurationService->removeAll();
    }

    public function testRemoveAllSuccess()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
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
