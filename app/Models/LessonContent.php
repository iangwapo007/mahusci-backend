<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'content',
        'image_name',
        'order'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function contentMedia()
    {
        return $this->morphMany(Media::class, 'model');
    }
}