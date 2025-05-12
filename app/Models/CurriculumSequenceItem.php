<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurriculumSequenceItem extends Model
{
    use HasFactory;

    protected $fillable = ['curriculum_sequence_quarter_id', 'sequence_id', 'order'];

    public function quarter()
    {
        return $this->belongsTo(CurriculumSequenceQuarter::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class)->with('items');
    }
}