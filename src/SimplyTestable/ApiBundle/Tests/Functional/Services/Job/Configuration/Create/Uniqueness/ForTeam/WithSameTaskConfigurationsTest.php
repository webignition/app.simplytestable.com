<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\Uniqueness\ForTeam;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\Uniqueness\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class WithSameTaskConfigurationsTest extends ServiceTest
{
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

    /**
     * @var ConfigurationValues
     */
    private $values;

    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $member = $userFactory->createAndActivateUser();

        $teamMemberService->add($teamService->create(
            'Foo',
            $leader
        ), $member);

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $taskTypeService->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $this->values = new ConfigurationValues();
        $this->values->setLabel(self::LABEL);
        $this->values->setParameters(self::PARAMETERS);
        $this->values->setTaskConfigurationCollection($taskConfigurationCollection);
        $this->values->setType($fullSiteJobType);
        $this->values->setWebsite($websiteService->fetch('http://example.com/'));

        $this->setUser($member);
        $this->getJobConfigurationService()->create($this->values);

        $this->setUser($leader);
    }

    public function testCreateWithSameArgumentsAndSameTaskConfigurationsThrowsException()
    {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Matching configuration already exists',
            JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
        );

        $this->values->setLabel('bar');
        $this->getJobConfigurationService()->create($this->values);
    }
}
