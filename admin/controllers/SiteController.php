<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-02 21:40:25
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-13 17:22:19
 */

namespace admin\controllers;

use admin\models\forms\LoginForm;
use admin\models\forms\PasswordResetRequestForm as FormsPasswordResetRequestForm;
use admin\models\forms\ResendVerificationEmailForm;
use admin\models\forms\ResetPasswordForm;
use admin\models\forms\SignupForm;
use admin\models\forms\VerifyEmailForm;
use common\helpers\ErrorsHelper;
use common\helpers\MapHelper;
use common\helpers\ResultHelper;
use common\models\DdUser;
use common\models\User;
use diandi\admin\acmodels\AuthItem;
use diandi\admin\acmodels\AuthRoute;
use Yii;
use yii\base\InvalidArgumentException;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;

/**
 * Site controllers.
 */
class SiteController extends AController
{
    public $modelClass = '';

    protected $authOptional = ['login', 'logout', 'error', 'signup', 'request-password-reset', 'setpassword'];

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return ResultHelper::json(200, '获取成功');
    }

    /**
     * @SWG\Post(path="/site/login",
     *     tags={"登录与注册"},
     *     summary="登录",
     *     @SWG\Response(
     *         response = 200,
     *         description = "登录",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $last_login_ip = MapHelper::get_client_ip();
            $user = DdUser::find()->where([
                'id' => Yii::$app->user->identity->id,
                'last_login_ip' => $last_login_ip,
            ])->select(['is_login'])->one();

            // if($user['is_login']==1 && $user['last_time']+60*5<time()){

            //     Yii::$app->user->logout();
            //     Yii::$app->session->setFlash('success', '该账户已在其他浏览器登录');
            //     return $this->goHome();
            // }
            // 记录最后登录的时间
            $password_reset_token = Yii::$app->security->generateRandomString().'_'.time();
            DdUser::updateAll([
                'last_time' => time(),
                'is_login' => 1,
                'last_login_ip' => $last_login_ip,
                'password_reset_token' => $password_reset_token,
            ], ['id' => Yii::$app->user->identity->id]);

            return $this->goHome();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * @SWG\Post(path="/site/logout",
     *     tags={"登录与注册"},
     *     summary="退出",
     *     @SWG\Response(
     *         response = 200,
     *         description = "退出",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionLogout()
    {
        DdUser::updateAll([
            'is_login' => 0,
        ], ['id' => Yii::$app->user->identity->id]);

        Yii::$app->user->logout();

        return ResultHelper::json(200, '退出成功', [
            'url' => Url::to(['site/login']),
        ]);
    }

    /**
     * @SWG\Post(path="/site/signup",
     *     tags={"登录与注册"},
     *     summary="注册",
     *     @SWG\Response(
     *         response = 200,
     *         description = "注册",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionSignup()
    {
        global $_GPC;
        $model = new SignupForm();
        $data = [
            'username' => $_GPC['username'],
            'email' => $_GPC['email'],
            'password' => $_GPC['password'],
        ];
        // p($model->load(Yii::$app->request->post()),$model->signup());
        if ($model->load($data, '') && $model->signup()) {
            return ResultHelper::json(200, '感谢您的注册，请验证您的邮箱');
        } else {
            $msg = ErrorsHelper::getModelError($model);

            return ResultHelper::json(400, '注册失败', $msg);
        }
    }

    /**
     * @SWG\Post(path="/site/request-password-reset",
     *     tags={"登录与注册"},
     *     summary="邮箱重置密码",
     *     @SWG\Response(
     *         response = 200,
     *         description = "重置密码",
     *     ),
     *     @SWG\Parameter(
     *      in="header",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionRequestPasswordReset()
    {
        $model = new FormsPasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', '发送成功，请查收您的邮箱');
            } else {
                Yii::$app->session->setFlash('error', '对不起，我们无法为提供的电子邮件地址重置密码。');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * @SWG\Post(path="/site/setpassword",
     *     tags={"登录与注册"},
     *     summary="后台设置密码",
     *     @SWG\Response(
     *         response = 200,
     *         description = "后台修改密码",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionSetpassword($token)
    {
        $this->layout = '@backend/views/layouts/main-login';
        $isGuest = Yii::$app->user->isGuest;
        if ($isGuest) {
            try {
                $model = new ResetPasswordForm($token);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        } else {
            $password_reset_token = Yii::$app->security->generateRandomString().'_'.time();

            User::updateAll([
                'password_reset_token' => $password_reset_token,
            ], ['id' => Yii::$app->user->id]);
            $model = new ResetPasswordForm($password_reset_token);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', '密码修改成功');
            $this->redirect(['site/login']);
        }

        return $this->render('setpassword', [
            'model' => $model,
        ]);
    }

    /**
     * @SWG\Post(path="/site/reset-password",
     *     tags={"登录与注册"},
     *     summary="后台重置密码",
     *     @SWG\Response(
     *         response = 200,
     *         description = "后台重置密码",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionResetPassword($token)
    {
        $this->layout = '@backend/views/layouts/main';
        $isGuest = Yii::$app->user->isGuest;
        if ($isGuest) {
            try {
                $model = new ResetPasswordForm($token);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        } else {
            $password_reset_token = Yii::$app->security->generateRandomString().'_'.time();

            User::updateAll([
                'password_reset_token' => $password_reset_token,
            ], ['id' => Yii::$app->user->id]);
            $model = new ResetPasswordForm($password_reset_token);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', '密码修改成功');
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * @SWG\Post(path="/site/verify-email",
     *     tags={"登录与注册"},
     *     summary="验证邮箱",
     *     @SWG\Response(
     *         response = 200,
     *         description = "验证邮箱",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="refresh_token",
     *      type="string",
     *      description="刷新token令牌",
     *      required=true,
     *    ),
     * )
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');

                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');

        return $this->goHome();
    }

    /**
     * Resend verification email.
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', '发送成功，请注意查收');

                // return $this->goHome();
            }
            Yii::$app->session->setFlash('error', '邮件发送失败，请重试');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model,
        ]);
    }

    /**
     * @SWG\Post(path="/site/xiufu",
     *     tags={"swg文档"},
     *     summary="数据修复",
     *     @SWG\Response(
     *         response = 200,
     *         description = "swg文档"
     *     ),
     *     @SWG\Parameter(ref="#/parameters/access-token"),
     *     @SWG\Parameter(ref="#/parameters/bloc-id"),
     *     @SWG\Parameter(ref="#/parameters/store-id"),
     *     @SWG\Parameter(
     *     in="formData",
     *     name="type",
     *     type="string",
     *     description="页面标识",
     *     required=true,
     *   ),
     * )
     */
    public function actionXiufu()
    {
        global $_GPC;
        if ($_GPC['type'] == 1) {
            $AuthRoute = new AuthRoute();
            $list = AuthRoute::find()->alias('a')->leftJoin(AuthItem::tableName().' as c',
                'a.route_name=c.name'
            )->select(['a.id', 'c.id as item_id'])->asArray()->all();

            foreach ($list as $key => $value) {
                $_AuthRoute = clone $AuthRoute;
                $_AuthRoute->updateAll([
                    'item_id' => $value['item_id'],
                ], [
                    'id' => $value['id'],
                ]);
            }
        } elseif ($_GPC['type'] == 2) {
            $authItem = new AuthItem();

            $AuthRoute = AuthRoute::find()->asArray()->all();

            foreach ($AuthRoute as $key => $value) {
                $_authItem = clone $authItem;
                $_authItem->setAttributes([
                    'name' => $value['route_name'],
                    'is_sys' => $value['is_sys'],
                    'permission_type' => 0,
                    'description' => $value['description'],
                    'parent_id' => 0,
                    'permission_level' => $value['route_type'],
                    'data' => $value['data'],
                    'module_name' => $value['module_name'],
                ]);
                $_authItem->save();
                $msg = ErrorsHelper::getModelError($_authItem);
                if (!empty($msg)) {
                    echo '<pre>';
                    print_r($msg);
                    echo '</pre>';
                }
            }
        } elseif ($_GPC['type'] == 3) {
            $AuthRoute = new AuthRoute();
            $list = AuthRoute::find()->where(['=', 'item_id', null])->asArray()->all();

            foreach ($list as $key => $value) {
                $_AuthRoute = clone $AuthRoute;
                $_AuthRoute->updateAll([
                    'route_name' => $value['name'],
                ], [
                    'id' => $value['id'],
                ]);
            }
        }
    }
}
