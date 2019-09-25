<?php
/**
 * Created by PhpStorm.
 * User: MapleSnow
 * Date: 2019/9/19
 * Time: 2:12 PM
 */

namespace Streaming;

use Qiniu\Auth;
use Qiniu\Http\Error;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Streaming\Helpers\Arr;
use Exception;

class Qiniu {

    /**
     * @var Auth
     */
    private $qiniu;

    /**
     * @var BucketManager
     */
    private $bucketMgr;

    /**
     * Qiniu constructor.
     * @param $accessKey
     * @param $secretKey
     */
    public function __construct($accessKey,$secretKey)
    {
        $this->qiniu = new Auth($accessKey,$secretKey);
        $this->bucketMgr = new BucketManager($this->qiniu);
    }

    /**
     * 获取七牛私有空间url
     * @param string $url 基础地址
     * @param int $expire 过期时间(秒)
     * @return string
     */
    public function privateDownloadUrl($url,$expire = 3600){
        return $this->qiniu->privateDownloadUrl($url,$expire);
    }

    /**
     * 获取所有空间
     * @return array
     */
    public function getAllBuckets() {
        $res =  $this->bucketMgr->buckets();
        return Arr::collapse($res);
    }

    /**
     * 上传
     * @param string $bucket 七牛空间名称
     * @param string $origin 上传文件源
     * @param bool $preserve 是否保存源文件
     * @param string|null $saveName 保存名称
     * @return bool|string
     * @throws Exception
     */
    public function upload($bucket, $origin, $preserve = true, $saveName = null){
        $this->checkBucket($bucket);
        is_null($saveName) && $saveName = basename($origin);

        $token = $this->qiniu->uploadToken($bucket);
        $uploadMgr = new UploadManager();
        list($res, $err) = $uploadMgr->putFile($token, $saveName, $origin);

        if (is_null($res)) {
            /** @var Error $err */
            throw new Exception("Qiniu upload {$saveName} failed :".$err->message());
        }

        !$preserve && @unlink($origin);

        $filename = $res['key'] ?? "";
        $fileHash = $res['hash'] ?? "";
        $url = $this->getDomain($bucket) . DIRECTORY_SEPARATOR . $res['key'];
        return compact('filename','fileHash','url');
    }

    /**
     * 获取域名列表
     * @param string $bucket 七牛空间名
     * @param bool $ignoreTmp 忽略零时域名
     * @return array
     */
    public function getDomains($bucket,$ignoreTmp = false) {
        $domains = Arr::collapse($this->bucketMgr->domains($bucket));
        return $ignoreTmp ? Arr::filter($domains,'clouddn.com') : $domains;
    }

    /**
     * 获取域名
     * @param $bucket
     * @return mixed
     * @throws Exception
     */
    public function getDomain($bucket) {
        $this->checkBucket($bucket);
        return Arr::first($this->getDomains($bucket,true));
    }

    /**
     * 删除文件
     * @param string $bucket 七牛空间名称
     * @param string $filename 文件名称
     * @return bool
     * @throws Exception
     */
    public function deleteFile($bucket,$filename) {
        $this->checkBucket($bucket);

        $err = $this->bucketMgr->delete($bucket, $filename);
        if (!is_null($err)) {
            /** @var Error $err */
            throw new Exception("delete {$filename} from {$bucket} failed:".$err->message());
        }

        return true;
    }

    /**
     * 检查空间名称有效性
     * @param $bucket
     * @return bool
     * @throws Exception
     */
    private function checkBucket($bucket) {
        if(!in_array($bucket,$this->getAllBuckets())){
            throw new Exception("bucket is not exist");
        }
        return true;
    }
}