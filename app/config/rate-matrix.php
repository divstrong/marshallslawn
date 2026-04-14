<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GL1 Rate Matrix — Turf 1000 Sq Ft
    |--------------------------------------------------------------------------
    |
    | Lot-size tiers used for pricing lookup on the Estimate Builder.
    | "from" and "to" are in thousands of square feet.
    |
    | Overflow rule: every 2.5 (thousand sqft) over 55 adds $5.55 to rate,
    | 0.1 to budgeted hours, and $6.00 to budgeted cost.
    |
    */

    'tiers' => [
        ['from' =>  0,    'to' =>  2.999, 'rate' =>  54.60, 'hours' => 0.25, 'cost' =>  27.28],
        ['from' =>  3,    'to' =>  4.999, 'rate' =>  54.60, 'hours' => 0.25, 'cost' =>  32.13],
        ['from' =>  5,    'to' =>  7.499, 'rate' =>  70.35, 'hours' => 0.25, 'cost' =>  38.19],
        ['from' =>  7.5,  'to' =>  9.999, 'rate' =>  75.70, 'hours' => 0.25, 'cost' =>  44.25],
        ['from' => 10,    'to' => 14.999, 'rate' =>  81.10, 'hours' => 0.25, 'cost' =>  56.38],
        ['from' => 15,    'to' => 19.999, 'rate' =>  91.95, 'hours' => 0.25, 'cost' =>  68.50],
        ['from' => 20,    'to' => 24.999, 'rate' => 102.75, 'hours' => 0.25, 'cost' =>  80.63],
        ['from' => 25,    'to' => 29.999, 'rate' => 113.50, 'hours' => 0.30, 'cost' =>  92.75],
        ['from' => 30,    'to' => 34.999, 'rate' => 124.45, 'hours' => 0.35, 'cost' => 104.88],
        ['from' => 35,    'to' => 39.999, 'rate' => 135.20, 'hours' => 0.40, 'cost' => 117.00],
        ['from' => 40,    'to' => 44.999, 'rate' => 152.20, 'hours' => 0.45, 'cost' => 129.13],
        ['from' => 45,    'to' => 49.999, 'rate' => 162.75, 'hours' => 0.50, 'cost' => 141.25],
        ['from' => 50,    'to' => 55,     'rate' => 173.25, 'hours' => 0.50, 'cost' => 153.38],
    ],

    'overflow' => [
        'every'          => 2.5,   // thousand sqft increment over the max tier
        'above'          => 55,    // thousand sqft threshold
        'rate_add'       => 5.55,
        'hours_add'      => 0.1,
        'cost_add'       => 6.00,
    ],

    'lot_size_options' => [
        '0-3'     => 'Under 3,000 sq ft',
        '3-5'     => '3,000 – 4,999 sq ft',
        '5-7.5'   => '5,000 – 7,499 sq ft',
        '7.5-10'  => '7,500 – 9,999 sq ft',
        '10-15'   => '10,000 – 14,999 sq ft',
        '15-20'   => '15,000 – 19,999 sq ft',
        '20-25'   => '20,000 – 24,999 sq ft',
        '25-30'   => '25,000 – 29,999 sq ft',
        '30-35'   => '30,000 – 34,999 sq ft',
        '35-40'   => '35,000 – 39,999 sq ft',
        '40-45'   => '40,000 – 44,999 sq ft',
        '45-50'   => '45,000 – 49,999 sq ft',
        '50-55'   => '50,000 – 55,000 sq ft',
        '55+'     => 'Over 55,000 sq ft',
    ],

];
