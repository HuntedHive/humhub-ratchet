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
            $conn->is_chating = $user->is_chating;
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
        if((bool)$from->is_chating) { //check can user chating
            if(is_string(json_decode($msg))) { // when send simple string
                $msg = json_decode($msg);
                $msg = $this->chat->validateText($msg);
                $msg = \yii\helpers\HtmlPurifier::process($msg, ['HTML.Allowed' => 'br']);
                if (!empty($msg)) {
                    $idMessage = $this->saveMessage($msg, $from);
                    $msg = $this->chat->toLink($msg);
                    $msg = $this->chat->toSmile($msg);
                    $user = $this->getUser($from);
                    $user_name = $user->profile->getName();

                    foreach ($this->clients as $client) {
                        $span = ($client->id == $from->id)?"<span data-pk='$idMessage' class='message-edit'>:msg</span>" . "<i style='display:none' class='pull-right edit-icon glyphicon glyphicon-edit'</div>":"<span data-pk='$idMessage' class='message-default'>:msg</span>";
                        $respond = "<div class='mes'>".$user_name.": ".str_replace(":msg", $msg, $span) . "</div>";
                        $client->send(json_encode($respond));
                    }
                }
            } elseif(is_array(json_decode($msg))) { // when send string for editing [in request json] [in response json]
                $message = json_decode($msg);
                $value = $this->chat->validateText($message[1]);
                $value = \yii\helpers\HtmlPurifier::process($value, ['HTML.Allowed' => 'br']);
                if (!empty($value)) {
                    $value = $this->chat->toLink($value);
                    $value = $this->chat->toSmile($value);
                    $value = $this->chat->getMentions($value);
                    foreach ($this->clients as $client) {
                        $respond = [1 => $value, 0 => $message[0]];
                        $client->send(json_encode($respond));
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "Connection {$conn->id} has reload\n";
        
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