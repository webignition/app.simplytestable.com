<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\ExpirePublicUserJobsCommand;
use App\Repository\JobRepository;
use App\Services\JobService;
use App\Services\StateService;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockInterface;

class ExpirePublicUserJobsCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunUnableToAcquireLock()
    {
        $lock = \Mockery::mock(LockInterface::class);
        $lock
            ->shouldReceive('acquire')
            ->andReturnFalse();

        $lockFactory = \Mockery::mock(Factory::class);
        $lockFactory
            ->shouldReceive('createLock')
            ->with(ExpirePublicUserJobsCommand::LOCK_KEY, ExpirePublicUserJobsCommand::LOCK_TTL)
            ->andReturn($lock);

        $command = new ExpirePublicUserJobsCommand(
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(JobRepository::class),
            \Mockery::mock(UserService::class),
            \Mockery::mock(StateService::class),
            \Mockery::mock(JobService::class),
            $lockFactory
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(ExpirePublicUserJobsCommand::RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK, $returnCode);
    }
}
