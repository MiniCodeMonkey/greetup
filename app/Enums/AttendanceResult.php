<?php

namespace App\Enums;

enum AttendanceResult: string
{
    case Attended = 'attended';
    case NoShow = 'no_show';
}
