<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Get;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForTeamWithNoMatchTest extends ServiceTest {

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

    public function setUp() {
        parent::setUp();

        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $member = $this->createAndActivateUser('user@example.com');

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
        $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $jobConfigurationValues->setParameters('parameters');

        $this->getJobConfigurationService()->setUser($leader);
        $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->getJobConfigurationService()->setUser($member);
    }

    public function testNoMatchReturnsNull() {
        $this->assertNull($this->getJobConfigurationService()->get('bar'));
    }

}
