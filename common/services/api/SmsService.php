<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-11-18 13:50:47
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-07-12 10:40:49
 */

namespace common\services\api;

use common\enums\StatusEnum;
use common\helpers\EchantsHelper;
use common\helpers\ErrorsHelper;
use common\models\common\SmsLog;
use common\models\DdAiSmsLog;
use common\queues\SmsJob;
use common\services\BaseService;
use Overtrue\EasySms\EasySms;
use Yii;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class SmsService.
 *
 * @author chunchun <2192138785@qq.com>
 */
class SmsService extends BaseService
{
    /**
     * 消息队列.
     *
     * @var bool
     */
    public $queueSwitch = false;

    /**
     * @var array
     */
    protected $config = [];

    public function __construct()
    {
        parent::__construct();

        $sms = Yii::$app->params['conf']['sms'];

        $this->config = [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,
            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
                // 默认可用的发送网关
                'gateways' => [
                    'aliyun',
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => Yii::getAlias('runtime').'/easy-sms.log',
                ],
                'aliyun' => [
                    'access_key_id' => $sms['access_key_id'],
                    'access_key_secret' => $sms['access_key_secret'],
                    'sign_name' => $sms['sign_name'],
                ],
            ],
        ];
    }

    /**
     * 发送短信
     *
     * ```php
     *       Yii::$app->services->sms->send($mobile, $code, $usage, $member_id)
     * ```
     *
     * @param int    $mobile    手机号码
     * @param int    $code      验证码
     * @param string $usage     用途
     * @param int    $member_id 用户ID
     *
     * @return string|null
     *
     * @throws UnprocessableEntityHttpException
     */
    public function send($mobile, $code, $usage, $member_id = 0)
    {
        $ip = ip2long(Yii::$app->request->userIP);
        if ($this->queueSwitch == true) {
            $messageId = Yii::$app->queue->push(new SmsJob([
                'mobile' => $mobile,
                'code' => $code,
                'usage' => $usage,
                'member_id' => $member_id,
                'ip' => $ip,
            ]));

            return $messageId;
        }

        return $this->realSend($mobile, $code, $usage, $member_id = 0, $ip);
    }

    /**
     * 真实发送短信
     *
     * @param $mobile
     * @param $code
     * @param $usage
     * @param int $member_id
     *
     * @throws UnprocessableEntityHttpException
     */
    public function realSend($mobile, $code, $usage, $member_id = 0, $ip = 0)
    {
        $sms = Yii::$app->params['conf']['sms'];
        $template = $sms['template_code'];
        try {
            // 校验发送是否频繁
            if (($smsLog = $this->findByMobile($mobile)) && $smsLog['created_at'] + 60 > time()) {
                throw new NotFoundHttpException('请不要频繁发送短信');
            }

            $easySms = new EasySms($this->config);
            $result = $easySms->send($mobile, [
                'template' => $template,
                'data' => [
                    'code' => $code,
                ],
            ]);

            $this->saveLog([
                'mobile' => $mobile,
                'code' => $code,
                'member_id' => $member_id,
                'usage' => $usage,
                'ip' => $ip,
                'error_code' => 200,
                'error_msg' => 'ok',
                'error_data' => Json::encode($result),
            ]);
        } catch (NotFoundHttpException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        } catch (\Exception $e) {
            $errorMessage = [];
            $exceptions = $e->getMessage();
            $gateways = $this->config['default']['gateways'];

            foreach ($gateways as $gateway) {
                if (isset($exceptions[$gateway])) {
                    $errorMessage[$gateway] = $exceptions[$gateway]->getMessage();
                }
            }

            $log = $this->saveLog([
                'mobile' => $mobile,
                'code' => $code,
                'member_id' => $member_id,
                'usage' => $usage,
                'ip' => $ip,
                'error_code' => 422,
                'error_msg' => '发送失败',
                'error_data' => Json::encode($errorMessage),
            ]);

            throw new UnprocessableEntityHttpException('短信发送失败');
        }
    }

    /**
     * 真实发送短信
     *
     * @param $mobile
     *
     * @throws UnprocessableEntityHttpException
     */
    public function sendContent($mobile, $data = [], $template)
    {
        try {
            $easySms = new EasySms($this->config);
            $result = $easySms->send($mobile, [
                'template' => $template,
                'data' => $data,
            ]);
        } catch (NotFoundHttpException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        } catch (\Exception $e) {
            $errorMessage = [];
            $exceptions = $e->getMessage();
            $gateways = $this->config['default']['gateways'];

            foreach ($gateways as $gateway) {
                if (isset($exceptions[$gateway])) {
                    $errorMessage[$gateway] = $exceptions[$gateway]->getMessage();
                }
            }

            throw new UnprocessableEntityHttpException('短信发送失败:'.implode(',', $errorMessage));
        }
    }

    /**
     * @return array
     */
    // public function stat($type)
    // {
    //     $fields = [
    //         'count' => '异常发送数量'
    //     ];

    //     // 获取时间和格式化
    //     list($time, $format) = EchantsHelper::getFormatTime($type);
    //     // 获取数据
    //     return EchantsHelper::lineOrBarInTime(function ($start_time, $end_time, $formatting) {
    //         return SmsLog::find()
    //             ->select(["from_unixtime(created_at, '$formatting') as time", 'count(id) as count'])
    //             ->andWhere(['between', 'created_at', $start_time, $end_time])
    //             ->andWhere(['status' => StatusEnum::ENABLED])
    //             ->andWhere(['>', 'error_code', 399])
    //             ->andFilterWhere(['merchant_id' => Yii::$app->services->merchant->getId()])
    //             ->groupBy(['time'])
    //             ->asArray()
    //             ->all();
    //     }, $fields, $time, $format);
    // }

    /**
     * @param $mobile
     *
     * @return array|\yii\db\ActiveRecord|null
     */
    public function findByMobile($mobile)
    {
        return DdAiSmsLog::find()
            ->where(['mobile' => $mobile])
            ->orderBy('id desc')
            ->asArray()
            ->one();
    }

    /**
     * @param array $data
     *
     * @return DdAiSmsLog
     */
    protected function saveLog($data = [])
    {
        $log = new DdAiSmsLog();

        if ($log->load($data, '') && $log->save()) {
            return $log;
        } else {
            $msg = ErrorsHelper::getModelError($log);
            throw new \Exception($msg);
        }
    }
}
