<?php

namespace App\Http\Controllers;

use App\Enums\CourseLevel;
use App\Models\Category;
use App\Models\Course;
use App\Support\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * The catalogue: published courses, filtered by search, category or level.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('categorie');
        $level = CourseLevel::tryFrom((string) $request->query('niveau'));

        $courses = Course::visibleTo($user)
            ->with(['owner', 'category'])
            ->withCount('lessons')
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $query->whereAny(['title', 'description'], 'ilike', $pattern);
            })
            ->when($category, fn ($query) => $query->whereRelation('category', 'slug', $category))
            ->when($level, fn ($query) => $query->where('level', $level))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        // Only the categories that have visible courses (categories belong to an organisation).
        $categories = Category::whereIn('id', Course::visibleTo($user)->select('category_id'))
            ->orderBy('name')
            ->get();

        return view('catalog.index', [
            'courses' => $courses,
            'categories' => $categories,
            'levels' => [CourseLevel::Beginner, CourseLevel::Intermediate],
            'search' => $search,
            'activeCategory' => $category,
            'activeLevel' => $level,
        ]);
    }

    /**
     * A course page: presentation, programme and, for a student, their progress.
     */
    public function show(Request $request, string $course): View
    {
        $course = Course::visibleTo($request->user())
            ->with(['owner', 'category', 'lessons.video', 'lessons.exercise', 'lessons.quiz'])
            ->findOrFail($course);

        return view('courses.show', [
            'course' => $course,
            'progress' => new CourseProgress($course, $request->user()),
        ]);
    }

    /**
     * "Commencer le cours": requires an account, so a visitor goes through the login page
     * and comes back here. Opens the current lesson once lesson pages exist (#23).
     */
    public function start(Request $request, string $course): RedirectResponse
    {
        $course = Course::visibleTo($request->user())->with('lessons')->findOrFail($course);
        $lesson = (new CourseProgress($course, $request->user()))->currentLesson();

        if ($lesson && Route::has('lessons.show')) {
            return redirect()->route('lessons.show', $lesson);
        }

        return redirect()->to(route('courses.show', $course).'#programme');
    }
}
