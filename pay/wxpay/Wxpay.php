<?php
/**
 * //                            _ooOoo_
 * //                           o8888888o
 * //                           88" . "88
 * //                           (| -_- |)
 * //                            O\ = /O
 * //                        ____/`---'\____
 * //                      .   ' \\| |// `.
 * //                       / \\||| : |||// \
 * //                     / _||||| -:- |||||- \
 * //                       | | \\\ - /// | |
 * //                     | \_| ''\---/'' | |
 * //                      \ .-\__ `-` ___/-. /
 * //                   ___`. .' /--.--\ `. . __
 * //                ."" '< `.___\_<|>_/___.' >'"".
 * //               | | : `- \`.;`\ _ /`;.`/ - ` : | |
 * //                 \ \ `-. \_ __\ /__ _/ .-` / /
 * //         ======`-.____`-.___\_____/___.-`____.-'======
 * //                            `=---='
 * //
 * //         .............................................
 * //                  佛祖保佑             永无BUG
 * DEC : 渠道设置
 * User: David Wang
 * Time: 2018/9/11 下午1:30
 */

namespace wallet\pay\wxpay;

use wallet\classes\form\WxpayForm;
use wallet\pay\PayChannel;
use wulaphp\form\FormTable;

class Wxpay extends PayChannel {
	//请求微信支付URL
	private $query_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
	private $key;//秘钥
	private $cert_pem;//证书pem
	private $cert_key;//证书key
	private $mch_id;//商户id
	private $appid;//appid
	private $spbill_ip;//服务器IP

	public function getId(): string {
		return 'wxpay';
	}

	public function getName(): string {
		return '微信提现渠道';
	}

	public function pay(string $account, array $withdraw_info): string {
		if (!$withdraw_info) {
			$this->error = '记录不存在';

			return '';
		}
		if (!$withdraw_info['bank_account'] || $withdraw_info['amount'] < 1 || $withdraw_info['status'] != 'A') {
			$this->error = '信息不完整';

			return '';
		}
		$account_info = $this->account;
		if (!$account_info) {
			$this->error = '账号信息不存在';

			return '';
		}
		$req['mch_appid']        = $this->appid;
		$req['mchid']            = $this->mch_id;
		$req['partner_trade_no'] = $this->cr_order_no('jwkj');
		$req['nonce_str']        = strtoupper(rand_str(32, 'a-z,0-9'));
		$req['openid']           = $withdraw_info['bank_account'];
		$req['check_name']       = 'NO_CHECK';
		$req['amount']           = intval($withdraw_info['amount'] * 100);
		$req['desc']             = '提现';
		$req['spbill_create_ip'] = $this->spbill_ip;
		$req['sign']             = $this->createSign($req);
		$xml                     = $this->data_to_xml($req);
		$response                = $this->postXmlCurl($xml, $this->query_url, true);
		if (!$response) {
			return '';
		}
		$result = $this->xml_to_data($response);
		if (!empty($result['result_code']) && !empty($result['err_code'])) {
			log_error(json_encode($result), 'wxpay_with');
			$this->error = $result['err_code_des'];

			return '';
		}
		if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {

			return $result['payment_no'];
		}

		return '';
	}

	public function validate($account): bool {
		$accounts = $this->getAccounts($account);
		if (!$accounts) {
			$this->error = '账号信息不存在';

			return false;
		}
		//校验账号信息
		$this->cert_pem  = $this->account['pem_path'];
		$this->cert_key  = $this->account['key_path'];
		$this->mch_id    = $this->account['mch_id'];
		$this->key       = $this->account['key'];
		$this->appid     = $this->account['appid'];
		$this->spbill_ip = $this->account['spbill_create_ip'];
		if (!$this->cert_pem || !$this->cert_key || !$this->mch_id || !$this->appid || !$this->spbill_ip) {
			$this->error = '账号信息不全';

			return false;
		}

		return true;
	}

	public function getConfigForm(): ?FormTable {
		return new WxpayForm(true);
	}

	/**
	 * 生成订单编号
	 *
	 * @param string $prefix 前缀
	 *
	 * @return string
	 */
	protected function cr_order_no(string $prefix = ''): string {
		return $prefix . date('YmdHis') . substr(microtime(), 2, 6);
	}

	public function createSign(array $params, string $type = 'md5'): string {
		//签名步骤一：按字典序排序数组参数
		ksort($params);
		$string = $this->ToUrlParams($params);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=" . $this->key;
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);

		return $result;
	}

	public function checkSign(array $arr, string $sign = ''): bool {
		unset($arr['sign']);
		$get_sign = $this->createSign($arr);

		return $get_sign == $sign;
	}

	/**
	 * 以post方式提交xml到对应的接口url
	 *
	 * @param string $xml     需要post的xml数据
	 * @param string $url     url
	 * @param bool   $useCert 是否需要证书，默认不需要
	 * @param int    $second  url执行超时时间，默认30s
	 *
	 * @return bool|array
	 *
	 */
	private function postXmlCurl($xml, $url, $useCert = false, $second = 30) {
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, false);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($useCert == true) {
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLCERT, $this->cert_pem);
			curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLKEY, $this->cert_key);
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if ($data) {
			curl_close($ch);

			return $data;
		} else {
			$error       = curl_errno($ch) . ':' . curl_error($ch);
			$this->error = $error;
			log_warn($error, 'wx_pay_log');
			curl_close($ch);

			return false;
		}
	}

	/**
	 * 将参数拼接为url: key=value&key=value
	 *
	 * @param   $params
	 *
	 * @return  string
	 */
	public function ToUrlParams(array $params): string {
		$string = '';
		if (!empty($params)) {
			$array = array();
			foreach ($params as $key => $value) {
				$array[] = $key . '=' . $value;
			}
			$string = implode("&", $array);
		}

		return $string;
	}

	/**
	 * 输出xml字符
	 *
	 * @param  array $params 参数名称
	 *
	 * @return   string      返回组装的xml
	 **/
	public function data_to_xml(array $params): string {
		if (!is_array($params) || count($params) <= 0) {
			return false;
		}
		$xml = "<xml>";
		foreach ($params as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";

		return $xml;
	}

	/**
	 * 将xml转为array
	 *
	 * @param string $xml
	 *
	 * @return array
	 */
	public function xml_to_data($xml): array {
		if (!$xml) {
			return [];
		}
		//将XML转为array
		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);
		$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

		return $data;
	}

}