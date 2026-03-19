<?php

namespace App\Enums;

enum EventType: string
{
    case InPerson = 'in_person';
    case Online = 'online';
    case Hybrid = 'hybrid';
}
