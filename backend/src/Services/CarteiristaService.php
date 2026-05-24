<?php

namespace App\Services;

use App\Models\CarteiristaModel;
use App\Repositories\CanteiroRepository;
use App\Repositories\CarteiristaRepository;
use App\Repositories\CargoRepository;
use Exception;
use Ramsey\Uuid\Nonstandard\Uuid;

class CarteiristaService
{
    protected CarteiristaRepository $carteiristaRepository;
    protected CanteiroRepository $canteiroRepository;
    protected HortaService $HortaService;
    protected CargoRepository $cargoRepository;
    protected UsuarioService $usuarioService;

    public function __construct(CarteiristaRepository $carteiristaRepository, 
        CanteiroRepository $canteiroRepository,
        HortaService $HortaService,
        CargoRepository $cargoRepository,
        UsuarioService $usuarioService
    )
    {
        $this->carteiristaRepository = $carteiristaRepository;
        $this->canteiroRepository = $canteiroRepository;
        $this->HortaService = $HortaService;
        $this->cargoRepository = $cargoRepository;
        $this->usuarioService = $usuarioService;
    }

    public function findAllWhere(array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário
        // Por enquanto, retorna todos os carteiristas
        return $this->carteiristaRepository->findAll();
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário
        return $this->carteiristaRepository->findByUuid($uuid);
    }

    public function create(array $data, array $payloadUsuarioLogado): CarteiristaModel
    {
        // TODO: Implementar verificação de permissões quando necessário

        $canteiros = $data['canteiros'] ?? [];
        unset($data['canteiros']);

        $usuarioData = [
            'nome_completo' => $data['nome_completo'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'email' => $data['email'] ?? null,
            'senha' => $data['senha'] ?? null,
            'data_de_nascimento' => $data['data_de_nascimento'] ?? null,
            'apelido' => $data['apelido'] ?? null,
        ];

        $guarded = ['uuid','usuario_criador_uuid','data_de_criacao','data_de_ultima_alteracao', 'usuario_uuid'];
        foreach ($guarded as $g) unset($data[$g]);

        // Validações básicas
        $horta = $this->HortaService->findByUuid($data['horta_uuid'], $payloadUsuarioLogado);

        if (!$horta) {
            throw new Exception("Horta não encontrada");
        }

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
        $data['excluido'] = 0;
        
        $carteirista = $this->carteiristaRepository->create($data);

        if (!empty($canteiros)) {
            $this->syncCanteiros($carteirista, $canteiros, $payloadUsuarioLogado['usuario_uuid']);
            $carteirista->refresh();
        }

        return $carteirista;
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
        
        $carteirista = $this->carteiristaRepository->findByUuid($uuid);
        if (!$carteirista) {
            return null;
        }

        if (!empty($usuarioData) && !empty($carteirista->usuario_uuid)) {
            $this->usuarioService->update(
                $carteirista->usuario_uuid,
                $usuarioData,
                $payloadUsuarioLogado['usuario_uuid'],
                $payloadUsuarioLogado
            );
        }

        $carteirista = $this->carteiristaRepository->update($uuid, $data);

        if ($carteirista && !empty($canteiros)) {
            $this->syncCanteiros($carteirista, $canteiros, $payloadUsuarioLogado['usuario_uuid']);
            $carteirista->refresh();
        }

        return $carteirista;
    }

    private function syncCanteiros(CarteiristaModel $carteirista, array $canteiros, string $usuarioUuid)
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

            if ($cant['horta_uuid'] !== $carteirista->horta_uuid) {
                throw new Exception('O canteiro a ser adicionado deve pertencer à mesma horta do carteirista.');
            }

            $syncData[$canteiroUuid] = [
                'uuid' => Uuid::uuid1()->toString(),
                'ativo' => 1,
                'excluido' => 0,
                'usuario_criador_uuid' => $usuarioUuid,
                'usuario_alterador_uuid' => $usuarioUuid,
            ];
        }

        $carteirista->canteiros()->sync($syncData);
    }

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        // TODO: Implementar verificação de permissões quando necessário
        
        $carteirista = $this->carteiristaRepository->findByUuid($uuid);
        
        if (!$carteirista) {
            throw new Exception("Canteirista não encontrado");
        }
        
        return $this->carteiristaRepository->delete($uuid);
    }
}
