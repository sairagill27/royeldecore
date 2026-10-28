<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// All the decor sums live here, in one place. The pages only show the answers.
class DecorCalculator
{
    private const FT_TO_M      = 0.3048;      // feet to metres
    private const FT2_TO_M2    = 0.09290304;  // square feet to square metres
    private const DOOR_M2      = 1.9;         // an average door
    private const WINDOW_M2    = 1.5;         // an average window
    private const PAINT_EXTRA  = 1.10;        // 10% extra for uneven walls and touch-ups

    // Names shown in error messages ("The room length field is required.")
    public static function attributes(): array
    {
        return [
            'unit'     => 'unit',
            'length'   => 'room length',
            'width'    => 'room width',
            'height'   => 'wall height',
            'doors'    => 'number of doors',
            'windows'  => 'number of windows',
            'coats'    => 'number of coats',
            'coverage' => 'paint coverage',
            'tin'      => 'tin size',
            'ceiling'  => 'ceiling option',
            'waste'    => 'waste allowance',
            'pack'     => 'pack coverage',
            'price'    => 'price per pack',
        ];
    }

    // The checks for each tool. The limits depend on the unit (feet or metres).
    public static function rules(string $tool, string $unit): array
    {
        $ft  = $unit === 'ft';
        $len = ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 330 : 100)];

        return match ($tool) {
            'paint' => [
                'unit'     => ['required', 'in:m,ft'],
                'length'   => $len,
                'width'    => $len,
                'height'   => ['required', 'numeric', 'min:' . ($ft ? 3 : 1), 'max:' . ($ft ? 20 : 6)],
                'doors'    => ['nullable', 'integer', 'min:0', 'max:20'],
                'windows'  => ['nullable', 'integer', 'min:0', 'max:30'],
                'coats'    => ['required', 'integer', 'min:1', 'max:4'],
                'coverage' => ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 1500 : 150)],
                'tin'      => ['required', 'numeric', 'gte:0.5', 'max:50'],
                'ceiling'  => ['nullable', 'boolean'],
            ],
            'flooring' => [
                'unit'   => ['required', 'in:m,ft'],
                'length' => $len,
                'width'  => $len,
                'waste'  => ['nullable', 'numeric', 'min:0', 'max:30'],
                'pack'   => ['nullable', 'numeric', 'gt:0', 'max:' . ($ft ? 540 : 50)],
                'price'  => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            ],
            default => [],
        };
    }

    // Works out the answer from values that have already been checked
    public static function calculate(string $tool, array $data): array
    {
        return match ($tool) {
            'paint'    => self::paint($data),
            'flooring' => self::flooring($data),
            default    => throw new \InvalidArgumentException("Unknown tool [{$tool}]"),
        };
    }

    // ---------- PAINT ----------
    private static function paint(array $d): array
    {
        $ft = ($d['unit'] ?? 'm') === 'ft';
        $k  = $ft ? self::FT_TO_M : 1.0;

        // Everything is worked out in metres
        $length   = (float) $d['length'] * $k;
        $width    = (float) $d['width'] * $k;
        $height   = (float) $d['height'] * $k;
        $doors    = (int) ($d['doors'] ?? 0);
        $windows  = (int) ($d['windows'] ?? 0);
        $coats    = (int) $d['coats'];
        $ceiling  = filter_var($d['ceiling'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $coverage = (float) $d['coverage'] * ($ft ? self::FT2_TO_M2 : 1.0);   // square metres per litre
        $tin      = (float) $d['tin'];

        $walls    = 2 * ($length + $width) * $height;
        $openings = $doors * self::DOOR_M2 + $windows * self::WINDOW_M2;

        if ($openings >= $walls) {
            throw ValidationException::withMessages([
                ($doors > 0 ? 'doors' : 'windows') => 'The doors and windows take up as much space as the walls. Please check the numbers.',
            ]);
        }

        $area   = $walls - $openings + ($ceiling ? $length * $width : 0);
        $litres = ceil(round($area * $coats / $coverage * self::PAINT_EXTRA * 10, 6)) / 10;   // rounded up to 0.1 L
        $tins   = self::ceilSafe($litres / $tin);
        $left   = max(0.0, $tins * $tin - $litres);

        $areaShown = $ft ? $area / self::FT2_TO_M2 : $area;
        $areaUnit  = $ft ? 'ft²' : 'm²';

        return [
            'results' => [
                [
                    'label' => 'Paint needed',
                    'value' => self::fmt($litres, 1) . ($litres == 1.0 ? ' litre' : ' litres'),
                    'note'  => 'Includes 10% extra for uneven walls and touch-ups',
                    'main'  => true,
                ],
                [
                    'label' => 'Tins to buy',
                    'value' => $tins . ' ' . Str::plural('tin', $tins) . ' of ' . self::fmt($tin, 1) . ' L',
                    'note'  => 'About ' . self::fmt($left, 1) . ' L left over',
                ],
                [
                    'label' => 'Area to paint',
                    'value' => self::fmt($areaShown, 1) . ' ' . $areaUnit,
                    'note'  => 'Walls minus doors and windows' . ($ceiling ? ', plus the ceiling' : ''),
                ],
            ],
            'tips' => [
                'Dark or bold colours often need an extra coat, so add one if you are changing colour a lot.',
                'Buy all the paint for one room at the same time, so the colour matches.',
                'Coverage varies by brand and finish. Use the figure printed on your tin.',
            ],
        ];
    }

    // ---------- FLOORING ----------
    private static function flooring(array $d): array
    {
        $ft    = ($d['unit'] ?? 'm') === 'ft';
        $unit  = $ft ? 'ft²' : 'm²';
        $waste = isset($d['waste']) && $d['waste'] !== '' ? (float) $d['waste'] : 10.0;
        $pack  = isset($d['pack'])  && $d['pack']  !== '' ? (float) $d['pack']  : null;
        $price = isset($d['price']) && $d['price'] !== '' ? (float) $d['price'] : null;

        $area = (float) $d['length'] * (float) $d['width'];
        $buy  = $area * (1 + $waste / 100);

        $rows = [
            [
                'label' => 'Area to buy',
                'value' => self::fmt($buy, 2) . ' ' . $unit,
                'note'  => 'Includes ' . self::fmt($waste, 1) . '% for cuts and waste',
                'main'  => $pack === null,
            ],
            [
                'label' => 'Room area',
                'value' => self::fmt($area, 2) . ' ' . $unit,
            ],
        ];

        if ($pack !== null) {
            $packs = self::ceilSafe($buy / $pack);

            array_unshift($rows, [
                'label' => 'Packs to buy',
                'value' => $packs . ' ' . Str::plural('pack', $packs),
                'note'  => 'Covers ' . self::fmt($packs * $pack, 2) . ' ' . $unit . ' in total',
                'main'  => true,
            ]);

            if ($price !== null) {
                $rows[] = [
                    'label' => 'Estimated cost',
                    'value' => config('shop.currency') . ' ' . number_format($packs * $price),
                    'note'  => 'For ' . $packs . ' ' . Str::plural('pack', $packs) . ', before delivery',
                ];
            }
        }

        return [
            'results' => $rows,
            'tips'    => [
                'Diagonal layouts and patterns waste more, so try 15% instead of 10%.',
                'Keep a few spare pieces. A later batch can differ slightly in colour.',
                'Check the pack size on the box. It differs between products.',
            ],
        ];
    }

    // ---------- helpers ----------

    // 22.000000000000004 / 2.2 must give 10 packs, not 11
    private static function ceilSafe(float $x): int
    {
        return (int) ceil(round($x, 6));
    }

    // 40.10 becomes "40.1" and 45.0 becomes "45"
    private static function fmt(float $n, int $decimals = 1): string
    {
        $s = number_format($n, $decimals, '.', ',');

        return ($decimals > 0 && str_contains($s, '.')) ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}