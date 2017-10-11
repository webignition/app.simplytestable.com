<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\AcceptAction\RemoveScheduledJobsAndJobConfigurations;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;

class RemoveTest extends BaseControllerJsonTestCase {


    /**
     * @var JobConfiguration[]
     */
    private $jobConfigurations;


    /**
     * @var ScheduledJob
     */
    private $scheduledJobs;


    /**
     * @var User
     */
    private $invitee;

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->invitee = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->setUser($this->invitee);

        //$methodName = $this->getActionNameFromRouter();

        $this->jobConfigurations[] = $this->createJobConfiguration([
            'label' => 'foo1',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->invitee);

        $this->jobConfigurations[] = $this->createJobConfiguration([
            'label' => 'foo2',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'CSS validation' => []
            ],

        ], $this->invitee);

        $this->scheduledJobs[] = $this->getScheduledJobService()->create($this->jobConfigurations[0], '* * * * *', true);
        $this->scheduledJobs[] = $this->getScheduledJobService()->create($this->jobConfigurations[1], '* * * * 1', true);

        $inviter = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getTeamInviteService()->get($inviter, $this->invitee);

        $this->assertEquals(2, count($this->getJobConfigurationService()->getList()));
        $this->assertEquals(2, count($this->getScheduledJobService()->getList()));

        foreach ($this->jobConfigurations as $jobConfiguration) {
            $this->assertNotNull($jobConfiguration->getId());
        }

        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName(
            $this->container->get('request')
        );
    }

    public function testEntitiesAreRemoved() {
        foreach ($this->jobConfigurations as $jobConfiguration) {
            $this->assertNull($jobConfiguration->getId());
        }

        foreach ($this->scheduledJobs as $scheduledJob) {
            $this->assertNull($scheduledJob->getId());
        }
    }


    /**
     * @param $rawValues
     * @param User $user
     * @return JobConfiguration
     */
    protected function createJobConfiguration($rawValues, User $user) {
        $jobConfigurationValues = new JobConfigurationValues();

        if (isset($rawValues['label'])) {
            $jobConfigurationValues->setLabel($rawValues['label']);
        }

        if (isset($rawValues['parameters'])) {
            $jobConfigurationValues->setParameters($rawValues['parameters']);
        }

        if (isset($rawValues['type'])) {
            $jobConfigurationValues->setType($this->getJobTypeService()->getByName($rawValues['type']));
        }

        if (isset($rawValues['website'])) {
            $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch($rawValues['website']));
        }

        if (isset($rawValues['task_configuration'])) {
            $taskConfigurationCollection = new TaskConfigurationCollection();

            foreach ($rawValues['task_configuration'] as $taskTypeName => $taskTypeOptions) {
                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($this->getTaskTypeService()->getByName($taskTypeName));
                $taskConfiguration->setOptions($taskTypeOptions);

                $taskConfigurationCollection->add($taskConfiguration);
            }

            $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        }

        $this->getJobConfigurationService()->setUser($user);
        return $this->getJobConfigurationService()->create($jobConfigurationValues);
    }


    /**
     * @return ScheduledJobService
     */
    private function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\ConfigurationService
     */
    private function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }

}