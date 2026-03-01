<?php

declare(strict_types=1);

namespace App\Formatter;

class NumberFormatter
{
    public function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        return match ($currency) {
            'USD' => '$' . number_format($amount, 2, '.', ','),
            'EUR' => number_format($amount, 2, ',', '.') . ' €',
            'GBP' => '£' . number_format($amount, 2, '.', ','),
            default => number_format($amount, 2) . ' ' . $currency,
        };
    }

    public function formatPercentage(float $value, int $decimals = 1): string
    {
        return number_format($value * 100, $decimals) . '%';
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 0) {
            throw new \InvalidArgumentException('Bytes cannot be negative');
        }

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = (int) floor(log($bytes, 1024));
        $index = min($index, count($units) - 1);

        return round($bytes / (1024 ** $index), 2) . ' ' . $units[$index];
    }
}
