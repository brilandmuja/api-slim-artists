<?php

declare(strict_types=1);

use App\Repository\AlbumRepository;
use App\Repository\RatingRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $json = function (Response $res, $data, int $status = 200): Response {
        $res->getBody()->write(json_encode($data));
        return $res->withStatus($status)->withHeader('Content-Type', 'application/json');
    };

    // Grade entre 0 et 5 : à adapter selon ta table
    $valid = fn($in) => is_array($in)
        && isset($in['Grade'], $in['Albums_idAlbums'])
        && is_numeric($in['Grade']) && $in['Grade'] >= 0 && $in['Grade'] <= 5
        && is_numeric($in['Albums_idAlbums']);

    $app->get('/ratings', function (Request $req, Response $res) use ($json) {
        return $json($res, $this->get(RatingRepository::class)->findAll());
    });

    $app->get('/ratings/{id}', function (Request $req, Response $res, array $args) use ($json) {
        $rating = $this->get(RatingRepository::class)->findById((int) $args['id']);
        return $rating ? $json($res, $rating) : $json($res, ['error' => 'Rating not found'], 404);
    });

    $app->post('/ratings', function (Request $req, Response $res) use ($json, $valid) {
        $in = $req->getParsedBody();
        if (!$valid($in)) {
            return $json($res, ['error' => 'Grade (0-5) et Albums_idAlbums requis'], 400);
        }
        if ($this->get(AlbumRepository::class)->findById((int) $in['Albums_idAlbums']) === null) {
            return $json($res, ['error' => 'Album not found'], 404);
        }
        $rating = $this->get(RatingRepository::class)->create((int) $in['Grade'], (int) $in['Albums_idAlbums']);
        return $json($res, $rating, 201);
    });

    $app->put('/ratings/{id}', function (Request $req, Response $res, array $args) use ($json, $valid) {
        $in = $req->getParsedBody();
        if (!$valid($in)) {
            return $json($res, ['error' => 'Grade (0-5) et Albums_idAlbums requis'], 400);
        }
        if ($this->get(AlbumRepository::class)->findById((int) $in['Albums_idAlbums']) === null) {
            return $json($res, ['error' => 'Album not found'], 404);
        }
        $rating = $this->get(RatingRepository::class)->update(
            (int) $args['id'],
            (int) $in['Grade'],
            (int) $in['Albums_idAlbums']
        );
        return $rating ? $json($res, $rating) : $json($res, ['error' => 'Rating not found'], 404);
    });

    $app->delete('/ratings/{id}', function (Request $req, Response $res, array $args) use ($json) {
        $ok = $this->get(RatingRepository::class)->delete((int) $args['id']);
        return $ok ? $res->withStatus(204) : $json($res, ['error' => 'Rating not found'], 404);
    });

    // Routes spécifiques
    $app->get('/albums/{id}/ratings', function (Request $req, Response $res, array $args) use ($json) {
        if ($this->get(AlbumRepository::class)->findById((int) $args['id']) === null) {
            return $json($res, ['error' => 'Album not found'], 404);
        }
        return $json($res, $this->get(RatingRepository::class)->findByAlbum((int) $args['id']));
    });

    $app->get('/albums/{id}/average', function (Request $req, Response $res, array $args) use ($json) {
        if ($this->get(AlbumRepository::class)->findById((int) $args['id']) === null) {
            return $json($res, ['error' => 'Album not found'], 404);
        }
        return $json($res, $this->get(RatingRepository::class)->averageByAlbum((int) $args['id']));
    });
};