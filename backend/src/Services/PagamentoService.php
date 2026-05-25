<?php

namespace App\Services;

use App\Repositories\PagamentoRepository;
use App\Repositories\CanteiristaRepository;
use InvalidArgumentException;

class PagamentoService
{
    private PagamentoRepository $repository;
    private CanteiristaRepository $CanteiristaRepository;

    public function __construct(
        PagamentoRepository $repository,
        CanteiristaRepository $CanteiristaRepository
    ) {
        $this->repository = $repository;
        $this->CanteiristaRepository = $CanteiristaRepository;
    }

    public function findAll()
    {
        return $this->repository->findAll();
    }

    public function findByUuid(string $uuid)
    {
        $pagamento = $this->repository->findByUuid($uuid);
        if (!$pagamento) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }
        return $pagamento;
    }

    public function create(array $data)
    {
        // Validações
        if (empty($data['Canteirista_uuid'])) {
            throw new InvalidArgumentException('Canteirista é obrigatório');
        }

        if (empty($data['valor']) || $data['valor'] <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }

        if (empty($data['forma_pagamento'])) {
            throw new InvalidArgumentException('Forma de pagamento é obrigatória');
        }

        if (!in_array($data['forma_pagamento'], ['dinheiro', 'pix'])) {
            throw new InvalidArgumentException('Forma de pagamento inválida');
        }

        // Verifica se Canteirista existe
        $Canteirista = $this->CanteiristaRepository->findByUuid($data['Canteirista_uuid']);
        if (!$Canteirista) {
            throw new InvalidArgumentException('Canteirista não encontrado');
        }

        // Define data_pagamento como hoje se não informada
        if (empty($data['data_pagamento'])) {
            $data['data_pagamento'] = date('Y-m-d');
        }

        return $this->repository->create($data);
    }

    public function update(string $uuid, array $data)
    {
        // Validações
        if (isset($data['Canteirista_uuid']) && !empty($data['Canteirista_uuid'])) {
            $Canteirista = $this->CanteiristaRepository->findByUuid($data['Canteirista_uuid']);
            if (!$Canteirista) {
                throw new InvalidArgumentException('Canteirista não encontrado');
            }
        }

        if (isset($data['valor']) && $data['valor'] <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }

        if (isset($data['forma_pagamento']) && !in_array($data['forma_pagamento'], ['dinheiro', 'pix'])) {
            throw new InvalidArgumentException('Forma de pagamento inválida');
        }

        $pagamento = $this->repository->update($uuid, $data);
        if (!$pagamento) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }

        return $pagamento;
    }

    public function delete(string $uuid)
    {
        $deleted = $this->repository->delete($uuid);
        if (!$deleted) {
            throw new InvalidArgumentException('Pagamento não encontrado');
        }
        return true;
    }
}

