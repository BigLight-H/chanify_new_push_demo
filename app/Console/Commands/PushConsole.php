<?php


namespace App\Console\Commands;


use App\Service\PushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PushConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push_news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送新闻消息';

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
        $p->getTodayNews();
        Log::info('zddddd');
    }
}
