<?php


namespace App\Console\Commands;


use App\Service\PushService;
use App\Service\pushTgService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class TGConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push_tg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送tg频道信息';

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
     * @return void
     */
    public function handle(): void
    {
        $tg = new pushTgService();
        $push = new PushService();
        try {
            $tg->pushR18();
            $tg->pushRedGif();
            $push->pushTgError('推送完成!');
        } catch (GuzzleException $e) {
            $push->pushTgError('定时推送错误:'.$e->getMessage());
        }
    }
}
