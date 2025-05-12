<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'assessment_id',
        'sequence_id',
        'score',
        'total_points',
        'percentage',
        'answers'
    ];

    protected $casts = [
        'answers' => 'array'
    ];

    public function student()
    {
        return $this->belongsTo(User::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
