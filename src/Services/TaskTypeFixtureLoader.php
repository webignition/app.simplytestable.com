<?php

namespace App\Services;

use App\Entity\Task\TaskType;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\OutputInterface;

class TaskTypeFixtureLoader implements FixtureLoaderInterface
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
        $this->repository = $entityManager->getRepository(TaskType::class);
    }

    public function load(?OutputInterface $output = null): void
    {
        if ($output) {
            $output->writeln('Migrating task types ...');
        }

        $data = $this->resourceLoader->getData();

        foreach ($data as $name => $taskTypeProperties) {
            $this->loadTaskType($name, $taskTypeProperties, $output);
        }

        if ($output) {
            $output->writeln('');
        }
    }

    private function loadTaskType(string $name, array $taskTypeProperties, ?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('');
        }

        if ($output) {
            $output->writeln("  " . '<comment>' . $name . '</comment>');
        }

        $taskType = $this->repository->findOneBy([
            'name' => $name,
        ]);

        if (is_null($taskType)) {
            if ($output) {
                $output->writeln('  <fg=cyan>creating</>');
            }

            $taskType = new TaskType();
            $taskType->setName($name);
        }

        $description = $taskTypeProperties['description'] ?? '';

        if ($output) {
            $output->writeln("    " . '<comment>description</comment>: ' . $description);
        }

        $taskType->setDescription($description);

        $isSelectable = $taskTypeProperties['selectable'] ?? false;

        if ($output) {
            $output->writeln("    " . '<comment>selectable</comment>: ' . ($isSelectable ? 'true' : 'false'));
        }

        $taskType->setSelectable($isSelectable);

        $this->entityManager->persist($taskType);
        $this->entityManager->flush();
    }
}
