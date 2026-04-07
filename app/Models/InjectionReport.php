<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InjectionReport extends Model
{
    protected $fillable = [
        'fecha',
        'turno_horario',
        'nombre_empleado',
        'maquina',
        'producto',
        'producto_por_color',
        'total',
        'rechazo',
        'sacos_usados',
        'observaciones',
    ];
}
