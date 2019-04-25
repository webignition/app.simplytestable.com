<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\RemoveUnusedOutputCommand;
use App\Repository\TaskOutputRepository;
use App\Tests\Factory\MockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\LockInterface;

class RemoveUnusedOutputCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $command = new RemoveUnusedOutputCommand(
            \Mockery::mock(LockFactory::class),
            MockFactory::createApplicationStateService(true),
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(TaskOutputRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            RemoveUnusedOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    public function testRunUnableToAcquireLock()
    {
        $lock = \Mockery::mock(LockInterface::class);
        $lock
            ->shouldReceive('acquire')
            ->andReturnFalse();

        $lockFactory = \Mockery::mock(LockFactory::class);
        $lockFactory
            ->shouldReceive('createLock')
            ->andReturn($lock);

        $command = new RemoveUnusedOutputCommand(
            $lockFactory,
            MockFactory::createApplicationStateService(false),
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(TaskOutputRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(RemoveUnusedOutputCommand::RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK, $returnCode);
    }
}
