<?php
namespace SimplyTestable\ApiBundle\Command\Backup;

use PDO;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BackupCommand extends BaseCommand
{
    const DEFAULT_PATH = '~/backup';    
    const CONFIG_RELATIVE_PATH = '/config';
    const DATA_RELATIVE_PATH = '/data';
    
    protected $applicationConfigurationFiles = array(
        'app/config/parameters.yml',
        'src/SimplyTestable/ApiBundle/Resources/config/parameters.yml'
    );    
    
    /**
     *
     * @var InputInterface
     */
    protected $input; 
    
    
    private $path = null;
    private $restorePoint = null;    
    
    /**
     *
     * @var \PDO
     */
    private $databaseHandle = null;
    
    
    private $tableFields = array();
    
    
    /**
     * 
     * @param string $tableName
     * @return array
     */
    protected function getTableFields($tableName) {
        if (!isset($this->tableFields[$tableName])) {
            $statement = $this->getDatabaseHandle()->prepare('DESCRIBE '.$tableName);        
            $statement->execute(); 

            $fields = array();

            while (($row = $statement->fetch(PDO::FETCH_ASSOC))) {
                $fields[] = $row['Field'];
            }     
            
            $this->tableFields[$tableName] = $fields;
        }        
        
        return $this->tableFields[$tableName];  
    }    
    
    
    /**
     * 
     * @return string
     */
    protected function getConfigPath() {
        return $this->getBasePath() . self::CONFIG_RELATIVE_PATH;
    }
    
    
    /**
     * 
     * @return string
     */
    protected function getDataPath() {
        return $this->getBasePath() . self::DATA_RELATIVE_PATH;
    }    
    

    /**
     * 
     * @return int
     */
    protected function isDryRun() {
        return $this->input->getOption('dry-run') == 'true';
    }
    
    
    /**
     * 
     * @return string
     */
    protected function getBasePath() {
        if (is_null($this->path)) {
            $this->path = $this->getPathOption() . '/' . $this->getRestorePoint();
        }
        
        return $this->path;
    } 
    
    
    /**
     * 
     * @return string
     */
    protected function getRestorePoint() {
        if (is_null($this->restorePoint)) {
            $this->restorePoint = $this->deriveRestorePoint();
        }
        
        return $this->restorePoint;        
    } 
    
    abstract protected function deriveRestorePoint();
    
    
    /**
     * 
     * @return string
     */
    protected function getPathOption() {        
        $path = strtolower($this->input->getOption('path'));
        if ($path == '') {
            $path = self::DEFAULT_PATH;
        }
        
        if (substr_count($path, '~')) {
            if (isset($_ENV['HOME'])) {
                $path = str_replace('~', $_ENV['HOME'], $path);
            }
            
            if (isset($_SERVER['HOME'])) {
                $path = str_replace('~', $_SERVER['HOME'], $path);
            }
        }
        
        return realpath($path);
    }    
    
    
    private function getDsn() {
        return 'mysql:dbname='.$this->getContainer()->getParameter('database_name').';host=127.0.0.1';
    }
    
    
    /**
     * 
     * @return \PDO
     */
    protected function getDatabaseHandle() {
        if (is_null($this->databaseHandle)) {            
            try {
                $this->databaseHandle = new PDO(
                        $this->getDsn(),
                        $this->getContainer()->getParameter('database_user'),
                        $this->getContainer()->getParameter('database_password')
                );
                
                $this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);                
            } catch (\PDOException $e) {
                echo 'Connection failed: ' . $e->getMessage();
                return false;
            }
        }

        return $this->databaseHandle;
    }    
}