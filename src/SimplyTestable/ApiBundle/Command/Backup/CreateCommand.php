<?php
namespace SimplyTestable\ApiBundle\Command\Backup;

use PDO;
use PDOException;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends BaseCommand
{
    const RETURN_CODE_FAILED_TO_CREATE_BACKUP_PATH = 1;
    const RETURN_CODE_FAILED_TO_CREATE_CONFIG_BACKUP_PATH = 2;
    const RETURN_CODE_FAILED_TO_COPY_APPLICATION_CONFIGURATION = 3;
    const RETURN_CODE_FAILED_TO_CREATE_DATA_BACKUP_PATH = 4;
    
    const LEVEL_ESSENTIAL = 'essential';
    const LEVEL_MINIMAL = 'minimal';    
    const DEFAULT_LEVEL = self::LEVEL_ESSENTIAL;
    const DEFAULT_PATH = '~/backup';
    const CONFIG_RELATIVE_PATH = '/config';
    const DATA_RELATIVE_PATH = '/data';
    
    const RECORD_PAGE_SIZE = 100;
    
    private $levels = array(
        self::LEVEL_ESSENTIAL,
        self::LEVEL_MINIMAL
    );
    
    private $levelHierarchy = array(
        self::LEVEL_MINIMAL => self::LEVEL_ESSENTIAL
    );
    
    private $path = null;
    
    private $applicationConfigurationFiles = array(
        'app/config/parameters.yml',
        'src/SimplyTestable/ApiBundle/Resources/config/parameters.yml'
    );    
    
    private $databaseTableNames = array(
        self::LEVEL_ESSENTIAL => array(
            'fos_user',
        ),
        self::LEVEL_MINIMAL => array(
            'WebSite',            
            'Job',
            'Worker', 
            'TaskOutput',
            'Task',            
        )
    );
    
    private $databaseFieldExclusions = array(
        'TaskOutput' => 'output'
    );
    
    /**
     *
     * @var InputInterface
     */
    private $input;
    
    
    /**
     *
     * @var \PDO
     */
    private $databaseHandle = null;
    
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
//        $this->dumpDatabaseTable('fos_user');
//        exit();
        
//        var_dump($this->getContainer()->getParameter('database_name'));
//        exit();
        
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
        
        if (!$this->makeBasePath()) {
            $output->writeln('<error>Failed to create backup path at '.$this->getBasePath().'</error>');
            return self::RETURN_CODE_FAILED_TO_CREATE_BACKUP_PATH;
        }
        
        $output->writeln('<info>ok</info>');
        $output->writeln('');
        
        $output->write('Backing up application configuration to '.$this->getConfigPath());
        
        if (!$this->makeConfigPath()) {
            $output->writeln(' ... <error>Failed to create config backup path at '.$this->getConfigPath().'</error>');
            return self::RETURN_CODE_FAILED_TO_CREATE_CONFIG_BACKUP_PATH;
        }        
        
        $output->writeln('');
        
        foreach ($this->applicationConfigurationFiles as $filePath) {
            $output->write($filePath . ' ... ');
            
            if (!$this->copyFileToPath('./' . $filePath, $this->getConfigPath() . '/' . $filePath)) {
                $output->writeln('<error>failed</error>');
                return self::RETURN_CODE_FAILED_TO_COPY_APPLICATION_CONFIGURATION;               
            }
            
            $output->writeln('<info>ok</info>');
        }
        $output->writeln('');
        
        $output->write('Backing up data to '.$this->getDataPath());
        
        if (!$this->makeDataPath()) {
            $output->writeln(' ... <error>Failed to create data backup path at '.$this->getDataPath().'</error>');
            return self::RETURN_CODE_FAILED_TO_CREATE_DATA_BACKUP_PATH;
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
                
                if (file_put_contents($tableDumpPath, $this->getDatabaseTableDump($tableName, $offset)) > 0) {
                    $output->writeln('<info>ok</info>');
                } else {
                    $output->writeln('<error>failed</error>');
                    return self::RETURN_CODE_FAILED_TO_CREATE_DATA_BACKUP_PATH;                
                }
                
                $sqlFileIndex++;
            }
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
     * @return int
     */
    private function isDryRun() {
        return $this->input->getOption('dry-run') == 'true';
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
    
    
    /**
     * 
     * @return string
     */
    private function getPathOption() {
        $path = strtolower($this->input->getOption('path'));
        return ($path == '') ? self::DEFAULT_PATH : $path;      
    }
    
    
    /**
     * 
     * @return string
     */
    private function getBasePath() {
        if (is_null($this->path)) {
            $this->path = $this->getPathOption() . '/' . date('Y-m-d-H-i-s');
        }
        
        return $this->path;
    }
    
    
    /**
     * 
     * @return string
     */
    private function getConfigPath() {
        return $this->getBasePath() . self::CONFIG_RELATIVE_PATH;
    }
    
    
    /**
     * 
     * @return string
     */
    private function getDataPath() {
        return $this->getBasePath() . self::DATA_RELATIVE_PATH;
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
    
    
    private function getDsn() {
        return 'mysql:dbname='.$this->getContainer()->getParameter('database_name').';host=127.0.0.1';
    }
    
    
    private function getDatabaseHandle() {
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
    
    
    /**
     * 
     * @param string $tableName
     * @return int
     */
    private function getTableRecordCount($tableName) {
        $statement = $this->getDatabaseHandle()->prepare('SELECT COUNT(id) AS idCount FROM '.$tableName);        
        $statement->execute();      
        
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return (int)$row['idCount'];       
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
    
    
    private function getDatabaseTableDump($tableName, $offset = 0) {        
        $recordCount = $this->getTableRecordCount($tableName);
        if ($recordCount === 0) {
            return true;
        }
        
        $insertQuery = 'INSERT INTO '.$tableName.' VALUES ';
        
        $insertValues = array();
        
        $recordsResult = $this->getDatabaseHandle()->prepare('SELECT * FROM ' . $tableName.' LIMIT '.$offset.', ' . self::RECORD_PAGE_SIZE);
        $recordsResult->execute();
        
        while ($row = $recordsResult->fetch(PDO::FETCH_ASSOC)) {
            $values = array_values($row);
            
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
    
}