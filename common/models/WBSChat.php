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

namespace common\models;

use app\models\Content;
use yii\db\ActiveRecord;
use yii\db\Expression;

class WBSChat extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'wbs_chat';
    }
    
    public function beforeSave($insert)
    {
        $this->created_at = date('Y-m-d H:i:s');
        $this->created_by = $this->user_id;
        $this->updated_at = date('Y-m-d H:i:s');
        $this->updated_by = $this->user_id;
        return parent::beforeSave($insert);
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            [['text'],'filter','filter' => function($value) {
                return \yii\helpers\HtmlPurifier::process($value, ['HTML.Allowed' => 'br']);
            }],
            ['text', 'string'],
            ['user_id', 'integer'],
            array(array('file','created_at', 'created_by', 'updated_at', 'updated_by'), 'safe'),
        );
    }
    
    public function validateText($msg)
    {
        $msg = str_replace("/[\r\n]{2,}/i", "\r\n", $msg);
        $msg = str_replace("/[\s]+/", "", $msg);
        $msg = trim($msg);
        $msg = nl2br($msg);
        $msg = rtrim(preg_replace('/((\<br \/>([\s]*)){2,})/', ' <br>', $msg), ' <br>');
        return $msg;
    }
    
    public function toSmile($absoluteUrl, $data)
    {
        $smiles = WBSChatSmile::find()->all();
        foreach ($smiles as $smile) {
            $data = preg_replace('/'. quotemeta($smile->symbol) .'/', "<img src='$absoluteUrl/uploads/emojione/$smile->link' " . 'data-symbol="' . $smile->symbol . '">', $data);
        }
        
        return $data;
    }
    
    public function toLink($data)
    {
        return preg_replace('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', " <a target='_blank' style='color:blue;text-decoration:underline;' href='$0'> $0 </a> ", $data);
    }
    
    public function getMentions($messages)
    {
        return preg_replace('/[\s]?(@[a-zA-z0-9]+)[\s]/', " <span class='mention'>$1</span> ", $messages);
    }


    /**
     * After Save Addons
     *
     * @return type
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $now = new Expression('NOW()');
            $activity = new Activity;
            $activity->class = "humhub\modules\chat\activities\ChatMessage";
            $activity->module = "chat";
            $activity->object_id = $this->id;
            $activity->object_model = "humhub\modules\chat\models\WBSChat";
            $activity->save(false);

            $content = new Content();
            $content->guid = \Yii::$app->security->generateRandomString(32);
            $content->object_model = "humhub\modules\activity\models\Activity";
            $content->object_id = $activity->id;
            $content->visibility = 1;
            $content->sticked = 0;
            $content->archived = 0;
            $content->space_id = null;
            $content->user_id = $this->user_id;
            $content->created_by = $this->created_by;
            $content->created_at = $now;
            $content->updated_by = $this->updated_by;
            $content->updated_at = $now;
            $content->save(false);

            $wall = Wall::find()->andWhere(['object_id' => $this->user_id])->one();
            if(empty($wall)) {
                $wall->object_model = "humhub\modules\user\models\User";
                $wall->object_id = $this->user_id;
                $wall->created_by = null;
                $wall->created_at = date('Y-m-d H:i:s');
                $wall->updated_by = null;
                $wall->updated_at = date('Y-m-d H:i:s');
                $wall->save(false);
            }

            $wallentry = new WallEntry;
            $wallentry->wall_id = $wall->id;
            $wallentry->content_id = $content->id;
            $wallentry->created_by = $this->created_by;
            $wallentry->created_at = $now;
            $wallentry->updated_by = $this->updated_by;
            $wallentry->updated_at = $now;
            $wallentry->save(false);
        }

        parent::afterSave($insert, $changedAttributes);

    }
}
