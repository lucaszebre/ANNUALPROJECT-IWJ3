<?php

namespace Database\Seeders;

use App\Enums\CodeLanguage;
use App\Enums\CompletionSource;
use App\Enums\CourseLevel;
use App\Enums\CoverTone;
use App\Enums\MemberRole;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Exercise;
use App\Models\ExerciseTest;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\Quiz;
use App\Models\QuizChoice;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * A demo organisation with one user per role and a published Python course.
 * Every user's password is "password".
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'ESGI Demo',
            'slug' => 'esgi-demo',
        ]);

        $this->member($organization, 'Admin Demo', 'admin@example.com', MemberRole::Admin);
        $teacher = $this->member($organization, 'Teacher Demo', 'teacher@example.com', MemberRole::Teacher);
        $student = $this->member($organization, 'Student Demo', 'student@example.com', MemberRole::Student);

        $categories = collect(['Python', 'Développement web', 'Bases de données'])->mapWithKeys(fn (string $name) => [
            $name => Category::factory()->for($organization)->create(['name' => $name, 'slug' => str($name)->slug()]),
        ]);

        $course = Course::factory()->published()->for($organization)->create([
            'owner_id' => $teacher->id,
            'category_id' => $categories['Python']->id,
            'title' => 'Débuter en Python',
            'slug' => 'debuter-en-python',
            'description' => 'Les bases de Python : variables, fonctions et premiers programmes.',
            'level' => CourseLevel::Beginner,
            'cover_label' => 'Python',
            'cover_tone' => CoverTone::Purple,
            'cover_code' => "print('Bonjour')",
        ]);

        $variables = Lesson::factory()->for($course)->create([
            'position' => 1,
            'title' => 'Variables et types',
            'content' => "Une **variable** associe un nom à une valeur.\n\n```python\nage = 20\nnom = \"Ada\"\n```",
        ]);

        $functions = Lesson::factory()->for($course)->create([
            'position' => 2,
            'title' => 'Les fonctions',
            'content' => "Une **fonction** regroupe des instructions réutilisables.\n\n```python\ndef carre(x):\n    return x * x\n```",
        ]);
        $this->sumExercise($functions);

        $quizLesson = Lesson::factory()->for($course)->create([
            'position' => 3,
            'title' => 'Quiz : les bases',
            'content' => null,
        ]);
        $this->basicsQuiz($quizLesson);

        $functions->prerequisites()->attach($variables);
        $quizLesson->prerequisites()->attach($functions);

        $draft = Course::factory()->for($organization)->create([
            'owner_id' => $teacher->id,
            'category_id' => $categories['Développement web']->id,
            'title' => 'Introduction au HTML',
            'slug' => 'introduction-au-html',
        ]);
        Lesson::factory()->for($draft)->create([
            'position' => 1,
            'title' => 'Structure d\'une page',
        ]);

        $this->catalogueCourses($organization, $teacher, $categories);

        $student->enrolledCourses()->attach($course);
        $student->completedLessons()->attach($variables, ['source' => CompletionSource::Manual]);

        $question = Comment::factory()->create([
            'lesson_id' => $functions->id,
            'user_id' => $student->id,
            'body' => 'Quelle est la différence entre return et print ?',
        ]);
        Comment::factory()->replyTo($question)->create([
            'user_id' => $teacher->id,
            'body' => 'print affiche une valeur, return la renvoie à l\'appelant pour qu\'il puisse l\'utiliser.',
        ]);

    }

    /**
     * More published courses, so the catalogue looks like the mockup.
     *
     * @param  Collection<string, Category>  $categories
     */
    private function catalogueCourses(Organization $organization, User $teacher, Collection $categories): void
    {
        $courses = [
            ['Laravel en pratique', 'Une application web, de A à Z.', 'Développement web', CourseLevel::Intermediate, 'Laravel', CoverTone::Soft, 'développer > comprendre',
                ['Routes et contrôleurs', 'Vues Blade', 'Eloquent et migrations', 'Formulaires et validation']],
            ['SQL et PostgreSQL', 'Comprendre et interroger les données.', 'Bases de données', CourseLevel::Beginner, 'SQL', CoverTone::Sky, 'SELECT savoirs;',
                ['Tables et types', 'SELECT et WHERE', 'Les jointures']],
            ['HTML sémantique', 'Structurer des pages accessibles.', 'Développement web', CourseLevel::Beginner, 'HTML', CoverTone::Warning, '<main>…</main>',
                ['Structure d’une page', 'Titres et sections', 'Formulaires accessibles']],
            ['CSS responsive', 'Créer des interfaces qui s’adaptent.', 'Développement web', CourseLevel::Beginner, 'CSS', CoverTone::Soft, 'display: grid;',
                ['Le modèle de boîte', 'Flexbox', 'Grid et media queries']],
        ];

        foreach ($courses as [$title, $description, $category, $level, $label, $tone, $code, $lessons]) {
            $course = Course::factory()->published()->for($organization)->create([
                'owner_id' => $teacher->id,
                'category_id' => $categories[$category]->id,
                'title' => $title,
                'slug' => str($title)->slug(),
                'description' => $description,
                'level' => $level,
                'cover_label' => $label,
                'cover_tone' => $tone,
                'cover_code' => $code,
            ]);

            foreach ($lessons as $index => $lessonTitle) {
                Lesson::factory()->for($course)->create(['position' => $index + 1, 'title' => $lessonTitle]);
            }
        }
    }

    private function member(Organization $organization, string $name, string $email, MemberRole $role): User
    {
        $user = User::factory()->create(['name' => $name, 'email' => $email]);
        $organization->members()->attach($user, ['role' => $role]);

        return $user;
    }

    private function sumExercise(Lesson $lesson): void
    {
        $exercise = Exercise::factory()->for($lesson)->create([
            'language' => CodeLanguage::Python,
            'statement' => 'Lisez deux entiers sur une ligne et affichez leur somme.',
        ]);

        foreach ([['1 2', '3', false], ['-5 5', '0', false], ['1000000 2000000', '3000000', true]] as [$stdin, $stdout, $hidden]) {
            ExerciseTest::factory()->for($exercise)->create([
                'name' => $stdin,
                'stdin' => "{$stdin}\n",
                'expected_stdout' => "{$stdout}\n",
                'is_hidden' => $hidden,
            ]);
        }
    }

    private function basicsQuiz(Lesson $lesson): void
    {
        $quiz = Quiz::factory()->for($lesson)->create();

        $questions = [
            'Quel mot-clé définit une fonction en Python ?' => ['def' => true, 'function' => false, 'fn' => false],
            'Que vaut 7 // 2 ?' => ['3' => true, '3.5' => false, '4' => false],
        ];

        foreach ($questions as $prompt => $choices) {
            $question = QuizQuestion::factory()->for($quiz)->create(['prompt' => $prompt]);

            foreach ($choices as $label => $isCorrect) {
                QuizChoice::factory()->for($question, 'question')->create([
                    'label' => (string) $label,
                    'is_correct' => $isCorrect,
                ]);
            }
        }
    }
}
