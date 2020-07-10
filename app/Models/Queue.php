<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $table = "queue";

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'periods' => 'array',
        'keywords' => 'array',
        'corelation' => 'array',
    ];
}
