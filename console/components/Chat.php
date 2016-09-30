<?php

/**
 * Connected Communities Initiative
 * Copyright (C) 2016  Queensland University of Technology
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.org/licences GNU AGPL v3
 *
 */

namespace console\components;

use common\models\HSetting;
use Yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use common\models\User;
use common\models\WBSChatSmile;
use yii\helpers\Url;
use serhatozles\simplehtmldom\SimpleHTMLDom;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $chat;
    protected $absoluteUrl;
    private $imageUrl;
    private $imageHost;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->chat = new \common\models\WBSChat;
    }

    public function userConnect($conn) {
        parse_str($conn->WebSocket->request->getQuery('data'), $array);
        $user = User::find()->andWhere(['guid' => $array['code']])->one();
        if (!empty($user)) {
            $this->absoluteUrl = HSetting::find()->andFilterWhere(['name' => 'baseUrl'])->one()->value;
            $conn->is_chating = $user->is_chating;
            $conn->id = $user->id;
            $this->clients->attach($conn);
        } else {
            echo "Not found user close connection";
            $conn->close();
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        try {
            $this->userConnect($conn);
        } catch(\Exception $e) {
            Yii::$app->db->close();
            Yii::$app->db->open();
            echo "Reconnect db connection";
            $this->userConnect($conn);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if((bool)$from->is_chating) { //check can user chating
            $this->imageUrl = "";
            if(is_string(json_decode($msg))) { // when send simple string
                $msg = json_decode($msg);
                $msg = $this->chat->validateText($msg);
                $msg = \yii\helpers\HtmlPurifier::process($msg, ['HTML.Allowed' => 'br']);
                if (!empty($msg)) {

                    $idMessage = $this->saveMessage($msg, $from);
                    $msg = $this->chat->toLink($msg);
                    $msg = $this->chat->toSmile($this->absoluteUrl, $msg);
                    $msg = $this->chat->getMentions($msg);
                    $this->getImage($msg);
                    $user = $this->getUser($from);
                    if(!empty($user->profile)) {
                        $user_name = $user->profile->getName();
                    } else {
                        $user_name = $user->getName();
                    }

                    foreach ($this->clients as $client) {
                        $span = ($client->id == $from->id)?
                                                            "<div class='col-xs-12 col-sm-6'>
                                                            <div class='pull-right edit-mes'>
                                                                <i style='display:none' class='pull-right edit-icon glyphicon glyphicon-edit'></i>
                                                            </div> 
                                                            <span class='mes-time pull-right'>"
                                                                .  date("F j, Y, g:i a", time())  .
                                                            "</span></div>".
                                                            "<div class='clearfix'></div>
                                                            <div class='col-xs-12 mes-body'>
                                                                <span data-pk='$idMessage' class='message-edit editable-click'>
                                                                    :msg
                                                                </span>
                                                            </div>"
                                                        :
                                                            "<span data-pk='$idMessage' class='message-default'>
                                                                <div class='col-xs-12 col-sm-6'>
                                                                    <span class='mes-time mes-time-other pull-right'>"
                                                                        . date("F j, Y, g:i a", time())  .
                                                                    "</span>
                                                                </div>
                                                                <div class='clearfix'></div>
                                                                <div class='col-xs-12 mes-body'>
                                                                    :msg
                                                                </div>
                                                            </span>";

                        $photoUser = $this->checkRemoteFile($this->absoluteUrl . "/uploads/profile_image/" .User::findOne($from->id)->guid. ".jpg")?$this->absoluteUrl . "/uploads/profile_image/" . User::findOne($from->id)->guid. ".jpg":$this->absoluteUrl."/img/default_user.jpg?cacheId=0";
                        $span .= (!empty($this->imageUrl))?"<a target='_blank' href='$this->imageHost'><img class='img-responsive mes-attachment' width='300' src='$this->imageUrl'></a>":'';
                        $respond = "<div class='mes'>
                                        <div class='profile-size-sm profile-img-navbar'>
                                            <img id='user-account-image profile-size-sm' class='img-rounded' src='$photoUser' alt='32x32' data-src='holder.js/32x32' height='32' width='32'>
                                            <div class='profile-overlay-img profile-overlay-img-sm'></div>
                                        </div>
                                        <div class='col-xs-12 col-sm-5 no-padding'>" . $user_name . " :</div> " . str_replace(':msg', $msg, preg_replace('/^\s+|\n|\r|\s+$/m', '', $span)) .
                                    "</div>";
                        $client->send(json_encode($respond));
                    }
                }
            } elseif(is_array(json_decode($msg))) { // when send string for editing [in request json] [in response json]
                $message = json_decode($msg);
                $value = $this->chat->validateText($message[1]);
                $value = \yii\helpers\HtmlPurifier::process($value, ['HTML.Allowed' => 'br']);
                if (!empty($value)) {
                    $value = $this->chat->toLink($value);
                    $value = $this->chat->toSmile($this->absoluteUrl, $value);
                    $value = $this->chat->getMentions($value);
                    $this->getImage($value);
                     foreach ($this->clients as $client) {
                         $span = $value;
                         $imageUrl = (!empty($this->imageUrl))?"<a target='_blank' href='$this->imageUrl'><img class='img-responsive mes-attachment' width='300' src='$this->imageUrl'></a>":'';
                         $respond = [3 => !empty($imageUrl) ,2 => $imageUrl, 1 => $span, 0 => $message[0]];
                         $client->send(json_encode($respond));
                    }
                }
            }
        }
    }

    protected function getImage($data)
    {
        $htmlText = SimpleHTMLDom::str_get_html($data);
        $imageText = '';
        if(!empty($htmlText->find('a', 0))) {
            preg_match('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', $htmlText->find('a', 0)->href, $matches);
            if(!empty($matches)) {
                if($this->ifImage($matches[0])) {
                    $this->imageUrl = $matches[0];
                    return;
                }
                $url = $matches[0];
                $htmlContent = SimpleHTMLDom::file_get_html($url);
                $urlHost = parse_url($url)['scheme'] ."://".parse_url($url)['host'] . "";
                // Find all images
                if(isset($htmlContent->find('img', 1)->src)) {
                    preg_match('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', $htmlContent->find('img', 1)->src, $matchesContent);
                    try {
                        if (empty($matchesContent)) {
//                            if (@getimagesize($urlHost . DIRECTORY_SEPARATOR . $htmlContent->find('img', 1)->src)) {
                                $this->imageHost = $urlHost;
                                $this->imageUrl = $urlHost . DIRECTORY_SEPARATOR . $htmlContent->find('img', 1)->src;
//                            }
                        } else {
//                            if (@getimagesize($htmlContent->find('img', 1)->src)) {
                                $this->imageHost = $htmlContent->find('img', 1)->src;
                                $this->imageUrl = $htmlContent->find('img', 1)->src;
//                            }
                        }
                    } catch (\Exception $e) {
                        //
                    }
                }
            }
        }
    }

    protected function ifImage($string)
    {
        preg_match('/(http|https|ftp|ftps)\:\/\/([\w\W]*).(png|jpg|gif|jpeg)/', $string, $matches);
        if(!empty($matches[0])){
            return true;
        }

        return false;
    }

    protected function checkRemoteFile($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        // don't download content
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(curl_exec($ch)!==FALSE)
        {
            return true;
        }
        else
        {
            return false;
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
