<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-04-20 20:25:49
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-02-23 11:26:22
 */

namespace common\services\admin;

use admin\models\DdApiAccessToken;
use admin\models\User;
use common\models\DdStoreUser;
use common\services\BaseService;
use diandi\addons\models\AddonsUser;
use diandi\addons\models\UserBloc;
use diandi\admin\acmodels\AuthUserGroup;

class UserService extends BaseService
{
    public static function deleteUser($user_id)
    {
        $where = [];
        $where['user_id'] = $user_id;
        AuthUserGroup::findOne($where)->delete();
        AddonsUser::findOne($where)->delete();
        DdStoreUser::findOne($where)->delete();
        User::findOne($user_id)->delete();
        DdApiAccessToken::findOne($where)->delete();
        UserBloc::findOne($where)->delete();
    }

    public static function deleteFile()
    {
        // dd_upload_file_user
    }
}
