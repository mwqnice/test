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
        $redis->set('test', 123);
        print_r($redis->get('test'));
    }
}
$obj = new RedisTest();
$obj->index();
