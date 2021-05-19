<?php


namespace App\Service;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class PushService
{
    public $channel;//频道
    public $city;//城市

    public const PAGE_NUM = 20;//新闻每页条数

    /**
     *  推送12小时天气预报
     */
    public function weather(): void
    {
        $this->channel = ChannelService::getChannelInfo();
        $this->city = ChannelService::getCityInfo();
        foreach ($this->channel as $v) {
            if ($v['type'] == 10) {
                foreach ($this->city as $c) {
                    $cs = explode(',', $c['push']);
                    if(in_array($v['id'], $cs)) {
                        //获取天气信息
                        $data = $this->getWeather(config('mojiapi.weather.name'), config('mojiapi.weather.api'), $c['city'], config('mojiapi.weather.token'));
                        //发送天气信息到手机APP频道
                        $city = $data['city'];
                        $title = $city['name'].'天气预报';
                        $list = $data['hourly'];
                        $con = PHP_EOL.PHP_EOL;
                        $date = date('Y-m-d H:i');
                        foreach ($list as $k => $val) {
                            $date = $val['date'];
                            if ($k < 12) {
                                $con .= '时间：'.$val['hour'].':00'.'    '.'天气：'.$val['condition'].'    '.'气温：'.$val['temp'].'℃'.PHP_EOL.PHP_EOL;
                            }
                        }
                        $content = $date . $title . $con;
                        //推送消息
                        $this->push($content, $v['channel']);
                        echo json_encode(['msg' => '推送24小时天气完成!']);
                    }
                }
            }
        }
    }

    /**
     * 获取生活指数推送到app
     */
    public function live(): void
    {
        $this->channel = ChannelService::getChannelInfo();
        $this->city = ChannelService::getCityInfo();
        foreach ($this->channel as $v) {
            if ($v['type'] == 11) {
                foreach ($this->city as $c) {
                    $cs = explode(',', $c['push']);
                    if(in_array($v['id'], $cs)) {
                        //获取生活指数信息
                        $data = $this->getWeather(config('mojiapi.live.name'), config('mojiapi.live.api'), $c['city'], config('mojiapi.live.token'));
                        $city = $data['city'];
                        $title = $city['name'].'生活指数';
                        $list = current($data['liveIndex']);//获取首个值
                        $con = PHP_EOL.PHP_EOL;
                        $date = date('Y-m-d H:i');
                        foreach ($list as $val) {
                            $date = $val['day'];
                            if(!in_array($val['name'],['洗车指数','息斯敏过敏指数','钓鱼指数','交通指数','旅游指数'])) {
                                $con .= $val['name'].'：'.$val['status'].'，'.$val['desc'].PHP_EOL.PHP_EOL;
                            }
                        }
                        $content = $date . $title . $con;
                        $this->push($content, $v['channel']);
                        echo json_encode(['msg' => '推送生活指数完成!']);
                    }
                }
            }
        }
    }

    /**
     * 获取城市天气预报
     * @param $key
     * @param $api
     * @param $city
     * @param $token
     * @return array|mixed
     */
    public function getWeather($key, $api, $city, $token)
    {
        if (Redis::exists($key.$city)) {
            return json_decode(Redis::get($key.$city), true);
        }

        $headers[]  =  "Content-Type:application/x-www-form-urlencoded; charset=UTF-8";
        $headers[]  =  "Authorization:APPCODE ".config('mojiapi.APPCODE');
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, 'http://aliv18.data.moji.com/whapi/json/alicityweather/'.$api);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER,0);
        //设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'cityId='.$city.'&token='.$token);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        $ret = json_decode($data, true);
        if ($ret['data']) {
            Redis::setex($key.$city, 86300, json_encode($ret['data']));//缓存30分钟
            return $ret['data'];
        }
        return [];
    }

    /**
     * 推送信息到手机APP
     */
    public function push($content, $key): void
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL           => env('PUSH_URL').'/v1/sender/'.$key,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS    => [ 'text' => $content ],
        ]);
        curl_exec($curl);
        curl_close($curl);
    }

    /**
     * 获取今日新闻
     */
    public function getTodayNews(): void
    {
        $this->channel = ChannelService::getChannelInfo();
        foreach ($this->channel as $val) {
            if (!in_array($val['type'], [10, 11])) {
                $this->getNews($val['url'], config('tianapi.KEY'), $val['channel'], $val['name']);
            }
        }
        echo json_encode(['msg' => '推送新闻完成!']);
    }

    /**
     * 获取新闻
     * @param $url
     * @param $key
     * @param $appKey
     * @param $titleName
     * @param int $num
     */
    private function getNews($url, $key, $appKey, $titleName, $num=0): void
    {
        $res = Http::get($url, [
            'key' => $key,
            'num' => $num>0 ? $num : self::PAGE_NUM
        ]);
        if ($res->status() === 200) {
            $data = json_decode($res->body(), true);
            $list = $data['newslist'];
            $title = date('Y-m-d H:i') . $titleName;
            $content = PHP_EOL . PHP_EOL;
            if ($list) {
                $num = 0;
                foreach ($list as $k => $val) {
                    if ($val['url']) {
                        $content .= ($k + 1) . '、' . $val['title'] . ';链接：' . $val['url']. PHP_EOL;
                    } else {
                        $content .= ($k + 1) . '、' . $val['title'] . PHP_EOL;
                    }

                    $num++;
                    if($num==5) {
                        $con = $title . $content;
                        //推送新闻
                        $this->push($con, $appKey);
                        $num = 0;
                        $content = PHP_EOL . PHP_EOL;
                    }
                }
            }
            echo json_encode(['msg' => '推送'.$titleName.'完成!']);
        }
    }
}
