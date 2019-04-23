<?php

namespace App\Services;

use App\Entity\State;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class StateMigrator
{
    private $resourceLoader;
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    public function __construct(YamlResourceLoader $resourceLoader, EntityManagerInterface $entityManager)
    {
        $this->resourceLoader = $resourceLoader;
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(State::class);
    }

    public function migrate()
    {
        $flushRequired = false;

        $names = $this->resourceLoader->getData();

        foreach ($names as $name) {
            $entity = $this->repository->findOneBy([
                'name' => $name,
            ]);

            if (!$entity) {
                $entity = State::create($name);
                $this->entityManager->persist($entity);
                $flushRequired = true;
            }
        }

        if ($flushRequired) {
            $this->entityManager->flush();
        }
    }
}
