<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-02-21 10:06:15
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-04-28 14:05:00
 */

namespace common\models;

use common\traits\ActiveQuery\StoreTrait;

/**
 * This is the model class for table "{{%qrcode_stat}}".
 *
 * @property int      $id
 * @property int      $store_id
 * @property int      $bloc_id
 * @property int      $qid
 * @property string   $openid
 * @property int      $type
 * @property int      $qrcid
 * @property string   $scene_str
 * @property string   $name
 * @property int      $create_time
 * @property int|null $update_time
 */
class QrcodeStat extends \yii\db\ActiveRecord
{
    use StoreTrait;

    const TYPE_ATTENTION = 1;
    const TYPE_SCAN = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%qrcode_stat}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['store_id', 'bloc_id', 'qid', 'openid', 'type', 'qrcid', 'scene_str', 'name', 'create_time'], 'required'],
            [['store_id', 'bloc_id', 'qid', 'type', 'qrcid', 'create_time', 'update_time'], 'integer'],
            [['openid', 'name'], 'string', 'max' => 50],
            [['scene_str'], 'string', 'max' => 64],
        ];
    }

    /**
     * 行为.
     */
    public function behaviors()
    {
        /*自动添加创建和修改时间*/
        return [
            [
                'class' => \common\behaviors\SaveBehavior::className(),
                'updatedAttribute' => 'update_time',
                'createdAttribute' => 'create_time',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'bloc_id' => 'Bloc ID',
            'qid' => 'Qid',
            'openid' => 'Openid',
            'type' => 'Type',
            'qrcid' => 'Qrcid',
            'scene_str' => 'Scene Str',
            'name' => 'Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
