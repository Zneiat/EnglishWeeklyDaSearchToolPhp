<?php
/**
 * EnglishWeeklyDaSearchTool
 * Author: https://github.com/Zneiat
 */
$checkCodeNum = initPassWord();
define('__CHECKCODE__',$checkCodeNum.'个滑稽币');
header('Content-type: application/json');
$op = @addslashes(htmlspecialchars(trim($_POST['op'])));
$checkCode = @addslashes(htmlspecialchars(trim($_POST['checkCode'])));
if($checkCode!=__CHECKCODE__&&substr($checkCode,0,strlen($checkCodeNum))!=$checkCodeNum){
    returnJson(['msg'=>'提取码不正确'],false);
}
switch ($op){
    case 'weekly':
        $how = @intval(trim($_POST['how']));
        if ($how < 10) {
            $how = "0".$how;
        }
        if ($how > 99) {
            returnJson(['msg'=>'没有可以找到的答案了'],false);
        }
        if ($how < 1) {
            returnJson(['msg'=>'什么鬼？！'],false);
        }
        $cacheFile = 'cache.json';
        // 判断是否有缓存文件
        if(!file_exists($cacheFile)){
            $nullJson = json_encode([]);
            @file_put_contents($cacheFile, $nullJson);
        }
        $cacheContent = @json_decode(file_get_contents($cacheFile),true);
        if(file_exists($cacheFile)&&!empty($cacheContent["AS".$how])){
            returnJson(['content'=>trim($cacheContent["AS".$how])]);
            die();
        }
        // Curl 一次请求
        $firstReq = json_decode(downloadPage('http://app.ew.com.cn/Weekly/index.php?c=ResourceController&a=getResourceById&id=00538023'.$how.'80&from=true'),true);
        if(!is_array($firstReq)||$firstReq['resultcode']==false||empty($firstReq['result'][0]['resource_path'])){
            returnJson(['msg'=>'答案获取失败'],false);
        }
        $getResourcePath = $firstReq['result'][0]['resource_path'];
        // Curl 二次请求
        $secondReq = downloadPage($getResourcePath.'/question.txt');
        if(empty($secondReq)){
            returnJson(['msg'=>'答案获取失败'],false);
        }
        // 写入缓存
        $cacheContent = array_merge($cacheContent,["AS$how"=>$secondReq]);
        @file_put_contents($cacheFile, json_encode($cacheContent));
        returnJson(['content'=>trim($secondReq)]);
        break;
    default:
        returnJson(['msg'=>'???'],false);
        break;
}
function returnJson($arr,$success=true){
    echo json_encode(array_merge($arr,['success'=>$success]));
    if($success==false){
        die();
    }
}
function downloadPage($url,$post=null,$referer=null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    if(!is_null($post)){
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $header = array();
    $header[0]  = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; // browsers keep this blank.
    curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
    if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    if(!is_null($referer)){
        curl_setopt($url, CURLOPT_REFERER, $referer); // 来源页
    }
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate'); // 解码 gzip
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,15); // 访问超时
    return curl_exec($curl);
}
function initPassWord(){
    $nowMonth = date('n'); // 2016年1月3日 => 1
    $nowHour = date('G'); // 下午23点 => 0
    $anotherNum = (date('A')=='AM')?1:2; // 下午23点 => 2
    $firstSum = ($nowMonth+$nowHour)*$anotherNum;
    $anotherAddend = pow($firstSum,strlen(intval($firstSum)));
    $anotherAddendEndOfThree = strlen(intval($anotherAddend))>3?substr($anotherAddend,-3):$anotherAddend;
    $sumAll = $firstSum + $anotherAddendEndOfThree;
    return $sumAll;
}