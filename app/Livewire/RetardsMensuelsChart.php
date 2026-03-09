<?php

namespace App\Livewire;

use Filament\Widgets\ChartWidget;

class RetardsMensuelsChart extends ChartWidget
{
    protected ?string $heading = 'Retards Mensuels Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
