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
 * Time: 2018/10/26 下午5:34
 */

namespace wallet\classes;

class PayChannelManager {
    /**
     * 依据名称获取channel
     *
     * @param $channel
     *
     * @return \wallet\deposit\DepositChannel
     */
    public static function getChannel($channel) {
        $channels = self::getChannels();
        if (isset($channels[ $channel ])) {
            return $channels[ $channel ];
        } else {
            return null;
        }
    }

    public static function getChannels() {
        $channels = apply_filter('get_pay_channel', []);

        return $channels;
    }
}