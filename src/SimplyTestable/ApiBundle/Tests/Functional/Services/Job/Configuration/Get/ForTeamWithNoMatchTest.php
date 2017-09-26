<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Get;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ForTeamWithNoMatchTest extends ServiceTest
{
    const LABEL = 'foo';

    private $taskTypeOptionsSet = [
        'HTML validation' => [
            'options' => [
                'html+foo' => 'html+bar'
            ]
        ],
        'CSS validation' => [
            'options' => [
                'css+foo' => 'css+bar'
            ]
        ],
        'JS static analysis' => [
            'options' => [
                'js+foo' => 'js+bar'
            ]
        ],
        'Link integrity' => [
            'options' => [
                'li+foo' => 'li+bar'
            ]
        ]
    ];

    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $member = $userFactory->createAndActivateUser();

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $leader
        ), $member);

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        $jobConfigurationValues->setType($fullSiteJobType);
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $jobConfigurationValues->setParameters('parameters');

        $this->getJobConfigurationService()->setUser($leader);
        $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->getJobConfigurationService()->setUser($member);
    }

    public function testNoMatchReturnsNull()
    {
        $this->assertNull($this->getJobConfigurationService()->get('bar'));
    }
}
