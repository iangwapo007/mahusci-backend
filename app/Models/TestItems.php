<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestItems extends Model
{
    use HasFactory;

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $table = 'test';

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $primaryKey = 'test_item_id';

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $fillable = [
        'test_item_title', 
        'test_item_content',
    ];
}
