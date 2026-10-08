<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

abstract class BaseRepository
{
    protected string $table = '';
    protected string $idColumn = '';

    public function __construct(protected PDO $pdo)
    {
    }

    abstract protected function hydrate(array $row): object;

    public function findAll(): array
    {
        $rows = $this->pdo->query("SELECT * FROM `{$this->table}`")->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $rows);
    }

    public function findById(int $id): ?object
    {
        $sth = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE `{$this->idColumn}` = :id");
        $sth->execute([':id' => $id]);
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function delete(int $id): bool
    {
        $sth = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE `{$this->idColumn}` = :id");
        $sth->execute([':id' => $id]);
        return $sth->rowCount() > 0;
    }
}