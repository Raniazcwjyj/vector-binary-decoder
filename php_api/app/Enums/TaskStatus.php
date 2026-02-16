<?php

namespace App\Enums;

enum TaskStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
