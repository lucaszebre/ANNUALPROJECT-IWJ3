<?php

namespace App\Enums;

enum MemberRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Admin = 'admin';

    /**
     * The role as shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Étudiant',
            self::Teacher => 'Enseignant',
            self::Admin => 'Administrateur',
        };
    }
}
