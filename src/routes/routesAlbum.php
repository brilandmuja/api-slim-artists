<?php

declare(strict_types=1);

use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $json = function (Response $response, $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    };

    $valid = fn($in) => is_array($in) && !empty($in['Titre']) && !empty($in['Artist_idArtist']);

    $app->get('/albums', function (Request $req, Response $res) use ($json) {
        return $json($res, $this->get(AlbumRepository::class)->findAll());
    });

    $app->get('/albums/{id}', function (Request $req, Response $res, array $args) use ($json) {
        $album = $this->get(AlbumRepository::class)->findById((int) $args['id']);
        return $album ? $json($res, $album) : $json($res, ['error' => 'Album not found'], 404);
    });

    $app->post('/albums', function (Request $req, Response $res) use ($json, $valid) {
        $in = $req->getParsedBody();
        if (!$valid($in)) {
            return $json($res, ['error' => 'Body JSON requis : Titre, Artist_idArtist'], 400);
        }
        if ($this->get(ArtistRepository::class)->findById((int) $in['Artist_idArtist']) === null) {
            return $json($res, ['error' => 'Artist not found'], 404);
        }
        $album = $this->get(AlbumRepository::class)->create($in['Titre'], (int) $in['Artist_idArtist']);
        return $json($res, $album, 201);
    });

    $app->put('/albums/{id}', function (Request $req, Response $res, array $args) use ($json, $valid) {
        $in = $req->getParsedBody();
        if (!$valid($in)) {
            return $json($res, ['error' => 'Body JSON requis : Titre, Artist_idArtist'], 400);
        }
        if ($this->get(ArtistRepository::class)->findById((int) $in['Artist_idArtist']) === null) {
            return $json($res, ['error' => 'Artist not found'], 404);
        }
        $album = $this->get(AlbumRepository::class)->update((int) $args['id'], $in['Titre'], (int) $in['Artist_idArtist']);
        return $album ? $json($res, $album) : $json($res, ['error' => 'Album not found'], 404);
    });

    $app->delete('/albums/{id}', function (Request $req, Response $res, array $args) use ($json) {
        try {
            $ok = $this->get(AlbumRepository::class)->delete((int) $args['id']);
        } catch (PDOException $e) {
            return $json($res, ['error' => 'Album utilisé par des notes, suppression impossible'], 409);
        }
        return $ok ? $res->withStatus(204) : $json($res, ['error' => 'Album not found'], 404);
    });

    // Méthode spécifique : albums d'un artiste
    $app->get('/artists/{id}/albums', function (Request $req, Response $res, array $args) use ($json) {
        if ($this->get(ArtistRepository::class)->findById((int) $args['id']) === null) {
            return $json($res, ['error' => 'Artist not found'], 404);
        }
        return $json($res, $this->get(AlbumRepository::class)->findByArtist((int) $args['id']));
    });
};