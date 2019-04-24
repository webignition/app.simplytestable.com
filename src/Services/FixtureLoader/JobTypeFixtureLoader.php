<?php

namespace App\Services\FixtureLoader;

use App\Entity\Job\Type as JobType;
use App\Services\DataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobTypeFixtureLoader extends AbstractFixtureLoader implements FixtureLoaderInterface
{
    public function __construct(EntityManagerInterface $entityManager, DataProviderInterface $dataProvider)
    {
        parent::__construct($entityManager, $dataProvider);
    }

    protected function getEntityClass(): string
    {
        return JobType::class;
    }

    public function load(?OutputInterface $output = null): void
    {
        if ($output) {
            $output->writeln('Migrating job types ...');
        }

        foreach ($this->data as $name => $description) {
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
