<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class WBSChatSmile extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'wbs_smiles';
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('link, symbol', 'required'),
            array('link', 'string'),
            array('symbol', 'string', 'max' => 50),
            array(array('created_at', 'created_by', 'updated_at', 'updated_by', 'link'), 'safe'),
        );
    }
}
