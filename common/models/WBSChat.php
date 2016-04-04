<?php

namespace common\models;

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
}
