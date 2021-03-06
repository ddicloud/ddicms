<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-07-29 01:59:56
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-07-12 11:50:15
 */

namespace admin\models;

use common\helpers\ErrorsHelper;
use common\helpers\FileHelper;
use common\helpers\ResultHelper;
use common\models\enums\UserStatus;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model.
 *
 * @property int    $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property int    $status
 * @property int    $created_at
 * @property int    $updated_at
 * @property string $password             write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = UserStatus::DELETE;
    const STATUS_INACTIVE = UserStatus::AUDIT;
    const STATUS_ACTIVE = UserStatus::APPROVE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => UserStatus::AUDIT],
            ['status', 'in', 'range' => UserStatus::getConstantsByName()],
            [['username', 'email'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function setStatus(UserStatus $status)
    {
        $this->status = $status->getValue();
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     */
    public function signup($username, $mobile, $email, $password, $company = '', $status = 0)
    {
        $logPath = Yii::getAlias('@runtime/wechat/login/'.date('ymd').'.log');

        if (!$this->validate()) {
            FileHelper::writeLog($logPath, '????????????:????????????????????????'.json_encode($this->validate()));

            return $this->validate();
        }

        /* ??????????????????????????? */
        $userinfo = $this->find()->where(['username' => $username])->select('id')->one();
        if (!empty($userinfo)) {
            return ResultHelper::json(401, '?????????????????????');
        }
        /* ??????????????????????????? */
        if ($mobile) {
            $userinfo = $this->find()->where(['mobile' => $mobile])
                ->andWhere(['<>', 'mobile', 0])->select('id')->one();
            if (!empty($userinfo)) {
                return ResultHelper::json(401, '?????????????????????');
            }
        }
        /* ???????????????????????? */
        if ($email) {
            $userinfo = $this->find()->where(['email' => $email])
                ->andWhere(['<>', 'email', 0])->select('id')->one();
            if (!empty($userinfo)) {
                return ResultHelper::json(401, '??????????????????');
            }
        }
        FileHelper::writeLog($logPath, '????????????:???????????????????????????'.json_encode($email));

        $this->username = $username;
        $this->email = $email;
        $this->company = $company;
        $this->mobile = $mobile;
        $this->status = (int) $status;

        $this->setPassword($password);
        $this->generateAuthKey();
        $this->generateEmailVerificationToken();
        $this->generatePasswordResetToken();
        if ($this->save()) {
            $user_id = Yii::$app->db->getLastInsertID();

            /* ????????????apitoken */
            $service = Yii::$app->service;
            $service->namespace = 'admin';
            $userinfo = $service->AccessTokenService->getAccessToken($this, 1);

            return $userinfo;
        } else {
            $msg = ErrorsHelper::getModelError($this);
            FileHelper::writeLog($logPath, '????????????:????????????????????????'.json_encode($msg));

            return ResultHelper::json(401, $msg);
        }
    }

    /**
     * ??????accessToken?????????.
     *
     * @return string
     *
     * @throws \yii\base\Exception
     */
    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString();

        return $this->access_token;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    public static function findUser($mobile, $username)
    {
        $query = static::find();
        if (!empty($mobile)) {
            $user = $query->where(['mobile' => $mobile])->one();
        }

        if (!empty($username)) {
            $user = $query->where(['username' => $username])->one();
        }

        return $user;
    }

    /**
     * Finds user by username.
     *
     * @param string $username
     *
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::find()->where(['username' => $username, 'status' => self::STATUS_ACTIVE])->one();
    }

    public static function findByMobile($mobile)
    {
        return static::find()->where(['mobile' => $mobile, 'status' => self::STATUS_ACTIVE])->one();
    }

    /**
     * Finds user by password reset token.
     *
     * @param string $token password reset token
     *
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token.
     *
     * @param string $token verify email token
     *
     * @return static|null
     */
    public static function findByVerificationToken($token)
    {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid.
     *
     * @param string $token password reset token
     *
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];

        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password.
     *
     * @param string $password password to validate
     *
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key.
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token.
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString().'_'.time();
    }

    /**
     * Generates new token for email verification.
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString().'_'.time();
    }

    /**
     * Removes password reset token.
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
}
