<?php


namespace App\Service;


use App\Models\Channel;
use App\Models\City;
use Illuminate\Support\Facades\Redis;

class ChannelService
{
    /**
     * 获取频道数据
     * @return array
     */
    public static function getChannelInfo(): array
    {
        if (self::existsInfo('channel')) {
            return self::getInfo('channel');
        }
        $data = Channel::all()->toArray();
        self::setInfo($data,'channel');
        return $data;
    }

    /**
     * 获取城市数据
     * @return array
     */
    public static function getCityInfo(): array
    {
        if (self::existsInfo('lives')) {
            return self::getInfo('lives');
        }
        $data = City::all()->toArray();
        self::setInfo($data,'lives');
        return $data;
    }

    /**
     * 缓存数据
     * @param $data
     * @param $key
     */
    public static function setInfo($data, $key): void
    {
        Redis::setex($key, 1800, json_encode($data));
    }

    /**
     * 获取缓存数据
     * @param $key
     * @return mixed
     */
    public static function getInfo($key)
    {
        return json_decode(Redis::get($key), true);
    }

    /**
     * 判断数据是否存在
     * @param $key
     * @return bool
     */
    public static function existsInfo($key): bool
    {
        return (bool)Redis::exists($key);
    }
}
