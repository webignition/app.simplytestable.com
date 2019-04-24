<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

abstract class AbstractFixtureLoaderTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->repository = $this->entityManager->getRepository($this->getEntityClass());

        $this->removeAllForEntities();
    }

    abstract protected function getEntityClass(): string;
    abstract protected function getEntityClassesToRemove(): array;

    private function removeAllForEntities()
    {
        foreach ($this->getEntityClassesToRemove() as $class) {
            $this->removeAllForEntity($class);
        }
    }

    private function removeAllForEntity(string $entityClass)
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();
    }
}
