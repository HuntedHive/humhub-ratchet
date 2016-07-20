<?php

namespace console\controllers;

use Ratchet\Server\IoServer;
use console\components\Chat;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use common\models\WBSChatSmile;
use common\models\User;

class ServerController extends \yii\console\Controller
{

    public function actionStart()
    {
        $address = !empty(Yii::$app->params['address'])?Yii::$app->params['address']:'0.0.0.0';
        $port = !empty(Yii::$app->params['port'])?Yii::$app->params['port']:8080;

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Chat()
                )
            ),
            $port,
            $address
        );

        $server->run();
    }
}