<?php


/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-02 16:48:20
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-02 16:56:40
 */
namespace console\services;

use yii\console\Request as ConsoleRequest;

class request extends ConsoleRequest
{
    public function __call($name, $params)
    {
        
    }
    
    function getAbsoluteUrl(){
        
    }

    public function getIsAjax()
    {
        
    }
}
?>