<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class SingleResponseTest extends ActionTest {
    /**
     * @var RedirectResponse
     */
    private $response;

    public function setUp() {
        parent::setUp();

        $this->preCall();

        $methodName = $this->getActionNameFromRouter([
            'site_root_url' => $this->getCanonicalUrl()
        ]);

        $this->response = $this->getCurrentController()->$methodName(
            $this->container->get('request'),
            $this->getCanonicalUrl()
        );
    }

    protected function getCanonicalUrl() {
        return self::DEFAULT_CANONICAL_URL;
    }

    protected function preCall() {}

    /**
     * @return int
     */
    abstract protected function getExpectedResponseStatusCode();


    public function testResponseStatusCode() {
        $this->assertEquals($this->getExpectedResponseStatusCode(), $this->response->getStatusCode());
    }
    
}