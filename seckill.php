<?php
/**
 * 秒杀测试
 */
require_once 'redis.class.php';

class Seckill
{
    public function buy($goods_id = 0, $uid = 0)
    {
        try {
            //判断goodsID
            if (!$goods_id) {
                exit("拍品id不正确!");
            }
            //判断uid是否登录
            if (!$uid) {
                exit("用户未登录!");
            }

            $redis = FM_Redis::getInstance('default');
            $redis_key = "goods_" . $goods_id;

            $len = $redis->llen($redis_key); //求队列的长度，也就是商品的库存。

            if ($len == 0) {
                exit("抢光了!");
            }

            $redis->rPop($redis_key);
            $bool = $this->buy($uid, $goods_id, $amount);

            if (!$bool) {
                //如果购买失败,则把取出的redis 队列的数据，再压回去。（回充库存）
                $redis->lPush($redis_key, 1);
            }
        } catch (Exception $e) {
            exit("异常!");
        }
    }
}
$obj = new Seckill();
$uid = rand(1, 10);
$goods_id = rand(1, 6);
$obj->buy($goods_id, $uid);
