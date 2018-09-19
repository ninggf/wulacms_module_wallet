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
 * Time: 2018/9/11 下午2:49
 */

namespace wallet\pay;

use wallet\pay\wxpay\Wxpay;

class Pay {
	private static $channels = false;
	/**
	 * 支付提现渠道.
	 *
	 */
	public static function channels() {
		if (self::$channels === false) {
			self::register(new Wxpay());
			fire('wallet\initPayChannel');
		}

		return self::$channels;
	}

	public static function register(PayChannel $channel){
		if (self::$channels === false) {
			self::$channels = [];
		}
		self::$channels[$channel->getId()]=$channel;
	}
	/**
	 * @param $channel
	 *
	 * @return \wallet\pay\PayChannel|null
	 */
	public static function getChannel(string $channel):?PayChannel{
		$channels = self::channels();
		if(!isset($channels[$channel])){
			return null;
		}

		return $channels[$channel];
	}
}