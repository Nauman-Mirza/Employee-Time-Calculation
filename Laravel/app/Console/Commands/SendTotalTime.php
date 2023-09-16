<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Http\Controllers\userController;

class SendTotalTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-total-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sending the work time of employee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userController = new userController();
        $userController->go();
    }   
}
