<?php


namespace App\Console\Commands;


use App\Service\PushService;
use Illuminate\Console\Command;

class PushNewsConsole extends Command
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
    protected $description = '推送每日新闻';

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
        $p->getTodayNews();
        if (date('d',time()) === '01') {
            $p->delPushNewsOld();
        }
    }
}
