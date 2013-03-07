<?php
namespace SimplyTestable\ApiBundle\Services;


class ApplicationStateService {
    
    const DEFAULT_STATE = 'active';
    
    private $allowedStates = array(
        'active',
        'maintenance-read-only'
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
     * @param string $state
     * @return boolean
     */
    public function setState($state) {
        if (!$this->isAllowedState($state)) {
            return false;
        }
        
        return file_put_contents($this->stateResourcePath, $state) > 0;
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