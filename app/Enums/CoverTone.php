<?php

namespace App\Enums;

/**
 * Colour of a course cover (Figma "Médias / Couverture de cours").
 */
enum CoverTone: string
{
    case Soft = 'soft';
    case Purple = 'purple';
    case Sky = 'sky';
    case Warning = 'warning';

    /**
     * Background and text colour classes of the cover.
     */
    public function classes(): string
    {
        return match ($this) {
            self::Soft => 'bg-soft text-primary',
            self::Purple => 'bg-purple text-purple-ink',
            self::Sky => 'bg-sky text-sky-ink',
            self::Warning => 'bg-warning-soft text-warning',
        };
    }
}
