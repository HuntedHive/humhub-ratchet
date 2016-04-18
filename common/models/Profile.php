<?php

namespace common\models;

use yii\db\ActiveRecord;

class Profile extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'profile';
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            [['firstname','lastname', 'title'], 'text'],
            ['user_id', 'integer'],
            array(array('file','created_at', 'created_by', 'updated_at', 'updated_by'), 'safe'),
        );
    }
    
    public function getName()
    {
        if(!empty($this->lastname) && !empty($this->firstname)) {
            return $this->firstname . ' ' . $this->lastname;
        } else {
            return "user_" . $this->user_id;
        }
    }
}
