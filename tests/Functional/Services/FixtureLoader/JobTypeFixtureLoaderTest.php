<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\Job\Type;
use App\Services\FixtureLoader\JobTypeFixtureLoader;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class JobTypeFixtureLoaderTest extends AbstractBaseTestCase
{
    /**
     * @var JobTypeFixtureLoader
     */
    private $jobTypeFixtureLoader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $jobTypeData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobTypeFixtureLoader = self::$container->get(JobTypeFixtureLoader::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(Type::class);

        $this->removeAllForEntity(Type::class);

        $this->entityManager->flush();

        $jobTypesDataProvider = self::$container->get('app.services.data-provider.job-types');
        $this->jobTypeData = $jobTypesDataProvider->getData();
    }

    public function testLoadFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->jobTypeFixtureLoader->load();

        $this->assertRepositoryJobTypes($this->jobTypeData);
    }

    public function testLoadFromNonEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $name = key($this->jobTypeData);
        $description = current($this->jobTypeData);

        $reflectionMethod = new \ReflectionMethod(JobTypeFixtureLoader::class, 'loadJobType');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->jobTypeFixtureLoader, $name, $description);

        $this->assertRepositoryJobTypes([
            $name => $description,
        ]);

        $this->jobTypeFixtureLoader->load();
        $this->assertRepositoryJobTypes($this->jobTypeData);
    }

    private function assertRepositoryJobTypes(array $jobTypeData)
    {
        $repositoryJobTypes = $this->repository->findAll();

        $this->assertCount(count($jobTypeData), $repositoryJobTypes);

        /* @var Type[] $jobTypes */
        $jobTypes = [];

        foreach ($repositoryJobTypes as $jobType) {
            $jobTypes[$jobType->getName()] = $jobType;
        }

        foreach ($jobTypeData as $name => $description) {
            $jobType = $jobTypes[$name];

            $this->assertEquals($name, $jobType->getName());
            $this->assertEquals($description, $jobType->getDescription());
        }
    }

    private function removeAllForEntity(string $entityClass)
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
    }
}
