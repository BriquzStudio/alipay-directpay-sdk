<?php

namespace Alipay;

class Notify {
    /**
     * HTTPS形式消息验证地址
     */
    protected $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     */
    protected $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    /**
     * @var $alipay_config array
     */
    protected $alipay_config;

    public function __construct($alipay_config){
        $this->alipay_config = $alipay_config;
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @param $post_data array
     * @return bool 验证结果
     */
    public function verifyNotify($post_data){
        if(empty($post_data)) {//判断POST来的数组是否为空
            return false;
        }
        else {
            //生成签名结果
            $isSign = $this->getSignVerify($post_data, $post_data['sign']);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'false';
            if (!empty($post_data['notify_id'])){
                $responseTxt = $this->getResponse($post_data['notify_id']);
            }
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match('/true$/i',$responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }



    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @param $get_data array
     * @return bool 验证结果
     */
    public function verifyReturn($get_data){
        if(empty($get_data)) {//判断GET来的数组是否为空
            return false;
        }
        else {
            //生成签名结果
            $isSign = $this->getSignVerify($get_data, $get_data['sign']);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'false';
            if (!empty($_GET['notify_id'])) {
                $responseTxt = $this->getResponse($get_data['notify_id']);
            }
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match('/true$/i',$responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp array 通知返回来的参数数组
     * @param $sign string 返回的签名结果
     * @return bool 签名验证结果
     */
    protected function getSignVerify($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = \Alipay\Util::paramsFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = \Alipay\Util::argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = \Alipay\Util::createLinkString($para_sort);
        $isSign = false;
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case 'MD5' :
                $isSign = \Alipay\Util::md5Verify($prestr, $sign, $this->alipay_config['key']);
                break;
            default :
                $isSign = false;
        }
        return $isSign;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id string 通知校验ID
     * @return string 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    protected function getResponse($notify_id) {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        if($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        }
        else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = sprintf('%spartner=%s&notify_id=%s',$veryfy_url,$partner,$notify_id);
        $responseTxt = \Alipay\Util::getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
        return $responseTxt;
    }
}