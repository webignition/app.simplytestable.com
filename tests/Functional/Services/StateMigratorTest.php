<?php

namespace App\Tests\Functional\Services;

use App\Entity\State;
use App\Services\StateMigrator;
use App\Services\StateNames;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class StateMigratorTest extends AbstractBaseTestCase
{
    /**
     * @var StateMigrator
     */
    private $stateMigrator;

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

        $this->stateMigrator = self::$container->get(StateMigrator::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(State::class);

        $states = $this->repository->findAll();

        foreach ($states as $state) {
            $this->entityManager->remove($state);
        }

        $this->entityManager->flush();

        $stateNames = self::$container->get(StateNames::class);
        $this->stateNames = $stateNames->getData();
    }

    public function testMigrateFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->stateMigrator->migrate();

        $this->assertStateNames($this->stateNames, $this->getRepositoryStateNames());
    }

    public function testMigrateFromNonEmpty()
    {
        $expectedStateNames = $this->stateNames;
        sort($expectedStateNames);

        $this->assertEmpty($this->repository->findAll());

        $stateName = $expectedStateNames[0];

        $state = State::create($stateName);
        $this->entityManager->persist($state);
        $this->entityManager->flush();

        $this->assertStateNames([$stateName], $this->getRepositoryStateNames());

        $this->stateMigrator->migrate();

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
