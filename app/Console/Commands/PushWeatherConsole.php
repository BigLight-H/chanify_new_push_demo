<?php


namespace App\Console\Commands;


use App\Service\PushService;
use Illuminate\Console\Command;

class PushWeatherConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push_weather';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送每日天气';

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
     * @return mixed
     */
    public function handle()
    {
        $p = new PushService();
        $p->weather();
        $p->live();
    }
}
