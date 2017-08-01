<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction;

use Symfony\Component\HttpFoundation\RedirectResponse;

class MultipleTest extends ActionTest {


    /**
     * @var RedirectResponse[]
     */
    private $responses;


    private $canonicalUrls = [
        'http://one.example.com',
        'http://two.example.com'
    ];

    protected function setUp() {
        parent::setUp();

        foreach ($this->canonicalUrls as $canonicalUrl) {
            $methodName = $this->getActionNameFromRouter([
                'site_root_url' => $canonicalUrl
            ]);
            $this->responses[$canonicalUrl] = $this->getCurrentController()->$methodName(
                $this->container->get('request'),
                $canonicalUrl
            );
        }
    }


    public function testHasCorrectNumberOfResponses() {
        $this->assertEquals(count($this->canonicalUrls), count($this->responses));
    }


    public function testHasUniqueSetOfResponses() {
        $responseLocations = [];

        foreach ($this->responses as $response) {
            $responseLocations[] = $response->headers->get('location');
        }

        foreach (array_count_values($responseLocations) as $canonicalUrl => $frequency) {
            $this->assertEquals(1, $frequency);
        }
    }

}