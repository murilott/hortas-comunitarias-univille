<?php

namespace App\Controllers;

use App\Services\HortaService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\CanteiristaService;

class CanteiristaController
{
    protected CanteiristaService $CanteiristaService;
    protected HortaService $hortaService;

    public function __construct(CanteiristaService $CanteiristaService, HortaService $hortaService)
    {
        $this->CanteiristaService = $CanteiristaService;
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

        $Canteiristas = $this->CanteiristaService->findAllWhere($payloadUsuarioLogado);
        $Canteiristas->load(['canteiros', 'usuario']);
        
        // Formatar resposta
        $CanteiristasFormatados = [];
        foreach ($Canteiristas as $Canteirista) {
            $CanteiristasFormatados[] = $this->formatCanteirista($Canteirista);
        }
        
        $response->getBody()->write(json_encode($CanteiristasFormatados));
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
        
        $Canteirista = $this->CanteiristaService->findByUuid($args['uuid'], $payloadUsuarioLogado);
        
        if (!$Canteirista) {
            $response->getBody()->write(json_encode(['error' => 'Canteirista não encontrado']));
            return $response->withStatus(404);
        }
        
        $Canteirista->load(['canteiros', 'usuario']);

        $CanteiristaFormatado = $this->formatCanteirista($Canteirista);

        $response->getBody()->write(json_encode($CanteiristaFormatado));
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
        
        $Canteirista = $this->CanteiristaService->create($data, $payloadUsuarioLogado);
        $Canteirista->load(['canteiros', 'usuario']);

        $CanteiristaFormatado = $this->formatCanteirista($Canteirista);

        $response->getBody()->write(json_encode($CanteiristaFormatado));
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
        
        $Canteirista = $this->CanteiristaService->update($args['uuid'], $data, $payloadUsuarioLogado);

        if (!$Canteirista) {
            $response->getBody()->write(json_encode(['error' => 'Canteirista não encontrado']));
            return $response->withStatus(404);
        }

        $Canteirista->load(['canteiros', 'usuario']);
        $CanteiristaFormatado = $this->formatCanteirista($Canteirista);

        $response->getBody()->write(json_encode($CanteiristaFormatado));
        return $response->withStatus(200);
    }

    private function formatCanteirista($Canteirista): array
    {
        return [
            'id' => $Canteirista->uuid,
            'usuario_uuid' => $Canteirista->usuario_uuid,
            'telefone' => $Canteirista->telefone ?? null,
            'ativo' => $Canteirista->ativo,
            'usuario' => [
                'uuid' => $Canteirista->usuario->uuid ?? null,
                'nome_completo' => $Canteirista->usuario->nome_completo ?? null,
                'cpf' => $Canteirista->usuario->cpf ?? null,
                'email' => $Canteirista->usuario->email ?? null,
                'endereco_uuid' => $Canteirista->usuario->endereco_uuid ?? null,
                'apelido' => $Canteirista->usuario->apelido ?? null,
                'data_de_nascimento' => $Canteirista->usuario->data_de_nascimento ?? null,
            ],
            'horta_vinculada' => $Canteirista->horta_uuid,
            'canteiros' => $Canteirista->canteiros->map(function ($canteiro) {
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
        
        $deleted = $this->CanteiristaService->delete($args['uuid'], $payloadUsuarioLogado);

        if (!$deleted) {
            $response->getBody()->write(json_encode(['error' => 'Canteirista não encontrado']));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode(['message' => 'Canteirista excluído com sucesso']));
        return $response->withStatus(200);
    }

    /**
     * GET /Canteiristas/filtro
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

        $Canteiristas = $this->CanteiristaService->findByFilters($filtros, $payloadUsuarioLogado);

        $CanteiristasFormatados = [];
        foreach ($Canteiristas as $Canteirista) {
            $CanteiristasFormatados[] = $this->formatCanteirista($Canteirista);
        }

        $response->getBody()->write(json_encode([
            'total' => count($CanteiristasFormatados),
            'filtros_aplicados' => $filtros,
            'data' => $CanteiristasFormatados,
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    /**
     * GET /Canteiristas/estatisticas
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

        $estatisticas = $this->CanteiristaService->getEstatisticas($payloadUsuarioLogado, $opcoes);

        $response->getBody()->write(json_encode($estatisticas));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}

