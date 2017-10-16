<?php

namespace SimplyTestable\ApiBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseTestCase extends WebTestCase
{
    const FIXTURES_DATA_RELATIVE_PATH = '/Fixtures/Data';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Application
     */
    protected $application;

    protected function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);

        $this->container->get('doctrine')->getConnection()->beginTransaction();
    }

    protected function clearRedis()
    {
        exec('redis-cli -r 1 flushall');
    }

    /**
     * @param string $testName
     *
     * @return string
     */
    protected function getFixturesDataPath($testName = null)
    {
        $path = str_replace('/Functional', '', __DIR__)
            . self::FIXTURES_DATA_RELATIVE_PATH
            . '/'
            . str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));

        if (!is_null($testName)) {
            $path .= '/' . $testName;
        }

        return $path;
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->container->get('doctrine')->getConnection()->close();

        $refl = new \ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }
}
