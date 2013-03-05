<?php
namespace SimplyTestable\ApiBundle\Command\Backup;

use PDO;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends BackupCommand
{   
    const SCOPE_CONFIG = 'config';
    const SCOPE_DATA = 'data';
    const SCOPE_ALL = 'all';
    const DEFAULT_SCOPE = self::SCOPE_DATA;
    
    const RETURN_CODE_FAILED_BACKUP_PATH_DOES_NOT_EXIST = 1;   
    const RETURN_CODE_FAILED_NO_RESTORE_POINTS_FOUND = 2;
    const RETURN_CODE_FAILED_RESTORE_POINT_DOES_NOT_EXIST = 3;
    const RETURN_CODE_FAILED_CONFIG_PATH_DOES_NOT_EXIST = 4;
    const RETURN_CODE_FAILED_CONFIG_FILE_DOES_NOT_EXIST = 5;
    const RETURN_CODE_FAILED_DATA_PATH_DOES_NOT_EXIST = 6;
    const RETURN_CODE_FAILED_DATA_PATH_CONTAINS_NO_SQL_FILES = 7;
    const RETURN_CODE_FAILED_CONFIG_FILE_ALREADY_EXISTS = 8;
    const RETURN_CODE_FAILED_TO_COPY_CONFIG_FILE = 9;
    const RETURN_CODE_DATABASE_CONNECTION_FAILED = 10;
    const RETURN_CODE_FAILED_TO_READ_SQL_DATA_FILE = 11;
    const RETURN_CODE_FAILED_SQL_DATA_FILE_CONTAINS_INVALID_STATEMENT = 12;
    const RETURN_CODE_FAILED_TO_RESTORE_FROM_SQL_DATA_FILE = 13;
    
    
    private $scopeOptions = array(
        self::SCOPE_CONFIG,
        self::SCOPE_DATA,
        self::SCOPE_ALL
    );
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:backup:restore')
            ->setDescription('Restore an application-level backup')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')
            ->addOption('overwrite-config', null, InputOption::VALUE_OPTIONAL, 'Overwrite exsiting config files if they exist')                
            ->addOption('skip-sql-integrity-check', null, InputOption::VALUE_OPTIONAL, 'Don\'t check the integrity of SQL data files')                
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'What to restore')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Backup storage path')
            ->addOption('restore-point', null, InputOption::VALUE_OPTIONAL, 'Restore point to use')
            ->setHelp(<<<EOF
Restore an application-level backup.
    
Use the 'restore-point' option to choose a specific backup. If not specified, the latest
backup will be used.

Choose the scope of what is restored using the 'scope' option:

config: restore application configuation only
data: restore application data only (default)
all: restore application configuration and data

EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->write('Using scope: <info>'.$this->getScopeOption().'</info> (');
        switch ($this->getScopeOption()) {
            case self::SCOPE_CONFIG:
                $output->write('application configuration only');
                break;
            
            case self::SCOPE_DATA:
                $output->write('application data only');
                break;
            
            case self::SCOPE_ALL:
                $output->write('application configuration and data');
                break;            
        }
        
        $output->writeln(')');        
        
        $output->write('Using path: <info>'.$this->getPathOption().'</info> ... ');
        
        if (!is_dir($this->getPathOption())) {
            $output->writeln('<error>failed - path does not exist</error>');
            return self::RETURN_CODE_FAILED_BACKUP_PATH_DOES_NOT_EXIST;            
        }
        
        $output->writeln('<info>ok</info>');
        
        $output->writeln('');
        
        $timestampOption = $this->getRestorePointOption();
        
        if (is_null($timestampOption)) {
            $output->write('Checking restore point integrity: <error>none specified and none found in backup path</error> ... ');
            return self::RETURN_CODE_FAILED_NO_RESTORE_POINTS_FOUND;
            
        }
        
        $output->writeln('Checking restore point integrity:');
        $output->write('<info>'.$this->getBasePath().'</info> ... ');
        if (!is_dir($this->getBasePath())) {
            $output->writeln('<error>failed - path does not exist</error>');
            return self::RETURN_CODE_FAILED_RESTORE_POINT_DOES_NOT_EXIST;              
        }  

        $output->writeln('<info>exists</info>'); 
        
        if ($this->isScopeForConfig()) {
            $output->write('<info>'.$this->getConfigPath().'</info> ... ');
            if (!is_dir($this->getConfigPath())) {
                $output->writeln('<error>failed - path does not exist</error>');
                return self::RETURN_CODE_FAILED_CONFIG_PATH_DOES_NOT_EXIST;              
            }         

            $output->writeln('<info>exists</info>');

            foreach ($this->applicationConfigurationFiles as $applicationConfigurationFile) {
                $configFilePath = $this->getConfigPath() . '/' . $applicationConfigurationFile;
                
                $output->write('<info>' .  $configFilePath.'</info> ... ');
                
                if (!file_exists($configFilePath)) {
                    $output->writeln('<error>failed - file does not exist</error>');
                    return self::RETURN_CODE_FAILED_CONFIG_FILE_DOES_NOT_EXIST;                     
                }
                
                $output->writeln('<info>exists</info>');
            }            
        }
        
        if ($this->isScopeForData()) {
            $output->write('<info>'.$this->getDataPath().'</info> ... ');
            if (!is_dir($this->getDataPath())) {
                $output->writeln('<error>failed - path does not exist</error>');
                return self::RETURN_CODE_FAILED_DATA_PATH_DOES_NOT_EXIST;              
            }         

            $output->writeln('<info>exists</info>');
            
            $output->write('<info>'.$this->getDataPath().'</info> ... ');
            if (!$this->dataDirectoryContainsSqlFiles()) {
                $output->writeln('<error>failed - no SQL files found</error>');
                return self::RETURN_CODE_FAILED_DATA_PATH_CONTAINS_NO_SQL_FILES;                   
            }
            
            $output->writeln('<info>contains SQL files</info>');
            
            $output->write('<info>SQL file integrity </info>');
            
            $sqlDataFiles = $this->getSqlDataFiles();
            foreach ($sqlDataFiles as $sqlDataFilePath) {                
                $query = file_get_contents($sqlDataFilePath);
                
                if ($query === false) {
                    $output->writeln('<error>F</error>');
                    $output->writeln('<error>'.$sqlDataFilePath.' could not be read</error>');
                    return self::RETURN_CODE_FAILED_TO_READ_SQL_DATA_FILE;                     
                }
                
                if (!$this->sqlStatementSeemsValid($query)) {
                    $output->writeln('<error>F</error>');
                    $output->writeln('<error>'.$sqlDataFilePath.' contains an invalid SQL statement</error>');
                    return self::RETURN_CODE_FAILED_SQL_DATA_FILE_CONTAINS_INVALID_STATEMENT;                       
                }                                                    
                
                $output->write('.');               
            }            
            
            $output->writeln(' <info>ok</info>');
            $output->writeln('');
            
            $output->write('Checking database connnection ... ');
            
            if ($this->getDatabaseHandle() === false) {
                $output->writeln('<error>failed</error>');
                return self::RETURN_CODE_DATABASE_CONNECTION_FAILED;                    
            } else {
                $output->writeln('<info>ok</info>');
            }
        }        
        
        if ($this->isScopeForConfig()) {
            $output->writeln('');
            $output->writeln('Restoring application configuration ... ');
            
            foreach ($this->applicationConfigurationFiles as $applicationConfigurationFile) {
                $sourceConfigFilePath = $this->getConfigPath() . '/' . $applicationConfigurationFile;
                $destinationConfigFilePath = './' . $applicationConfigurationFile;
                
                $output->write('Copying '.$sourceConfigFilePath.' to '.$destinationConfigFilePath.' ... ');
                
                if (!$this->isDryRun()) {
                    if (file_exists($destinationConfigFilePath) && !$this->shouldOverwriteConfig()) {
                        $output->writeln('<error>Destination config file already exists. Delete it or use --overwrite-config=true</error>');
                        return self::RETURN_CODE_FAILED_CONFIG_FILE_ALREADY_EXISTS;
                    }
                    
                    if (!copy($sourceConfigFilePath, $destinationConfigFilePath)) {
                        $output->writeln('<error>failed</error>');
                        return self::RETURN_CODE_FAILED_TO_COPY_CONFIG_FILE;                          
                    }
                }                
                
                $output->writeln('<info>ok</info>');
            }
        }
        
        if ($this->isScopeForData()) {
            $output->writeln('');
            $output->writeln('Restoring application data ...');
            
            $sqlDataFiles = $this->getSqlDataFiles();
            $currentTableName = null;
            foreach ($sqlDataFiles as $sqlDataFilePath) {
                $query = file_get_contents($sqlDataFilePath);
                
                $tableName = $this->getTableNameFromInsertStatement($query);
                
                if ($currentTableName != $tableName) {
                    if (!is_null($currentTableName)) {
                        $output->writeln(' <info>ok</info>');
                    }
                    
                    $output->write($tableName.' ...');
                    $currentTableName = $tableName;
                }
                
                $output->write('.');
                $statement = $this->getDatabaseHandle()->prepare($query);

                if (!$this->isDryRun()) {
                    try {
                        if ($statement->execute() !== true) {
                            $output->writeln('<error>F</error>');
                            $output->writeln('<error>Failed to restore from ' . $sqlDataFilePath . '</error>');
                            return self::RETURN_CODE_FAILED_TO_RESTORE_FROM_SQL_DATA_FILE;
                        }
                    } catch (\PDOException $pdoException) {
                        $output->writeln('<error>F</error>');
                        $output->writeln('<error>Failed to restore from ' . $sqlDataFilePath . '</error>');
                        //$output->writeln($query);
                        return self::RETURN_CODE_FAILED_TO_RESTORE_FROM_SQL_DATA_FILE;
                    }
                }
            }
            
//            $output->writeln('');
//            $output->writeln('');
            $output->writeln(' <info>ok</info>');
        }
        
        $output->writeln('');
        
        return 0;
    }
    
    /**
     * 
     * @return int
     */
    protected function shouldOverwriteConfig() {
        return $this->input->getOption('overwrite-config') == 'true';
    }  
    
    /**
     * 
     * @return int
     */
    protected function shouldSkipSqlIntegrityCheck() {
        return $this->input->getOption('skip-sql-integrity-check') == 'true';
    }    
    
    /**
     * 
     * @return boolean
     */
    private function dataDirectoryContainsSqlFiles() {
        return count($this->getSqlDataFiles()) > 0;
    }    
    
    
    private function getSqlDataFiles() {
        $files = array();
        
        $iterator = new \DirectoryIterator($this->getDataPath());        
        
        foreach ($iterator as $directoryItem) {            
            if ($directoryItem->getFileInfo()->getExtension() == 'sql') {
                $files[] = $directoryItem->getPathname();
            }            
        }
        
        sort($files);
        
        return $files;
    }
    
    private function getScopeOption() {
        $scope = strtolower($this->input->getOption('scope')); 
        return in_array($scope, $this->scopeOptions) ? $scope : self::DEFAULT_SCOPE;
    }
    
    
    /**
     * 
     * @return string
     */
    private function getRestorePointOption() {
        $restorePoint = strtolower($this->input->getOption('restore-point'));
        return ($restorePoint == '') ? $this->deriveRestorePoint() : $restorePoint;      
    }    
        
    
    /**
     * 
     * @return string
     */
    protected function deriveRestorePoint() {
        $iterator = new \DirectoryIterator($this->getPathOption());
        
        $restorePoints = array();
        
        foreach ($iterator as $currentDirectoryItem) {
            if ($currentDirectoryItem->isDir() && !$currentDirectoryItem->isDot() && $this->isRestorePoint($currentDirectoryItem->getFilename())) {
                $restorePoints[] = $currentDirectoryItem->getFilename();
            }
        }
        
        if (count($restorePoints) === 0) {
            return null;
        }
        
        rsort($restorePoints);
        
        return $restorePoints[0];
    }
    
    
    /**
     * 
     * @param string $string
     * @return boolean
     */
    private function isRestorePoint($string) {
        return preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/', $string) > 0;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function isScopeForConfig() {
        return $this->getScopeOption() == self::SCOPE_CONFIG || $this->getScopeOption() == self::SCOPE_ALL;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function isScopeForData() {
        return $this->getScopeOption() == self::SCOPE_DATA || $this->getScopeOption() == self::SCOPE_ALL;
    }   
    
    
    /**
     * 
     * @param string $statement
     * @return string
     */
    private function getTableNameFromInsertStatement($statement) {        
        $matches = array();
        preg_match('/INSERT INTO [a-zA-Z0-9_]+ /', $statement, $matches);
        
        if (count($matches) === 0) {
            return null;
        }
        
        $words = explode(' ', $matches[0]);        
        return $words[2];
    }

    
    /**
     * 
     * @param string $statement
     * @return boolean
     */
    private function sqlStatementSeemsValid($statement) {
        if ($this->shouldSkipSqlIntegrityCheck()) {
            return true;
        }
        
        $tableName = $this->getTableNameFromInsertStatement($statement);
        if (is_null($tableName)) {
            return false;
        }
        
        $preamble = 'INSERT INTO ' . $tableName . ' VALUES ';
        if (substr($statement, 0, strlen($preamble)) != $preamble) {
            return false;
        }
        
        $statementMinusPreamble = trim(substr($statement, strlen($preamble)));
        
        $insertBlocks = explode('),'."\n".'(', $statementMinusPreamble);
        foreach ($insertBlocks as $index => $insertBlock) {
            $insertBlock = trim($insertBlock);
            
            if ($index < count($insertBlocks) - 1) {
                $insertBlock = $insertBlock . ')';
            }
            
            if ($index > 0) {
                $insertBlock = '('.$insertBlock;
            }
       
            $insertBlocks[$index] = $insertBlock;
        }        
        
        $tableFields = $this->getTableFields($tableName);
        $tableFieldCount = count($tableFields);
        
        foreach ($insertBlocks as $insertBlock) {            
            if ($insertBlock[0] != '(') {
                return false;
            }
            
            $insertBlockLength = strlen($insertBlock);            
            if ($insertBlock[$insertBlockLength - 1] != ')') {
                return false;
            }
            
            $commaSeparatedValues = substr($insertBlock, 1, $insertBlockLength - 2);
            
            $csvContent = str_getcsv($commaSeparatedValues);
            
            if (count($csvContent) != $tableFieldCount) {
                return false;
            }
        }
        
        return true;
    }
    
}