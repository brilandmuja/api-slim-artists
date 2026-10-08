<?php

declare(strict_types=1);

namespace App\Domain\Artist;

use JsonSerializable;

class Artist implements JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $annee,
        private string $description
    ) {
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getAnnee(): string { return $this->annee; }
    public function getDescription(): string { return $this->description; }

    public function jsonSerialize(): array
    {
        return [
            'idArtist'    => $this->id,
            'Name'        => $this->name,
            'Annee'       => $this->annee,
            'Description' => $this->description,
        ];
    }
}