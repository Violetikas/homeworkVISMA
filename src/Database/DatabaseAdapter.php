<?php

namespace App\Database;

use PDO;
use PDOStatement;

class DatabaseAdapter
{
    /** * @var PDO */
    private $connection;

    public function __construct()
    {
        // Create connection instance.
        $dsn = getenv('DB_URL') ?: 'sqlite://' . dirname(__FILE__, 3) . '/var/data.sqlite3';
        $this->connection = new PDO($dsn);
        // Throw exception on any error.
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Create tables if not exist.
        $this->connection->exec($this->getSchemaSQL());
    }

    /**
     * Return database schema SQL for initial database creation.
     *
     * @return string
     */
    private function getSchemaSQL(): string
    {
        return <<<EOF
create table if not exists companies
(
    id                integer not null
        constraint companies_pk
            primary key autoincrement,
    name              text    not null,
    registration_code text    not null,
    email             text    not null,
    phone             text    not null,
    comment           text
) 
EOF;
    }

    public function executeQuery(string $query, array $parameters = []): PDOStatement
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($parameters);

        return $stmt;
    }

    public function lastInsertId(): ?int
    {
        return $this->connection->lastInsertId();
    }
}
