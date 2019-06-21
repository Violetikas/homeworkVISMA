<?php

namespace App\Command;

use App\Database\DatabaseAdapter;
use App\Validator\EmailValidator;
use App\Validator\PhoneValidator;
use PDOException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCompaniesCommand extends Command
{
    protected function configure()
    {
        $this->setName('company:import')
            ->setDescription('Import companies from CSV file')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to CSV file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = new DatabaseAdapter();
        $path = $input->getArgument('path');

        $fh = @fopen($path, 'rb');
        if (!$fh) {
            $output->writeln('<error>' . error_get_last()['message'] . '</error>');

            return 1;
        }

        // Read header row.
        $header = fgetcsv($fh);
        $expectedHeader = ['id', 'name', 'registration_code', 'email', 'phone', 'comment'];
        // Check if file header matches.
        if ($header !== $expectedHeader) {
            $output->writeln('<error>Unrecognized column headers!</error>');

            return 1;
        }

        // Execute all inserts in a transaction.
        if (!$db->beginTransaction()) {
            $output->writeln('<error>Failed to begin DB transaction</error>');

            return 1;
        }

        // Import remaining lines.
        $hasErrors = $this->importLines($output, $fh, $db);

        // Close input file
        fclose($fh);

        if (!$hasErrors) {
            // No errors, commit all changes to DB.
            $db->commit();
        } else {
            // Has errors, revert all changes.
            $db->rollBack();
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param resource        $fh
     * @param DatabaseAdapter $db
     *
     * @return bool
     */
    private function importLines(OutputInterface $output, $fh, DatabaseAdapter $db): bool
    {
        // Keep track of all errors.
        $hasErrors = false;

        // Keep current line number in memory for error messages.
        $lineNo = 1;

        // Read lines.
        while ($row = fgetcsv($fh)) {
            // Increment line number.
            ++$lineNo;
            // Read fields directly into variables for easier handling.
            [$id, $name, $registration_code, $email, $phone, $comment] = $row;

            // Normalize ID type.
            if (!$id) {
                $id = null;
            }

            // Validate email.
            if (!(new EmailValidator($db))->isValid($email, $id)) {
                $output->writeln('<error>Line ' . $lineNo . ': Invalid or duplicate email: ' . $email . '</error>');
                $hasErrors = true;

                continue;
            }

            // Validate phone number.
            if (!(new PhoneValidator())->isValid($phone)) {
                $output->writeln('<error>Line ' . $lineNo . ': Invalid phone: ' . $phone . '</error>');
                $hasErrors = true;

                continue;
            }

            try {
                $db->executeQuery(
                    'insert into companies (id, name, registration_code, email, phone, comment)
values (?, ?, ?, ?, ?, ?)',
                    [
                        $id,
                        $name,
                        $registration_code,
                        $email,
                        $phone,
                        $comment
                    ]
                );
            } catch (PDOException $e) {
                $output->writeln('<error>Line ' . $lineNo . ': Insert failed: ' . $e->getMessage() . '</error>');
                $hasErrors = true;

                continue;
            }
            $output->writeln(
                'Company <info>' . $name . '</info> added with ID <info>' . $db->lastInsertId() . '</info>.'
            );
        }

        return $hasErrors;
    }
}
