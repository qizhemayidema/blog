<?php
/**
 * Created by PhpStorm.
 * User: 刘彪
 * Date: 2018/6/5
 * Time: 19:38
 */
$mysql_conf = [
    'host'      =>  '127.0.0.1',
    'port'      =>  '3306',
    'username'  =>  'root',
    'pwd'       =>  'lbliubiao',
    'dbname'    =>  'blogtwo'
    ];

//redis实例对象
$redis_obj = new Redis();
$redis_obj->pconnect('127.0.0.1',6379);

//PDO实例对象
$pdo_obj = new PDO('mysql:host='.$mysql_conf['host'].';dbname='.$mysql_conf['dbname'].';charset=utf8;',$mysql_conf['username'],$mysql_conf['pwd']);

$sql = 'insert into blog_line values(null,:username,:ip,:message,:create_time,:state,:state_message)';

$e_obj = $pdo_obj->prepare($sql);

while(true){
    sleep(1);
    $json_data = $redis_obj->rPop('line_list');
    $data = json_decode($json_data,true);
    if (!$data){
        echo "没有数据\r\n";
        continue;
    }
    var_dump($data);
    $e_obj->bindValue(':username',$data['username']);
    $e_obj->bindValue(':ip',$data['ip']);
    $e_obj->bindValue(':message',$data['message']);
    $e_obj->bindValue(':create_time',$data['create_time']);
    $e_obj->bindValue(':state',$data['state']);
    $e_obj->bindValue(':state_message',$data['state_message']);
    $res = $e_obj->execute();
    if (!$res){
        print_r($e_obj->errorInfo());
    }

    echo "录入一条数据\r\n";
}