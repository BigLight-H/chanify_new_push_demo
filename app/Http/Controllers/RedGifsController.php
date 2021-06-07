<?php


namespace App\Http\Controllers;

use GuzzleHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RedGifsController extends Controller
{
    /**
     * 获取订阅列表
     * @param Request $request
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $token = $request->input('token');
        $url = env('FOLLOWS_URL');

        $ret = $this->httpPush($url, $limit, $token);
        if ($ret['follows']??null) {
            $this->getFollowsDetail($ret['follows'], $limit, $token);
        }
    }

    /**
     * 便利订阅详情数据
     * @param $arr
     * @param $limit
     * @param $token
     */
    public function getFollowsDetail($arr, $limit, $token): void
    {
        foreach($arr as $val) {
            $this->getFollowsLists($val['username'], $limit, $token);
        }
    }

    /**
     * 获取跟随列表
     * @param $username
     * @param $limit
     * @param $token
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    protected function getFollowsLists($username, $limit, $token): void
    {
        $base_path = env('BRATTY_SOFIA_URL');
        $chat_id = env('CHAT_ID');
        $telegram_api_url = env('TELEGRAM_API_URL');
        $url = str_replace('username', $username, $base_path);
        $ret = $this->httpPush($url, $limit, $token);

        if($ret['gfycats']) {
            foreach ($ret['gfycats'] as $val) {
                $mp4 = $val['content_urls']['mobile']['url'];
                if(!Redis::HEXISTS('follows_list_detail_urls', $mp4)) {
                    //推送到tg机器人
                    $client = new GuzzleHttp\Client();
                    $client->request('GET', $telegram_api_url.'/sendVideo?video='.$mp4.'&chat_id='.$chat_id);
                    //连接加入redis
                    Redis::hset('follows_list_detail_urls', $mp4, 1);
                }
            }
        }
    }

    /**
     * @param $url
     * @param $limit
     * @param $token
     * @return mixed
     */
    private function httpPush($url, $limit, $token)
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
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            //存入redis
            Redis::setex($url, 86400, $response);//缓存一天
        }
        //显示获得的数据
        return json_decode($response, true);
    }

}
