<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Console\Command;

class GenererQrQuotidien extends Command
{
    protected $signature = 'pointage:generer-qr {--days=1 : Nombre de jours à générer}';
    
    protected $description = 'Génère les QR codes quotidiens pour le pointage';

    protected $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        parent::__construct();
        $this->qrCodeService = $qrCodeService;
    }

    public function handle()
    {
        $days = $this->option('days');
        $admins = User::where('role', 'admin')->get();
        
        $this->info("Génération des QR codes pour les {$days} prochains jours...");
        
        $bar = $this->output->createProgressBar(count($admins) * $days);
        $bar->start();

        foreach ($admins as $admin) {
            for ($i = 0; $i < $days; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                $this->qrCodeService->genererQrPourDate($admin, $date);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('QR codes générés avec succès !');
    }
}