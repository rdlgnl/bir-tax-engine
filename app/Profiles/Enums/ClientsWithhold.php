<?php

namespace App\Profiles\Enums;

enum ClientsWithhold: string
{
    case Yes = 'yes';
    case No = 'no';
    case Unsure = 'unsure';
}
