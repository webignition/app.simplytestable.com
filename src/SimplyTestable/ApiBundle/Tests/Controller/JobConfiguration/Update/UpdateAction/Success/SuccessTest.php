<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\Success;

use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\UpdateTest;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

abstract class SuccessTest extends UpdateTest {

    const LABEL = 'foo';

    /**
     * @var Response
     */
    private $response;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    /**
     * @var array
     */
    protected  $originalTaskConfiguration = [
        'HTML validation' => []
    ];


    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

        $this->getUserService()->setUser($user);
        $this->getJobConfigurationService()->setUser($user);

        $jobConfigurationValues = new JobConfigurationValues();
        $jobConfigurationValues->setLabel($this->getOriginalLabel());
        $jobConfigurationValues->setTaskConfigurationCollection($this->getOriginalTaskConfigurationCollection());
        $jobConfigurationValues->setType($this->getOriginalJobType());
        $jobConfigurationValues->setWebsite($this->getOriginalWebsite());
        $jobConfigurationValues->setParameters($this->getOriginalParameters());

        $this->getJobConfigurationService()->create($jobConfigurationValues);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request'),
            $this->getOriginalLabel()
        );

        $this->jobConfiguration = $this->getJobConfigurationService()->get($this->getUpdatedLabel());
    }

    protected function getRequestPostData() {
        return [
            'website' => (string)$this->getNewWebsite(),
            'type' => (string)$this->getNewJobType(),
            'task-configuration' => $this->getNewTaskConfiguration(),
            'parameters' => $this->getNewParameters(),
            'label' => $this->getNewLabel()
        ];
    }

    protected function getUpdatedLabel() {
        $newLabel = $this->getNewLabel();
        return empty($newLabel) ? $this->getOriginalLabel() : $newLabel;
    }

    protected function getOriginalLabel() {
        return self::LABEL;
    }

    protected function getOriginalTaskConfigurationCollection() {
        return $this->getStandardTaskConfigurationCollection();
    }

    protected function getOriginalJobType() {
        return $this->getJobTypeService()->getFullSiteType();
    }

    protected function getOriginalWebsite() {
        return $this->getWebSiteService()->fetch('http://example.com/');
    }

    protected function getOriginalParameters() {
        return 'parameters';
    }

    abstract protected function getNewLabel();
    abstract protected function getNewWebsite();
    abstract protected function getNewJobType();
    abstract protected function getNewTaskConfiguration();
    abstract protected function getNewParameters();

    /**
     * @return TaskConfigurationCollection
     */
    protected function getStandardTaskConfigurationCollection() {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->originalTaskConfiguration as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions);
            $taskConfigurationCollection->add($taskConfiguration);
        }

        return $taskConfigurationCollection;
    }


    public function testResponseStatusCode() {
        $this->assertEquals(302, $this->response->getStatusCode());
    }


    public function testResponseRedirectLocation() {
        $newLabel = $this->getNewLabel();

        $this->assertEquals($this->getRouter()->generate('jobconfiguration_get_get', [
            'label' => empty($newLabel) ? $this->getOriginalLabel() : $newLabel
        ]), $this->response->headers->get('location'));
    }


    public function testUpdatedLabelWebsiteTypeParameters() {
        $this->assertEquals($this->getUpdatedLabel(), $this->jobConfiguration->getLabel());
        $this->assertEquals($this->getNewWebsite(), $this->jobConfiguration->getWebsite());
        $this->assertEquals($this->getNewJobType(), $this->jobConfiguration->getType());
        $this->assertEquals($this->getNewParameters(), $this->jobConfiguration->getParameters());
    }


    public function testUpdatedTaskConfiguration() {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->getNewTaskConfiguration() as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions);
            $taskConfigurationCollection->add($taskConfiguration);
        }

        $this->assertTrue($this->jobConfiguration->getTaskConfigurationsAsCollection()->equals($taskConfigurationCollection));
    }

}