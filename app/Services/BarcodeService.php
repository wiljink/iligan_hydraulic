<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Throwable;

class BarcodeService
{
    /**
     * Check if a 13-digit string has a valid GS1 EAN-13 check digit.
     */
    public static function isValidEan13(string $code): bool
    {
        if (! preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        $checkDigit = static::calculateEan13CheckDigit(substr($code, 0, 12));

        return (int) $code[12] === $checkDigit;
    }

    /**
     * Calculate the EAN-13 modulo-10 check digit for a 12-digit prefix.
     */
    public static function calculateEan13CheckDigit(string $digits12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $digits12[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Determine the optimal barcode format type for Picqer barcode generator.
     */
    public static function resolveType(string $code): string
    {
        if (static::isValidEan13($code)) {
            return BarcodeGeneratorPNG::TYPE_EAN_13;
        }

        // Default to universal Code 128 (accepts any alphanumeric string)
        return BarcodeGeneratorPNG::TYPE_CODE_128;
    }

    /**
     * Generate barcode image bytes in PNG format.
     */
    public static function generatePng(string $code, int $scale = 2, int $height = 60): string
    {
        $generator = new BarcodeGeneratorPNG();
        $code = trim($code);

        if ($code === '') {
            $code = 'UNKNOWN';
        }

        $type = static::resolveType($code);

        try {
            return $generator->getBarcode($code, $type, $scale, $height);
        } catch (Throwable $e) {
            // Fallback to Code 128
            try {
                return $generator->getBarcode($code, BarcodeGeneratorPNG::TYPE_CODE_128, $scale, $height);
            } catch (Throwable $e2) {
                // Return a 1x1 transparent PNG fallback if all else fails
                return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
            }
        }
    }

    /**
     * Generate barcode in SVG format (crisp vector for high-DPI printing).
     */
    public static function generateSvg(string $code, int $scale = 2, int $height = 60): string
    {
        $generator = new BarcodeGeneratorSVG();
        $code = trim($code);

        if ($code === '') {
            $code = 'UNKNOWN';
        }

        $type = static::resolveType($code);

        try {
            return $generator->getBarcode($code, $type, $scale, $height);
        } catch (Throwable $e) {
            // Fallback to Code 128
            try {
                return $generator->getBarcode($code, BarcodeGeneratorSVG::TYPE_CODE_128, $scale, $height);
            } catch (Throwable $e2) {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40"><text x="10" y="25" fill="red">Barcode Error</text></svg>';
            }
        }
    }

    /**
     * Generate an inline Data URI for SVG barcode to embed directly in HTML.
     */
    public static function generateSvgDataUri(string $code, int $scale = 2, int $height = 60): string
    {
        $svg = static::generateSvg($code, $scale, $height);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Generate a new, unique 13-digit barcode with a valid GS1 EAN-13 check digit.
     * Uses prefix '200' reserved for internal retail inventory.
     */
    public static function generateUniqueEan13(): string
    {
        do {
            $random9 = str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT);
            $prefix12 = '200'.$random9;
            $checkDigit = static::calculateEan13CheckDigit($prefix12);
            $barcode = $prefix12.$checkDigit;
        } while (\App\Models\Product::where('barcode', $barcode)->exists());

        return $barcode;
    }
}
