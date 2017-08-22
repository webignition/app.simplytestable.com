<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\WebClientBundle\Entity\Task\Output as TaskOutput;

class MigrateAddHashToHashlessOutputCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

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

    protected function configure()
    {
        $this
            ->setName('simplytestable:add-hash-to-hashless-output')
            ->setDescription('Set the hash property on TaskOutput objects that have no hash set')
            ->addOption('limit')
            ->addOption('dry-run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $output->writeln('Finding hashless output ...');
        $hashlessOutputIds = $this->getTaskOutputRepository()->findHashlessOutputIds($this->getLimit($input));

        if (count($hashlessOutputIds) === 0) {
            $output->writeln('No task outputs require a hash to be set. Done.');
            return true;
        }

        $output->writeln(count($hashlessOutputIds).' outputs require a hash to be set.');

        $processedTaskOutputCount = 0;

        foreach ($hashlessOutputIds as $hashlessOutputId) {
            $taskOutput = $this->getTaskOutputRepository()->find($hashlessOutputId);

            /* @var $output TaskOutput */
            $processedTaskOutputCount++;
            $output->writeln('Setting hash for ['.$taskOutput->getId().'] ('.(count($hashlessOutputIds) - $processedTaskOutputCount).' remaining)');
            $taskOutput->generateHash();

            if (!$this->isDryRun($input)) {
                $this->getManager()->persist($taskOutput);
                $this->getManager()->flush();
            }

            $this->getManager()->detach($taskOutput);
        }

        return true;
    }


    /**
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return int
     */
    private function isDryRun(InputInterface $input) {
        return $input->getOption('dry-run');
    }


    /**
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return int
     */
    private function getLimit(InputInterface $input) {
        if ($input->getOption('limit') === false) {
            return 0;
        }

        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);

        return ($limit <= 0) ? 0 : $limit;
    }


    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function getManager() {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        }

        return  $this->entityManager;
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private function getTaskOutputRepository() {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }

        return $this->taskOutputRepository;
    }
}