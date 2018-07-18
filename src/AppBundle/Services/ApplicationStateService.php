<?php

namespace AppBundle\Services;

use AppBundle\Model\ApplicationStateInterface;

class ApplicationStateService
{
    const RESOURCE_PATH = '/config/state/%s';

    /**
     * @var string
     */
    private $stateResourcePath;

    /**
     * @var string
     */
    private $state;

    public function __construct(string $kernelRootDirectory, string $environment)
    {
        $this->stateResourcePath = $kernelRootDirectory . sprintf(self::RESOURCE_PATH, $environment);
    }

    /**
     * @return bool
     */
    public function isInActiveState()
    {
        return ApplicationStateInterface::STATE_ACTIVE === $this->getState();
    }

    /**
     * @return bool
     */
    public function isInMaintenanceReadOnlyState()
    {
        return ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY === $this->getState();
    }

    /**
     * @return bool
     */
    public function isInMaintenanceBackupReadOnlyState()
    {
        return ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY === $this->getState();
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
                $this->state = ApplicationStateInterface::DEFAULT_STATE;
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
        $allowedStates = [
            ApplicationStateInterface::STATE_ACTIVE,
            ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY,
            ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY
        ];

        return in_array($state, $allowedStates);
    }
}
