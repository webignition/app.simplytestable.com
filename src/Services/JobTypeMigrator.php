<?php

namespace App\Services;

use App\Entity\Job\Type as JobType;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class JobTypeMigrator
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
        $this->repository = $entityManager->getRepository(JobType::class);
    }

    public function migrate()
    {
        $data = $this->resourceLoader->getData();

        foreach ($data as $name => $description) {
            $this->migrateJobType($name, $description);
        }
    }

    private function migrateJobType(string $name, string $description)
    {
        $jobType = $this->repository->findOneBy([
            'name' => $name,
        ]);

        if (is_null($jobType)) {
            $jobType = new JobType();
            $jobType->setName($name);
        }

        $jobType->setDescription($description);

        $this->entityManager->persist($jobType);
        $this->entityManager->flush();
    }
}
