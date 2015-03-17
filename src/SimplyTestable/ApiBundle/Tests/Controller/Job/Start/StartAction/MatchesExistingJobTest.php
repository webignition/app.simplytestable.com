<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

use Symfony\Component\HttpFoundation\RedirectResponse;

class MatchesExistingJobTest extends ActionTest {


    /**
     * @var RedirectResponse[]
     */
    private $responses;

    public function setUp() {
        parent::setUp();

        $methodName = $this->getActionNameFromRouter([
            'site_root_url' => self::DEFAULT_CANONICAL_URL
        ]);

        $this->responses[] = $this->getCurrentController()->$methodName(
            $this->container->get('request'),
            self::DEFAULT_CANONICAL_URL
        );
        $this->responses[] = $this->getCurrentController()->$methodName(
            $this->container->get('request'),
            self::DEFAULT_CANONICAL_URL
        );
    }


    public function testHasCorrectNumberOfResponses() {
        $this->assertEquals(2, count($this->responses));
    }


    public function testHasNonUniqueSetOfResponses() {
        $this->assertEquals($this->responses[0]->headers->get('location'), $this->responses[1]->headers->get('location'));
    }
    
}