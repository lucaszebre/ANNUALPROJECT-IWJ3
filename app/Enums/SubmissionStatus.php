<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Passed = 'passed';
    case Failed = 'failed';
    case Error = 'error';
}
