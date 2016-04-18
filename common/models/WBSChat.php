<?php

namespace common\models;

use app\models\Content;
use yii\db\ActiveRecord;

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
        $this->created_at = date('Y-m-d H:i;s');
        $this->created_by = $this->user_id;
        $this->updated_at = date('Y-m-d H:i;s');
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
    
    public function toSmile($data)
    {
        $smiles = WBSChatSmile::find()->all();
        foreach ($smiles as $smile) {
            $data = preg_replace('/'. quotemeta($smile->symbol) .'/', "<img src='$smile->link'>", $data);
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
            $activity = new Activity;
            $activity->type = "ChatMessage";
            $activity->module = "chat";
            $activity->object_id = $this->id;
            $activity->object_model = "WBSChat";
            $activity->created_by = $this->created_by;
            $activity->created_at = date('Y-m-d H:i:s');
            $activity->updated_by = $this->updated_by;
            $activity->updated_at = date('Y-m-d H:i:s');
            $activity->save(false);

            $content = new Content();
            $content->guid = \Yii::$app->security->generateRandomString(32);
            $content->object_model = "WBSChat";
            $content->object_id = $this->id;
            $content->visibility = 1;
            $content->sticked = 0;
            $content->archived = 0;
            $content->space_id = null;
            $content->user_id = $this->user_id;
            $content->created_by = $this->created_by;
            $content->created_at = date('Y-m-d H:i:s');
            $content->updated_by = $this->updated_by;
            $content->updated_at = date('Y-m-d H:i:s');
            $content->save(false);

            $content2 = new Content();
            $content2->guid = \Yii::$app->security->generateRandomString(32);
            $content2->object_model = "Activity";
            $content2->object_id = $activity->id;
            $content2->visibility = 1;
            $content2->sticked = 0;
            $content2->archived = 0;
            $content2->space_id = null;
            $content2->user_id = $this->user_id;
            $content2->created_by = $this->created_by;
            $content2->created_at = date('Y-m-d H:i:s');
            $content2->updated_by = $this->updated_by;
            $content2->updated_at = date('Y-m-d H:i:s');
            $content2->save(false);

            $wall = Wall::find()->andWhere(['object_id' => $this->user_id])->one();
            if(empty($wall)) {
                $wall->object_model = "User";
                $wall->object_id = $this->user_id;
                $wall->created_by = null;
                $wall->created_at = date('Y-m-d H:i:s');
                $wall->updated_by = null;
                $wall->updated_at = date('Y-m-d H:i:s');
                $wall->save(false);
            }

            $wallentry = new WallEntry;
            $wallentry->wall_id = $wall->id;
            $wallentry->content_id = $content2->id;
            $wallentry->created_by = $this->created_by;
            $wallentry->created_at = date('Y-m-d H:i:s');
            $wallentry->updated_by = $this->updated_by;
            $wallentry->updated_at = date('Y-m-d H:i:s');
            $wallentry->save(false);
        }

        parent::afterSave($insert, $changedAttributes);

    }
}
