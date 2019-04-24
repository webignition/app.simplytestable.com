<?php

namespace App\Services;

use Symfony\Component\Console\Output\OutputInterface;

interface FixtureLoaderInterface
{
    public function load(?OutputInterface $output = null): void;
}
