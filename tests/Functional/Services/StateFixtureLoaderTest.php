<?php

namespace App\Tests\Functional\Services;

use App\Entity\State;
use App\Services\StateFixtureLoader;
use App\Services\StatesDataProvider;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class StateFixtureLoaderTest extends AbstractBaseTestCase
{
    /**
     * @var StateFixtureLoader
     */
    private $stateFixtureLoader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    /**
     * @var string[]
     */
    private $stateNames;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateFixtureLoader = self::$container->get(StateFixtureLoader::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(State::class);

        $states = $this->repository->findAll();

        foreach ($states as $state) {
            $this->entityManager->remove($state);
        }

        $this->entityManager->flush();

        $stateNames = self::$container->get(StatesDataProvider::class);
        $this->stateNames = $stateNames->getData();
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
