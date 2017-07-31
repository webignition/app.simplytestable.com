<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

class InUseByScheduledJobTest extends DeleteTest {

    /**
     * @var Response
     */
    private $response;

    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://original.example.com/'));

        $this->getJobConfigurationService()->setUser($user);
        $jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->getScheduledJobService()->create($jobConfiguration);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName(self::LABEL);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(400, $this->response->getStatusCode());
    }


    public function testResponseHeaderErrorCode() {
        $this->assertEquals(
            [
                'code' => 1,
                'message' => 'Job configuration is in use by a scheduled job'
            ],
            json_decode($this->response->headers->get('X-JobConfigurationDelete-Error'), true)
        );
    }


    /**
     * @return TaskConfigurationCollection
     */
    private function getStandardTaskConfigurationCollection() {
        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setType(
            $this->getTaskTypeService()->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $taskConfigurationCollection = new TaskConfigurationCollection();
        $taskConfigurationCollection->add($taskConfiguration);

        return $taskConfigurationCollection;
    }

}