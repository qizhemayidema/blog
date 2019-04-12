<?php

namespace app\push\controller;

use think\worker\Server;

class Worker extends Server
{
    protected $socket = 'websocket://192.168.1.210:9050';

    protected $redis_obj;       //redis实例

    protected $list_name = 'line_list';     //链表名字
    /**
     * 收到信息
     * @param $connection
     * @param $data
     */

    public function onMessage($connection, $data)
    {
        $res = json_decode($data,true);
        if ($res['type'] == 1){     //初次连接
            $connection->username = $res['username'];       //存储用户昵称
        }

        $res['time'] = date('Y-m-d H:i:s',time());

        if ($res['type'] == 3){         //代表将要离开
            $res['message'] = '【' . $res['username'] . '】 离开了';
        }

        $new_data = json_encode($res);

        foreach ($this->worker->connections as $k => $v){
            $v->send($new_data);
        }
        $this->redis_insert_data_list($connection,$res);
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {

    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        $res['type'] = 3;
        $res['username'] = $connection->username;
        $res['message'] = '【' . $res['username'] . '】 离开了';
        $res['time'] = date('Y-m-d H:i:s',time());
        $new_data = json_encode($res);
        foreach ($this->worker->connections as $k => $v){
            $v->send($new_data);
        }

        $this->redis_insert_data_list($connection,$res);
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        if (!$this->redis_obj){
            $redis = new \Redis();
            $redis->pconnect('127.0.0.1', 6379,0);
            $this->redis_obj = $redis;
        }
    }

    //数据进redis队列
    public function redis_insert_data_list($connection,$res)
    {
        $redis_data = [];
        $redis_data['state'] = $res['type'];
        $redis_data['message'] = $res['message'];
        $redis_data['ip'] = $connection->getRemoteIp();
        $redis_data['username'] = $connection->username;
        $redis_data['create_time'] = time();

        switch ($redis_data['state']){
            case 1;
                $redis_data['state_message'] = '登陆';
                break;

            case 2;
                $redis_data['state_message'] = '普通消息';
                break;

            case 3;
                $redis_data['state_message'] = '退出';
                break;
                
            default;
                $redis_data['state_message'] = '未知';
        }

        $redis_data = json_encode($redis_data,true);

        $this->redis_obj->lPush($this->list_name,$redis_data);
    }
}

    /*
    ws = new WebSocket("ws://120.78.174.210:9050");
    ws.onopen = function() {
        alert("连接成功");
        ws.send('tom');
        alert("给服务端发送一个字符串：tom");
    };
    ws.onmessage = function(e) {
        alert("收到服务端的消息：" + e.data);
    };

    */