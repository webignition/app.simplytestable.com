<?php

namespace Tests\ApiBundle\Functional\Entity\Job;

use Doctrine\ORM\EntityManagerInterface;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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

        $this->jobFactory = new JobFactory($this->container);
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
    }

    public function testSetPersistGetParameters()
    {
        $job = $this->jobFactory->create();
        $job->setParameters(json_encode(array(
            'foo' => 'bar'
        )));

        $this->entityManager->persist($job);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertEquals('{"foo":"bar"}', $job->getParameters());
    }

    public function testUtf8()
    {
        $key = 'key-ɸ';
        $value = 'value-ɸ';

        $job = $this->jobFactory->create();
        $job->setParameters(json_encode(array(
            $key => $value
        )));

        $this->entityManager->persist($job);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertEquals('{"key-\u0278":"value-\u0278"}', $job->getParameters());
    }
}
