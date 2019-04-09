<?php

namespace App\Tests\Functional\Entity\Job;

use App\Entity\Job\Job;
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
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    public function testSetPersistGetParameters()
    {
        $job = $this->createJob([
            'foo' => 'bar',
        ]);

        $this->assertEquals('{"foo":"bar"}', $job->getParametersString());
    }

    public function testUtf8()
    {
        $key = 'key-ɸ';
        $value = 'value-ɸ';

        $job = $this->createJob([
            $key => $value,
        ]);

        $this->assertEquals('{"key-\u0278":"value-\u0278"}', $job->getParametersString());
    }

    private function createJob(array $parameters): Job
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_PARAMETERS => $parameters
        ]);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return $job;
    }
}
