<?php

namespace console\components;

use Yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use common\models\User;
use common\models\WBSChatSmile;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $chat;
    
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->chat = new \common\models\WBSChat;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        parse_str($conn->WebSocket->request->getQuery('data'), $array);
        $user = User::find()->andWhere(['guid' => $array['code']])->one();
        if (!empty($user) && $this->checkUserInConnect($this->clients, $user->id)) {
            $conn->id = $user->id;
            $this->clients->attach($conn);
        } else {
            echo 'Try connect secondery in one user';
            $conn->close();
        }
    }
    
    protected function checkUserInConnect($users, $checkId)
    {
        foreach ($users as $user) {
            if($user->id == $checkId) {
                return false;
            }
        }
        
        return true;
    }
    
    public function onMessage(ConnectionInterface $from, $msg)
    {
        
        $msg = $this->chat->validateText($msg);
        $msg = \yii\helpers\HtmlPurifier::process($msg, ['HTML.Allowed' => 'br']);
        if (!empty($msg)) {
            $idMessage = $this->saveMessage($msg, $from);
            $msg = $this->chat->toLink($msg);
            $msg = $this->chat->toSmile($msg);
            $user = $this->getUser($from);
            $user_name = $user->profile->getName();
            
            foreach ($this->clients as $client) {
                $span = ($client->id == $from->id)?"<span data-type='$idMessage' class='message-edit'>:msg</span>" . "<i style='display:none' class='pull-right edit-icon glyphicon glyphicon-edit'</div>":"<span class='message-default'>:msg</span>";
                $respond = "<div class='mes'>".$user_name.": ".str_replace(":msg", $msg, $span) . "</div>";
                $client->send($respond);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "Connection {$conn->id} has disconnected\n";
        
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function getUser($user)
    {
        return User::find()->joinWith("profile")->andWhere(['user.id' => $user->id])->one();
    }

    protected function saveMessage($msg, $user)
    {
        $model = new \common\models\WBSChat;
        $model->user_id = $user->id;
        $model->text = $msg;
        $model->save();
        return $model->id;
    }
}