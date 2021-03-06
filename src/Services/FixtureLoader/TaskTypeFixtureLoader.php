<?php

namespace App\Services\FixtureLoader;

use App\Entity\Task\TaskType;
use App\Services\DataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskTypeFixtureLoader extends AbstractFixtureLoader implements FixtureLoaderInterface
{
    public function __construct(EntityManagerInterface $entityManager, DataProviderInterface $dataProvider)
    {
        parent::__construct($entityManager, $dataProvider);
    }

    protected function getEntityClass(): string
    {
        return TaskType::class;
    }

    public function load(?OutputInterface $output = null): void
    {
        if ($output) {
            $output->writeln('Migrating task types ...');
        }

        foreach ($this->data as $name => $taskTypeProperties) {
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
