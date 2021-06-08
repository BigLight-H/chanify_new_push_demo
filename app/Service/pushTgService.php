<?php


namespace App\Service;
use GuzzleHttp;
use Illuminate\Support\Facades\Redis;

class pushTgService
{
    /**
     * 推送r18消息到指定频道
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function pushR18(): void
    {
        $start = env('R18_PAGE_START');
        $end = env('R18_PAGE_END');
        $url = env('R18_URL');
        $pick_url = env('R18_PICK_URL');
        $client = new GuzzleHttp\Client();
        $client->request('GET', $pick_url.$start.'&end='.$end.'&url='.$url);
    }

    /**
     * 获取订阅列表
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function pushRedGif(): void
    {
        $limit = 100;
        $ret = $this->getFollows();
        if ($ret['follows']??null) {
            $this->getFollowsDetail($ret['follows'], $limit);
        } else {
            (new PushService())->pushTgError('跟随列表不能为空,请补充跟随列表!');
        }
    }

    /**
     * 便利订阅详情数据
     * @param $arr
     * @param $limit
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function getFollowsDetail($arr, $limit): void
    {
        foreach($arr as $val) {
            $this->getFollowsLists($val['username'], $limit);
        }
    }

    /**
     * 获取跟随列表
     * @param $username
     * @param $limit
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    protected function getFollowsLists($username, $limit): void
    {
        $base_path = env('BRATTY_SOFIA_URL');
        $chat_id = env('CHAT_ID');
        $telegram_api_url = env('TELEGRAM_API_URL');
        $url = str_replace('username', $username, $base_path);
        $ret = $this->httpPush($url, $limit);

        if($ret['gfycats']??null) {
            $num = 0;
            foreach ($ret['gfycats'] as $val) {
                if(!array_key_exists('mobile', $val['content_urls'])) {
                    continue;
                }
                $mp4 = $val['content_urls']['mobile']['url'];
                if(!Redis::HEXISTS('follows_list_detail_urls', $mp4)) {
                    if($num>10) {
                        sleep(10);
                        $num = 0;
                    }
                    //推送到tg机器人
                    $client = new GuzzleHttp\Client();
                    $client->request('GET', $telegram_api_url.'/sendVideo?video='.$mp4.'&chat_id='.$chat_id);
                    //连接加入redis
                    Redis::hset('follows_list_detail_urls', $mp4, 1);
                    $num++;
                }
            }
        }
    }

    /**
     * @param $url
     * @param $limit
     * @return mixed
     */
    private function httpPush($url, $limit)
    {
        if (Redis::exists($url)) {
            $response = Redis::get($url);
        } else {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url . $limit,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            //存入redis
            Redis::setex($url, 86400, $response);//缓存一天
        }
        //显示获得的数据
        return json_decode($response, true);
    }

    /**
     * 获取收藏列表
     * @return array|mixed
     */
    public function getFollows()
    {
        $url = env('FOLLOWS_URL');
        if (Redis::exists($url)) {
            $response = Redis::get($url);
        }
        //显示获得的数据
        return json_decode($response, true) ?? [];
    }

}
