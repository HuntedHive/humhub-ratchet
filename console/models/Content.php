<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "content".
 *
 * @property integer $id
 * @property string $guid
 * @property string $object_model
 * @property integer $object_id
 * @property integer $visibility
 * @property integer $sticked
 * @property string $archived
 * @property integer $space_id
 * @property integer $user_id
 * @property string $created_at
 * @property integer $created_by
 * @property string $updated_at
 * @property integer $updated_by
 */
class Content extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'content';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['guid', 'object_model', 'object_id'], 'required'],
            [['object_id', 'visibility', 'sticked', 'user_id', 'created_by', 'updated_by'], 'integer'],
            [['archived'], 'string'],
            [['created_at', 'updated_at', 'space_id'], 'safe'],
            [['guid'], 'string', 'max' => 255],
            [['object_model'], 'string', 'max' => 100],
            [['guid'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'guid' => 'Guid',
            'object_model' => 'Object Model',
            'object_id' => 'Object ID',
            'visibility' => 'Visibility',
            'sticked' => 'Sticked',
            'archived' => 'Archived',
            'space_id' => 'Space ID',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }
}
