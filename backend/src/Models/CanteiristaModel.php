<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanteiristaModel extends Model
{
    protected $table = 'canteiristas';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;
    const CREATED_AT = 'data_de_criacao';
    const UPDATED_AT = 'data_de_ultima_alteracao';

    protected $fillable = [
        'uuid',
        'horta_uuid',
        'usuario_uuid',
        'telefone',
        'excluido',
        'usuario_criador_uuid',
        'usuario_alterador_uuid',
        'usuario_anterior_uuid',
    ];

    protected $casts = [
        'excluido' => 'boolean',
    ];

    // Relacionamentos
    public function horta()
    {
        return $this->belongsTo(HortaModel::class, 'horta_uuid', 'uuid');
    }

    public function usuarioCriador()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_criador_uuid', 'uuid');
    }

    public function usuarioAlterador()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_alterador_uuid', 'uuid');
    }

    public function usuarioAnterior()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_anterior_uuid', 'uuid');
    }

    public function usuario()
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_uuid', 'uuid');
    }

    public function canteiros()
    {
        return $this->belongsToMany(
            CanteiroModel::class,
            'canteiristas_canteiros',
            'canteirista_uuid',
            'canteiro_uuid'
        )->withPivot([
            'uuid',
            'ativo',
            'excluido',
            'usuario_criador_uuid',
            'usuario_alterador_uuid',
        ]);
    }
}

