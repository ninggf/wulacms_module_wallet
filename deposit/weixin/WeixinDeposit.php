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
 * DEC :
 * User: David Wang
 * Time: 2018/10/26 上午10:16
 */

namespace wallet\deposit\weixin;

use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use wallet\deposit\DepositChannel;
use wulaphp\app\App;

class WeixinDeposit extends DepositChannel {
    private   $id     = 'weixin';
    protected $config = [];
    /**
     * @var \EasyWeChat\Payment\Application
     */
    protected $app;

    public function getName(): string {
        return '微信支付';
    }

    public function getSettingForm() {
        return '';
    }

    /**
     * 获取一个配置项的id key
     * @return string
     */
    public function getId() {

        return $this->id;
    }

    public function getDesc() {
        return '微信支付渠道';
    }

    public function onCallback(string $accid): string {
        $config    = $this->getTypeConfig($accid);
        $app       = Factory::payment($config);
        $this->app = $app;
        try {
            $response = $app->handlePaidNotify(function ($message, $fail) {
                if ($message['return_code'] === 'SUCCESS') {
                    // 用户是否支付成功
                    if ($message['result_code'] === 'SUCCESS') {
                        //查询订单
                        if ($this->queryOrder($message['transaction_id'])) {
                            apply_filter('wallet\on_wx_deposit_success', $message);
                        }

                        return true;
                    } else if ($message['result_code'] === 'FAIL') {
                        //失败处理
                        apply_filter('wallet\on_wx_deposit_fail', $message);

                        return true;
                    }
                } else {
                    return $fail('Order not exists.');
                }

                return true;
            });
        } catch (Exception $e) {
            log_error($e->getMessage(), 'weixn_pay');
        }
        ob_start();
        $response->send();

        return ob_end_clean();
    }

    public function getPayScript($body, $order, $amount, $trade_type, $accid = '', $openid = '') {
        $rtn = $this->createOrder($body, $order, $amount, $trade_type, $accid, $openid);
        if (!$rtn) {
            return false;
        }
        $rtn['timeStamp'] = $rtn['timestamp'];
        unset($rtn['timestamp']);

        return json_encode($rtn);
    }

    public function getPayUrl($body, $order, $amount, $trade_type, $accid = '', $openid = '') {
        $rtn = $this->createOrder($body, $order, $amount, $trade_type, $accid, $openid);
        if (!$rtn) {
            return false;
        }

        return $rtn['mweb_url'];

    }

    /**
     * 统一下单
     *
     * @param        $body
     * @param        $order
     * @param        $amount
     * @param        $trade_type
     * @param        $accid
     * @param string $openid
     *
     * @return array|bool
     */
    private function createOrder($body, $order, $amount, $trade_type, $accid, $openid = '') {
        $data['body']         = $body;
        $data['out_trade_no'] = $order;
        $data['total_fee']    = (int)($amount * 100);
        $data['trade_type']   = $trade_type;
        $data['openid']       = $openid;
        $data['accid']        = $accid;
        if (!$this->paramsCheck($data)) {
            return false;
        }
        $easyPay = Factory::payment($this->config);
        try {
            //参数组装
            $result = $easyPay->order->unify([
                'body'         => $data['body'],
                'out_trade_no' => $data['out_trade_no'],
                'total_fee'    => $data['total_fee'],
                'notify_url'   => $this->notify_url,
                'trade_type'   => $data['trade_type'], // 请对应换成你的支付方式对应的值类型
                'openid'       => $data['openid']
            ]);
        } catch (InvalidConfigException $e) {
            log_error($e->getMessage(), 'weixin_pay');
            $this->error = $e->getMessage();

            return false;
        }
        if ($result['return_code'] = 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $rtn = [];
            //根据支付类型返回需要的支付参数
            switch ($trade_type) {
                case 'JSAPI':
                    $rtn = $easyPay->jssdk->sdkConfig($result['prepay_id']);
                    break;
                case 'MWEB':
                case 'NATIVE':
                    $rtn['mweb_url'] = $result['mweb_url'];
                    break;
                case 'APP':
                    break;
                default:
                    $this->error = '未知支付类型';
            }
            if (empty($rtn)) {
                $this->error = '生成支付发送错误';

                return false;
            }

            return $rtn;
        } else {
            $this->error = $result['return_msg'];

            return false;
        }
    }

    /**
     * 查询第三方订单是否正常
     *
     * @param string $order_no
     * @param int    $config_id
     * @param int    $type 0交易号 1订单号
     *
     *
     * @return bool
     */
    public function queryOrder(string $order_no = '', int $type = 0, int $config_id = 0) {
        if ($config_id) {
            $config    = $this->getTypeConfig($config_id);
            $app       = Factory::payment($config);
            $this->app = $app;
        }
        if (!$this->app) {
            $this->error = 'app未实例化';

            return false;
        }
        try {
            if ($type == 0) {
                $result = $this->app->order->queryByTransactionId($order_no);
            } else {
                $result = $this->app->order->queryByOutTradeNumber($order_no);
            }
        } catch (InvalidConfigException $e) {
        }
        if (!empty($result['result_code']) && !empty($result['err_code'])) {

            log_error($result['err_code'], 'weixin_pay');

            return false;
        }
        if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
            $this->query_info = $result;

            return true;
        }

        return false;

    }

    public function paramsCheck(array $data): bool {
        if (!$data['body']) {
            $this->error = 'body参数缺失(支付主题)';

            return false;
        }
        if (!$data['out_trade_no']) {
            $this->error = 'out_trade_no参数缺失(订单号)';

            return false;
        }
        if (!$data['total_fee']) {
            $this->error = 'total_fee参数缺失(支付金额,分)';

            return false;
        }

        if (!$data['trade_type']) {
            $this->error = 'trade_type参数缺失(支付方式:jsapi H5)';

            return false;
        }
        if (($data['trade_type'] == 'JSAPI') && !$data['openid']) {
            $this->error = 'openid参数缺失(JSAPI不知道是谁要支付)';

            return false;
        }
        if (!$this->prepareConfig($data)) {
            return false;
        }

        return true;
    }

    public static function get_pay_channel($channel) {
        $channel['weixin'] = new self();

        return $channel;
    }

    private function prepareConfig(array $data): bool {
        $config = $this->getTypeConfig($data['accid']);
        if (!$config) {
            $this->error = '请先微信后台配置账号';

            return false;
        }
        if (empty($this->config)) {
            $this->error = '没有可支付的第三方账号';

            return false;
        }
        $notify_domain = App::cfg('notify_url@wallet');
        if (!$notify_domain) {
            $this->error = '请先在后台微信配置中配置回调域名';

            return false;
        }
        $this->notify_url = $notify_domain . 'wallet/gateway/' . $this->id . '/' . $data['accid'];

        return true;

    }


    private function getTypeConfig(string $accid) {
        try {
            if (!$accid) {
                return null;
            }
            $db = App::db();
            if (preg_match('/^[1-9]\d*$/', $accid)) {
                $acc = $db->queryOne('SELECT *  FROM {weixin_pay_setting} WHERE id=%d AND status=%d AND deleted=%d LIMIT 0,1', $accid, 1, 0);
            } else {
                $acc = $db->queryOne('SELECT * FROM {weixin_pay_setting} WHERE appid=%s AND status=%d AND deleted=%d LIMIT 0,1', $accid, 1, 0);
            }
            if (!$acc) {
                return null;
            }
            $acc['app_id']  = $acc['appid'];
            $this->config   = $acc;
            $this->configId = $acc['id'];
        } catch (\Exception $e) {
            return null;
        }

        return $this->config;
    }

    public function getConfigId(): int {
        return $this->configId;
    }

}