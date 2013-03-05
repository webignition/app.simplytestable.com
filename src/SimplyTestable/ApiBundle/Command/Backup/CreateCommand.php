<?php
namespace SimplyTestable\ApiBundle\Command\Backup;

use PDO;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends BackupCommand
{
    const RETURN_CODE_FAILED_TO_CREATE_BACKUP_PATH = 1;
    const RETURN_CODE_FAILED_TO_CREATE_CONFIG_BACKUP_PATH = 2;
    const RETURN_CODE_FAILED_TO_COPY_APPLICATION_CONFIGURATION = 3;
    const RETURN_CODE_FAILED_TO_CREATE_DATA_BACKUP_PATH = 4;
    const RETURN_CODE_FAILED_TO_COMPRESS_BACKUP = 5;
    
    const LEVEL_ESSENTIAL = 'essential';
    const LEVEL_MINIMAL = 'minimal';    
    const DEFAULT_LEVEL = self::LEVEL_ESSENTIAL;
    
    const RECORD_PAGE_SIZE = 10000;
    
    private $levels = array(
        self::LEVEL_ESSENTIAL,
        self::LEVEL_MINIMAL
    );
    
    private $levelHierarchy = array(
        self::LEVEL_MINIMAL => self::LEVEL_ESSENTIAL
    );   
    
    
    private $databaseTableNames = array(
        self::LEVEL_ESSENTIAL => array(
            'fos_user',
        ),
        self::LEVEL_MINIMAL => array(
            'WebSite', 
            'TimePeriod',
            'Job',
            'Worker', 
            'TaskOutput',
            'Task',            
        )
    );
    
    private $databaseFieldExclusions = array(
        'TaskOutput' => array('output')
    );
    
    
    private $databasePrimaryKeyExclusions = array(
        'fos_user' => array(1,2)
    );
    

    private $tableRecordCounts = array();

    
    protected function configure()
    {
        $this
            ->setName('simplytestable:backup:create')
            ->setDescription('Create an application-level backup')
            ->addOption('level', null, InputOption::VALUE_OPTIONAL, 'Choose the backup level: essential, minimal')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Backup storage path')
            ->setHelp(<<<EOF
Create an application-level backup.
    
You can choose a level (or depth) of backup:

essential:
  Backs up the bare essential application configuration and data, covering:
  - app/config/parameters.yml
  - src/SimplyTestable/ApiBundle/Resources/config/parameters.yml
  - all fos_user entities

minimal:
  Backs up a minimal amount of data, covering:
  - all essential assets
  - all Job entities
  - all Task entities
  - all TaskOutput entities, minus the output field contents
  - all Worker entities
  - all WebSite entities
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        
        if (!$this->hasValidLevelOption()) {
            $output->writeln('<error>Incorrect level, must be one of: '.  implode(',', $this->levels).'</error>');
            passthru('php app/console '.$this->getName().' --help');
        }
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->writeln('Using level: <info>'.$this->getLevelOption().'</info>');
        $output->writeln('Using path: <info>'.$this->getPathOption().'</info>');
        $output->writeln('');
        $output->write('Creating backup directory at '.$this->getBasePath().' ... </info>');

        if (!$this->isDryRun()) {
            if (!$this->makeBasePath()) {           
                $output->writeln('<error>Failed to create backup path at '.$this->getBasePath().'</error>');
                return self::RETURN_CODE_FAILED_TO_CREATE_BACKUP_PATH;
            }            
        }
        
        $output->writeln('<info>ok</info>');
        $output->writeln('');
        
        $output->write('Backing up application configuration to '.$this->getConfigPath());
        
        if (!$this->isDryRun()) {        
            if (!$this->makeConfigPath()) {
                $output->writeln(' ... <error>Failed to create config backup path at '.$this->getConfigPath().'</error>');
                return self::RETURN_CODE_FAILED_TO_CREATE_CONFIG_BACKUP_PATH;
            }             
        }       
        
        $output->writeln('');
        
        foreach ($this->applicationConfigurationFiles as $filePath) {
            $output->write($filePath . ' ... ');
            
            if (!$this->isDryRun()) {
                if (!$this->copyFileToPath('./' . $filePath, $this->getConfigPath() . '/' . $filePath)) {
                    $output->writeln('<error>failed</error>');
                    return self::RETURN_CODE_FAILED_TO_COPY_APPLICATION_CONFIGURATION;               
                }                
            }
            

            
            $output->writeln('<info>ok</info>');
        }
        
        $output->writeln('');     
        
        $output->write('Backing up data to '.$this->getDataPath());
        
        if (!$this->isDryRun()) {
            if (!$this->makeDataPath()) {
                $output->writeln(' ... <error>Failed to create data backup path at '.$this->getDataPath().'</error>');
                return self::RETURN_CODE_FAILED_TO_CREATE_DATA_BACKUP_PATH;
            }            
        }
        
        $output->writeln('');
        
        $sqlFileIndex = 0;
        $tableNames = $this->getDatabaseTableNames();
        
        $totalPageCount = $this->getTotalPageCount();
        
        foreach ($tableNames as $tableIndex => $tableName) {
            $output->writeln('');
            $output->writeln('Processing ' . $tableName);
            
            $tableRecordCount = $this->getTableRecordCount($tableName);
            
            $output->writeln($tableRecordCount. ' records to back up');
            $output->writeln('Using ' . $this->getRecordPageCount($tableName). ' pages of ' . self::RECORD_PAGE_SIZE. ' records');
            
            $offsets = $this->getRecordPageOffsets($tableName);
            
            foreach ($offsets as $pageIndex => $offset) {                
                $this->getTableDumpFilePrefix($sqlFileIndex, $totalPageCount);
                
                $tableDumpPath = $this->getDataPath() . '/' . ($this->getTableDumpFilePrefix($sqlFileIndex, $totalPageCount)) . '_' . $tableName . '_page_'.$pageIndex.'.sql';
                $output->write('Storing '.$tableName.' records '.$offset.' to '.(($pageIndex + 1) * self::RECORD_PAGE_SIZE).' in ' . $tableDumpPath.' ... ');
                
                if ($this->isDryRun()) {
                    $output->writeln('<info>ok</info>');                   
                } else {
                    if (file_put_contents($tableDumpPath, $this->getDatabaseTableDump($tableName, $offset)) > 0) {
                        $output->writeln('<info>ok</info>');
                    } else {
                        $output->writeln('<error>failed</error>');
                        return self::RETURN_CODE_FAILED_TO_CREATE_DATA_BACKUP_PATH;                
                    }                     
                }               
                
                $sqlFileIndex++;
            }
        }
        $output->writeln('');
        
        $output->write('Compressing backup files ...');
        
        $command = 'tar -cvzf '.$this->getArchivePath().' ' . $this->getBasePath().' ';
        $compressOutput = array();
        $compressReturnValue = null;
        
        if ($this->isDryRun()) {
            $compressReturnValue = 0;
        } else {
            exec($command, $compressOutput, $compressReturnValue);
        }        
        
        if ($compressReturnValue === 0) {
            $output->writeln('<info>ok</info>');
        } else {
            $output->writeln('<error>failed</error>');
            return self::RETURN_CODE_FAILED_TO_COMPRESS_BACKUP;                
        }
        
        $output->writeln('Tidying up ... removing temporary backup directory ... <info>ok</info> ');
        
        if (!$this->isDryRun()) {
            $this->deleteDirectory($this->getBasePath());            
        }
        
        $output->writeln('');
        
        return 0;
    }
    
    
    private function getTableDumpFilePrefix($index, $totalPageCount) {
        $prefix = $index;
        
        if (strlen($index) < strlen($totalPageCount)) {
            $diff = strlen($totalPageCount) - strlen($index);
            $prefix = str_repeat('0', $diff) . $prefix;
        }
        
        return $prefix;
    }
    
    
    /**
     * 
     * @param string $tableName
     * @return int
     */
    private function getRecordPageCount($tableName) {
        $recordCount = $this->getTableRecordCount($tableName);        
        return (int)ceil($recordCount / self::RECORD_PAGE_SIZE);        
    }
    
    
    /**
     * 
     * @param string $tableName
     * @return array
     */
    private function getRecordPageOffsets($tableName) {
        $constraints = array();
        
        $pageCount = $this->getRecordPageCount($tableName);
        
        for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {            
            $constraints[] = self::RECORD_PAGE_SIZE * $pageIndex;
        }
        
        return $constraints;
   }
    
    
    /**
     * 
     * @return array
     */
    private function getDatabaseTableNames() {
        $levels = $this->getLevels();
        $tableNames = array();
        
        foreach ($levels as $level) {
            $tableNames = array_merge($tableNames, $this->databaseTableNames[$level]);
        }
        
        return $tableNames;
    }
    
    
    /**
     * 
     * @return array
     */
    private function getLevels() {
        $level = $this->getLevelOption();
        $currentLevel = $level;
        
        $levels = array(
            $currentLevel
        );        
        
        while (isset($this->levelHierarchy[$currentLevel])) {            
            $currentLevel = $this->levelHierarchy[$currentLevel];
            $levels[] = $currentLevel;
        }
        
        return array_reverse($levels);
    }
    
    
    private function copyFileToPath($source, $destination) {
        if (!file_exists($source) || !is_file($source)) {
            return false;
        }
        
        $destinationPath = dirname($destination);
        if (!is_dir($destinationPath)) {
            if (!$this->makeDirectory($destinationPath)) {
                return false;
            }
        }
        
        return copy($source, $destination);
        
    }   
    

    
    /**
     * 
     * @return string
     */
    private function getLevelOption() {
        $level = strtolower($this->input->getOption('level'));
        if ($level == '') {
            return self::DEFAULT_LEVEL;
        }
        
        return in_array($level, $this->levels) ? $level : null;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasValidLevelOption() {
        return !is_null($this->getLevelOption());
    }

    
    
    private function getArchivePath() {
        return $this->getBasePath() . '.tar.gz';
    }   

    
    
    /**
     * 
     * @return boolean
     */
    private function makeBasePath() {
        return $this->makeDirectory($this->getBasePath());
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function makeConfigPath() {
        return $this->makeDirectory($this->getConfigPath());      
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function makeDataPath() {
        return $this->makeDirectory($this->getDataPath());      
    }    
    
    
    /**
     * 
     * @param string $path
     * @return boolean
     */
    private function makeDirectory($path) {        
        if (!file_exists($path)) {
            return mkdir($path, 0777, true);
        }
        
        return is_file($path);         
    }
    
    
    private function deleteDirectory($path) {
        if (!is_dir($path)) {
            return false;
        }

        $objects = scandir($path);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {                
                if (filetype($path . "/" . $object) == "dir") {
                    $this->deleteDirectory($path . "/" . $object);
                } else {
                    unlink($path . "/" . $object);
                }                    
            }
        }

        rmdir($path);        
        return true;
    }
    
    
    /**
     * 
     * @return string
     */
    protected function deriveRestorePoint() {
        return date('Y-m-d-H-i-s');      
    }
    
    
    /**
     * 
     * @param string $tableName
     * @return int
     */
    private function getTableRecordCount($tableName) {
        if (!isset($this->tableRecordCounts[$tableName])) {
            $statement = $this->getDatabaseHandle()->prepare('SELECT COUNT(id) AS idCount FROM '.$tableName);        
            $statement->execute();      

            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $this->tableRecordCounts[$tableName] = (int)$row['idCount'];             
        }
        
        return $this->tableRecordCounts[$tableName];      
    }
    
    
    /**
     * 
     * @return int
     */
    private function getTotalTableRecordCount() {
        $tableNames = $this->getDatabaseTableNames();
        $totalTableRecordCount = 0;
        
        foreach ($tableNames as $tableName) {
            $totalTableRecordCount += $this->getTableRecordCount($tableName);
        }
        
        return $totalTableRecordCount;
    }
    
    
    /**
     * 
     * @return int
     */
    private function getTotalPageCount() {
        $tableNames = $this->getDatabaseTableNames();
        $totalTableRecordCount = 0;
        
        foreach ($tableNames as $tableName) {
            $totalTableRecordCount += $this->getRecordPageCount($tableName);
        }
        
        return $totalTableRecordCount;
    }
    
    
    private function getFieldsToSelect($tableName) {
        $exclusions = $this->getTableFieldExclusions($tableName);
        if (count($exclusions) === 0) {
            return '*';
        }
        
        $fields = $this->getTableFields($tableName);        
        $fieldsToSelect = array();
        foreach ($fields as $field) {
            if (!in_array($field, $exclusions)) {
                $fieldsToSelect[] = $field;
            }
        }
        
        return implode(',', $fieldsToSelect);       
    }
    
    private function getDatabaseTableDump($tableName, $offset = 0) {        
        $recordCount = $this->getTableRecordCount($tableName);
        if ($recordCount === 0) {
            return true;
        }
        
        $insertQuery = 'INSERT INTO '.$tableName.' VALUES ';
        
        $insertValues = array();
        
        $recordsResult = $this->getDatabaseHandle()->prepare('SELECT '.$this->getFieldsToSelect($tableName).' FROM ' . $tableName.' ORDER BY id ASC LIMIT '.$offset.', ' . self::RECORD_PAGE_SIZE);     
        $recordsResult->execute();
        
        $fieldExclusions = $this->getTableFieldExclusions($tableName);
        $hasFieldExclusions = count($fieldExclusions) > 0;
        
        $primaryKeyExclusions = $this->getTablePrimaryKeyExclusions($tableName);
        $hasPrimaryKeyExclusions = count($primaryKeyExclusions) > 0;
        
        while ($row = $recordsResult->fetch(PDO::FETCH_ASSOC)) {
            $values = array_values($row);
            
            if ($hasPrimaryKeyExclusions) {
                if (in_array($values[0], $primaryKeyExclusions)) {
                    continue;
                }               
            }
            
            if ($hasFieldExclusions) {
                $exclusionIndices = $this->getExclusionIndices($tableName);
                
                foreach ($exclusionIndices as $fieldName => $index) {
                    if ($index === 0) {
                        $values = array_merge(array(''), $values);
                    } elseif ($index === count($values)) {
                        $values[] = '';
                    } else {
                        $preValues = array_slice($values, $index - 1, $index);
                        $postValues = array_slice($values, $index);
                        
                        $values = array_merge($preValues, array(''), $postValues);
                    }
                }
            }
            
            foreach ($values as $key => $value) {               
                $value = addslashes($value);
                $value = preg_replace("/\n/", "\\n", $value);
                $value = '"'.$value.'"';
                
                $values[$key] = $value;
            }
            
            $insertValues[] = '(' .implode(',', $values) . ')';
        }
        
        $insertQuery .= implode(',', $insertValues);
        
        return $insertQuery;        
    }
    

    private function getTableFieldExclusions($tableName) {
        return (isset($this->databaseFieldExclusions[$tableName])) ? $this->databaseFieldExclusions[$tableName] : array();
    }
    
    
    private function getTablePrimaryKeyExclusions($tableName) {
        return (isset($this->databasePrimaryKeyExclusions[$tableName])) ? $this->databasePrimaryKeyExclusions[$tableName] : array();        
    }
    
    
    private function getExclusionIndices($tableName) {
        $fieldsToExclude = $this->getTableFieldExclusions($tableName);
        
        if (count($fieldsToExclude) === 0) {
            return $fieldsToExclude;
        }   
        
        $exclusions = array();
        $fields = $this->getTableFields($tableName);
        
        foreach ($fields as $fieldIndex => $field) {
            if (in_array($field, $fieldsToExclude)) {
                $exclusions[$field] = $fieldIndex;
            }
        }
        
        return $exclusions;
    }
    
}