<?php


namespace App\Http\Controllers;

use App\Models\RedgifToken;
use App\Service\PushService;
use GuzzleHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RedGifsController extends Controller
{
    /**
     * 获取订阅列表
     * @param Request $request
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function index(Request $request): void
    {
        $limit = $request->input('limit');
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
            foreach ($ret['gfycats'] as $val) {
                $mp4 = $val['content_urls']['mobile']['url'];
                if(!array_key_exists('mobile', $val['content_urls'])) {
                    continue;
                }
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
     * 写入新的收藏数据
     * @param Request $request
     */
    public function setFollows(Request $request): void
    {
        $token = (new RedgifToken())->where('id', 1)->value('token');
        $limit = $request->input('limit', 100);
        $url = env('FOLLOWS_URL');
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
        $ret = json_decode($response, true);
        if($ret['follows']) {
            //删除之前的redis缓存
            Redis::del($url);
            //存入新数据redis
            Redis::set($url, $response);//缓存一天
            echo json_encode('写入成功!');
        }
        echo json_encode('写入失败!');
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
