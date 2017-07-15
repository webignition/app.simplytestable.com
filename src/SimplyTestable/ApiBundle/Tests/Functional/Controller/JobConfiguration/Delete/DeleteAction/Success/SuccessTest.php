<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction\Success;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction\DeleteTest;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class SuccessTest extends DeleteTest {

    /**
     * @var Response
     */
    private $response;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->createAndActivateUser('user@example.com'));

        $methodName = $this->getActionNameFromRouter();

        $this->getJobConfigurationCreateController('createAction', [
            'label' => 'foo',
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => [],
            ]
        ])->createAction(
            $this->container->get('request')
        );

        $this->jobConfiguration = $this->getJobConfigurationService()->get(self::LABEL);


        $this->response = $this->getCurrentController()->$methodName(self::LABEL);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testJobConfigurationIsRemoved() {
        $this->assertNull($this->jobConfiguration->getId());
    }
}