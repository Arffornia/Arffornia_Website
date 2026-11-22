@props(['seconds'])
@php
    $text = '-';
    if ($seconds !== null) {
        $seconds = max(0, (int) $seconds);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $text = match (true) {
            $days > 0 && $hours > 0 => __('common.duration.days_hours', ['d' => $days, 'h' => $hours]),
            $days > 0 => __('common.duration.days', ['d' => $days]),
            $hours > 0 && $minutes > 0 => __('common.duration.hours_minutes', ['h' => $hours, 'm' => str_pad((string) $minutes, 2, '0', STR_PAD_LEFT)]),
            $hours > 0 => __('common.duration.hours', ['h' => $hours]),
            $minutes > 0 || $seconds === 0 => __('common.duration.minutes', ['m' => $minutes]),
            default => __('common.duration.seconds', ['s' => $seconds]),
        };
    }
@endphp
{{ $text }}
