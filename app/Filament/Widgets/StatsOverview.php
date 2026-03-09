<?php
// app/Filament/Widgets/StatsOverview.php
namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Pointage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $aujourdhui = now()->format('Y-m-d');
        
        $totalStagiaires = User::where('role', 'stagiaire')->count();
        $totalCoachs = User::where('role', 'coach')->count();
        
        $presencesAujourdhui = Pointage::whereDate('date', $aujourdhui)
            ->whereIn('statut', ['present', 'retard'])
            ->count();
        
        $retardsMois = Pointage::whereMonth('date', now()->month)
            ->where('statut', 'retard')
            ->count();

        return [
            Stat::make('Total Stagiaires', $totalStagiaires)
                ->description('Inscrits')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            
            Stat::make('Présences aujourd\'hui', $presencesAujourdhui)
                ->description($totalStagiaires > 0 ? round(($presencesAujourdhui / $totalStagiaires) * 100, 1) . '% de présence' : '0%')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),
            
            Stat::make('Retards ce mois', $retardsMois)
                ->description('À surveiller')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}