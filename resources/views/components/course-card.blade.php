{{-- Catalogue card of a course (Figma "Cartes / Cours catalogue"). --}}
@props(['course'])

<article {{ $attributes->merge(['class' => 'flex flex-col rounded-[14px] border border-line bg-white p-[11px]']) }}>
    <x-course-cover :course="$course" />

    <div class="flex flex-1 flex-col px-2.5 pt-[18px]">
        <x-badge class="self-start">{{ $course->level->label() }}</x-badge>

        <h2 class="mt-[15px] text-2xl leading-[1.5]">
            <a href="{{ route('courses.show', $course) }}" class="rounded-field focus-visible:outline-2 focus-visible:outline-primary">{{ $course->title }}</a>
        </h2>
        @if ($course->description)
            <p class="mt-2 line-clamp-2 text-sm text-muted">{{ $course->description }}</p>
        @endif
        <p class="mt-3 text-label text-muted">
            {{ $course->owner->name }} · {{ trans_choice(':count leçon|:count leçons', $course->lessons_count) }}
        </p>

        <x-button variant="ghost" :href="route('courses.show', $course)" class="mt-auto w-full" aria-hidden="true" tabindex="-1">
            Découvrir le cours →
        </x-button>
    </div>
</article>
