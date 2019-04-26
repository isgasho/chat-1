<?php

namespace App;


use GatewayWorker\Lib\Gateway;

class MessageHandler
{
    public static function login($client_id, array $data)
    {
        $_SESSION['username'] = $data['username'];

        Gateway::sendToAll(IO::encode('login', $_SESSION), null, $client_id);
    }

    public static function message($client_id, array $data)
    {
        // $client_id 发送的人
        // $data['to_id'] 发送给谁
        // $data['content'] 发送的内容
        $to = $data['to_id'];
        if (Gateway::isOnline($to)) {

            $response = [
                'from_id' => $client_id,
                'content' => $data['content'],
                'time' => date('Y-m-d H:i:s')
            ];

            Gateway::sendToClient($to, IO::encode('message', $response));
        }
    }

    public static function allUsers($client_id, array $data)
    {
        $users = Gateway::getAllClientSessions();

        Gateway::sendToClient($client_id, IO::encode('allUsers', array_values($users)));
    }

    public function updateAvatar($client_id, array $data)
    {
        $src = $data['src'];

        // 修改自己的头像, 推送给其他人
        $_SESSION['avatar'] = $src;
        Gateway::sendToAll(IO::encode('updateAvatar', compact('src', 'client_id')), null, $client_id);
    }
}
