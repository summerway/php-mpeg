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
use Streaming\Helpers\FileManager;
use Exception;
use Streaming\Helpers\Helper;

class Qiniu {

    const
        TYPE_FILE = 'file',
        TYPE_DIRECTORY = 'directory'
    ;

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
     * 获取url中的目录值
     * @param $url
     * @return string
     */
    public function getUrlDirectoryName($url){
        Helper::isURL($url);
        $urlArr = explode("/",$url);
        $dirArr = array_slice($urlArr,3,-1);
        return $dirArr ? implode("/",$dirArr) : "";
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
     * @param string $bucket 七牛空间名称
     * @param string $path 上传文件源
     * @param bool $preserve 是否保存源文件
     * @param bool $withDirName 上传的文件是否带目录名称
     * @return array
     * @throws Exception
     */
    public function upload($bucket, $path, $preserve = true, $withDirName = true) {
        $this->checkBucket($bucket);
        $token = $this->qiniu->uploadToken($bucket);
        if(is_dir($path)){
            $prefix = pathinfo($path,PATHINFO_FILENAME);
            $data = [];
            foreach(FileManager::files($path) as $file){
                $saveName = $withDirName ? $prefix . DIRECTORY_SEPARATOR . $file : $file;
                $data[] = $this->uploadFile($bucket,$path .DIRECTORY_SEPARATOR . $file,$token,$preserve,$saveName);
            }

            return 1 == count($data) ? Arr::first($data) : [
                'type' => $this::TYPE_DIRECTORY,
                'url' => $this->getDomain($bucket) . DIRECTORY_SEPARATOR . $prefix,
                'dirName' => $prefix,
                'data' => $data
            ];
        }else{
            return $this->uploadFile($bucket,$path,$token,$preserve);
        }
    }

    /**
     * 上传单文件
     * @param string $bucket 七牛空间名称
     * @param string $file 上传文件源
     * @param string|null $token
     * @param bool $preserve 是否保存源文件
     * @param string|null $saveName 保存名称
     * @return array [
     *      "filename" 文件名
     *      "cloudHash" 云端hash
     *      "url" 文件链接
     *      "md5" 文件md5值
     * ]
     * @throws Exception
     */
    public function uploadFile($bucket, $file, $token = null, $preserve = true, $saveName = null){
        $this->checkBucket($bucket);
        is_null($token) && $token = $this->qiniu->uploadToken($bucket);

        if(!file_exists($file)){
            throw new Exception("Qiniu upload {$file} not found");
        }
        is_null($saveName) && $saveName = basename($file);

        $uploadMgr = new UploadManager();
        list($res, $err) = $uploadMgr->putFile($token, $saveName, $file);

        if (is_null($res)) {
            /** @var Error $err */
            throw new Exception("Qiniu upload {$saveName} failed :".$err->message());
        }

        !$preserve && @unlink($file);

        $filename = $res['key'] ?? "";
        $cloudHash = $res['hash'] ?? "";
        $url = $this->getDomain($bucket) . DIRECTORY_SEPARATOR . $res['key'];
        $md5 = md5_file($this->privateDownloadUrl($url));
        return [
            'type' => $this::TYPE_FILE,
            'data' => compact('filename','cloudHash','url','md5')
        ];
    }

    /**
     * 获取域名列表
     * @param string $bucket 七牛空间名
     * @param bool $ignoreTmp 忽略零时域名
     * @return array
     */
    public function getDomains($bucket,$ignoreTmp = false) {
        $domains = Arr::collapse($this->bucketMgr->domains($bucket));

        $domains = array_map(function($val){
            return 'http://'.$val;
        },$domains);
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
     * 获取空间文件列表
     * @param string $bucket 空间名
     * @param string|null $prefix 前缀
     * @return array
     * @throws Exception
     */
    public function listFiles($bucket,$prefix = null){
        $this->checkBucket($bucket);
        $files =  Arr::collapse($this->bucketMgr->listFiles($bucket,$prefix));

        return array_column(array_get($files,'items'),'key');
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
     * 批量删除文件
     * @param string $bucket 七牛空间名称
     * @param array $files 文件列表
     * @return bool
     * @throws Exception
     */
    public function batchDeleteFiles($bucket,array $files) {
        foreach ($files as $file){
            $this->deleteFile($bucket,$file);
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