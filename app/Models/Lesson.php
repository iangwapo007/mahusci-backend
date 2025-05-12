<?php

// app/Models/Lesson.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject',
        'grades',
        'difficulty',
        'quarter',
        'objectives',
        'is_draft',
        'teacher_id'
    ];

    protected $casts = [
        'objectives' => 'array',
        'is_draft' => 'boolean'
    ];

    public function contents()
    {
        return $this->hasMany(LessonContent::class)->with('contentMedia');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    // Helper method to get grades as array
    public function getGradesArrayAttribute()
    {
        return explode(',', $this->grades);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function sequenceItems()
    {
        return $this->morphMany(SequenceItem::class, 'item');
    }
}