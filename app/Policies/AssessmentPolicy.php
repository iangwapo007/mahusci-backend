<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentPolicy
{
    use HandlesAuthorization;

    public function view(Teacher $user, Assessment $assessment)
    {
        return $user->id === $assessment->teacher_id;
    }

    public function create(Teacher $user)
    {
        return $user->role === 'Teacher';
    }

    public function update(Teacher $user, Assessment $assessment)
    {
        return $user->id === $assessment->teacher_id;
    }

    public function delete(Teacher $user, Assessment $assessment)
    {
        return $user->id === $assessment->teacher_id;
    }
}
