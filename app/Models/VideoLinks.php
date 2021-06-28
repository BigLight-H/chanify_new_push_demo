<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class VideoLinks extends Model
{
    protected $table = 'video_links';
    public $timestamps= false;

    public static function saveVideo($link): void
    {
        $data[] = [
            'link' => $link,
        ];
        self::save($data);
    }


}
