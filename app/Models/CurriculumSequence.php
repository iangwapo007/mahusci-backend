<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurriculumSequence extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'teacher_id', 'description', 'curriculum_image', 'is_draft', 'grades'];

    public function quarters()
    {
        return $this->hasMany(CurriculumSequenceQuarter::class);
    }

    public function user()
    {
        return $this->belongsTo(Teacher::class);
    }
}
