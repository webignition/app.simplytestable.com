<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobPrepareCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:prepare')
            ->setDescription('Prepare a set of tasks for a given job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to prepare')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = (int)$input->getArgument('id');
        $job = $this->getContainer()->get('simplytestable.services.jobservice')->getById($id);
        
        //var_dump($this->getContainer()->get('simplytestable.services.jobservice')->has($id));
        
//        $name = $input->getArgument('name');
//        if ($name) {
//            $text = 'Hello '.$name;
//        } else {
//            $text = 'Hello';
//        }
//
//        if ($input->getOption('yell')) {
//            $text = strtoupper($text);
//        }
//
//        $output->writeln($text);
    }
}