<?php

namespace App\Repositories;

use App\Models\CarteiristaModel;
use Illuminate\Database\Eloquent\Collection;

class CarteiristaRepository
{
    public function findAll()
    {
        return CarteiristaModel::where('excluido', false)->get();
    }

    public function findByUuid(string $uuid)
    {
        return CarteiristaModel::where('uuid', $uuid)
            ->where('excluido', false)
            ->first();
    }

    public function create(array $data)
    {
        return CarteiristaModel::create($data);
    }

    public function update(string $uuid, array $data)
    {
        $carteirista = $this->findByUuid($uuid);
        
        if (!$carteirista) {
            return null;
        }

        $carteirista->update($data);
        return $carteirista->fresh();
    }

    public function delete(string $uuid)
    {
        $carteirista = $this->findByUuid($uuid);
        
        if (!$carteirista) {
            return false;
        }

        // Soft delete
        $carteirista->excluido = true;
        $carteirista->save();
        
        return true;
    }

    /**
     * Aplica filtros dinâmicos sobre os canteiristas.
     *
     * Filtros suportados:
     *  - nome_completo (LIKE %nome%)
     *  - cpf (LIKE %cpf%)
     *  - email (LIKE %email%)
     *  - telefone (LIKE %telefone%)
     *  - horta_uuid (igualdade exata)
     *  - com_canteiros (bool: true => apenas com canteiros vinculados ativos; false => apenas sem)
     *  - data_inicio (data_de_criacao >= data_inicio)
     *  - data_fim (data_de_criacao <= data_fim)
     *  - ordenar_por (campo de ordenação. default: data_de_criacao)
     *  - ordem (asc | desc. default: desc)
     */
    public function findByFilters(array $filtros): Collection
    {
        $query = CarteiristaModel::with(['canteiros', 'usuario'])
            ->where('excluido', false);

        if (!empty($filtros['nome_completo'])) {
            $query->whereHas('usuario', function ($q) use ($filtros) {
                $q->where('nome_completo', 'like', '%' . $filtros['nome_completo'] . '%');
            });
        }

        if (!empty($filtros['cpf'])) {
            $query->whereHas('usuario', function ($q) use ($filtros) {
                $q->where('cpf', 'like', '%' . $filtros['cpf'] . '%');
            });
        }

        if (!empty($filtros['email'])) {
            $query->whereHas('usuario', function ($q) use ($filtros) {
                $q->where('email', 'like', '%' . $filtros['email'] . '%');
            });
        }

        if (!empty($filtros['telefone'])) {
            $query->where('telefone', 'like', '%' . $filtros['telefone'] . '%');
        }

        if (!empty($filtros['horta_uuid'])) {
            $query->where('horta_uuid', $filtros['horta_uuid']);
        }

        if (!empty($filtros['data_inicio'])) {
            $query->where('data_de_criacao', '>=', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $query->where('data_de_criacao', '<=', $filtros['data_fim']);
        }

        if (array_key_exists('com_canteiros', $filtros) && $filtros['com_canteiros'] !== null) {
            $comCanteiros = filter_var($filtros['com_canteiros'], FILTER_VALIDATE_BOOLEAN);
            if ($comCanteiros) {
                $query->whereHas('canteiros', function ($q) {
                    $q->where('canteiristas_canteiros.ativo', 1)
                      ->where('canteiristas_canteiros.excluido', 0);
                });
            } else {
                $query->whereDoesntHave('canteiros', function ($q) {
                    $q->where('canteiristas_canteiros.ativo', 1)
                      ->where('canteiristas_canteiros.excluido', 0);
                });
            }
        }

        $ordenarPor = $filtros['ordenar_por'] ?? 'data_de_criacao';
        $ordem = strtolower($filtros['ordem'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $colunasPermitidas = ['data_de_criacao', 'data_de_ultima_alteracao'];
        if (!in_array($ordenarPor, $colunasPermitidas, true)) {
            $ordenarPor = 'data_de_criacao';
        }
        $query->orderBy($ordenarPor, $ordem);

        return $query->get();
    }

    /**
     * Retorna o total de canteiristas (não excluídos), opcionalmente filtrado por horta.
     */
    public function countTotal(?string $hortaUuid = null): int
    {
        $query = CarteiristaModel::where('excluido', false);

        if (!empty($hortaUuid)) {
            $query->where('horta_uuid', $hortaUuid);
        }

        return $query->count();
    }

    /**
     * Retorna o total de canteiristas que possuem ao menos um canteiro vinculado ativo.
     */
    public function countComCanteirosVinculados(?string $hortaUuid = null): int
    {
        $query = CarteiristaModel::where('excluido', false)
            ->whereHas('canteiros', function ($q) {
                $q->where('canteiristas_canteiros.ativo', 1)
                  ->where('canteiristas_canteiros.excluido', 0);
            });

        if (!empty($hortaUuid)) {
            $query->where('horta_uuid', $hortaUuid);
        }

        return $query->count();
    }

    /**
     * Retorna o total de canteiros ativos vinculados a canteiristas (não excluídos).
     */
    public function countCanteirosVinculados(?string $hortaUuid = null): int
    {
        $query = CarteiristaModel::where('excluido', false)
            ->withCount(['canteiros' => function ($q) {
                $q->where('canteiristas_canteiros.ativo', 1)
                  ->where('canteiristas_canteiros.excluido', 0);
            }]);

        if (!empty($hortaUuid)) {
            $query->where('horta_uuid', $hortaUuid);
        }

        return (int) $query->get()->sum('canteiros_count');
    }

    /**
     * Retorna a contagem de canteiristas agrupada por horta.
     */
    public function countPorHorta(?string $hortaUuid = null): array
    {
        $query = CarteiristaModel::where('excluido', false)
            ->selectRaw('horta_uuid, COUNT(*) as total')
            ->groupBy('horta_uuid');

        if (!empty($hortaUuid)) {
            $query->where('horta_uuid', $hortaUuid);
        }

        return $query->get()
            ->map(fn($row) => [
                'horta_uuid' => $row->horta_uuid,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * Retorna a contagem de canteiristas criados por mês nos últimos $meses meses.
     */
    public function countPorMes(int $meses = 6, ?string $hortaUuid = null): array
    {
        $dataLimite = date('Y-m-d 00:00:00', strtotime("-{$meses} months"));

        $query = CarteiristaModel::where('excluido', false)
            ->where('data_de_criacao', '>=', $dataLimite)
            ->selectRaw("DATE_FORMAT(data_de_criacao, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->orderBy('mes', 'asc');

        if (!empty($hortaUuid)) {
            $query->where('horta_uuid', $hortaUuid);
        }

        return $query->get()
            ->map(fn($row) => [
                'mes' => $row->mes,
                'total' => (int) $row->total,
            ])
            ->all();
    }
}
