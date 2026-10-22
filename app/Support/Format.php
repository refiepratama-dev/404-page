<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Format
{
    public static function rupiah(int|float|null $amount, bool $withPrefix = true): string
    {
        $formatted = number_format((float) $amount, 0, ',', '.');

        return $withPrefix ? 'Rp'.$formatted : $formatted;
    }

    /**
     * Short form for dense tables and cards: 1.2 jt / 450 rb.
     */
    public static function rupiahShort(int|float|null $amount): string
    {
        $amount = (float) $amount;

        if ($amount >= 1_000_000_000) {
            return number_format($amount / 1_000_000_000, 1, ',', '.').' M';
        }

        if ($amount >= 1_000_000) {
            return number_format($amount / 1_000_000, 1, ',', '.').' jt';
        }

        if ($amount >= 1_000) {
            return number_format($amount / 1_000, 0, ',', '.').' rb';
        }

        return number_format($amount, 0, ',', '.');
    }

    public static function date(?CarbonInterface $date): string
    {
        return $date?->translatedFormat('d M Y') ?? '-';
    }

    public static function dateLong(?CarbonInterface $date): string
    {
        return $date?->translatedFormat('d M Y, H:i') ?? '-';
    }

    public static function time(?CarbonInterface $date): string
    {
        return $date?->format('H:i') ?? '-';
    }

    public static function dayMonth(?CarbonInterface $date): string
    {
        return $date?->translatedFormat('d') ?? '--';
    }

    public static function monthShort(?CarbonInterface $date): string
    {
        return $date?->translatedFormat('M') ?? '---';
    }

    public static function dayName(?CarbonInterface $date): string
    {
        return $date?->translatedFormat('l') ?? '-';
    }

    public static function range(?CarbonInterface $from, ?CarbonInterface $to): string
    {
        if (! $from) {
            return '-';
        }

        if (! $to) {
            return $from->translatedFormat('d M Y');
        }

        if ($from->isSameDay($to)) {
            return $from->translatedFormat('d M Y, H:i').' - '.$to->format('H:i');
        }

        return $from->translatedFormat('d M Y, H:i').' - '.$to->translatedFormat('d M Y, H:i');
    }

    public static function number(int|float|null $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    /**
     * "3 hari lagi" style countdown used on the event detail page.
     */
    public static function countdown(?CarbonInterface $date): string
    {
        if (! $date) {
            return '-';
        }

        $diff = now()->diffInSeconds($date, false);

        if ($diff <= 0) {
            return 'Sudah berlangsung';
        }

        $days = intdiv($diff, 86400);
        $hours = intdiv($diff % 86400, 3600);
        $minutes = intdiv($diff % 3600, 60);

        return match (true) {
            $days > 0 => $days.' hari lagi',
            $hours > 0 => $hours.' jam lagi',
            default => $minutes.' menit lagi',
        };
    }
}
