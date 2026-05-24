<?php

namespace App\Controllers;

use App\Services\HortaService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\CarteiristaService;

class CarteiristaController
{
    protected CarteiristaService $carteiristaService;
    protected HortaService $hortaService;

    public function __construct(CarteiristaService $carteiristaService, HortaService $hortaService)
    {
        $this->carteiristaService = $carteiristaService;
        $this->hortaService = $hortaService;
    }

    public function list(Request $request, Response $response)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];

        $carteiristas = $this->carteiristaService->findAllWhere($payloadUsuarioLogado);
        $carteiristas->load('canteiros');
        
        // Formatar resposta
        $carteiristasFormatados = [];
        foreach ($carteiristas as $carteirista) {
            $carteiristasFormatados[] = $this->formatCarteirista($carteirista);
        }
        
        $response->getBody()->write(json_encode($carteiristasFormatados));
        return $response->withStatus(200);
    }

    public function get(Request $request, Response $response, array $args)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];
        
        $carteirista = $this->carteiristaService->findByUuid($args['uuid'], $payloadUsuarioLogado);
        
        if (!$carteirista) {
            $response->getBody()->write(json_encode(['error' => 'Carteirista não encontrado']));
            return $response->withStatus(404);
        }
        
        $carteirista->load('canteiros');

        $carteiristaFormatado = $this->formatCarteirista($carteirista);

        $response->getBody()->write(json_encode($carteiristaFormatado));
        return $response->withStatus(200);
    }

    public function create(Request $request, Response $response)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];
        
        $data = (array)$request->getParsedBody();
        
        $carteirista = $this->carteiristaService->create($data, $payloadUsuarioLogado);
        $carteirista->load('canteiros');

        $carteiristaFormatado = $this->formatCarteirista($carteirista);

        $response->getBody()->write(json_encode($carteiristaFormatado));
        return $response->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];
        
        $data = (array)$request->getParsedBody();
        
        $carteirista = $this->carteiristaService->update($args['uuid'], $data, $payloadUsuarioLogado);

        if (!$carteirista) {
            $response->getBody()->write(json_encode(['error' => 'Carteirista não encontrado']));
            return $response->withStatus(404);
        }

        $carteirista->load('canteiros');
        $carteiristaFormatado = $this->formatCarteirista($carteirista);

        $response->getBody()->write(json_encode($carteiristaFormatado));
        return $response->withStatus(200);
    }

    private function formatCarteirista($carteirista): array
    {
        return [
            'id' => $carteirista->uuid,
            'nome_completo' => $carteirista->nome_completo,
            'telefone' => $carteirista->telefone,
            'cpf' => $carteirista->cpf,
            'email' => $carteirista->email,
            'endereco' => $carteirista->endereco_uuid,
            'horta_vinculada' => $carteirista->horta_uuid,
            'usuario_uuid' => $carteirista->usuario_uuid,
            'canteiros' => $carteirista->canteiros->map(function ($canteiro) {
                return [
                    'uuid' => $canteiro->uuid,
                    'numero_identificador' => $canteiro->numero_identificador,
                    'tamanho_m2' => $canteiro->tamanho_m2,
                    'ativo' => $canteiro->pivot->ativo ?? 1,
                ];
            })->all(),
        ];
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];
        
        $deleted = $this->carteiristaService->delete($args['uuid'], $payloadUsuarioLogado);

        if (!$deleted) {
            $response->getBody()->write(json_encode(['error' => 'Carteirista não encontrado']));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode(['message' => 'Carteirista excluído com sucesso']));
        return $response->withStatus(200);
    }
}
