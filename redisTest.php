<?php
/**
 * Redis Test .
 * By Mao.w.q
 */
require_once 'redis.class.php';

class RedisTest
{
    public function index()
    {
        $redis = FM_Redis::getInstance('default');
        $redis->set('test', 252);
        print_r($redis->get('test'));
    }

    public function redisList()
    {
        $redis = FM_Redis::getInstance('default');
        //存储到列表中
        // $redis->lpush('list', 1);
        // $redis->lpush('list', 2);
        // $redis->lpush('list', 3);

        //列表右侧加入一个
        //$redis->rpush('list', '4');

        //左侧加入一个
        //$redis->lpush('list',4);

        //左侧弹出一个
        //$redis->lpop('list');

        //右侧弹出一个
        //$redis->rpop('list');

        //获取列表的长度
        //echo $redis->lLen('list');

        //返回列表key中的index位置的值
        //echo $redis->lIndex('list', 1);

        //设置列表中index位置的值
        //$redis->lset('list', 2, 'hello');

        //获取列表中所有的值
        $list = $redis->lRange('list', 0, -1);
        print_r($list);
    }
}
$obj = new RedisTest();
$obj->redisList();
