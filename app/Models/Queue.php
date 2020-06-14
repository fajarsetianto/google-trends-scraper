<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $table = "queue";

    protected $guarded = [];

    protected $casts = [
        'dataset' => 'array',
        'periods' => 'array',
        'keywords' => 'array',
        'is_finished' => 'boolean'
    ];
}
