<?php

namespace App\Enums;

enum VideoLanguage: string
{
    case French = 'fr';
    case English = 'en';

    /**
     * The PostgreSQL text search configuration used to index transcripts in this language.
     */
    public function searchConfig(): string
    {
        return match ($this) {
            self::French => 'fr_unaccent',
            self::English => 'en_unaccent',
        };
    }
}
