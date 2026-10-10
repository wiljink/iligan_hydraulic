<?php

namespace App\Models\Concerns;

trait GeneratesDocumentNumber
{
    /**
     * Next daily-sequenced document number, e.g. CR-20261010-0001.
     */
    protected static function nextDocumentNumber(string $prefix, string $column): string
    {
        $prefix = $prefix.'-'.now()->format('Ymd').'-';
        $last = static::where($column, 'like', $prefix.'%')->orderByDesc('id')->first();

        $sequence = $last ? (int) substr($last->{$column}, -4) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
