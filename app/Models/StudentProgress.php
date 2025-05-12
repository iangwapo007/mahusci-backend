<?php
// StudentProgress.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'sequence_id',
        'sequence_item_id',
        'status',
        'score',
        'attempts',
        'started_at',
        'completed_at',
        'answers'
    ];

    protected $casts = [
        'answers' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(User::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function sequenceItem()
    {
        return $this->belongsTo(SequenceItem::class);
    }
}