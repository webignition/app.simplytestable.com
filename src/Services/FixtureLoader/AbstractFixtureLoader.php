<?php

namespace App\Services\FixtureLoader;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

abstract class AbstractFixtureLoader
{
    protected $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    protected $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository($this->getEntityClass());
    }

    abstract protected function getEntityClass(): string;
}
