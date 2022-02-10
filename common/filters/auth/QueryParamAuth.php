<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-12-30 01:48:37
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-08-25 14:45:30
 */

namespace common\filters\auth;


use common\models\enums\CodeStatus;
use Yii;
use yii\filters\auth\QueryParamAuth as AuthQueryParamAuth;
use yii\web\UnauthorizedHttpException;

class QueryParamAuth extends AuthQueryParamAuth
{

    /**
     * @var string the parameter name for passing the access token
     */
    public $tokenParam = 'access-token';


    /**
     * {@inheritdoc}
     */
    public function authenticate($user, $request, $response)
    {
        global $_GPC;
        $accessToken = Yii::$app->request->headers->get('access-token', '');

        if (is_string($accessToken)) {
            $identity = $user->loginByAccessToken($accessToken, get_class($this));
            if ($identity !== null) {
                return $identity;
            }
        }
        if ($accessToken !== null) {
            $this->handleFailure($response);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function handleFailure($response)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        throw new UnauthorizedHttpException('用户token验证失败', CodeStatus::getValueByName('token失效'));
    }
}
