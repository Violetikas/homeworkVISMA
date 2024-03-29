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

class AddCompanyCommand extends Command
{
    protected function configure()
    {
        $this->setName('company:add')
            ->setDescription('Add new company')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Company ID (autogenerated if not set)')
            ->addArgument('name', InputArgument::REQUIRED, 'Company Name')
            ->addArgument('registration_code', InputArgument::REQUIRED, 'Company Registration Code')
            ->addArgument('email', InputArgument::REQUIRED, 'Company Email address')
            ->addArgument('phone', InputArgument::REQUIRED, 'Company Phone Number')
            ->addArgument('comment', InputArgument::OPTIONAL, 'Additional Comment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = new DatabaseAdapter();
        $id = $input->getOption('id');
        $name = $input->getArgument('name');
        $email = $input->getArgument('email');
        $phone = $input->getArgument('phone');

        // Validate email.
        if (!(new EmailValidator($db))->isValid($email, $id)) {
            $output->writeln('<error>Invalid or duplicate email: ' . $email . '</error>');

            return 1;
        }

        // Validate phone number.
        if (!(new PhoneValidator())->isValid($phone)) {
            $output->writeln('<error>Invalid phone: ' . $phone . '</error>');

            return 1;
        }

        $db->executeQuery(
            'insert into companies (id, name, registration_code, email, phone, comment)
values (?, ?, ?, ?, ?, ?)',
            [
                $id,
                $name,
                $input->getArgument('registration_code'),
                $email,
                $phone,
                $input->getArgument('comment')
            ]
        );
        $output->writeln('Company <info>' . $name . '</info> added with ID <info>' . $db->lastInsertId() . '</info>.');

        return 0;
    }
}
