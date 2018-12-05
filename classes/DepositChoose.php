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
 * Time: 2018/10/26 下午3:47
 */

namespace wallet\classes;

class DepositChoose {
    public $error;

    /**
     * 获取支付需要的信息
     *
     * @param int    $mid        用户id
     * @param string $cur        币种
     * @param string $body       支付主题
     * @param string $channel    渠道 weixin|alipay...
     * @param string $order      订单号
     * @param string $accid      指定账号
     * @param string $amount     金额元
     * @param string $trade_type 支付类型 如果是微信(JSAPI,H5..)
     * @param string $openid     用户openid 可选
     *
     * @throws
     */
    public function getPayRequst(int $mid, string $cur, $order_type, string $body, string $channel, string $order, string $amount, string $accid, string $trade_type = '', string $openid = '') {
        if (!$mid || !$cur || !$order_type || !$amount || !$channel || !$order || !$accid) {
            $this->error = '参数错误';

            return false;
        }
        $currency = Currency::init($cur);
        if (!$currency) {
            $this->error = '未知币种实例';

            return false;
        }
        $wallet = Wallet::connect($mid);
        try {
            $createOrder = $wallet->newDepositOrder($currency, $amount, $order_type, $order);
        } catch (exception\WalletException $e) {
            $this->error = $e->getMessage();

            return false;
        }
        if (!$createOrder) {
            $this->error = '创建订单失败了';

            return false;
        }
        $payChannel = PayChannelManager::getChannel($channel);
        if (!$payChannel) {
            $this->error = $channel . '支付渠道不存在';

            return false;
        }
        if ($trade_type == 'JSAPI') {
            $rtn = $payChannel->getPayScript($body, $order, $amount, $trade_type, $accid, $openid);
        } else {
            $rtn = $payChannel->getPayUrl($body, $order, $amount, $trade_type, $accid, $openid);
        }
        if (!$rtn) {
            $this->error = $payChannel->error;

            return false;
        }

        return $rtn;

    }
}