<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\Job\Type;
use App\Services\FixtureLoader\JobTypeFixtureLoader;

class JobTypeFixtureLoaderTest extends AbstractFixtureLoaderTest
{
    /**
     * @var JobTypeFixtureLoader
     */
    private $jobTypeFixtureLoader;

    /**
     * @var array
     */
    private $jobTypeData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobTypeFixtureLoader = self::$container->get(JobTypeFixtureLoader::class);
        $jobTypesDataProvider = self::$container->get('app.services.data-provider.job-types');
        $this->jobTypeData = $jobTypesDataProvider->getData();
    }

    protected function getEntityClass(): string
    {
        return Type::class;
    }

    protected function getEntityClassesToRemove(): array
    {
        return [
            Type::class,
        ];
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
}
