<?php

namespace App\Services;

use App\Models\CanteiristaModel;
use App\Repositories\CanteiroRepository;
use App\Repositories\CanteiristaRepository;
use App\Repositories\CargoRepository;
use Exception;
use Ramsey\Uuid\Nonstandard\Uuid;

class CanteiristaService
{
    protected CanteiristaRepository $CanteiristaRepository;
    protected CanteiroRepository $canteiroRepository;
    protected HortaService $HortaService;
    protected CargoRepository $cargoRepository;
    protected UsuarioService $usuarioService;

    public function __construct(CanteiristaRepository $CanteiristaRepository, 
        CanteiroRepository $canteiroRepository,
        HortaService $HortaService,
        CargoRepository $cargoRepository,
        UsuarioService $usuarioService
    )
    {
        $this->CanteiristaRepository = $CanteiristaRepository;
        $this->canteiroRepository = $canteiroRepository;
        $this->HortaService = $HortaService;
        $this->cargoRepository = $cargoRepository;
        $this->usuarioService = $usuarioService;
    }

    public function findAllWhere(array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário
        // Por enquanto, retorna todos os Canteiristas
        return $this->CanteiristaRepository->findAll();
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário
        return $this->CanteiristaRepository->findByUuid($uuid);
    }

    public function create(array $data, array $payloadUsuarioLogado): CanteiristaModel
    {
        // TODO: Implementar verificação de permissões quando necessário

        $canteiros = $data['canteiros'] ?? [];
        unset($data['canteiros']);

        if (empty($data['cpf'])) {
            throw new Exception("Cpf é obrigatório");
        }
        if (empty($data['nome_completo'])) {
            throw new Exception("Nome completo é obrigatório");
        }
        if (empty($data['telefone'])) {
            throw new Exception("Telefone é obrigatório");
        }
        if (empty($data['horta_uuid'])) {
            throw new Exception("Horta é obrigatória");
        }

        $usuarioData = [
            'nome_completo' => $data['nome_completo'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'email' => $data['email'] ?? null,
            'senha' => $data['senha'] ?? null,
            'data_de_nascimento' => $data['data_de_nascimento'] ?? null,
            'apelido' => $data['apelido'] ?? null,
            'endereco_uuid' => $data['endereco_uuid'] ?? null,
        ];

        foreach (['cpf', 'nome_completo', 'email', 'senha', 'data_de_nascimento', 'apelido', 'endereco_uuid'] as $field) {
            unset($data[$field]);
        }

        $guarded = ['uuid','usuario_criador_uuid','data_de_criacao','data_de_ultima_alteracao', 'usuario_uuid'];
        foreach ($guarded as $g) unset($data[$g]);

        // Validações básicas
        $horta = $this->HortaService->findByUuid($data['horta_uuid'], $payloadUsuarioLogado);

        if (!$horta) {
            throw new Exception("Horta não encontrada");
        }

        // Validar dados do usuário
        if (empty($usuarioData['email'])) {
            throw new Exception("Email é obrigatório para criar o usuário");
        }
        if (empty($usuarioData['senha'])) {
            throw new Exception("Senha é obrigatória para criar o usuário");
        }
        if (empty($usuarioData['data_de_nascimento'])) {
            throw new Exception("Data de nascimento é obrigatória para criar o usuário");
        }
        if (empty($usuarioData['apelido'])) {
            throw new Exception("Apelido é obrigatório para criar o usuário");
        }

        $cargo = $this->cargoRepository->findAllWhere(['slug' => 'canteirista'])->first();
        if ($cargo) {
            $usuarioData['cargo_uuid'] = $cargo->uuid;
        }

        $usuario = $this->usuarioService->create($usuarioData, "NEW_ACCOUNT", $payloadUsuarioLogado);
        
        $data['uuid'] = Uuid::uuid1()->toString();
        $data['usuario_criador_uuid'] =  $payloadUsuarioLogado['usuario_uuid'];
        $data['usuario_alterador_uuid'] =  $payloadUsuarioLogado['usuario_uuid'];
        $data['usuario_uuid'] = $usuario->uuid;
        $data['ativo'] = 1;
        $data['excluido'] = 0;
        
        $Canteirista = $this->CanteiristaRepository->create($data);

        if (!empty($canteiros)) {
            $this->syncCanteiros($Canteirista, $canteiros, $payloadUsuarioLogado['usuario_uuid']);
            $Canteirista->refresh();
        }

        return $Canteirista;
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário

        $canteiros = $data['canteiros'] ?? [];
        unset($data['canteiros']);

        $usuarioData = [];
        foreach (['nome_completo', 'cpf', 'email', 'senha', 'data_de_nascimento', 'apelido', 'endereco_uuid'] as $field) {
            if (array_key_exists($field, $data)) {
                $usuarioData[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        // Adiciona o UUID do usuário alterador
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];
        
        // Remove campos que não devem ser atualizados
        unset($data['uuid']);
        unset($data['usuario_criador_uuid']);
        unset($data['usuario_uuid']);
        unset($data['data_de_criacao']);
        
        $Canteirista = $this->CanteiristaRepository->findByUuid($uuid);
        if (!$Canteirista) {
            return null;
        }

        if (!empty($usuarioData) && !empty($Canteirista->usuario_uuid)) {
            $this->usuarioService->update(
                $Canteirista->usuario_uuid,
                $usuarioData,
                $payloadUsuarioLogado['usuario_uuid'],
                $payloadUsuarioLogado
            );
        }

        $Canteirista = $this->CanteiristaRepository->update($uuid, $data);

        if ($Canteirista && !empty($canteiros)) {
            $this->syncCanteiros($Canteirista, $canteiros, $payloadUsuarioLogado['usuario_uuid']);
            $Canteirista->refresh();
        }

        return $Canteirista;
    }

    private function syncCanteiros(CanteiristaModel $Canteirista, array $canteiros, string $usuarioUuid)
    {
        $syncData = [];

        // agora $canteiros é um array simples de UUIDs de canteiro
        foreach ($canteiros as $canteiroUuid) {
            $cant = $this->canteiroRepository->findByUuid($canteiroUuid);

            if (!$cant) {
                throw new Exception("Canteiro não encontrado: $canteiroUuid");
            }

            if (empty($canteiroUuid)) {
                throw new Exception('UUID do canteiro é obrigatório em cada entrada de canteiros.');
            }

            if ($cant['horta_uuid'] !== $Canteirista->horta_uuid) {
                throw new Exception('O canteiro a ser adicionado deve pertencer à mesma horta do Canteirista.');
            }

            $syncData[$canteiroUuid] = [
                'uuid' => Uuid::uuid1()->toString(),
                'ativo' => 1,
                'excluido' => 0,
                'usuario_criador_uuid' => $usuarioUuid,
                'usuario_alterador_uuid' => $usuarioUuid,
            ];
        }

        $Canteirista->canteiros()->sync($syncData);
    }

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário
        
        $Canteirista = $this->CanteiristaRepository->findByUuid($uuid);
        
        if (!$Canteirista) {
            throw new Exception("Canteirista não encontrado");
        }
        
        return $this->CanteiristaRepository->delete($uuid);
    }

    /**
     * Lista canteiristas aplicando os filtros recebidos via query string.
     *
     * @param array $filtros
     * @param array $payloadUsuarioLogado
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByFilters(array $filtros, array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário

        // Se o usuário logado tem horta vinculada, restringe a busca à horta dele.
        if (!empty($payloadUsuarioLogado['horta_uuid']) && empty($filtros['horta_uuid'])) {
            $filtros['horta_uuid'] = $payloadUsuarioLogado['horta_uuid'];
        }

        return $this->CanteiristaRepository->findByFilters($filtros);
    }

    /**
     * Retorna estatísticas agregadas sobre os canteiristas.
     *
     * @param array $payloadUsuarioLogado
     * @param array $opcoes  meses (int) — janela para a série temporal mensal.
     * @return array
     */
    public function getEstatisticas(array $payloadUsuarioLogado, array $opcoes = []): array
    {
        // TODO: Implementar verificação de permissões quando necessário

        $meses = isset($opcoes['meses']) ? (int) $opcoes['meses'] : 6;
        if ($meses < 1) {
            $meses = 6;
        }

        // Quando o usuário logado tem horta vinculada, restringe as estatísticas a ela.
        $hortaUuid = !empty($payloadUsuarioLogado['horta_uuid'])
            ? $payloadUsuarioLogado['horta_uuid']
            : null;

        $total = $this->CanteiristaRepository->countTotal($hortaUuid);
        $comCanteiros = $this->CanteiristaRepository->countComCanteirosVinculados($hortaUuid);
        $semCanteiros = $total - $comCanteiros;
        $totalCanteirosVinculados = $this->CanteiristaRepository->countCanteirosVinculados($hortaUuid);

        $mediaCanteirosPorCanteirista = $total > 0
            ? round($totalCanteirosVinculados / $total, 2)
            : 0;

        return [
            'total' => $total,
            'com_canteiros_vinculados' => $comCanteiros,
            'sem_canteiros_vinculados' => $semCanteiros,
            'total_canteiros_vinculados' => $totalCanteirosVinculados,
            'media_canteiros_por_Canteirista' => $mediaCanteirosPorCanteirista,
            'por_horta' => $this->CanteiristaRepository->countPorHorta($hortaUuid),
            'por_mes' => $this->CanteiristaRepository->countPorMes($meses, $hortaUuid),
        ];
    }
}

