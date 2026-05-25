<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagamentoModel extends Model
{
    protected $table = 'pagamentos';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'data_de_criacao';
    const UPDATED_AT = 'data_de_ultima_alteracao';

    protected $fillable = [
        'uuid',
        'Canteirista_uuid',
        'valor',
        'forma_pagamento',
        'data_pagamento',
        'observacao',
        'excluido',
        'usuario_criador_uuid',
        'usuario_alterador_uuid',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'date',
        'excluido' => 'boolean',
        'data_de_criacao' => 'datetime',
        'data_de_ultima_alteracao' => 'datetime',
    ];

    public function Canteirista()
    {
        return $this->belongsTo(CanteiristaModel::class, 'Canteirista_uuid', 'uuid');
    }
}

