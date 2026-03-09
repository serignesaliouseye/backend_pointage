<?php

namespace App\Livewire;

use Filament\Widgets\ChartWidget;

class PresencesChart extends ChartWidget
{
    protected ?string $heading = 'Presences Chart';

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
