<?php

namespace App\Command;

use App\Database\DatabaseAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCompanyCommand extends Command
{
    protected function configure()
    {
        $this->setName('company:delete')
            ->setDescription('Delete existing company')
            ->addArgument('id', InputArgument::REQUIRED, 'Company ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = new DatabaseAdapter();
        $id = $input->getArgument('id');

        // Check that the company actually exists.
        $existing = $db->executeQuery('select id, name from companies where id = ?', [$id])->fetch();
        if (!$existing) {
            $output->writeln('<error>Company with ID ' . $id . ' not found!');

            return 1;
        }

        // Execute delete query.
        $db->executeQuery('delete from companies where id = ?', [$id]);

        $output->writeln('Company <info>#' . $existing['id'] . ' ' . $existing['name'] . '</info> deleted.');
    }
}
