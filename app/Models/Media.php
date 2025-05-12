<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'file_path',
        'file_type',
        'original_name',
        'size'
    ];

    public function model()
    {
        return $this->morphTo();
    }
}