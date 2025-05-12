<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Lesson;
use App\Policies\AssessmentPolicy;
use App\Policies\LessonPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Lesson::class => LessonPolicy::class,
        Assessment::class => AssessmentPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}