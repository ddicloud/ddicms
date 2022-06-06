<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-03 09:53:09
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-06 11:31:23
 */

namespace common\helpers;

use yii\helpers\BaseUrl;
use yii\helpers\Json;

/**
 * Class ArrayHelper.
 *
 * @author chunchun <2192138785@qq.com>
 */
class UrlHelper extends BaseUrl
{
   public static function addonsUrl($addons,$controller,$action,$options=[])
   {
       return self::toRoute([$addons.'/'.$controller.'/'.$action]);
   }
}
