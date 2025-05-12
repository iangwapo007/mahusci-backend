<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurriculumSequenceQuarter extends Model
{
    use HasFactory;

    protected $fillable = ['curriculum_sequence_id', 'quarter'];

    public function sequence()
    {
        return $this->belongsTo(CurriculumSequence::class);
    }

    public function items()
    {
        return $this->hasMany(CurriculumSequenceItem::class)->with('sequence')->orderBy('order');
    }
}