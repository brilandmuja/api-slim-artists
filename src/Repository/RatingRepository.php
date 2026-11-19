<?php

declare(strict_types=1);

namespace App\Repository;

class RatingRepository extends BaseRepository
{
    protected string $table = 'ratings';
    protected string $idColumn = 'idRatings';

    protected function hydrate(array $row): object
    {
        return (object) $row;
    }

    public function create(int $grade, int $albumId): ?object
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ratings (Grade, Albums_idAlbums) VALUES (:grade, :album)'
        );
        $stmt->execute([':grade' => $grade, ':album' => $albumId]);
        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, int $grade, int $albumId): ?object
    {
        if ($this->findById($id) === null) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE ratings SET Grade = :grade, Albums_idAlbums = :album WHERE idRatings = :id'
        );
        $stmt->execute([':grade' => $grade, ':album' => $albumId, ':id' => $id]);
        return $this->findById($id);
    }

    // MÉTHODES SPÉCIFIQUES
    public function findByAlbum(int $albumId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ratings WHERE Albums_idAlbums = :album');
        $stmt->execute([':album' => $albumId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function averageByAlbum(int $albumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT AVG(Grade) AS moyenne, COUNT(*) AS nb FROM ratings WHERE Albums_idAlbums = :album'
        );
        $stmt->execute([':album' => $albumId]);
        $row = $stmt->fetch();
        return [
            'album'   => $albumId,
            'moyenne' => $row['moyenne'] !== null ? round((float) $row['moyenne'], 2) : null,
            'nb'      => (int) $row['nb'],
        ];
    }
}