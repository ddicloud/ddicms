<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-12-13 01:11:14
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-01-06 01:39:17
 */

namespace common\components\FileUpload;

define('ATTACH_OSS', 'alioss');
define('ATTACH_QINIU', 'qiniu');
define('ATTACH_COS', 'cos');
define('ATTACHMENT_ROOT', 'attachment');

use Alioss\Core\OssException;
use Alioss\OssClient;
use common\helpers\FileHelper;
use common\helpers\ResultHelper;
use Qcloud\Cos\Client;
use Qiniu\Auth;
use function Qiniu\base64_urlSafeEncode;
use Qiniu\Config;
use Qiniu\Storage\UploadManager;
use Yii;
use yii\base\Component;

class OssUpload extends Component
{
    public $type;

    public function __construct()
    {
        $this->type = Yii::$app->params['conf']['oss']['remote_type'];
    }

    public function attachment_set_attach_url()
    {
        global $_W;
        if (empty(Yii::$app->params['conf']['oss']['remote_complete_info'])) {
            Yii::$app->params['conf']['oss']['remote_complete_info'] = Yii::$app->params['conf']['oss'];
        }
        if (!empty($_W['uniacid'])) {
            $uni_remote_setting = $this->uni_setting_load('remote');
            if (!empty($uni_remote_setting['remote_type'])) {
                Yii::$app->params['conf']['oss'] = $uni_remote_setting['remote'];
            }
        }
        $attach_url = $_W['attachurl_local'] = $_W['siteroot'].$_W['config']['upload']['attachdir'].'/';
        if (!empty(Yii::$app->params['conf']['oss']['remote_type'])) {
            if (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_OSS) {
                $attach_url = $_W['attachurl_remote'] = Yii::$app->params['conf']['oss']['alioss']['url'].'/';
            } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_QINIU) {
                $attach_url = $_W['attachurl_remote'] = Yii::$app->params['conf']['oss']['qiniu']['url'].'/';
            } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_COS) {
                $attach_url = $_W['attachurl_remote'] = Yii::$app->params['conf']['oss']['cos']['url'].'/';
            }
        }

        return $attach_url;
    }

    public function file_remote_upload($filename, $auto_delete_local = true)
    {
        if (empty(Yii::$app->params['conf']['oss']['remote_type']) || Yii::$app->params['conf']['oss']['remote_type'] == 'local') {
            return ResultHelper::serverJson(400, '?????????????????????');
        }
        if (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_OSS) {
            $buckets = $this->attachment_alioss_buctkets(Yii::$app->params['conf']['oss']['Aliyunoss_accessKeyId'], Yii::$app->params['conf']['oss']['Aliyunoss_accessKeySecret']);
            $host_name = Yii::$app->params['conf']['oss']['Aliyunoss_resource'] ? '-internal.aliyuncs.com' : '.aliyuncs.com';
            $endpoint = 'http://'.Yii::$app->params['conf']['oss']['Aliyunoss_endPoint'].$host_name;
            try {
                $ossClient = new OssClient(Yii::$app->params['conf']['oss']['Aliyunoss_accessKeyId'], Yii::$app->params['conf']['oss']['Aliyunoss_accessKeySecret'], $endpoint);
                $filePath = Yii::getAlias('@'.ATTACHMENT_ROOT.'/'.$filename);
                $ossClient->uploadFile(Yii::$app->params['conf']['oss']['Aliyunoss_bucket'], ATTACHMENT_ROOT.'/'.$filename, $filePath);
            } catch (OssException $e) {
                return ResultHelper::serverJson(405, $e->getMessage());
            }
            if ($auto_delete_local) {
                $this->file_delete($filename);
            }
        } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_QINIU) {
            $auth = new Auth(Yii::$app->params['conf']['oss']['qiniu']['accesskey'], Yii::$app->params['conf']['oss']['qiniu']['secretkey']);
            $config = new Config();
            $uploadmgr = new UploadManager($config);
            $putpolicy = base64_urlSafeEncode(json_encode([
            'scope' => Yii::$app->params['conf']['oss']['qiniu']['bucket'].':'.$filename,
        ]));
            $uploadtoken = $auth->uploadToken(Yii::$app->params['conf']['oss']['qiniu']['bucket'], $filename, 3600, $putpolicy);
            list($ret, $err) = $uploadmgr->putFile($uploadtoken, $filename, ATTACHMENT_ROOT.'/'.$filename);
            if ($auto_delete_local) {
                $this->file_delete($filename);
            }
            if (null !== $err) {
                return ResultHelper::json(405, '?????????????????????????????????????????????????????????');
            } else {
                return true;
            }
        } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_COS) {
            try {
                $bucket = Yii::$app->params['conf']['oss']['cos']['bucket'].'-'.Yii::$app->params['conf']['oss']['cos']['appid'];
                $cosClient = new Client(
                [
                    'region' => Yii::$app->params['conf']['oss']['cos']['local'],
                    'credentials' => [
                        'secretId' => Yii::$app->params['conf']['oss']['cos']['secretid'],
                        'secretKey' => Yii::$app->params['conf']['oss']['cos']['secretkey'], ], ]);
                $cosClient->Upload($bucket, $filename, fopen(ATTACHMENT_ROOT.$filename, 'rb'));
                if ($auto_delete_local) {
                    $this->file_delete($filename);
                }
            } catch (\Exception $e) {
                return ResultHelper::json(405, $e->getMessage());
            }
        }

        return ResultHelper::serverJson(200, '????????????', [
            'storage' => Yii::$app->params['conf']['oss']['remote_type'],
        ]);
    }

    public function file_remote_upload_util($filename, $chunk_partSize, $auto_delete_local = true)
    {
        if (empty(Yii::$app->params['conf']['oss']['remote_type']) || Yii::$app->params['conf']['oss']['remote_type'] == 'local') {
            return ResultHelper::serverJson(200, '?????????????????????', [
                'file' => $filename,
            ]);
        }

        if (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_OSS) {
            $buckets = $this->attachment_alioss_buctkets(Yii::$app->params['conf']['oss']['Aliyunoss_accessKeyId'], Yii::$app->params['conf']['oss']['Aliyunoss_accessKeySecret']);
            $host_name = Yii::$app->params['conf']['oss']['Aliyunoss_resource'] ? '-internal.aliyuncs.com' : '.aliyuncs.com';
            $endpoint = 'http://'.Yii::$app->params['conf']['oss']['Aliyunoss_endPoint'].$host_name;
            try {
                $ossClient = new OssClient(Yii::$app->params['conf']['oss']['Aliyunoss_accessKeyId'], Yii::$app->params['conf']['oss']['Aliyunoss_accessKeySecret'], $endpoint);
                $options = [
                    OssClient::OSS_CHECK_MD5 => true,
                    OssClient::OSS_PART_SIZE => 1,
                ];
                $ossClient->setTimeout(280);
                $fileInfo = pathinfo($filename);
                $pathInfo = explode('attachment/', $filename);
                // $object = $fileInfo['basename'];
                $ossClient->multiuploadFile(Yii::$app->params['conf']['oss']['Aliyunoss_bucket'],'attachment/'.$pathInfo[1], $filename, $options);
                if ($auto_delete_local) {
                    $this->file_delete($filename);
                }
                return ResultHelper::serverJson(200, '????????????', [
                    'storage' => Yii::$app->params['conf']['oss']['remote_type'],
                    'file' =>'attachment/'.$pathInfo[1],
                ]);
            } catch (OssException $e) {
                return ResultHelper::serverJson(405, $e->getMessage());
            }
           
        } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_QINIU) {
            $auth = new Auth(Yii::$app->params['conf']['oss']['qiniu']['accesskey'], Yii::$app->params['conf']['oss']['qiniu']['secretkey']);
            $config = new Config();
            $uploadmgr = new UploadManager($config);
            $putpolicy = base64_urlSafeEncode(json_encode([
            'scope' => Yii::$app->params['conf']['oss']['qiniu']['bucket'].':'.$filename,
        ]));
            $uploadtoken = $auth->uploadToken(Yii::$app->params['conf']['oss']['qiniu']['bucket'], $filename, 3600, $putpolicy);
            list($ret, $err) = $uploadmgr->putFile($uploadtoken, $filename, $filename);
            if ($auto_delete_local) {
                $this->file_delete($filename);
            }
            if (null !== $err) {
                return ResultHelper::json(405, '?????????????????????????????????????????????????????????');
            } else {
                return true;
            }
        } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_COS) {
            try {
                $bucket = Yii::$app->params['conf']['oss']['cos']['bucket'].'-'.Yii::$app->params['conf']['oss']['cos']['appid'];
                $cosClient = new Client(
                [
                    'region' => Yii::$app->params['conf']['oss']['cos']['local'],
                    'credentials' => [
                        'secretId' => Yii::$app->params['conf']['oss']['cos']['secretid'],
                        'secretKey' => Yii::$app->params['conf']['oss']['cos']['secretkey'], ], ]);
                $cosClient->Upload($bucket, $filename, fopen($filename, 'rb'));
                if ($auto_delete_local) {
                    $this->file_delete($filename);
                }
            } catch (\Exception $e) {
                return ResultHelper::json(405, $e->getMessage());
            }
        }
    }

    // ????????????
    public function file_remote_upload_util_merge($filename, $uploadId, $responseUploadPart, $auto_delete_local = true)
    {
        if (empty(Yii::$app->params['conf']['oss']['remote_type']) || Yii::$app->params['conf']['oss']['remote_type'] == 'local') {
            return ResultHelper::serverJson(400, '?????????????????????');
        }
        if (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_OSS) {
            $buckets = $this->attachment_alioss_buctkets(Yii::$app->params['conf']['oss']['Aliyunoss_accessKeyId'], Yii::$app->params['conf']['oss']['Aliyunoss_accessKeySecret']);
            $host_name = Yii::$app->params['conf']['oss']['Aliyunoss_resource'] ? '-internal.aliyuncs.com' : '.aliyuncs.com';
            $endpoint = 'http://'.Yii::$app->params['conf']['oss']['Aliyunoss_endPoint'].$host_name;
            try {
                $ossClient = new OssClient(Yii::$app->params['conf']['oss']['Aliyunoss_accessKeyId'], Yii::$app->params['conf']['oss']['Aliyunoss_accessKeySecret'], $endpoint);
                // ??????????????????
                $uploadFile = Yii::getAlias('@'.ATTACHMENT_ROOT.'/'.$filename);

                $ossClient->listParts(Yii::$app->params['conf']['oss']['Aliyunoss_bucket'], $filename, $uploadId, $options = null);
                // $uploadParts?????????????????????ETag???????????????PartNumber?????????????????????
                $uploadParts = [];
                foreach ($responseUploadPart as $i => $eTag) {
                    $uploadParts[] = [
                        'PartNumber' => ($i + 1),
                        'ETag' => trim($eTag),
                    ];
                }

                /*
                 * ??????3??????????????????
                 */
                try {
                    // ??????completeMultipartUpload???????????????????????????????????????$uploadParts???OSS???????????????$uploadParts??????????????????????????????????????????????????????????????????????????????????????????OSS???????????????????????????????????????????????????
                    $ossClient->completeMultipartUpload(Yii::$app->params['conf']['oss']['Aliyunoss_bucket'], $filename, $uploadId, $uploadParts);
                } catch (OssException $e) {
                    printf(__FUNCTION__.": completeMultipartUpload FAILED\n");
                    printf($e->getMessage()."\n");

                    return;
                }
            } catch (OssException $e) {
                return ResultHelper::serverJson(405, $e->getMessage());
            }
            if ($auto_delete_local) {
                $this->file_delete($filename);
            }
        } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_QINIU) {
            $auth = new Auth(Yii::$app->params['conf']['oss']['qiniu']['accesskey'], Yii::$app->params['conf']['oss']['qiniu']['secretkey']);
            $config = new Config();
            $uploadmgr = new UploadManager($config);
            $putpolicy = base64_urlSafeEncode(json_encode([
            'scope' => Yii::$app->params['conf']['oss']['qiniu']['bucket'].':'.$filename,
        ]));
            $uploadtoken = $auth->uploadToken(Yii::$app->params['conf']['oss']['qiniu']['bucket'], $filename, 3600, $putpolicy);
            list($ret, $err) = $uploadmgr->putFile($uploadtoken, $filename, $filename);
            if ($auto_delete_local) {
                $this->file_delete($filename);
            }
            if (null !== $err) {
                return ResultHelper::json(405, '?????????????????????????????????????????????????????????');
            } else {
                return true;
            }
        } elseif (Yii::$app->params['conf']['oss']['remote_type'] == ATTACH_COS) {
            try {
                $bucket = Yii::$app->params['conf']['oss']['cos']['bucket'].'-'.Yii::$app->params['conf']['oss']['cos']['appid'];
                $cosClient = new Client(
                [
                    'region' => Yii::$app->params['conf']['oss']['cos']['local'],
                    'credentials' => [
                        'secretId' => Yii::$app->params['conf']['oss']['cos']['secretid'],
                        'secretKey' => Yii::$app->params['conf']['oss']['cos']['secretkey'], ], ]);
                $cosClient->Upload($bucket, $filename, fopen($filename, 'rb'));
                if ($auto_delete_local) {
                    $this->file_delete($filename);
                }
            } catch (\Exception $e) {
                return ResultHelper::json(405, $e->getMessage());
            }
        }

        return ResultHelper::serverJson(200, '????????????', [
            'storage' => Yii::$app->params['conf']['oss']['remote_type'],
        ]);
    }

    public function attachment_alioss_datacenters()
    {
        $bucket_datacenter = [
        'oss-cn-hangzhou' => '??????????????????',
        'oss-cn-qingdao' => '??????????????????',
        'oss-cn-beijing' => '??????????????????',
        'oss-cn-hongkong' => '??????????????????',
        'oss-cn-shenzhen' => '??????????????????',
        'oss-cn-shanghai' => '??????????????????',
        'oss-us-west-1' => '????????????????????????',
    ];

        return $bucket_datacenter;
    }

    public function attachment_newalioss_auth($key, $secret, $bucket, $internal = false)
    {
        $buckets = $this->attachment_alioss_buctkets($key, $secret);
        $host = $internal ? '-internal.aliyuncs.com' : '.aliyuncs.com';
        $url = 'http://'.Yii::$app->params['conf']['oss']['Aliyunoss_endPoint'].$host;
        $filename = 'MicroEngine.ico';
        try {
            $ossClient = new OssClient($key, $secret, $url);
            $ossClient->uploadFile($bucket, $filename, ATTACHMENT_ROOT.'images/global/'.$filename);
        } catch (OssException $e) {
            return ResultHelper::json(405, $e->getMessage());
        }

        return 1;
    }

    public function attachment_alioss_buctkets($key, $secret)
    {
        $url = 'http://oss-cn-beijing.aliyuncs.com';
        try {
            $ossClient = new OssClient($key, $secret, $url);
        } catch (OssException $e) {
            return ResultHelper::json(405, $e->getMessage());
        }
        try {
            $bucketlistinfo = $ossClient->listBuckets();
        } catch (OssException $e) {
            return ResultHelper::json(405, $e->getMessage());
        }
        $bucketlistinfo = $bucketlistinfo->getBucketList();
        $bucketlist = [];
        foreach ($bucketlistinfo as &$bucket) {
            $bucketlist[$bucket->getName()] = ['name' => $bucket->getName(), 'location' => $bucket->getLocation()];
        }

        return $bucketlist;
    }

    public function attachment_qiniu_auth($key, $secret, $bucket)
    {
        $auth = new Auth($key, $secret);
        $token = $auth->uploadToken($bucket);
        $config = new Config();
        $uploadmgr = new UploadManager($config);
        list($ret, $err) = $uploadmgr->putFile($token, 'MicroEngine.ico', ATTACHMENT_ROOT.'images/global/MicroEngine.ico');
        if ($err !== null) {
            $err = (array) $err;
            $err = (array) array_pop($err);
            $err = json_decode($err['body'], true);

            return ResultHelper::json(405, $err);
        } else {
            return true;
        }
    }

    public function attachment_cos_auth($bucket, $appid, $key, $secret, $bucket_local = '')
    {
        if (!is_numeric($appid)) {
            return ResultHelper::json(405, '??????appid????????????, ???????????????');
        }
        if (!preg_match('/^[a-zA-Z0-9]{36}$/', $key)) {
            return ResultHelper::json(405, '??????secretid??????????????????????????????');
        }
        if (!preg_match('/^[a-zA-Z0-9]{32}$/', $secret)) {
            return ResultHelper::json(405, '??????secretkey??????????????????????????????');
        }
        try {
            $cosClient = new Client(
            [
                'region' => $bucket_local,
                'credentials' => [
                    'secretId' => $key,
                    'secretKey' => $secret, ], ]);
            $cosClient->Upload($bucket.'-'.$appid, 'MicroEngine.ico', fopen(ATTACHMENT_ROOT.'images/global/MicroEngine.ico', 'rb'));
        } catch (\Exception $e) {
            return ResultHelper::json(405, $e->getMessage());
        }

        return true;
    }

    public function attachment_reset_uniacid($uniacid)
    {
        return true;
    }

    public function attachment_replace_article_remote_url($old_url, $new_url)
    {
    }

    public function attachment_recursion_group($group_data = [], $pid = 0)
    {
        if (empty($group_data)) {
            return [];
        }
        $return_data = [];
        foreach ($group_data as $key => $group_data_value) {
            if ($group_data_value['pid'] == $pid) {
                $return_data[$group_data_value['id']] = $group_data_value;
                $sub_group = $this->attachment_recursion_group($group_data, $group_data_value['id']);
                if (0 == $pid) {
                    $return_data[$group_data_value['id']]['sub_group'] = !empty($sub_group) ? $sub_group : [];
                }
            }
        }

        return $return_data;
    }

    public function attachment_get_type($type_sign)
    {
        $attach_type = [
            ATTACH_OSS => 'alioss',
            ATTACH_QINIU => 'qiniu',
            ATTACH_COS => 'cos',
        ];

        return !empty($attach_type[$type_sign]) ? $attach_type[$type_sign] : '';
    }

    public function file_delete($filename)
    {
        // ??????????????????
        FileHelper::file_delete($filename);
    }
}
