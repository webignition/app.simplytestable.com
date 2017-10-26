<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Services\JobTypeService;

class WithTaskConfigurationsTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

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

    protected function setUp() {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $values = new ConfigurationValues();
        $values->setLabel(self::LABEL);
        $values->setParameters('parameters');
        $values->setTaskConfigurationCollection($taskConfigurationCollection);
        $values->setType($fullSiteJobType);
        $values->setWebsite($websiteService->fetch('http://example.com/'));

        $this->getJobConfigurationService()->setUser($userService->getPublicUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($values);
    }

    public function testIdIsSet() {
        $this->assertNotNull($this->jobConfiguration->getId());
    }

    public function testTaskConfigurationsAreSetOnJobConfiguration() {
        $this->assertEquals(count($this->taskTypeOptionsSet), count($this->jobConfiguration->getTaskConfigurations()));
    }

    public function testTaskConfigurationsHaveJobConfigurationSet() {
        foreach ($this->jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertEquals($this->jobConfiguration, $taskConfiguration->getJobConfiguration());
        }
    }


}