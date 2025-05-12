<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;

    public function view(Teacher $user, Lesson $lesson)
    {
        return $user->id === $lesson->teacher_id;
    }

    public function create(Teacher $user)
    {
        return $user->role === 'Teacher';
    }

    public function update(Teacher $user, Lesson $lesson)
    {
        return $user->id === $lesson->teacher_id;
    }

    public function delete(Teacher $user, Lesson $lesson)
    {
        return $user->id === $lesson->teacher_id;
    }
}