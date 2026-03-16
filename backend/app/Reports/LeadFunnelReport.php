<?php

namespace App\Reports;

use koolreport\KoolReport;
use koolreport\laravel\Friendship;

class LeadFunnelReport extends KoolReport
{
    use Friendship;

    public function settings(): array
    {
        return [
            'assets' => [
                'path' => public_path('koolreport_assets'),
                'url' => '/koolreport_assets',
            ],
            'dataSources' => [
                'rows' => [
                    'class' => '\koolreport\datasources\ArrayDataSource',
                    'dataFormat' => 'associate',
                    'data' => $this->params['rows'] ?? [],
                ],
            ],
        ];
    }

    protected function setup(): void
    {
        $this->src('rows')
            ->pipe($this->dataStore('funnel'));
    }
}
