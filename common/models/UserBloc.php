<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-05-12 20:49:40
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-02-11 10:12:23
 */

namespace common\models;

use diandi\addons\models\Bloc;
use diandi\addons\models\BlocStore;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "diandi_user_bloc".
 *
 * @property int         $id
 * @property int|null    $user_id     管理员id
 * @property int|null    $bloc_id     集团id
 * @property int|null    $store_id    子公司id
 * @property string|null $create_time
 * @property string|null $update_time
 */
class UserBloc extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_bloc}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'bloc_id', 'store_id', 'status', 'is_default'], 'integer'],
            ['is_default', 'default', 'value' => 0],
            ['status', 'default', 'value' => 1],
            [['create_time', 'update_time'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time', // 自己根据数据库字段修改
                'updatedAtAttribute' => 'update_time', // 自己根据数据库字段修改, // 自己根据数据库字段修改
                'value' => function () {return time(); },
            ],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getBloc()
    {
        return $this->hasOne(Bloc::className(), ['bloc_id' => 'bloc_id']);
    }

    public function getStore()
    {
        return $this->hasOne(BlocStore::className(), ['store_id' => 'store_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '管理员id',
            'bloc_id' => '集团id',
            'store_id' => '子公司id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
