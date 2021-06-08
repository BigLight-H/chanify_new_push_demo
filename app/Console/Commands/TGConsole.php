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
        $push = new PushService();
        $this->goPush();
        $push->pushTgError('推送完成!');
    }

    /**
     * 推送
     */
    public function goPush(): void
    {
        $push = new PushService();
        $tg = new pushTgService();
        try {
            $tg->pushR18();
            //$tg->pushRedGif();暂时屏蔽
        } catch (GuzzleException $e) {
            $error = $e->getMessage();
            $str = substr($error,strripos($error,"retry_after")+1);
            $num =  preg_replace('/\D/s', '', $str);
            if ($num) {
                sleep($num);
                $push->pushTgError('沉睡'.$num.'后重新拉起服务!');
                $this->goPush();
            } else {
                $push->pushTgError('定时推送错误:'.$e->getMessage());
            }
        }
    }
}

