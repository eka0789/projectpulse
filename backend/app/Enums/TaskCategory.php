<?php

namespace App\Enums;

enum TaskCategory: string
{
    case FRONTEND = 'frontend';
    case BACKEND = 'backend';
    case DESIGN = 'design';
    case QA = 'qa';
    case DEVOPS = 'devops';
    case MANAGEMENT = 'management';
    case OTHER = 'other';
}
