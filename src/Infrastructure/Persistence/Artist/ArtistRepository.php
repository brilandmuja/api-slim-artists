<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Artist;

use App\Domain\Artist\Artist;
use App\Infrastructure\Persistence\BaseRepository;

class ArtistRepository extends BaseRepository
{
    protected string $table = 'artists';
    protected string $idColumn = 'idArtist';

    protected function hydrate(array $row): Artist
    {
        return new Artist(
            (int) $row['idArtist'],
            (string) $row['Name'],
            (string) $row['Annee'],
            (string) $row['Description']
        );
    }

    public function create(Artist $artist): Artist
    {
        $sth = $this->pdo->prepare(
            'INSERT INTO `artists` (`Name`, `Annee`, `Description`) VALUES (:name, :annee, :description)'
        );
        $sth->execute([
            ':name'        => $artist->getName(),
            ':annee'       => $artist->getAnnee(),
            ':description' => $artist->getDescription(),
        ]);

        return new Artist(
            (int) $this->pdo->lastInsertId(),
            $artist->getName(),
            $artist->getAnnee(),
            $artist->getDescription()
        );
    }

    public function update(int $id, Artist $artist): ?Artist
    {
        if ($this->findById($id) === null) {
            return null;
        }

        $sth = $this->pdo->prepare(
            'UPDATE `artists` SET `Name` = :name, `Annee` = :annee, `Description` = :description WHERE `idArtist` = :id'
        );
        $sth->execute([
            ':name'        => $artist->getName(),
            ':annee'       => $artist->getAnnee(),
            ':description' => $artist->getDescription(),
            ':id'          => $id,
        ]);

        return $this->findById($id);
    }
}