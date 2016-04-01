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
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Chat()
                )
            ),
            8080
        );

        $server->run();
    }
}