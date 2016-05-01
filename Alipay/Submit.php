<?php

namespace Alipay;

class Submit {

    /**
     * @var $alipay_config array
     */
    protected $alipay_config;

    /**
     *支付宝网关地址（新）
     */
    protected $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';

    public function __construct($alipay_config){
        $this->alipay_config = $alipay_config;
    }

    /**
     * 生成签名结果
     * @param $params_sort array 已排序要签名的数组
     * @return string 签名结果字符串
     */
    private function buildRequestMySign($params_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr =\Alipay\Util::createLinkString($params_sort);
        $mysign = "";
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case 'MD5' :
                $mysign = \Alipay\Util::md5Sign($prestr, $this->alipay_config['key']);
                break;
            default :
                $mysign = "";
        }
        return $mysign;
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $params_temp array 请求前的参数数组
     * @return array 要请求的参数数组
     */
    private function buildRequestParams($params_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = \Alipay\Util::paramsFilter($params_temp);
        //对待签名参数数组排序
        $para_sort = \Alipay\Util::argSort($para_filter);
        //生成签名结果
        $mysign = $this->buildRequestMySign($para_sort);
        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
        return $para_sort;
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $params_temp array 请求前的参数数组
     * @return string 要请求的参数数组字符串
     */
    private function buildRequestParaToString($params_temp) {
        //待请求参数数组
        $para = $this->buildRequestParams($params_temp);
        //把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
        $request_data = \Alipay\Util::createLinkStringUrlencoded($para);
        return $request_data;
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp array 请求参数数组
     * @param $method string 提交方式。两个值可选：post、get
     * @param $button_name string 确认按钮显示文字
     * @return string 提交表单HTML文本
     */
    public function buildRequestForm($para_temp, $method, $button_name) {
        //待请求参数数组
        $para = $this->buildRequestParams($para_temp);

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."'>";
        while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='".$button_name."' style='display:none;'></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }
}