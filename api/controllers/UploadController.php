<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-19 18:05:45
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-01-01 17:04:05
 */


namespace api\controllers;

use Yii;
use api\controllers\AController;
use common\components\FileUpload\Upload;
use yii\filters\VerbFilter;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use Faker\Provider\Uuid;
use yii\helpers\Json;
use yii\rest\ActiveController;


class UploadController extends AController
{
    public $modelClass = '';

    public $enableCsrfValidation = false;
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'delete' => ['POST'],
            ],
        ];
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            \Yii::$app->response->setStatusCode(204);
            \Yii::$app->end(0);
        }
        return $behaviors;
    }

    /**
     * @SWG\Post(path="/upload/images",
     *     tags={"资源上传"},
     *     summary="上传图片",
     *     @SWG\Response(
     *         response = 200,
     *         description = "上传图片",
     *     ),
     *     @SWG\Parameter(
     *      in="formData",
     *      name="images",
     *      type="string",
     *      description="需要上传的图片",
     *      required=true,
     *    ),
     *    @SWG\Parameter(
     *      name="access-token",
     *      type="string",
     *      in="query",
     *      required=true
     *     )
     * )
     */
    public function actionImages()
    {
        global $_GPC;
        try {
            $model = new Upload();
            $info = $model->upImage();
            $info && is_array($info) ?
                exit(Json::htmlEncode($info)) :
                exit(Json::htmlEncode([
                    'code' => 1,
                    'msg' => 'error'
                ]));
        } catch (\Exception $e) {
            exit(Json::htmlEncode([
                'code' => 1,
                'msg' => $e->getMessage()
            ]));
        }
    }

    public function actionBaseimg()
    {
        global $_GPC;

        header('Content-type:text/html;charset=utf-8');
        $base64_image_content       = $_GPC['images'];

        $member_id = Yii::$app->user->identity->member_id;

        if (!$base64_image_content) return ['code' => 404, 'msg' => '数据不能为空'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $relativePath = Yii::$app->params['imageUploadRelativePath'];

            $new_file = $relativePath;

            if (!file_exists($new_file)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($new_file, 0700);
            }
            $new_file = $new_file . Uuid::uuid() . ".{$type}";
            // base64解码后保存图片
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {

                $file = str_replace('../attachment/', '', $new_file);
                return ['code' => 200, 'message' => '文件保存成功', 'data' => [
                    'attachment' => $file,
                    'url' => ImageHelper::tomedia($file),
                ]];
            } else {
                return ['code' => 4041, 'message' => '文件保存失败', 'data' => null];
            }
        }
    }
}
