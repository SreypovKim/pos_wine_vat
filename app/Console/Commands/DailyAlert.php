<?php

namespace App\Console\Commands;
use App\Notifications\SendNotification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Console\Command;

class DailyAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send due date';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::first();
        $user->notify(new SendNotification());
    }
}
