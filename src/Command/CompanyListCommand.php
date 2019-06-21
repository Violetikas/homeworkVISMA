<?php

namespace App\Command;

use App\Database\DatabaseAdapter;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompanyListCommand extends Command
{
    protected function configure()
    {
        $this->setName('company:list')
            ->setDescription('List existing companies');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = new DatabaseAdapter();

        // Create table with headers.
        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Name', 'Registration Code', 'Email', 'Phone', 'Comment']);

        // Fill table rows from database.
        $results = $db->executeQuery('select id, name, registration_code, email, phone, comment from companies')
            ->fetchAll(PDO::FETCH_ASSOC);
        $table->setRows($results);

        // Render table to output.
        $table->render();
    }
}
