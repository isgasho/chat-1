<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    // 大厅组, 默认所有人加入这个组
    const LOBBY_GROUP = 'lobby_group';
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     * @throws Exception
     */
    public static function onConnect($client_id)
    {
        Gateway::joinGroup($client_id, self::LOBBY_GROUP);

        // 初始化 session
        $_SESSION['username'] = '游客';
        $_SESSION['avatar'] = 'assets/img/default.png';
        $_SESSION['group'] = self::LOBBY_GROUP;

        // 先告诉他自己的 ID 是多少
        Gateway::sendToClient($client_id, Message::jsonMessage('bind', compact('client_id')));
    }

    /**
     * 当客户端发来消息时触发
     *
     * @param int   $client_id 连接id
     * @param mixed $message   具体消息
     * @throws Exception
     */
   public static function onMessage($client_id, $message)
   {
       $array = Message::parseMessage($message);

       switch ($array['type']) {

           // 如果是登录操作
           case 'login':

               $username = $array['username'];
               $_SESSION['username'] = $username;

               // 告诉其他人有人上线了
               Gateway::sendToAll(
                   Message::jsonMessage('login_ed', compact('client_id', 'username')),
                   null,
                   $client_id
               );
               break;

           // 前端获取所有人列表
           case 'all_lobby':

               // 获取大厅人数, 发送大厅所有人给前端, 一般初始化使用
               $users = Gateway::getClientSessionsByGroup(self::LOBBY_GROUP);
               Gateway::sendToClient(
                   $client_id,
                   Message::jsonMessage('all_lobby_ed', compact('users'))
               );
               break;

           // 有人发起了房间的连接
           case 'vs_connection':

               $vsId = $array['vs_id'];
               $username = Gateway::getSession($client_id)['username'] ?? '未知用户';
               Gateway::sendToClient(
                   $vsId,
                   Message::jsonMessage('vs_connection_ed', compact('client_id', 'username'))
               );
               break;

           // 当双方同意了挑战, 开始
           case 'vs_build':

               // 要 PK 的人
               $vsId = $array['vs_id'];
               $vsSession = Gateway::getSession($vsId);

               // 如果这两个人不在大厅, 那么提示在战斗中
               if ($_SESSION['group'] !== self::LOBBY_GROUP) {
                   Gateway::sendToClient(
                       $client_id,
                       Message::jsonMessage('error_msg_ed', ['content' => '你不在大厅, 无法参与战斗'])
                   );

                   break;
               }

               if ($vsSession['group'] !== self::LOBBY_GROUP) {
                   Gateway::sendToClient(
                       $client_id,
                       Message::jsonMessage('error_msg_ed', ['content' => '对手不在大厅, 无法参与战斗'])
                   );
                   break;
               }



               $group = uniqid(true);
               // 创建一个分组, 把这两个人加入同一个分组
               Gateway::joinGroup($client_id, $group);
               Gateway::joinGroup($vsId, $group);

               $_SESSION['group'] = $group;
               Gateway::updateSession($vsId, compact('group'));
               // 把这两个人从大厅移除
               Gateway::leaveGroup($client_id, self::LOBBY_GROUP);
               Gateway::leaveGroup($vsId, self::LOBBY_GROUP);

               $users = [
                   ['id' => $client_id, 'username' => $_SESSION['username']],
                   ['id' => $vsId, 'username' => $vsSession['username']],
               ];
               // 发送消息通知大厅, 有人开始打斗了, 然后并动态移除这两个人的身份
               $msg = Message::jsonMessage('vs_build_ed', compact('users'));
               Gateway::sendToAll($msg);
               break;

           // 当有大厅消息发送来的时候
           case 'msg':
               $content = $array['content'];
               $username = Gateway::getSession($client_id)['username'];
               Gateway::sendToAll(Message::jsonMessage('msg_ed', compact('client_id', 'username', 'content')));
               break;

           // 当有组内消息
           case 'msg_group':
               $content = $array['content'];
               $username = Gateway::getSession($client_id)['username'];
               $group = Gateway::getSession($client_id)['group'];
               Gateway::sendToGroup(
                   $group,
                   Message::jsonMessage('msg_group_ed', compact('client_id', 'username', 'content'))
               );
               break;


           // 开始游戏, 把它移除组内

           // 结束游戏, 返回大厅组

           default:
               break;
       }
   }

    /**
     * 当用户断开连接时触发
     *
     * @param int $client_id 连接id
     * @throws Exception
     */
   public static function onClose($client_id)
   {
       // 向所有人发送
       $message = Message::jsonMessage('logout_ed', compact('client_id'));
       GateWay::sendToAll($message, null, $client_id);
   }
}


class Message
{
    public static function jsonMessage($type, $data)
    {
        return json_encode(compact('type', 'data'));
    }

    public static function parseMessage($json)
    {
        $array = json_decode($json, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            return [];
        }

        return $array;
    }
}
