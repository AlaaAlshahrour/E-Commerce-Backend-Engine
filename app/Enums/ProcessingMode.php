<?php

namespace App\Enums;

enum ProcessingMode: string
{
    case Batch = 'batch';
    case Normal = 'normal';
    case Compare = 'compare';
}
