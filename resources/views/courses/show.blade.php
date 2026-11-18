{{-- Course page (Figma "03 · Découvrir PHP · visiteur" and "09 · Découvrir PHP · connecté"). --}}
<x-layouts.app :title="$course->title" active="catalog">
    @php
        $lessons = $course->lessons;
        $minutes = $lessons->sum(fn ($lesson) => $lesson->durationMinutes() ?? 0);
        $current = $progress->currentLesson();
        $startUrl = Route::has('login') || auth()->check() ? route('courses.start', $course) : '#';
    @endphp

    <div class="page-x pt-[30px] pb-16">
        <nav aria-label="Fil d’Ariane" class="text-label text-muted">
            <ol class="flex flex-wrap gap-x-3">
                <li><a href="{{ route('catalog') }}" class="hover:text-ink">Catalogue</a></li>
                @if ($course->category)
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('catalog', ['categorie' => $course->category->slug]) }}" class="hover:text-ink">{{ $course->category->name }}</a></li>
                @endif
                <li aria-hidden="true">/</li>
                <li aria-current="page">{{ $course->coverLabel() }}</li>
            </ol>
        </nav>

        {{-- Figma "Cours / Présentation" --}}
        <section class="mt-6 flex items-center justify-between gap-8 rounded-overlay bg-soft p-7 lg:pr-[36px]">
            <div class="min-w-0 max-w-[900px]">
                <x-badge tone="white">{{ mb_strtoupper($course->level->label()) }}</x-badge>
                <h1 class="mt-7 text-[clamp(2rem,5vw,3rem)] leading-[1.22] tracking-[-0.03em]">{{ $course->title }}</h1>
                @if ($course->description)
                    <p class="mt-4 text-[19px] leading-[1.5] text-muted">{{ $course->description }}</p>
                @endif
                <p class="mt-5 flex flex-wrap gap-x-6 text-sm text-muted">
                    <span>Par {{ $course->owner->name }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ trans_choice(':count leçon|:count leçons', $lessons->count()) }}</span>
                    @if ($minutes)
                        <span aria-hidden="true">·</span>
                        <span>{{ $minutes >= 60 ? intdiv($minutes, 60).' h'.($minutes % 60 ? ' '.str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) : '') : $minutes.' min' }}</span>
                    @endif
                </p>
            </div>
            <x-course-cover :course="$course" size="hero" class="hidden shrink-0 md:flex" />
        </section>

        <div class="mt-11 grid gap-8 lg:grid-cols-[minmax(0,1fr)_408px]">
            {{-- Programme --}}
            <section id="programme" aria-labelledby="programme-title" x-data="{ q: '', matches(title) { return title.includes(this.q.trim().toLowerCase()) } }">
                <h2 id="programme-title" class="text-[29px] leading-[1.5]">Le programme</h2>
                <label for="lesson-search" class="mt-4 block text-sm font-semibold text-ink">Rechercher dans ce cours</label>
                <input
                    id="lesson-search"
                    type="search"
                    x-model="q"
                    placeholder="Une notion, une leçon…"
                    class="mt-2 h-field w-full rounded-field border border-line bg-white px-[13px] text-sm text-ink placeholder:text-muted focus:border-primary focus:ring-3 focus:ring-primary/15 focus:outline-none"
                >

                @if ($lessons->isEmpty())
                    <x-empty-state title="Pas encore de leçon" class="mt-9">Les leçons de ce cours arrivent bientôt.</x-empty-state>
                @else
                    <ol x-ref="list" class="mt-9 flex flex-col gap-1.5">
                        @foreach ($lessons as $lesson)
                            @php
                                $state = match (true) {
                                    $progress->isCompleted($lesson) => 'completed',
                                    auth()->check() && ! $progress->isUnlocked($lesson) => 'locked',
                                    auth()->check() && $current?->is($lesson) => 'current',
                                    default => 'default',
                                };
                            @endphp
                            <x-lesson-row
                                :lesson="$lesson"
                                :number="$loop->iteration"
                                :state="$state"
                                :href="Route::has('lessons.show') ? route('lessons.show', $lesson) : null"
                                :missing="$progress->missingPrerequisites($lesson)"
                            />
                        @endforeach
                    </ol>
                    <p x-cloak x-show="q && ! [...$refs.list.children].some(li => matches(li.dataset.title))" class="mt-4 text-sm text-muted">
                        Aucune leçon ne correspond.
                    </p>
                @endif
            </section>

            <aside class="flex flex-col gap-8">
                @auth
                    {{-- Figma "Cartes / Votre cours en cours" --}}
                    <section class="rounded-[14px] border border-line bg-white p-[23px]">
                        <h2 class="text-[28px] leading-[1.55]">Votre progression</h2>
                        <p class="mt-2 text-base leading-[1.55] text-muted">
                            {{ trans_choice('{0} Aucune leçon terminée pour l’instant.|{1} 1 leçon terminée sur :total.|[2,*] :count leçons terminées sur :total.', $progress->completedCount(), ['total' => $lessons->count()]) }}
                        </p>
                        <x-progress-bar :percent="$progress->percent()" label="Progression dans le cours" class="mt-5" />
                        <p class="mt-6 text-base leading-[1.55] font-semibold text-ink">
                            {{ trans_choice(':count leçon|:count leçons', $lessons->count()) }} · {{ $progress->percent() }} %
                        </p>
                        @if ($current)
                            <x-button :href="$startUrl" class="mt-6 w-full">
                                {{ $progress->completedCount() ? 'Continuer le cours' : 'Commencer le cours' }} →
                            </x-button>
                        @elseif ($lessons->isNotEmpty() && $progress->completedCount() === $lessons->count())
                            <x-alert type="success" class="mt-6">Cours terminé, bravo !</x-alert>
                        @endif
                    </section>
                @else
                    {{-- Figma "Cartes / Inscription au cours" --}}
                    <section class="rounded-[14px] border border-line bg-white p-[23px]">
                        <h2 class="text-[28px] leading-[1.5]">Ne perdez plus le fil.</h2>
                        <p class="mt-3 text-body-lg text-muted">Reprenez au bon timecode, posez une question sur le passage qui bloque et voyez vos progrès.</p>
                        <p class="mt-4 text-[15px] leading-[1.5] font-semibold text-ink">
                            {{ trans_choice(':count leçon|:count leçons', $lessons->count()) }} · Accès avec un compte
                        </p>
                        <div class="mt-6 flex flex-col gap-4">
                            <x-button :href="$startUrl">Commencer le cours →</x-button>
                            <x-button variant="ghost" :href="Route::has('register') ? route('register') : '#'">Créer un compte</x-button>
                        </div>
                    </section>
                @endauth

                @if ($progress->hasPrerequisites())
                    {{-- Figma "Cartes / Comprendre les prérequis" --}}
                    <section class="rounded-[14px] bg-warning-soft px-6 py-5">
                        <h2 class="text-title-sm">Des leçons à débloquer</h2>
                        <p class="mt-4 text-base text-warning">Certaines leçons s’ouvrent quand vous avez terminé celles dont elles dépendent. Suivez le programme dans l’ordre.</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.app>
