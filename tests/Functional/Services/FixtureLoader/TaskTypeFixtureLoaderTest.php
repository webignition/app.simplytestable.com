<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\Task\TaskType;
use App\Services\FixtureLoader\TaskTypeFixtureLoader;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class TaskTypeFixtureLoaderTest extends AbstractBaseTestCase
{
    /**
     * @var TaskTypeFixtureLoader
     */
    private $taskTypeFixtureLoader;

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
    private $taskTypeData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskTypeFixtureLoader = self::$container->get(TaskTypeFixtureLoader::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(TaskType::class);

        $this->removeAllForEntity(TaskType::class);

        $this->entityManager->flush();

        $taskTypesDataProvider = self::$container->get('app.services.data-provider.task-types');
        $this->taskTypeData = $taskTypesDataProvider->getData();
    }

    public function testLoadFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->taskTypeFixtureLoader->load();

        $this->assertRepositoryTaskTypes($this->taskTypeData);
    }

    public function testLoadFromNonEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $name = key($this->taskTypeData);
        $taskTypeProperties = current($this->taskTypeData);

        $reflectionMethod = new \ReflectionMethod(TaskTypeFixtureLoader::class, 'loadTaskType');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->taskTypeFixtureLoader, $name, $taskTypeProperties);

        $this->assertRepositoryTaskTypes([
            $name => $taskTypeProperties,
        ]);

        $this->taskTypeFixtureLoader->load();
        $this->assertRepositoryTaskTypes($this->taskTypeData);
    }

    private function assertRepositoryTaskTypes(array $taskTypeData)
    {
        $repositoryJobTypes = $this->repository->findAll();

        $this->assertCount(count($taskTypeData), $repositoryJobTypes);

        /* @var TaskType[] $taskTypes */
        $taskTypes = [];

        foreach ($repositoryJobTypes as $taskType) {
            $taskTypes[$taskType->getName()] = $taskType;
        }

        foreach ($taskTypeData as $name => $taskTypeProperties) {
            $taskType = $taskTypes[$name];

            $this->assertEquals($name, $taskType->getName());
            $this->assertEquals($taskTypeProperties['description'], $taskType->getDescription());
            $this->assertEquals($taskTypeProperties['selectable'], $taskType->getSelectable());
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
