<?php

declare(strict_types=1);

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $json = function (Response $response, $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    };

    $valid = fn($in) => is_array($in) && !empty($in['Name']) && !empty($in['Annee']) && !empty($in['Description']);

    $app->get('/artists', function (Request $req, Response $res) use ($json) {
        return $json($res, $this->get(ArtistRepository::class)->findAll());
    });

    // Méthode spécifique : artistes d'une année (avant /artists/{id} pour plus de clarté)
    $app->get('/artists/year/{annee}', function (Request $req, Response $res, array $args) use ($json) {
        return $json($res, $this->get(ArtistRepository::class)->findByYear($args['annee']));
    });

    $app->get('/artists/{id}', function (Request $req, Response $res, array $args) use ($json) {
        $artist = $this->get(ArtistRepository::class)->findById((int) $args['id']);
        return $artist ? $json($res, $artist) : $json($res, ['error' => 'Artist not found'], 404);
    });

    $app->post('/artists', function (Request $req, Response $res) use ($json, $valid) {
        $in = $req->getParsedBody();
        if (!$valid($in)) {
            return $json($res, ['error' => 'Body JSON requis : Name, Annee, Description'], 400);
        }
        $artist = $this->get(ArtistRepository::class)->create(
            new Artist(null, $in['Name'], (string) $in['Annee'], $in['Description'])
        );
        return $json($res, $artist, 201);
    });

    $app->put('/artists/{id}', function (Request $req, Response $res, array $args) use ($json, $valid) {
        $in = $req->getParsedBody();
        if (!$valid($in)) {
            return $json($res, ['error' => 'Body JSON requis : Name, Annee, Description'], 400);
        }
        $artist = $this->get(ArtistRepository::class)->update(
            (int) $args['id'],
            new Artist(null, $in['Name'], (string) $in['Annee'], $in['Description'])
        );
        return $artist ? $json($res, $artist) : $json($res, ['error' => 'Artist not found'], 404);
    });

    $app->delete('/artists/{id}', function (Request $req, Response $res, array $args) use ($json) {
        try {
            $ok = $this->get(ArtistRepository::class)->delete((int) $args['id']);
        } catch (PDOException $e) {
            // des albums pointent encore vers cet artiste (clé étrangère)
            return $json($res, ['error' => 'Artiste utilisé par des albums, suppression impossible'], 409);
        }
        return $ok ? $res->withStatus(204) : $json($res, ['error' => 'Artist not found'], 404);
    });
};