{{--
    Landing page: what CodeCampus is, how a lesson works, what it offers students and teachers.
    Visitors are sent to sign up or to the catalogue, signed-in users back to their courses.
--}}
@php
    $link = fn (string $name) => Route::has($name) ? route($name) : '#';

    $steps = [
        ['Regarder', 'Des leçons vidéo courtes, avec leur transcription pour retrouver un passage en un clic.'],
        ['Pratiquer', 'Un exercice à chaque étape : vous écrivez le code dans le navigateur, les tests le corrigent tout de suite.'],
        ['Progresser', 'Votre avancement est suivi leçon après leçon, et la suivante se débloque quand vous êtes prêt.'],
    ];

    $features = [
        ['▶', 'bg-soft text-primary', 'Vidéos en streaming', 'Lecture adaptée à votre connexion, du téléphone à la fibre.'],
        ['{ }', 'bg-purple text-purple-ink', 'Éditeur de code intégré', 'Votre code tourne dans un bac à sable isolé, sans rien installer.'],
        ['✓', 'bg-sky text-sky-ink', 'Correction automatique', 'Chaque soumission passe des tests et vous dit ce qui manque.'],
        ['Aa', 'bg-warning-soft text-warning', 'Transcriptions consultables', 'Cherchez un mot dans toutes les vidéos et sautez au bon moment.'],
        ['?', 'bg-soft text-primary', 'Quiz', 'Vérifiez ce que vous avez retenu avant de passer à la suite.'],
        ['@', 'bg-purple text-purple-ink', 'Questions aux enseignants', 'Commentez une leçon à la seconde près, l’enseignant vous répond.'],
    ];
@endphp

<x-layouts.app active="home">
    {{-- Hero --}}
    <section class="page-x grid items-center gap-12 py-14 lg:grid-cols-[1fr_minmax(0,520px)] lg:gap-16 lg:py-24">
        <div>
            <p class="inline-flex rounded-full bg-soft px-3 py-1 text-2xs font-semibold text-primary uppercase">Plateforme d’apprentissage du code</p>
            <h1 class="mt-6 text-[40px] leading-[1.1] tracking-[-0.03em] sm:text-[56px]">
                Apprenez à coder,<br class="hidden sm:inline"> une leçon à la fois.
            </h1>
            <p class="mt-6 max-w-[540px] text-body-lg text-muted">
                Des vidéos claires, des exercices corrigés dans votre navigateur et un parcours qui s’adapte à votre rythme.
                Comprendre, pratiquer, progresser : tout est au même endroit.
            </p>
            <div class="mt-9 flex flex-wrap gap-3">
                @auth
                    <x-button :href="$link('learning')">Reprendre mes cours <span aria-hidden="true">→</span></x-button>
                @else
                    <x-button :href="$link('register')">Commencer gratuitement <span aria-hidden="true">→</span></x-button>
                @endauth
                <x-button variant="secondary" :href="$link('catalog')">Voir le catalogue</x-button>
            </div>
        </div>

        {{-- Preview of a lesson: the video and the exercise below it. --}}
        <div class="min-w-0 rounded-overlay bg-dark p-4 shadow-xl shadow-ink/10 sm:p-5" aria-hidden="true">
            <div class="flex aspect-video items-center justify-center rounded-card bg-code">
                <span class="flex size-14 items-center justify-center rounded-full bg-lime pl-1 text-xl text-ink">▶</span>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                <div class="h-full w-2/5 rounded-full bg-lime"></div>
            </div>
            <div class="mt-4 rounded-card bg-code p-5 font-mono text-[13px] leading-relaxed sm:text-sm">
                <p class="text-code-muted">// Exercice 3 · Inverser une chaîne</p>
                <pre class="mt-3 overflow-x-auto text-lime">function inverser(texte) {
  return [...texte].reverse().join('');
}</pre>
                <p class="mt-4 flex items-center gap-2 font-sans text-2xs font-semibold text-lime">
                    <span class="flex size-4 items-center justify-center rounded-full bg-lime text-[10px] text-ink">✓</span>
                    3 tests sur 3 réussis
                </p>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-y border-line bg-white">
        <div class="page-x py-16 lg:py-20">
            <p class="text-2xs font-semibold text-primary uppercase">Comment ça marche</p>
            <h2 class="mt-3 max-w-[560px] text-title leading-tight sm:text-title-lg">Trois étapes, répétées jusqu’à ce que ça devienne naturel.</h2>
            <ol class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($steps as $i => [$title, $text])
                    <li class="rounded-card border border-line bg-canvas p-6">
                        <span class="font-mono text-sm font-semibold text-primary">0{{ $i + 1 }}</span>
                        <h3 class="mt-3 text-title-sm">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Features --}}
    <section class="page-x py-16 lg:py-20">
        <p class="text-2xs font-semibold text-primary uppercase">Pour les étudiants</p>
        <h2 class="mt-3 max-w-[560px] text-title leading-tight sm:text-title-lg">Tout ce qu’il faut pour apprendre en faisant.</h2>
        <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as [$glyph, $colors, $title, $text])
                <li class="rounded-card border border-line bg-white p-6">
                    <span class="flex size-10 items-center justify-center rounded-tile font-mono text-sm font-bold {{ $colors }}" aria-hidden="true">{{ $glyph }}</span>
                    <h3 class="mt-4 text-base">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Teachers --}}
    <section class="page-x pb-16 lg:pb-20">
        <div class="grid gap-10 rounded-overlay bg-dark px-6 py-10 sm:px-10 lg:grid-cols-2 lg:items-center lg:px-14 lg:py-14">
            <div>
                <p class="inline-flex rounded-full bg-lime px-3 py-1 text-2xs font-semibold text-ink uppercase">Pour les enseignants</p>
                <h2 class="mt-5 text-title leading-tight text-white sm:text-title-lg">Créez vos cours, voyez où vos étudiants bloquent.</h2>
                <p class="mt-4 text-body-lg text-code-muted">
                    Déposez vos vidéos, écrivez vos leçons et vos exercices : CodeCampus s’occupe de l’encodage, de la transcription et de la correction.
                </p>
            </div>
            <ul class="grid gap-3 text-sm text-white">
                @foreach ([
                    'Éditeur de cours et de leçons, avec un ordre et des prérequis',
                    'Exercices avec vos propres cas de test',
                    'Courbe d’abandon des vidéos et taux d’échec par exercice',
                    'Espace séparé pour chaque école ou organisation',
                ] as $item)
                    <li class="flex items-start gap-3 rounded-card bg-white/5 px-4 py-3.5">
                        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-lime text-[11px] font-bold text-ink" aria-hidden="true">✓</span>
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Call to action --}}
    <section class="border-t border-line bg-white">
        <div class="page-x flex flex-col items-start gap-6 py-14 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-title leading-tight">Prêt à écrire votre première ligne ?</h2>
                <p class="mt-2 text-muted">Créez un compte en une minute et commencez par le cours de votre choix.</p>
            </div>
            @auth
                <x-button :href="$link('learning')" class="shrink-0">Reprendre mes cours</x-button>
            @else
                <x-button :href="$link('register')" class="shrink-0">Créer un compte</x-button>
            @endauth
        </div>
    </section>
</x-layouts.app>
