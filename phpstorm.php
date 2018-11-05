<?php
/**
 * Created by PhpStorm.
 *
 * @desc 文件功能
 * @author  Administrator
 * @version  V1.0
 * @date  2018/10/17
 */
//关键代码

function saveFormIds(){
    $openId = $_GET['openId'];
    $formIds = $_GET['formIds'];;//获取formIds数组
    if($formIds){
        $formIds = json_decode($formIds,TRUE);//JSON解码为数组
        $this ->_saveFormIdsArray($openId,$formIds);//保存
    }
}
function _get($openId){
    $cacheKey = md5('user_formId'.$openId);
    $data = $this->cache->redis->get($cacheKey);//修改为你自己的Redis调用方式
    if($data)
        return json_decode($data,TRUE);
    else
        return FALSE;
}
function _save($openId,$data){
    $cacheKey = md5('user_formId'.$openId);
    return $this->cache->redis->save($cacheKey,json_encode($data),60*60*24*7);//修改为你自己的Redis调用方式
}
function _saveFormIdsArray($openId,$arr){
    $res = $this->_get($openId);
    if($res){
        $new = array_merge($res, $arr);//合并数组
        return $this->_save($openId,$new);
    }else{
        $result = $arr;
        return $this->_save($openId,$result);
    }
}
//这一步主要是构建服务器程序高效存储用户的推送码formId，这下推送机会有了，接下来我们考虑如何利用后端程序来想特定用户发送模板消息，考虑怎样去合理运用推送机会。
//
//
//四.如何实现高性能的模板消息推送？
//构建高性能的服务器端异步任务推送，可以满足 模板消息的群发、以及定时发送 的需求，如小打卡就采用了高性能分布式内存队列系统 BEANSTALKD，来实现模板消息的异步定时推送。实现发送模板消息的群发、定时发送分为2个步骤：
//1.设置任务执行时间并将该发送任务推送到异步任务队列。
//2.通过任务发送服务轮询执行任务，获取access_token、指定你需要推送消息的用户的openId，根据openId获取用户的推送码formId，并结合模板id拼装模板上的通知内容，调用模板消息发送接口来异步发送。
//
//普通的模板消息的发送就不赘述了，可参考官方文档中的模板消息功能 一步步进行操作，我们重点来看高性能异步任务推送的实现方法。涉及到的关键代码如下：

//设置异步任务
function put_task($data,$priority=2,$delay=3,$ttr=60){
    //任务数据、优先级、时间定时、任务处理时间
    $pheanstalk = new Pheanstalk('127.0.0.1:11300');
    return $pheanstalk ->useTube('test') ->put($data,$priority,$delay,$ttr);
}
//执行异步任务
function run() {
    while(1) {
        $job = $this->pheanstalk->watch('test')->ignore('default')->reserve();//监听任务
        $this->send_notice_by_key($job->getData());//执行模板消息的发送
        $this->pheanstalk->delete($job);//删除任务
        $memory = memory_get_usage();
        usleep(10);
    }
}
//1.取出一个可用的用户openId对应的推送码
function getFormId($openId){
    $res = $this->_get($openId);
    if($res){
        if(!count($res)){
            return FALSE;
        }
        $newData = array();
        $result = FALSE;
        for($i = 0;$i < count($res);$i++){
            if($res[$i]['expire'] > time()){
                $result = $res[$i]['formId'];//得到一个可用的formId
                for($j = $i+1;$j < count($res);$j++){//移除本次使用的formId
                    array_push($newData,$res[$j]);//重新获取可用formId组成的新数组
                }
                break;
            }
        }
        $this->_save($openId,$newData);
        return $result;
    }else{
        return FALSE;
    }
}
//2.拼装模板，创建通知内容
function create_template($openId,$formId,$content){
    $templateData['keyword1']['value'] = '打卡即将开始';
    $templateData['keyword1']['color'] = '#d81e06';
    $templateData['keyword2']['value'] = '打卡名称';
    $templateData['keyword2']['color'] = '#1aaba8';
    $templateData['keyword3']['value'] = '05:00';
    $templateData['keyword4']['value'] = '备注说明';
    $data['touser'] = $openId;
    $data['template_id'] = '模板id';
    $data['page'] = 'pages/detail/detail?id=1000';//用户点击模板消息后的跳转页面
    $data['form_id'] = $formId;
    $data['data'] = $templateData;
    return json_encode($data);
}
//3.执行模板消息发布
function send_notice($key){
    $openId = '用户openId';
    $formId = $this -> getFormId($openId);//获取formId
    $access_token = '获取access_token';
    $content='通知内容';//可通过$key作为键来获取对应的通知数据
    if($access_token){
        $templateData = $this->create_template($openId,$formId,$content);//拼接模板数据
        $res = json_decode($this->http_post('https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token,$templateData));
        if($res->errcode == 0){
            return $res;
        }else{
            return false;
        }
    }
}

