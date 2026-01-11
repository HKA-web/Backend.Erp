<?php

namespace App\Helpers;

class DebugHelper
{
    public static function formatTrace(\Throwable $e): array
    {
        return collect($e->getTrace())->map(function ($frame) {
            return [
                'file'     => $frame['file'] ?? null,
                'line'     => $frame['line'] ?? null,
                'class'    => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
                'args'     => isset($frame['args'])
                    ? array_map(fn($a) => is_object($a) ? get_class($a) : $a, $frame['args'])
                    : [],
            ];
        })->all();
    }
}
