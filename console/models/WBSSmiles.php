<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wbs_smiles".
 *
 * @property integer $id
 * @property string $symbol
 * @property string $link
 * @property string $created_at
 * @property integer $created_by
 * @property string $updated_at
 * @property integer $updated_by
 */
class WBSSmiles extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wbs_smiles';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['link', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'required'],
            [['link'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['created_by', 'updated_by'], 'integer'],
            [['symbol'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'symbol' => 'Symbol',
            'link' => 'Link',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }
}
