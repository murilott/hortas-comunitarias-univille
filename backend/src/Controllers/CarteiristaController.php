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

    /**
     * GET /carteiristas/filtro
     *
     * Query params suportados:
     *  - nome_completo (string)
     *  - cpf (string)
     *  - email (string)
     *  - telefone (string)
     *  - horta_uuid (string)
     *  - com_canteiros (true | false)
     *  - data_inicio (YYYY-MM-DD)
     *  - data_fim (YYYY-MM-DD)
     *  - ordenar_por (nome_completo | cpf | email | telefone | data_de_criacao | data_de_ultima_alteracao)
     *  - ordem (asc | desc)
     */
    public function filter(Request $request, Response $response)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];

        $filtros = $request->getQueryParams() ?? [];

        $carteiristas = $this->carteiristaService->findByFilters($filtros, $payloadUsuarioLogado);

        $carteiristasFormatados = [];
        foreach ($carteiristas as $carteirista) {
            $carteiristasFormatados[] = $this->formatCarteirista($carteirista);
        }

        $response->getBody()->write(json_encode([
            'total' => count($carteiristasFormatados),
            'filtros_aplicados' => $filtros,
            'data' => $carteiristasFormatados,
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    /**
     * GET /carteiristas/estatisticas
     *
     * Query params opcionais:
     *  - meses (int) — janela em meses para a série temporal de criações. Default: 6.
     */
    public function statistics(Request $request, Response $response)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];

        $queryParams = $request->getQueryParams() ?? [];
        $opcoes = [
            'meses' => isset($queryParams['meses']) ? (int) $queryParams['meses'] : 6,
        ];

        $estatisticas = $this->carteiristaService->getEstatisticas($payloadUsuarioLogado, $opcoes);

        $response->getBody()->write(json_encode($estatisticas));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    /**
     * PATCH /carteiristas/{uuid}/ativar
     *
     * Reativa o acesso do canteirista (status_de_acesso = 'ativo' no usuário vinculado).
     */
    public function activate(Request $request, Response $response, array $args)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];

        $carteirista = $this->carteiristaService->ativar($args['uuid'], $payloadUsuarioLogado);

        if (!$carteirista) {
            $response->getBody()->write(json_encode(['error' => 'Carteirista não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $carteirista->load('canteiros');
        $response->getBody()->write(json_encode([
            'message' => 'Canteirista ativado com sucesso',
            'data' => $this->formatCarteirista($carteirista),
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    /**
     * PATCH /carteiristas/{uuid}/desativar
     *
     * Bloqueia o acesso do canteirista (status_de_acesso = 'inativo' no usuário vinculado).
     * Body opcional: { "motivo": "string" }
     */
    public function deactivate(Request $request, Response $response, array $args)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];

        $body = (array) ($request->getParsedBody() ?? []);
        $motivo = !empty($body['motivo']) ? (string) $body['motivo'] : null;

        $carteirista = $this->carteiristaService->desativar($args['uuid'], $payloadUsuarioLogado, $motivo);

        if (!$carteirista) {
            $response->getBody()->write(json_encode(['error' => 'Carteirista não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $carteirista->load('canteiros');
        $response->getBody()->write(json_encode([
            'message' => 'Canteirista desativado com sucesso',
            'data' => $this->formatCarteirista($carteirista),
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
