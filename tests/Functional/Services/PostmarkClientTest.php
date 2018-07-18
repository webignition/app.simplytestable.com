<?php

namespace App\Tests\Functional\Services;

use GuzzleHttp\Client;
use Postmark\PostmarkClient;
use ReflectionClass;
use App\Tests\Functional\AbstractBaseTestCase;

class PostmarkClientTest extends AbstractBaseTestCase
{
    /**
     * @var PostmarkClient
     */
    private $postmarkClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->postmarkClient = self::$container->get(PostmarkClient::class);
    }

    public function testPostmarkClientHttpClient()
    {
        $reflection = new ReflectionClass($this->postmarkClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);

        $this->assertEquals(self::$container->get(Client::class), $property->getValue($this->postmarkClient));
    }
}
