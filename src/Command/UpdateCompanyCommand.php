<?php

namespace App\Command;

use App\Database\DatabaseAdapter;
use App\Validator\EmailValidator;
use App\Validator\PhoneValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCompanyCommand extends Command
{
    protected function configure()
    {
        $this->setName('company:update')
            ->setDescription('Update existing company')
            ->addArgument('id', InputArgument::REQUIRED, 'Company ID')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Company Name')
            ->addOption('registration_code', null, InputOption::VALUE_REQUIRED, 'Company Registration Code')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Company Email address')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'Company Phone Number')
            ->addOption('comment', null, InputOption::VALUE_REQUIRED, 'Additional Comment');
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

        // Extract fields to be updated.
        $options = ['name', 'registration_code', 'email', 'phone', 'comment'];
        $updates = [];
        foreach ($options as $option) {
            $value = $input->getOption($option);
            if ($value) {
                $updates[$option] = $value;
            }
        }

        // Validate email.
        if (array_key_exists('email', $updates) && !(new EmailValidator($db))->isValid($updates['email'], $id)) {
            $output->writeln('<error>Invalid or duplicate email: '.$updates['email'].'</error>');

            return 1;
        }

        // Validate phone.
        if (array_key_exists('phone', $updates) && !(new PhoneValidator())->isValid($updates['phone'])) {
            $output->writeln('<error>Invalid phone: '.$updates['phone'].'</error>');

            return 1;
        }

        // Only execute queries if there is anything to update.
        if ($updates) {
            $setSQL = implode(
                ',',
                array_map(
                    static function ($name) {
                        return $name . ' = ' . '?';
                    },
                    array_keys($updates)
                )
            );
            $query = 'update companies set ' . $setSQL . ' where id = ?';
            $parameters = array_merge(array_values($updates), [$id]);
            $db->executeQuery($query, $parameters);
        }

        $output->writeln('Company <info>#' . $existing['id'] . ' ' . $existing['name'] . '</info> updated.');

        return 0;
    }
}
