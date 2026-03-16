<?php

use koolreport\d3\FunnelChart;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .report-wrap {
            padding: 8px 8px 0;
        }
    </style>
</head>
<body>
<div class="report-wrap">
    <?php
    FunnelChart::create([
        'dataSource' => $this->dataStore('funnel'),
        'width' => '100%',
        'height' => 280,
        'columns' => [
            'label',
            'amount' => [
                'type' => 'number',
            ],
        ],
        'chart' => [
            'bottomWidth' => 0.30,
            'bottomPinch' => 0,
            'inverted' => false,
            'animate' => 0,
            'curve' => [
                'enabled' => false,
                'height' => 20,
            ],
        ],
        'block' => [
            'dynamicHeight' => false,
            'dynamicSlope' => false,
            'barOverlay' => false,
            'minHeight' => 40,
            'highlight' => false,
            'fill' => [
                'type' => 'solid',
                'scale' => [
                    '#1f77b4', // blue
                    '#ff7f0e', // orange
                    '#2ca02c', // green
                    '#d62728', // red
                    '#9467bd',
                    '#8c564b',
                    '#e377c2',
                    '#7f7f7f',
                    '#bcbd22',
                    '#17becf',
                ],
            ],
        ],
        'label' => [
            'enabled' => true,
            'fontSize' => '14px',
            'fill' => '#ffffff',
            'format' => '{l}',
        ],
        'tooltip' => [
            'enabled' => true,
            'format' => '{l}',
        ],
    ]);
    ?>
</div>
</body>
</html>
