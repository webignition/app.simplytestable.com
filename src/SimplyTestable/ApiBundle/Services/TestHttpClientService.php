<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Plugin\History\HistoryPlugin;
use Guzzle\Plugin\Mock\MockPlugin;

class TestHttpClientService extends HttpClientService
{
    /**
     * @var HistoryPlugin
     */
    private $historyPlugin;

    /**
     * @var MockPlugin
     */
    private $mockPlugin;

    public function get()
    {
        if (is_null($this->httpClient)) {
            $this->historyPlugin = new HistoryPlugin();
            $this->mockPlugin = new MockPlugin();

            $this->httpClient = new HttpClient();
            $this->httpClient->addSubscriber($this->historyPlugin);
            $this->httpClient->addSubscriber($this->mockPlugin);
        }

        return $this->httpClient;
    }

    public function queueFixtures($fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->queueFixture($fixture);
        }
    }

    public function queueFixture($fixture)
    {
        $this->get();

        if ($fixture instanceof CurlException) {
            $this->mockPlugin->addException($fixture);
        } else {
            $this->mockPlugin->addResponse($fixture);
        }
    }

    /**
     * @return MockPlugin
     */
    public function getMockPlugin()
    {
        return $this->mockPlugin;
    }

    /**
     * @return HistoryPlugin
     */
    public function getHistoryPlugin()
    {
        return $this->historyPlugin;
    }
}