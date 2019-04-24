<?php

namespace App\Services\FixtureLoader;

use App\Services\DataProviderInterface;
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

    /**
     * @var array
     */
    protected $data;

    public function __construct(EntityManagerInterface $entityManager, DataProviderInterface $dataProvider)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository($this->getEntityClass());
        $this->data = $dataProvider->getData();
    }

    abstract protected function getEntityClass(): string;
}
