<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Failure;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\UpdateController;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\UpdateTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

abstract class FailureTest extends UpdateTest {

    const LABEL1 = 'foo';
    const LABEL2 = 'bar';

    /**
     * @var array
     */
    protected  $originalTaskConfiguration = [
        'HTML validation' => []
    ];

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $this->setUser($this->getCurrentUser());
        $this->getJobConfigurationService()->setUser($this->getCurrentUser());

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $jobConfigurationValues = new JobConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL1);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($fullSiteJobType);
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $jobConfigurationValues->setParameters($this->getOriginalParameters());

        $this->getJobConfigurationService()->create($jobConfigurationValues);

        $jobConfigurationValues->setLabel(self::LABEL2);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($fullSiteJobType);
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://foo.example.com/'));
        $jobConfigurationValues->setParameters($this->getOriginalParameters());

        $this->getJobConfigurationService()->create($jobConfigurationValues);

        $controller = new UpdateController();
        $controller->setContainer($this->container);

        $request = new Request([], $this->getRequestPostData());

        $this->response = $controller->updateAction(
            $request,
            $this->getMethodLabel()
        );
    }

    abstract protected function getMethodLabel();
    abstract protected function getNewLabel();
    abstract protected function getNewParameters();
    abstract protected function getCurrentUser();
    abstract protected function getHeaderErrorCode();
    abstract protected function getHeaderErrorMessage();

    protected function getOriginalParameters() {
        return 'parameters';
    }

    public function testResponseStatusCodeIs400() {
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    public function testResponseHeaderErrorCode() {
        $this->assertEquals(
            [
                'code' => $this->getHeaderErrorCode(),
                'message' => $this->getHeaderErrorMessage()
            ],
            json_decode($this->response->headers->get('X-JobConfigurationCreate-Error'), true)
        );
    }

    protected function getRequestPostData() {
        return [
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => []
            ],
            'parameters' => $this->getNewParameters(),
            'label' => $this->getNewLabel()
        ];
    }


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
}