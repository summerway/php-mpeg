## 概述
该工具由[aminyazdanpanah/PHP-FFmpeg-video-streaming](https://github.com/aminyazdanpanah/PHP-FFmpeg-video-streaming)改写而成，兼容php7.0及以上版本。  
将ffmpeg常用操作包装简化，支持视频转码(video->mp4)和视频呢转流(video->dash、video->hls)功能。
视频文件可选自本地或者[七牛](https://qiniu.com)，转化后文件也可选择保留本地目录或者存储到七牛云端。

## 要求
1. 该工具要求7.0及以上。

2. 依赖 **[FFMpeg](https://ffmpeg.org/download.html)**。需要系统支持`FFMpeg` 和 `FFProbe`。

## 安装
``` bash
composer require maplesnow/php-ffmpeg
```

### 配置
指定ffmpeg初始配置参数

``` php
$config = [
    'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
    'ffprobe.binaries' => '/usr/bin/ffprobe',
    'timeout'          => 3600, // The timeout for the underlying process
    'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
];
    
$ffmpeg = Streaming\FFMpeg::create($config);
```

### 打开文件
目前支持2种打开方式

#### 本地
``` php
$video = $ffmpeg->open('/var/www/media/videos/test.mp4');
```

#### 七牛云
```php
$qiniu = new \Streaming\Qiniu($accessKey,$secretKey);
$video = $ffmpeg->fromQiniu($qiniu,$url);
```

### 视频转码
mp4格式输出
```php
$video->MP4()->X264()->save('mp4/demo.mp4');
```

### DASH
**[Dynamic Adaptive Streaming over HTTP (DASH)](https://en.wikipedia.org/wiki/Dynamic_Adaptive_Streaming_over_HTTP)**, also known as MPEG-DASH, is an adaptive bitrate streaming technique that enables high quality streaming of media content over the Internet delivered from conventional HTTP web servers.

``` php
$video->DASH()
    ->HEVC() // Format of the video. Alternatives: X264() and VP9()
    ->autoGenerateRepresentations() // Auto generate representations
    ->setAdaption('id=0,streams=v id=1,streams=a') // Set the adaption.
    ->save(); // It can be passed a path to the method or it can be null
```

自定义`Representation`参数
``` php
use Streaming\Representation;

$rep_1 = (new Representation())->setKiloBitrate(800)->setResize(1080 , 720);
$rep_2 = (new Representation())->setKiloBitrate(300)->setResize(640 , 360);

$video->DASH()
    ->HEVC()
    ->addRepresentation($rep_1) // Add a representation
    ->addRepresentation($rep_2) 
    ->setAdaption('id=0,streams=v id=1,streams=a') // Set a adaption.
    ->save('dash/test.mpd');
```
更多参数详见 **[DASH options](https://ffmpeg.org/ffmpeg-formats.html#dash-2)**

### HLS
**[HTTP Live Streaming (also known as HLS)](https://en.wikipedia.org/wiki/HTTP_Live_Streaming)** is an HTTP-based adaptive bitrate streaming communications protocol implemented by Apple Inc. as part of its QuickTime, Safari, OS X, and iOS software. Client implementations are also available in Microsoft Edge, Firefox and some versions of Google Chrome. Support is widespread in streaming media servers.

``` php
$video->HLS()
    ->X264()
    ->autoGenerateRepresentations([720, 360]) // You can limit the numbers of representatons
    ->save();
```
自定义`Representation`参数
``` php
use Streaming\Representation;

$rep_1 = (new Representation())->setKiloBitrate(1000)->setResize(1080 , 720);
$rep_2 = (new Representation())->setKiloBitrate(500)->setResize(640 , 360);
$rep_3 = (new Representation())->setKiloBitrate(200)->setResize(480 , 270);

$video->HLS()
    ->X264()
    ->setHlsBaseUrl('https://bucket.s3-us-west-1.amazonaws.com/videos') // Add a base URL
    ->addRepresentation($rep_1)
    ->addRepresentation($rep_2)
    ->addRepresentation($rep_3)
    ->setHlsTime(5) // Set Hls Time. Default value is 10 
    ->setHlsAllowCache(false) // Default value is true 
    ->save();
```
**NOTE:** 不能使用`HEVC` 和 `VP9` 格式

#### HLS加密
The encryption process requires some kind of secret (key) together with an encryption algorithm. HLS uses AES in cipher block chaining (CBC) mode. This means each block is encrypted using the ciphertext of the preceding block. [Learn more](https://en.wikipedia.org/wiki/Block_cipher_mode_of_operation)

You need to pass both `URL to the key` and `path to save a random key` to the `generateRandomKeyInfo` method:
``` php
//A path you want to save a random key on your server
$save_to = '/var/www/my_website_project/keys/enc.key';

//A URL (or a path) to access the key on your website
$url = 'https://www.aminyazdanpanah.com/keys/enc.key';// or '/keys/enc.key';

$video->HLS()
    ->X264()
    ->setTsSubDirectory('ts_files')// put all ts files in a subdirectory
    ->generateRandomKeyInfo($url, $save_to)
    ->autoGenerateRepresentations([1080, 480, 240])
    ->save('/var/www/media/videos/hls/test.m3u8');
```
**NOTE:** It is very important to protect your key on your website using a token or a session/cookie(****It is highly recommended****).    

更多参数详见 **[HLS options](https://ffmpeg.org/ffmpeg-formats.html#hls-2)** 

### 保存文件
两种文件存储方式
#### 本地保存
指定目录保存文件
``` php
$dash = $video->DASH()
            ->HEVC()
            ->autoGenerateRepresentations()
            ->setAdaption('id=0,streams=v id=1,streams=a');
            
$dash->save('dash/test.mpd');
```
默认保存目录为文件来源目录
``` php
$hls = $video->HLS()
            ->X264()
            ->autoGenerateRepresentations();
            
$hls->save();
```

#### 保存至七牛云
``` php
$mp4 = $video->MP4()->X264();
$qiniu = new \Streaming\Qiniu($accessKey,$secretKey);
$mp4->save2qiniu($qiniu,'bucket-name')
```

## 开源的播放插件
你可以用下列播放你转换的流文件
- **WEB**
    - DASH and HLS: **[video.js](https://github.com/videojs/video.js)**
    - DASH: **[dash.js](https://github.com/Dash-Industry-Forum/dash.js)**

