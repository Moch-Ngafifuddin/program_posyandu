<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\LayananFonnte;

class ProsesKirimWa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $noHp;
    protected $pesanFinal;

    public function __construct($noHp, $pesanFinal)
    {
        $this->noHp = $noHp;
        $this->pesanFinal = $pesanFinal;
    }

    public function handle()
    {
        LayananFonnte::kirimPesan($this->noHp, $this->pesanFinal);
    }
}
