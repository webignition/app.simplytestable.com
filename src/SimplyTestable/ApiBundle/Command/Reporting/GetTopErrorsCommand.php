<?php
namespace SimplyTestable\ApiBundle\Command\Reporting;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

use webignition\HtmlValidationErrorNormaliser\HtmlValidationErrorNormaliser;


class GetTopErrorsCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_INVALID_TASK_TYPE = 2;    
    const DEFAULT_REPORT_LIMIT = 100;
    
    /**
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;
    
    
    /**
     *
     * @var TaskType
     */
    private $taskType;
    
    
    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $taskOutputRepository;
    
    
    private $messages = array();    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:get-top-errors')
            ->setDescription('Generate top errors report by task type')
            ->addOption('task-type', null, InputOption::VALUE_REQUIRED, 'Name of task type for which to generate report')
            ->addOption('task-output-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of task outputs processed')
            ->addOption('task-output-offset', null, InputOption::VALUE_OPTIONAL, 'Offset for task output list')
            ->addOption('report-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number lines in the report')            
            ->addOption('type-filter', null, InputOption::VALUE_OPTIONAL, 'Filter to normalised only (N) or non-normalised only (R)')
            ->addOption('normalise', null, InputOption::VALUE_OPTIONAL, 'Normalise error messages to common form?')
            ->addOption('report-only', null, InputOption::VALUE_OPTIONAL, 'Output report only, no errors or meta data')
            ->addOption('error-only', null, InputOption::VALUE_OPTIONAL, 'Output errors only, no counts')
            ->setHelp(<<<EOF
Generate top errors report by task type.
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        ini_set('xdebug.var_display_max_depth', '10'); 
        
        $this->input = $input;
        
        if ($this->isReportOnly() === false) $output->write('<info>Requested task type: '.$input->getOption('task-type').' ... </info>');        
        $this->taskType = $this->getTaskTypeService()->getByName($input->getOption('task-type'));
        
        if (is_null($this->taskType)) {
            if ($this->isReportOnly() === false) $output->writeln('invalid task type');
            return self::RETURN_CODE_INVALID_TASK_TYPE;
        }
        
        if ($this->isReportOnly() === false) $output->writeln('ok');
        
        $limit = $this->getTaskOutputLimit();
        if ($this->isReportOnly() === false) $output->write('<info>Requested limit: ');
        
        if (is_null($limit)) {
            if ($this->isReportOnly() === false) $output->writeln('NONE');
        } else {
            if ($this->isReportOnly() === false) $output->writeln($limit);
        }        
        
        $offset = $this->getTaskOutputOffset();
        if ($this->isReportOnly() === false) $output->write('<info>Requested offset: ');
        
        if (is_null($offset)) {
            if ($this->isReportOnly() === false) $output->writeln('NONE');
        } else {
            if ($this->isReportOnly() === false) $output->writeln($offset);
        }
        
        if ($this->isReportOnly() === false) $output->writeln('');
        
        if ($this->isReportOnly() === false) $output->write('Finding task output for ['.$this->taskType->getName().'] tasks ... ');
        
        $taskOutputIds = $this->getTaskOutputRepository()->findIdsByTaskType($this->taskType, $limit, $offset);
        $taskOutputCount = count($taskOutputIds);
        
        if ($this->isReportOnly() === false) $output->writeln('['.$taskOutputCount.'] task outputs found');
        
        $processedTaskOutputCount = 0;
        
        $normaliser = new HtmlValidationErrorNormaliser();
        
        $messageCount = 0;
        $outputIds = array();
        
        foreach ($taskOutputIds as $taskOutputId) {            
            $processedTaskOutputCount++;
            if ($this->isReportOnly() === false) $output->writeln('Processing task output ['.$taskOutputId.'] ['.$processedTaskOutputCount.' of '.$taskOutputCount.']');
            
            $taskOutput = $this->getTaskOutputRepository()->find($taskOutputId);
            
            $messages = $this->getMessagesForTaskOutput($taskOutput);
            
            foreach ($messages as $message) {
                $messageCount++;
                
                if ($this->shouldNormalise()) {
                    $normaliser = new HtmlValidationErrorNormaliser();
                    $normalisationResult = $normaliser->normalise($message);                
                    $messageToStore = $normalisationResult->isNormalised() ? trim($normalisationResult->getNormalisedError()->getNormalForm()) : trim($message); 
                } else {
                    $messageToStore = trim($message);
                }              

                if (!array_key_exists($messageToStore, $this->messages)) {
                    $this->messages[$messageToStore] = array(
                        'count' => 0,
                        'normalised' => ($this->shouldNormalise()) ? $normalisationResult->isNormalised() : false
                    );
                    
                    if ($this->shouldNormalise() && $normalisationResult->isNormalised()) {                        
                        $this->messages[$messageToStore]['parameters'] = array();
                    }                    
                }
                
                $this->messages[$messageToStore]['count']++;
                
                if ($this->shouldNormalise() && $normalisationResult->isNormalised()) {                    
                    $currentParameterIndex = array();
                    $parameterCount = count($normalisationResult->getNormalisedError()->getParameters());
                    
                    foreach ($normalisationResult->getNormalisedError()->getParameters() as $position => $value) {                        
                        $currentParameterIndex[] = $value;                          
                        $parameterStore = $this->getParameterStore($currentParameterIndex, $messageToStore, $parameterCount);                        
                        
                        $parameterStore['count']++;
                        $this->setParameterStore($currentParameterIndex, $messageToStore, $parameterStore);                                            
                    }
                }
            }
            
            $this->getEntityManager()->detach($taskOutput);
        }
        
        if ($this->isReportOnly() === false) $output->writeln('');
        if ($this->isReportOnly() === false) $output->writeln('<info>============================================</info>');
        if ($this->isReportOnly() === false) $output->writeln('');
        if ($this->isReportOnly() === false) $output->writeln('Total messages analysed: ' . $messageCount);
        if ($this->isReportOnly() === false) $output->writeln('');
        
        $this->sortMessages();
        
        $this->messages = array_slice($this->messages, 0, $this->getReportLimit());
        
        $reportData = array();
        
        foreach ($this->messages as $message => $messageStatistics) {
            if ($this->includeMessageInReport($messageStatistics) === false) {
                continue;
            }
            
            if ($this->shouldNormalise()) {                
                $reportItem = new \stdClass();
                $reportItem->count = $messageStatistics['count'];
                $reportItem->normal_form = $message;
                
                if (isset($messageStatistics['parameters'])) {
                    $reportItem->parameters = $messageStatistics['parameters'];
                }
                
                $reportData[] = $reportItem;
            } else {
                if ($this->isErrorOnly()) {
                    if (substr_count($message, "\n") === 0) {
                        $output->writeln($message);
                    }                    
                } else {
                    $output->writeln($messageStatistics['count'] . "\t" . $message);
                }
            }            

        }
        
        if ($this->shouldNormalise()) {
            $output->writeln(json_encode($reportData));
        }        
        
        return self::RETURN_CODE_OK;
    }
    
    private function getEmptyParameterStore($parameterIndex, $parameterCount) {
        $parameterStore = array(
            'count' => 0
        );
        
        if ($parameterCount > $parameterIndex) {
            $parameterStore['children'] = array();
        }
        
        return $parameterStore;
    }
    
    private function getParameterStore($parameterIndex, $messageToStore, $parameterCount) {
        switch (count($parameterIndex)) {
            case 1:                
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]] = $this->getEmptyParameterStore($parameterIndex, $parameterCount);
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]];
            
            case 2:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]] = $this->getEmptyParameterStore($parameterIndex, $parameterCount);;
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]];                
            
            case 3:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]] = $this->getEmptyParameterStore($parameterIndex, $parameterCount);
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]];                  
            
            case 4:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]] = $this->getEmptyParameterStore($parameterIndex, $parameterCount);
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]];
            
            case 5:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]] = $this->getEmptyParameterStore($parameterIndex, $parameterCount);
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]];                
        }
    }
    
    
    private function setParameterStore($parameterIndex, $messageToStore, $parameterStore) {        
        switch (count($parameterIndex)) {
            case 1:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]] = $parameterStore;
                break;
            
            case 2:                
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]] = $parameterStore;                
                break;
            
            case 3:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]] = $parameterStore;   
                break;
            
            case 4:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]] = $parameterStore;
                break;
            
            case 5:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]] = $parameterStore;
                break;
        }        
    }

    /**
     * 
     * @return boolean
     */
    private function isReportOnly() {        
        return $this->input->getOption('report-only') == 'true';
    }     
    
    /**
     * 
     * @return boolean
     */
    private function isErrorOnly() {        
        return $this->input->getOption('error-only') == 'true';
    }     
    
    /**
     * 
     * @return boolean
     */
    private function shouldNormalise() {        
        return $this->input->getOption('normalise') == 'true';
    }    
    
    private function includeMessageInReport($messageStatistics) {
        if (is_null($this->getTypeFilter())) {
            return true;
        }
        
        if ($this->getTypeFilter() === 'N' && $messageStatistics['normalised'] === true) {
            return true;
        }
        
        if ($this->getTypeFilter() === 'R' && $messageStatistics['normalised'] === false) {
            return true;
        }
        
        return false;
    }
    
    
    private function sortMessages() {
        $frequencyIndex = array();
        
        foreach ($this->messages as $message => $messageStatistics) {
            $frequencyIndex[$message] = $messageStatistics['count'];
        }
        
        arsort($frequencyIndex);
        
        $messages = array();
        
        foreach ($frequencyIndex as $message => $count) {            
            $messages[$message] = $this->messages[$message];
            
            if (isset($messages[$message]['parameters'])) {            
                $messages[$message]['parameters'] = $this->sortMessageParameters($messages[$message]['parameters']);
            }
        }

        $this->messages = $messages;
    }
    
    private function sortMessageParameters($parameters) {
        $index = array();
        foreach ($parameters as $value => $properties) {
            if (isset($properties['children'])) {                
                $parameters[$value]['children'] = $this->sortMessageParameters($properties['children']);                
            }
            
            $index[$value] = $properties['count'];
        }
        
        arsort($index);
        
        $sortedParameters = array();
        
        foreach ($index as $value => $count) {
            $sortedParameters[$value] = $parameters[$value];
        }       
        
        return $sortedParameters;
        

    }
    
    /**
     * 
     * @return int
     */
    private function getTypeFilter() {
        return $this->input->getOption('type-filter');        
    }    
    
    /**
     * 
     * @return int
     */
    private function getTaskOutputLimit() {
        $limit = (int)$this->input->getOption('task-output-limit');        
        return ($limit > 0) ? $limit : null;
    }       
        
    
    /**
     * 
     * @return int
     */
    private function getTaskOutputOffset() {
        $offset = (int)$this->input->getOption('task-output-offset');        
        return ($offset > 0) ? $offset : null;
    }    
    
    
    /**
     * 
     * @return int
     */
    private function getReportLimit() {
        $limit = (int)$this->input->getOption('report-limit');        
        return ($limit > 0) ? $limit : self::DEFAULT_REPORT_LIMIT;
    }
    
    
    private function getMessagesForTaskOutput(TaskOutput $taskOutput) {
        $messages = array();
        
        if ($taskOutput->getErrorCount() === 0) {
            return $messages;
        }
        
        switch ($this->taskType->getName()) {
            case 'HTML validation':
                $decodedOutput = json_decode($taskOutput->getOutput());
                
                if ($decodedOutput instanceof \stdClass) {
                    foreach ($decodedOutput->messages as $message) {
                        if ($message->type == 'error') {
                            $messages[] = $message->message;   
                        }                                   
                    }                    
                }
                
                break;
        }
        
        return $messages;      
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskTypeService
     */    
    private function getTaskTypeService() {
        return $this->getContainer()->get('simplytestable.services.tasktypeservice');
    }
    
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager() {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        }
        
        return  $this->entityManager;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private function getTaskOutputRepository() {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }
        
        return $this->taskOutputRepository;
    }  
}