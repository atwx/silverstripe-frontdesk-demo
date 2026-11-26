<?php

namespace App\Commands;

use App\Demo\DemoSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * sake demo:reset — removes all demo data and what visitors changed, then
 * seeds again (nightly cron job on the server).
 */
#[AsCommand(name: 'demo:reset', description: 'Remove all demo data and visitor changes, then seed again')]
class ResetCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (DemoSeeder::create()->reset() as $line) {
            $output->writeln($line);
        }
        return Command::SUCCESS;
    }
}
