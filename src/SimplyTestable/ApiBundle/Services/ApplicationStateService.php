<?php
namespace SimplyTestable\ApiBundle\Services;


class ApplicationStateService {
    
    const STATE_ACTIVE = 'active';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';
    const STATE_MAINTENANCE_BACKUP_READ_ONLY = 'maintenance-backup-read-only';
    const DEFAULT_STATE = self::STATE_ACTIVE;
    
    private $allowedStates = array(
        self::STATE_ACTIVE,
        self::STATE_MAINTENANCE_READ_ONLY,
        self::STATE_MAINTENANCE_BACKUP_READ_ONLY
    );
    
    /**
     *
     * @var string
     */
    private $stateResourcePath;
    
    
    /**
     *
     * @var string
     */
    private $state;
    
    
    /**
     * 
     * @return boolean
     */
    public function isInActiveState() {
        return $this->getState() == self::STATE_ACTIVE;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function isInMaintenanceReadOnlyState() {
        return $this->getState() == self::STATE_MAINTENANCE_READ_ONLY;
    }
    

    /**
     * 
     * @return boolean
     */
    public function isInMaintenanceBackupReadOnlyState() {
        return $this->getState() == self::STATE_MAINTENANCE_BACKUP_READ_ONLY;
    }    
    
    
    /**
     * 
     * @param string $state
     * @return boolean
     */
    public function setState($state) {
        if (!$this->isAllowedState($state)) {
            return false;
        }
        
        if (file_put_contents($this->stateResourcePath, $state) > 0) {
            $this->state = $state;
            return true;
        }
        
        return false;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getState() {       
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
     * 
     * @param string $stateResourcePath
     */
    public function setStateResourcePath($stateResourcePath) {
        $this->stateResourcePath = $stateResourcePath;
    }
    
    
    /**
     * 
     * @param string $state
     * @return boolean
     */
    private function isAllowedState($state) {
        return in_array($state, $this->allowedStates);
    }
    
    
}