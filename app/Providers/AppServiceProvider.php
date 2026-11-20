<?php

namespace App\Providers;

use App\Enums\MemberRole;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

        // Role checks used by the navigation (@can), scoped to the selected organisation.
        Gate::define('teach', fn (User $user) => $user->memberships()
            ->where('organization_id', request()->session()->get('current_organization_id'))
            ->whereIn('role', [MemberRole::Teacher, MemberRole::Admin])
            ->exists());

        Gate::define('administer', fn (User $user) => $user->memberships()
            ->where('organization_id', request()->session()->get('current_organization_id'))
            ->where('role', MemberRole::Admin)
            ->exists());
    }
}
