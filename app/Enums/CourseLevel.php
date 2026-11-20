<?php

namespace App\Enums;

enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Débutant',
            self::Intermediate => 'Intermédiaire',
            self::Advanced => 'Avancé',
        };
    }
}
