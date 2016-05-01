<?php

namespace Alipay;

class Util {

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $params array 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    public static function createLinkString($params) {
        $arg = [];
        foreach ($params as $key => $param) {
            $arg[]=sprintf('%s=%s',$key,$param);
        }
        return join('&',$arg);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $params array 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    public static  function createLinkStringUrlencoded($params) {
        $arg = [];
        foreach ($params as $key => $param) {
            $arg[]=sprintf('%s=%s',$key,urlencode($param));
        }
        return join('&',$arg);
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $params string 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    public static function paramsFilter($params) {
        $params_filter = array();
        while (list ($key, $val) = each ($params)) {
            if($key == 'sign' || $key == 'sign_type' || $val == '')continue;
            else	$params_filter[$key] = $params[$key];
        }
        return $params_filter;
    }

    /**
     * 对数组排序
     * @param $params array 排序前的数组
     * @return array 排序后的数组
     */
    public static function argSort($params) {
        ksort($params);
//        reset($params);
        return $params;
    }

    /**
     * 远程获取数据，POST模式
     * @param $url string 指定URL完整路径地址
     * @param $cacert_url string 指定当前工作目录绝对路径
     * @param $params string 请求的数据
     * @param $input_charset string 编码格式。默认值：空值
     * @return string 远程输出的数据
     */
    public static function getHttpResponsePOST($url, $cacert_url, $params, $input_charset = '') {
        if (trim($input_charset) != '') {
            $url = $url."_input_charset=".$input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$params);// post传输数据
        $responseText = curl_exec($curl);
        if(curl_error($curl)!=''){
          throw new \Alipay\Exception\Exception('curl post error',-1);
        }
        curl_close($curl);
        return $responseText;
    }


    /**
     * 远程获取数据，GET模式
     * @param $url string 指定URL完整路径地址
     * @param $cacert_url string 指定当前工作目录绝对路径
     * @return  string 远程输出的数据
     */
    public static function getHttpResponseGET($url,$cacert_url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
        $responseText = curl_exec($curl);
        if(curl_error($curl)!=''){
            throw new \Alipay\Exception\Exception('curl get error',-1);
        }
        curl_close($curl);
        return $responseText;
    }

    /**
     * 实现多种字符编码方式
     * @param $input string 需要编码的字符串
     * @param $_output_charset string 输出的编码格式
     * @param $_input_charset string 输入的编码格式
     * @return string 编码后的字符串
     * @throws Exception\Exception
     */
    public static function charsetEncode($input,$_output_charset ,$_input_charset) {
        $output = "";
        if(!isset($_output_charset) )$_output_charset  = $_input_charset;
        if($_input_charset == $_output_charset || $input ==null ) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input,$_output_charset,$_input_charset);
        } elseif(function_exists("iconv")) {
            $output = iconv($_input_charset,$_output_charset,$input);
        } else {
            throw new \Alipay\Exception\Exception('sorry, you have no libs support for charset change',-1);
        }
        return $output;
    }

    /**
     * 实现多种字符解码方式
     * @param $input string 需要解码的字符串
     * @param $_output_charset string 输出的解码格式
     * @param $_input_charset string 输入的解码格式
     * @return string 解码后的字符串
     */
    public static function charsetDecode($input,$_input_charset ,$_output_charset) {
        $output = "";
        if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
        if($_input_charset == $_output_charset || $input ==null ) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input,$_output_charset,$_input_charset);
        } elseif(function_exists("iconv")) {
            $output = iconv($_input_charset,$_output_charset,$input);
        } else{
            throw new \Alipay\Exception\Exception('sorry, you have no libs support for charset change',-1);
        }
        return $output;
    }

    /**
     * 签名字符串
     * @param $prestr string 需要签名的字符串
     * @param $key string 私钥
     * @return string 签名结果
     */
    public static function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     * @param $prestr string 需要签名的字符串
     * @param $sign string 签名结果
     * @param $key string 私钥
     * @return bool 签名结果
     */
    public static function md5Verify($prestr, $sign, $key) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if($mysgin == $sign) {
            return true;
        }
        else {
            return false;
        }
    }

}