<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\State;
use App\Services\FixtureLoader\StateFixtureLoader;
use App\Services\StatesDataProvider;

class StateFixtureLoaderTest extends AbstractFixtureLoaderTest
{
    /**
     * @var StateFixtureLoader
     */
    private $stateFixtureLoader;

    /**
     * @var string[]
     */
    private $stateNames;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateFixtureLoader = self::$container->get(StateFixtureLoader::class);
        $stateNames = self::$container->get(StatesDataProvider::class);
        $this->stateNames = $stateNames->getData();
    }

    protected function getEntityClass(): string
    {
        return State::class;
    }

    protected function getEntityClassesToRemove(): array
    {
        return [
            State::class,
        ];
    }

    public function testLoadFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->stateFixtureLoader->load();

        $this->assertStateNames($this->stateNames, $this->getRepositoryStateNames());
    }

    public function testLoadFromNonEmpty()
    {
        $expectedStateNames = $this->stateNames;
        sort($expectedStateNames);

        $this->assertEmpty($this->repository->findAll());

        $stateName = $expectedStateNames[0];

        $state = State::create($stateName);
        $this->entityManager->persist($state);
        $this->entityManager->flush();

        $this->assertStateNames([$stateName], $this->getRepositoryStateNames());

        $this->stateFixtureLoader->load();

        $this->assertStateNames($expectedStateNames, $this->getRepositoryStateNames());
    }

    /**
     * @return string[]
     */
    private function getRepositoryStateNames(): array
    {
        $names = [];

        /* @var State[] $states */
        $states = $this->repository->findAll();

        foreach ($states as $state) {
            $this->assertInstanceOf(State::class, $state);
            $names[] = (string)$state;
        }

        sort($names);

        return $names;
    }

    private function assertStateNames(array $expectedStateNames, array $stateNames)
    {
        sort($expectedStateNames);
        sort($stateNames);

        $this->assertEquals($expectedStateNames, $stateNames);
    }
}
