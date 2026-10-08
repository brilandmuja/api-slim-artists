<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class AlbumRepository extends BaseRepository
{
    protected string $table = 'albums';
    protected string $idColumn = 'idAlbums';

    protected function hydrate(array $row): object
    {
        return (object) $row;
    }

    public function create(string $titre, int $artistId): object
    {
        $sth = $this->pdo->prepare(
            'INSERT INTO `albums` (`Titre`, `Artist_idArtist`) VALUES (:titre, :artist)'
        );
        $sth->execute([':titre' => $titre, ':artist' => $artistId]);
        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, string $titre, int $artistId): ?object
    {
        if ($this->findById($id) === null) {
            return null;
        }
        $sth = $this->pdo->prepare(
            'UPDATE `albums` SET `Titre` = :titre, `Artist_idArtist` = :artist WHERE `idAlbums` = :id'
        );
        $sth->execute([':titre' => $titre, ':artist' => $artistId, ':id' => $id]);
        return $this->findById($id);
    }

    // Méthode spécifique : albums d'un artiste
    public function findByArtist(int $artistId): array
    {
        $sth = $this->pdo->prepare('SELECT * FROM `albums` WHERE `Artist_idArtist` = :artist');
        $sth->execute([':artist' => $artistId]);
        return array_map([$this, 'hydrate'], $sth->fetchAll(PDO::FETCH_ASSOC));
    }
}