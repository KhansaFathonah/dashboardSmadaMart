<?php

namespace App\Console\Commands\Etl;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:run-initial-load')]
#[Description('Command description')]
class RunInitialLoad extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
