<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\Uniqueness\ForTeam;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\Uniqueness\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class WithSameTaskConfigurationsTest extends ServiceTest {

    const LABEL = 'foo';
    const PARAMETERS = 'parameters';

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

        /* @var $taskConfigurations TaskConfiguration[] */
        $taskConfigurations = [];

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurations[] = $taskConfiguration;
        }

        $this->getJobConfigurationService()->setUser($member);
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $taskConfigurations,
            self::LABEL,
            self::PARAMETERS
        );

        $this->getJobConfigurationService()->setUser($leader);
    }


    public function testCreateWithSameArgumentsAndSameTaskConfigurationsThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Matching configuration already exists',
            JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
        );

        /* @var $taskConfigurations TaskConfiguration[] */
        $taskConfigurations = [];

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurations[] = $taskConfiguration;
        }

        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $taskConfigurations,
            'bar',
            self::PARAMETERS
        );
    }


}