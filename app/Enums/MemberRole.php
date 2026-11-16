<?php

namespace App\Enums;

enum MemberRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Admin = 'admin';
}
