<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SequenceItem extends Model
{
    use HasFactory;

    protected $fillable = ['sequence_id', 'item_id', 'item_type', 'position', 'title'];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'item_id');
    }

       public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'item_id');
    }

    public function sequenceItems()
    {
        return $this->morphMany(SequenceItem::class, 'item');
    }
}