<?php

namespace App\Enums;

enum RsvpStatus: string
{
    case Going = 'going';
    case NotGoing = 'not_going';
    case Waitlisted = 'waitlisted';
}
