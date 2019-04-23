<?php

namespace App\Tests\Functional\Services;

use App\Entity\Task\TaskType;
use App\Services\TaskTypeMigrator;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class TaskTypeMigratorTest extends AbstractBaseTestCase
{
    /**
     * @var TaskTypeMigrator
     */
    private $taskTypeMigrator;

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

        $this->taskTypeMigrator = self::$container->get(TaskTypeMigrator::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(TaskType::class);

        $this->removeAllForEntity(TaskType::class);

        $this->entityManager->flush();

        $taskTypesDataProvider = self::$container->get('app.services.data-provider.task-types');
        $this->taskTypeData = $taskTypesDataProvider->getData();
    }

    public function testMigrateFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->taskTypeMigrator->migrate();

        $this->assertRepositoryTaskTypes($this->taskTypeData);
    }

    public function testMigrateFromNonEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $name = key($this->taskTypeData);
        $taskTypeProperties = current($this->taskTypeData);

        $reflectionMethod = new \ReflectionMethod(TaskTypeMigrator::class, 'migrateTaskType');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->taskTypeMigrator, $name, $taskTypeProperties);

        $this->assertRepositoryTaskTypes([
            $name => $taskTypeProperties,
        ]);

        $this->taskTypeMigrator->migrate();
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
