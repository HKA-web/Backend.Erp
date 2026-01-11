<?php

namespace App\Helpers;

class ExpandHelper
{
    public static function parse(?string $expand): array
    {
        if (!$expand) return [];

        $result = [];

        foreach (explode(',', $expand) as $path) {
            $levels = explode('.', trim($path));
            $current = &$result;

            foreach ($levels as $level) {
                if (!isset($current[$level])) {
                    $current[$level] = [];
                }
                $current = &$current[$level];
            }
        }

        return $result;
    }

    // untuk with()
    public static function toWith(array $tree, string $prefix = ''): array
    {
        $with = [];

        foreach ($tree as $key => $children) {
            $path = $prefix ? "$prefix.$key" : $key;
            $with[] = $path;

            if (!empty($children)) {
                $with = array_merge($with, self::toWith($children, $path));
            }
        }

        return $with;
    }
}
