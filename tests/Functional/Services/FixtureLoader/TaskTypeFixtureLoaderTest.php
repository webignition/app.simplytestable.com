<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\Task\TaskType;
use App\Services\FixtureLoader\TaskTypeFixtureLoader;

class TaskTypeFixtureLoaderTest extends AbstractFixtureLoaderTest
{
    /**
     * @var TaskTypeFixtureLoader
     */
    private $taskTypeFixtureLoader;

    /**
     * @var array
     */
    private $taskTypeData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskTypeFixtureLoader = self::$container->get(TaskTypeFixtureLoader::class);
        $taskTypesDataProvider = self::$container->get('app.services.data-provider.task-types');
        $this->taskTypeData = $taskTypesDataProvider->getData();
    }

    protected function getEntityClass(): string
    {
        return TaskType::class;
    }

    protected function getEntityClassesToRemove(): array
    {
        return [
            TaskType::class,
        ];
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
}
