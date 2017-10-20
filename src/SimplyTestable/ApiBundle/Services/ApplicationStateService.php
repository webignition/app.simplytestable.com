<?php

namespace SimplyTestable\ApiBundle\Services;

use Symfony\Component\HttpKernel\KernelInterface;

class ApplicationStateService
{
    const RESOURCE_PATH = '@SimplyTestableApiBundle/Resources/config/state/%s';

    const STATE_ACTIVE = 'active';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';
    const STATE_MAINTENANCE_BACKUP_READ_ONLY = 'maintenance-backup-read-only';
    const DEFAULT_STATE = self::STATE_ACTIVE;

    /**
     * @var string[]
     */
    private $allowedStates = [
        self::STATE_ACTIVE,
        self::STATE_MAINTENANCE_READ_ONLY,
        self::STATE_MAINTENANCE_BACKUP_READ_ONLY
    ];

    /**
     * @var string
     */
    private $stateResourcePath;

    /**
     * @var string
     */
    private $state;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param ResourceLocator $resourceLocator
     * @param KernelInterface $kernel
     */
    public function __construct(ResourceLocator $resourceLocator, KernelInterface $kernel)
    {
        $this->resourceLocator = $resourceLocator;
        $this->kernel = $kernel;

        $this->stateResourcePath = $resourceLocator->locate(sprintf(
            self::RESOURCE_PATH,
            $kernel->getEnvironment()
        ));
    }

    /**
     * @return bool
     */
    public function isInActiveState()
    {
        return $this->getState() == self::STATE_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isInMaintenanceReadOnlyState()
    {
        return $this->getState() == self::STATE_MAINTENANCE_READ_ONLY;
    }

    /**
     * @return bool
     */
    public function isInMaintenanceBackupReadOnlyState()
    {
        return $this->getState() == self::STATE_MAINTENANCE_BACKUP_READ_ONLY;
    }

    /**
     * @return bool
     */
    public function isInReadOnlyMode()
    {
        return $this->isInMaintenanceReadOnlyState() || $this->isInMaintenanceBackupReadOnlyState();
    }

    /**
     * @param string $state
     *
     * @return bool
     */
    public function setState($state)
    {
        if (!$this->isAllowedState($state)) {
            return false;
        }

        if (file_put_contents($this->stateResourcePath, $state) !== false) {
            $this->state = $state;

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getState()
    {
        if (is_null($this->state)) {
            if (file_exists($this->stateResourcePath)) {
                $this->state = trim(file_get_contents($this->stateResourcePath));
            }

            if (!$this->isAllowedState($this->state)) {
                $this->state = self::DEFAULT_STATE;
            }
        }

        return $this->state;
    }

    /**
     * @param string $state
     * @return boolean
     */
    private function isAllowedState($state)
    {
        return in_array($state, $this->allowedStates);
    }
}
