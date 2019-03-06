<?php

namespace App\Tests\Functional\Entity\Job;

use App\Tests\Services\JobFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Functional\AbstractBaseTestCase;

class ParametersTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
    }

    public function testSetPersistGetParameters()
    {
        $job = $this->jobFactory->create();
        $job->setParametersString(json_encode(array(
            'foo' => 'bar'
        )));

        $this->entityManager->persist($job);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertEquals('{"foo":"bar"}', $job->getParametersString());
    }

    public function testUtf8()
    {
        $key = 'key-ɸ';
        $value = 'value-ɸ';

        $job = $this->jobFactory->create();
        $job->setParametersString(json_encode(array(
            $key => $value
        )));

        $this->entityManager->persist($job);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertEquals('{"key-\u0278":"value-\u0278"}', $job->getParametersString());
    }
}
