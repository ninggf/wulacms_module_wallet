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
 * DEC : 支付回调处理
 * User: David Wang
 * Time: 2018/10/26 下午5:46
 */

namespace wallet\controllers;

use wallet\classes\PayChannelManager;
use wallet\deposit\DepositChannel;
use wulaphp\io\Response;
use wulaphp\mvc\controller\Controller;

class GatewayController extends Controller {
    /**
     * @param string $channel 支付渠道 weixin alipay
     * @param string $accid   渠道下的具体账号
     *
     * @return string
     */
    public function index($channel, $accid) {
        $c = PayChannelManager::getChannel($channel);
        if ($c instanceof DepositChannel) {
            return $c->onCallback($accid);
        }
        Response::respond(403);
    }

    public function index_post($channel, $accid) {
        return $this->index($channel, $accid);
    }
}