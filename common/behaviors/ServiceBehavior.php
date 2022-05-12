<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-05-15 22:50:42
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-05-12 14:46:24
 */

namespace common\behaviors;

use common\helpers\loggingHelper;
use Yii;
use yii\base\Behavior;

/**
 * @author Skilly
 */
class ServiceBehavior extends Behavior
{
    // 定义事件名
    const EVENT_ADDONS_SERVICE = 'serviceEvents';

    public function init()
    {
        global $_GPC;
    }

    //@see http://www.yiichina.com/doc/api/2.0/yii-base-behavior#events()-detail
    public function events()
    {
        return [
            self::EVENT_ADDONS_SERVICE => 'serviceEvents',
        ];
    }

    public function serviceEvents($event)
    {
        loggingHelper::writeLog('SignUpBehavior', 'SignUpBehavior', '会员存储行为', [
            'owner' => $this->owner,
            'event' => $event,
        ]);
        $service = Yii::$app->service;
        $service->namespace = $event->addons;
        $serviceClassName = $event->serviceClassName;
        $action = $event->action;
        $service->$serviceClassName->$action($event->params);
    }
}