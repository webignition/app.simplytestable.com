<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\Lock;

abstract class AbstractLockableCommand extends Command
{
    const LOCK_KEY_PREFIX = 'cmd:';
    const LOCK_TTL = 1800; // 30 minutes in seconds

    private $lockFactory;

    /**
     * @var Lock|null
     */
    protected $lock;

    public function __construct(LockFactory $lockFactory, $name = null)
    {
        parent::__construct($name);

        $this->lockFactory = $lockFactory;
    }

    protected function getLockTtl()
    {
        return self::LOCK_TTL;
    }

    protected function createAndAcquireLock(): bool
    {
        $key = self::LOCK_KEY_PREFIX . $this->getName();

        $this->lock = $this->lockFactory->createLock($key, $this->getLockTtl());

        if (!$this->lock->acquire()) {
            return false;
        }

        return true;
    }

    protected function releaseLock()
    {
        if ($this->lock) {
            $this->lock->release();
        }
    }
}
