<?php

namespace App\Enums;

enum PlaybackEventType: string
{
    case Play = 'play';
    case Pause = 'pause';
    case Seek = 'seek';
    case Heartbeat = 'heartbeat';
    case Ended = 'ended';
}
