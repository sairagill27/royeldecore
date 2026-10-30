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
    private const HEM_M        = 0.30;        // 30 cm extra curtain length for the hem and the heading

    // Names shown in error messages ("The room length field is required.")
    public static function attributes(): array
    {
        return [
            'unit'      => 'unit',
            'length'    => 'room length',
            'width'     => 'room width',
            'height'    => 'wall height',
            'doors'     => 'number of doors',
            'windows'   => 'number of windows',
            'coats'     => 'number of coats',
            'coverage'  => 'paint coverage',
            'tin'       => 'tin size',
            'ceiling'   => 'ceiling option',
            'waste'     => 'waste allowance',
            'pack'      => 'pack coverage',
            'price'     => 'price',
            'rollw'     => 'roll width',
            'rolll'     => 'roll length',
            'repeat'    => 'pattern repeat',
            'drop'      => 'curtain drop',
            'fullness'  => 'fullness',
            'panelw'    => 'panel width',
            'budget'    => 'budget',
            'items'     => 'list of items',
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
            'wallpaper' => [
                'unit'    => ['required', 'in:m,ft'],
                'length'  => $len,
                'width'   => $len,
                'height'  => ['required', 'numeric', 'min:' . ($ft ? 3 : 1), 'max:' . ($ft ? 20 : 6)],
                'doors'   => ['nullable', 'integer', 'min:0', 'max:20'],
                'windows' => ['nullable', 'integer', 'min:0', 'max:30'],
                'rollw'   => ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 6.6 : 2)],
                'rolll'   => ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 330 : 100)],
                'repeat'  => ['nullable', 'numeric', 'min:0', 'max:' . ($ft ? 6.6 : 2)],
                'price'   => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            ],
            'curtain' => [
                'unit'     => ['required', 'in:m,ft'],
                'width'    => ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 33 : 10)],
                'drop'     => ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 16 : 5)],
                'fullness' => ['required', 'in:1.5,2,2.5'],
                'panelw'   => ['required', 'numeric', 'gt:0', 'max:' . ($ft ? 16 : 5)],
            ],
            // The budget has no feet or metres. Items are checked one by one inside budget().
            'budget' => [
                'budget'        => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
                'items'         => ['required', 'array', 'min:1', 'max:30'],
                'items.*.name'  => ['nullable', 'string', 'max:60'],
                'items.*.cost'  => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            ],
            default => [],
        };
    }

    // Works out the answer from values that have already been checked
    public static function calculate(string $tool, array $data): array
    {
        return match ($tool) {
            'paint'     => self::paint($data),
            'flooring'  => self::flooring($data),
            'wallpaper' => self::wallpaper($data),
            'curtain'   => self::curtain($data),
            'budget'    => self::budget($data),
            default     => throw new \InvalidArgumentException("Unknown tool [{$tool}]"),
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

    // ---------- WALLPAPER ----------
    private static function wallpaper(array $d): array
    {
        $ft = ($d['unit'] ?? 'm') === 'ft';
        $k  = $ft ? self::FT_TO_M : 1.0;

        // Everything is worked out in metres
        $length  = (float) $d['length'] * $k;
        $width   = (float) $d['width'] * $k;
        $height  = (float) $d['height'] * $k;
        $doors   = (int) ($d['doors'] ?? 0);
        $windows = (int) ($d['windows'] ?? 0);
        $rollW   = (float) $d['rollw'] * $k;
        $rollL   = (float) $d['rolll'] * $k;
        $repeat  = isset($d['repeat']) && $d['repeat'] !== '' ? (float) $d['repeat'] * $k : 0.0;
        $price   = isset($d['price']) && $d['price'] !== '' ? (float) $d['price'] : null;

        $perimeter = 2 * ($length + $width);
        $walls     = $perimeter * $height;
        $openings  = $doors * self::DOOR_M2 + $windows * self::WINDOW_M2;

        if ($openings >= $walls) {
            throw ValidationException::withMessages([
                ($doors > 0 ? 'doors' : 'windows') => 'The doors and windows take up as much space as the walls. Please check the numbers.',
            ]);
        }

        // One strip is as tall as the wall, plus the pattern repeat so the pattern lines up
        $strip         = $height + $repeat;
        $stripsPerRoll = (int) floor(round($rollL / $strip, 6));

        if ($stripsPerRoll < 1) {
            throw ValidationException::withMessages([
                'rolll' => 'One roll is shorter than a single wall-height strip. Please check the roll length, the wall height and the pattern repeat.',
            ]);
        }

        // Doors and windows save some strips: take off the width they cover
        $coveredWidth = ($doors * 0.9) + ($windows * 1.2);
        $stripsNeeded = max(1, self::ceilSafe(($perimeter - min($coveredWidth, $perimeter * 0.8)) / $rollW));
        $rolls        = self::ceilSafe($stripsNeeded / $stripsPerRoll);

        $areaShown = $ft ? ($walls - $openings) / self::FT2_TO_M2 : ($walls - $openings);
        $areaUnit  = $ft ? 'ft²' : 'm²';

        $rows = [
            [
                'label' => 'Rolls to buy',
                'value' => $rolls . ' ' . Str::plural('roll', $rolls),
                'note'  => $stripsNeeded . ' ' . Str::plural('strip', $stripsNeeded) . ' needed, ' . $stripsPerRoll . ' ' . Str::plural('strip', $stripsPerRoll) . ' per roll',
                'main'  => true,
            ],
            [
                'label' => 'Wall area',
                'value' => self::fmt($areaShown, 1) . ' ' . $areaUnit,
                'note'  => 'Walls minus doors and windows',
            ],
        ];

        if ($price !== null) {
            $rows[] = [
                'label' => 'Estimated cost',
                'value' => config('shop.currency') . ' ' . number_format($rolls * $price),
                'note'  => 'For ' . $rolls . ' ' . Str::plural('roll', $rolls) . ', before delivery',
            ];
        }

        return [
            'results' => $rows,
            'tips'    => [
                'Buy one roll extra if you can. A later batch can differ slightly in colour.',
                'A pattern that slips half a repeat from one strip to the next needs a larger repeat figure, so enter the repeat printed on the roll.',
                'Check the batch number on every roll, and hang the rolls from the same batch together.',
            ],
        ];
    }

    // ---------- CURTAINS ----------
    private static function curtain(array $d): array
    {
        $ft = ($d['unit'] ?? 'm') === 'ft';
        $k  = $ft ? self::FT_TO_M : 1.0;

        $width    = (float) $d['width'] * $k;
        $drop     = (float) $d['drop'] * $k;
        $fullness = (float) $d['fullness'];
        $panelW   = (float) $d['panelw'] * $k;

        $fabricW = $width * $fullness;                  // the fabric width needed across the window
        $cutLen  = $drop + self::HEM_M;                 // each length, with the hem and the heading
        $panels  = max(1, self::ceilSafe($fabricW / $panelW));
        $fabricL = $cutLen * $panels;                   // the total length of fabric to buy

        // Shown back in the unit the visitor chose
        $show   = fn (float $m): string => $ft ? self::fmt($m / $k, 1) . ' ft' : self::fmt($m, 2) . ' m';
        $buyLen = $ft ? self::fmt($fabricL / 0.9144, 1) . ' yards' : self::fmt($fabricL, 2) . ' m';

        return [
            'results' => [
                [
                    'label' => 'Panels needed',
                    'value' => $panels . ' ' . Str::plural('panel', $panels),
                    'note'  => 'Each panel ' . $show($panelW) . ' wide',
                    'main'  => true,
                ],
                [
                    'label' => 'Fabric to buy',
                    'value' => $buyLen,
                    'note'  => $panels . ' ' . Str::plural('length', $panels) . ' of ' . $show($cutLen) . ' each',
                ],
                [
                    'label' => 'Fabric width across the window',
                    'value' => $show($fabricW),
                    'note'  => 'The window width times a fullness of ' . self::fmt($fullness, 1),
                ],
            ],
            'tips' => [
                'Fullness of 2 is the usual choice: it gives soft, even folds. Use 2.5 for a rich, luxurious look.',
                'The 30 cm allowance is typical. Your tailor may need more for a deep hem or a special heading.',
                'Measure the drop from the curtain rail to where you want the curtain to end.',
            ],
        ];
    }

    // ---------- ROOM BUDGET ----------
    private static function budget(array $d): array
    {
        $budget = isset($d['budget']) && $d['budget'] !== '' ? (float) $d['budget'] : null;
        $cur    = config('shop.currency');

        // Keep only the rows that have a cost; a row without a name is called "Item"
        $items = [];
        foreach ($d['items'] ?? [] as $row) {
            $cost = isset($row['cost']) && $row['cost'] !== '' ? (float) $row['cost'] : 0.0;
            if ($cost <= 0) {
                continue;
            }
            $name    = Str::squish((string) ($row['name'] ?? ''));
            $items[] = ['name' => $name !== '' ? $name : 'Item', 'cost' => $cost];
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Please add the cost of at least one item.',
            ]);
        }

        $total = array_sum(array_column($items, 'cost'));
        $money = fn (float $n): string => $cur . ' ' . number_format($n);

        $rows = [[
            'label' => 'Total spend',
            'value' => $money($total),
            'note'  => count($items) . ' ' . Str::plural('item', count($items)),
            'main'  => true,
        ]];

        if ($budget !== null && $budget > 0) {
            $diff   = $budget - $total;
            $rows[] = $diff >= 0
                ? ['label' => 'Money left', 'value' => $money($diff), 'note' => 'Your budget is ' . $money($budget) . '. You have used ' . self::fmt($total / $budget * 100, 0) . '% of it.']
                : ['label' => 'Over budget by', 'value' => $money(abs($diff)), 'note' => 'Your budget is ' . $money($budget) . '. Try removing or swapping an item.'];
        }

        // The biggest items first, with their share of the total
        usort($items, fn ($a, $b) => $b['cost'] <=> $a['cost']);
        foreach (array_slice($items, 0, 5) as $item) {
            $rows[] = [
                'label' => $item['name'],
                'value' => $money($item['cost']),
                'note'  => self::fmt($item['cost'] / $total * 100, 0) . '% of your total',
            ];
        }

        return [
            'results' => $rows,
            'tips'    => [
                'Decide the big items first, such as the sofa or the curtains. Then fit the smaller pieces around them.',
                'Keep about 10% of your budget aside for delivery and things you forgot.',
                'The list shows your five biggest items. The total includes every item.',
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