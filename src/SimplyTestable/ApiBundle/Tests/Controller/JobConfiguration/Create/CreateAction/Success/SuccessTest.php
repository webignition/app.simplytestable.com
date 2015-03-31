<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Success;

use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\CreateTest;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

abstract class SuccessTest extends CreateTest {

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

        $this->preCallController();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request')
        );

        $this->jobConfiguration = $this->getJobConfigurationService()->get($this->getLabel());
    }

    protected function preCallController() {

    }

    /**
     * @return string
     */
    abstract protected function getLabel();

    /**
     * @return bool
     */
    abstract protected function getExpectedTaskConfigurationIsEnabled();


    public function testResponseStatusCode() {
        $this->assertEquals(302, $this->response->getStatusCode());
    }


    public function testResponseRedirectLocation() {
        $this->assertEquals('/jobconfiguration/foo/', $this->response->headers->get('location'));
    }


    public function testJobConfigurationIsPersisted() {
        $this->assertNotNull($this->jobConfiguration->getId());
    }


    public function testHasTaskConfigurationCollection() {
        $this->assertEquals(2, $this->jobConfiguration->getTaskConfigurations()->count());
    }


    public function testTaskConfigurationsAreNotEnabled() {
        foreach ($this->jobConfiguration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertEquals($this->getExpectedTaskConfigurationIsEnabled(), $taskConfiguration->getIsEnabled());
        }
    }


    protected function getRequestPostData() {
        return [
            'label' => $this->getLabel(),
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => [
                    'is-enabled' => true
                ],
                'CSS validation' => [
                    'is-enabled' => true,
                    'domains-to-ignore' => [
                        'one.cdn.example.com'
                    ]
                ]
            ],
            'parameters' => json_encode([
                'http-auth-username' => 'html-user',
                'http-auth-password' => 'html-password',
                'cookies' => [
                    [
                        'Name' => 'cookie-name',
                        'Domain' => '.example.com',
                        'Value' => 'cookie-value'
                    ]
                ]
            ])
        ];
    }

}