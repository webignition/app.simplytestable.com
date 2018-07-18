<?php

namespace Tests\AppBundle\Services;

use GuzzleHttp\Handler\MockHandler;
use AppBundle\Services\HttpClientService;

class TestHttpClientService extends HttpClientService
{
    const MIDDLEWARE_HISTORY_KEY = 'history';

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @return MockHandler
     */
    protected function createInitialHandler()
    {
        parent::createInitialHandler();

        $this->mockHandler = new MockHandler();

        return $this->mockHandler;
    }

    /**
     * @param array $fixtures
     */
    public function appendFixtures(array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->mockHandler->append($fixture);
        }
    }

    /**
     * @return MockHandler
     */
    public function getMockHandler()
    {
        return $this->mockHandler;
    }
}
