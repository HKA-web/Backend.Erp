<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;

trait SerializableDate
{
    protected function serializeDate(DateTimeInterface $date): array
    {
        $user = Auth::guard('sanctum')->user();
        $targetTimezone = $user->timezone ?? config('app.timezone', 'Asia/Jakarta');

        $dt = Carbon::instance($date);

        $utc = $dt->copy()->utc();

        $dt->setTimezone($targetTimezone);

        return [
            'timestamp' => $dt->timestamp,
            'zone' => $dt->getOffsetString(),
            'timezonename' => $targetTimezone,
            'utc' => $utc->toIso8601ZuluString('microsecond'),
            'date' => $dt->toDateString(),
            'time' => $dt->toTimeString('microsecond'),
            'datetime' => $dt->toIso8601String('microsecond'),
            'datetimezone' => $dt->format('Y-m-d\TH:i:s.uP'),
        ];
    }
}
