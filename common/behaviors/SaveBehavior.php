<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-05-15 22:50:42
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-05-15 09:44:43
 */

namespace common\behaviors;

use diandi\addons\models\Bloc;
use Yii;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * @author Skilly
 */
class SaveBehavior extends Behavior
{
    public $createdAttribute = 'create_time';

    public $updatedAttribute = 'update_time';

    public $adminAttribute = 'admin_id';

    public $storeAttribute = 'store_id';

    public $blocAttribute = 'bloc_id';

    public $blocPAttribute = 'bloc_pid'; //上级公司

    public $globalBlocAttribute = 'global_bloc_id'; //上级公司

    public $attributes = [];

    public $noAttributes = [];

    public $is_bloc = false; //是否是集团数据模型

    public $time_type = 'init'; //默认为init,可以设置为datetime

    private $_map;

    public function init()
    {
        global $_GPC;

        $Res = Yii::$app->service->commonGlobalsService->getGlobalBloc();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->createdAttribute, $this->updatedAttribute, $this->blocAttribute, $this->storeAttribute, $this->blocPAttribute, $this->adminAttribute, $this->globalBlocAttribute], //准备数据 在插入之前更新created和updated两个字段
                BaseActiveRecord::EVENT_BEFORE_UPDATE => [$this->updatedAttribute, $this->blocAttribute, $this->storeAttribute, $this->blocPAttribute, $this->adminAttribute], // 在更新之前更新updated字段
            ];
        }

        if (!$this->is_bloc) {
            $bloc_id = Yii::$app->service->commonGlobalsService->getBloc_id();
            $store_id = Yii::$app->service->commonGlobalsService->getStore_id();
        } else {
            $bloc_id = Yii::$app->params['global_bloc_id'];
            $store_id = Yii::$app->params['global_store_id'];
        }

        // 后台用户使用
        if (!empty($_GPC['bloc_id']) && $_GPC['bloc_id'] != $bloc_id) {
            $bloc_id = $_GPC['bloc_id'];
        }

        $blocPid = Bloc::find()->where(['bloc_id' => $bloc_id])->select('pid')->one();

        // if (Yii::$app->user->identity->store_id) {
        //     $store_id = Yii::$app->user->identity->store_id;
        // }

        $admin_id = Yii::$app->user->identity->id;

        $time = $this->time_type === 'init' ? time() : date('Y-m-d H:i:s', time());

        $this->_map = [
            $this->createdAttribute => $time, //在这里你可以随意格式化
            $this->updatedAttribute => $time,
            $this->blocAttribute => (int) $bloc_id,
            $this->storeAttribute => (int) $store_id,
            $this->blocPAttribute => (int) $blocPid['pid'],
            $this->adminAttribute => (int) $admin_id,
            $this->globalBlocAttribute => Yii::$app->params['global_bloc_id'],
        ];
    }

    //@see http://www.yiichina.com/doc/api/2.0/yii-base-behavior#events()-detail
    public function events()
    {
        return array_fill_keys(array_keys($this->attributes), 'evaluateAttributes');
    }

    public function evaluateAttributes($event)
    {
        if (!empty($this->attributes[$event->name])) {
            $attributes = $this->attributes[$event->name];

            foreach ($attributes as $attribute) {
                // 如果赋值了，就不需要改变
                if (!empty($this->owner->attributes[$attribute]) && $attribute == 'store_id') {
                    continue;
                }

                if (!empty($this->owner->attributes[$attribute]) && $attribute == 'bloc_id') {
                    continue;
                }

                if (array_key_exists($attribute, $this->owner->attributes) && !in_array($attribute, $this->noAttributes)) {
                    $this->owner->$attribute = $this->getValue($attribute);
                }
            }
        }
    }

    protected function getValue($attribute)
    {
        return $this->_map[$attribute];
    }
}
