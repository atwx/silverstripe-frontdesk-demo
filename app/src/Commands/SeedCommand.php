<?php

namespace App\Commands;

use App\Demo\DemoSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * sake demo:seed — creates the demo data that is missing (run on every deploy).
 */
#[AsCommand(name: 'demo:seed', description: 'Create the demo data that is missing')]
class SeedCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (DemoSeeder::create()->seed() as $line) {
            $output->writeln($line);
        }
        return Command::SUCCESS;
    }
}
