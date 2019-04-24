<?php

namespace App\Services;

use App\Entity\Job\Type as JobType;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\OutputInterface;

class JobTypeFixtureLoader implements FixtureLoaderInterface
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

    public function load(?OutputInterface $output = null): void
    {
        if ($output) {
            $output->writeln('Migrating job types ...');
        }

        $data = $this->resourceLoader->getData();

        foreach ($data as $name => $description) {
            $this->loadJobType($name, $description, $output);
        }
    }

    private function loadJobType(string $name, string $description, ?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('');
        }

        if ($output) {
            $output->writeln("  " . '<comment>' . $name . '</comment>');
        }

        $jobType = $this->repository->findOneBy([
            'name' => $name,
        ]);

        if (is_null($jobType)) {
            if ($output) {
                $output->writeln('   <fg=cyan>creating</>');
            }

            $jobType = new JobType();
            $jobType->setName($name);
        }

        if ($output) {
            $output->writeln("    " . '<comment>description</comment>: ' . $description);
        }

        $jobType->setDescription($description);

        $this->entityManager->persist($jobType);
        $this->entityManager->flush();

        if ($output) {
            $output->writeln('');
        }
    }
}
