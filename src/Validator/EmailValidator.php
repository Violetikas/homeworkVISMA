<?php

namespace App\Validator;

use App\Database\DatabaseAdapter;

class EmailValidator
{
    private $db;

    public function __construct(DatabaseAdapter $db)
    {
        $this->db = $db;
    }

    public function isValid(string $value, ?int $id): bool
    {
        return $this->isEmailValid($value) && $this->isEmailUnique($value, $id);
    }

    /**
     * Validate email syntax.
     *
     * @param string $value
     *
     * @return mixed
     */
    private function isEmailValid(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate email uniqueness.
     *
     * @param string   $value
     * @param int|null $id
     *
     * @return bool
     */
    private function isEmailUnique(string $value, ?int $id): bool
    {
        if (null !== $id) {
            // Check if email exists and is with different company.
            return $this->db->executeQuery(
                    'select id from companies where email = ? and id != ? limit 1',
                    [$value, $id]
                )->fetchColumn() === false;
        }

        // Check if email exists at all.
        return $this->db->executeQuery(
                'select id from companies where email = ? limit 1',
                [$value]
            )->fetchColumn() === false;
    }
}
