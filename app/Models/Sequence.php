<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'teacher_id', 'quarter'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function items()
    {
        return $this->hasMany(SequenceItem::class)->orderBy('position');
    }
}