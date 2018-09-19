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
 * Time: 2018/9/11 下午6:45
 */

namespace wallet\classes\form;

use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class WxpayForm extends FormTable {
	use JQueryValidator;
	public $table = null;
	/**
	 * appid
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   对应公众号的appid
	 * @layout 1,col-xs-6
	 */
	public $appid;

	/**
	 * key
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   商户秘钥
	 * @layout 1,col-xs-6
	 */
	public $key;

	/**
	 * mch_id
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   商户id
	 * @layout 2,col-xs-6
	 */
	public $mch_id;

	/**
	 * 服务端ip
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   服务端ip
	 * @layout 2,col-xs-6
	 */
	public $spbill_create_ip;


	/**
	 * 证书路径PEM
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   证书路径PEM
	 * @layout 3,col-xs-6
	 */
	public $pem_path;

	/**
	 * 证书路径KEY
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   证书路径KEY
	 * @layout 3,col-xs-6
	 */
	public $key_path;


	/**
	 * account
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   账号标识(唯一)
	 * @layout 4,col-xs-6
	 */
	public $account;


	/**
	 * 优先级
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @note   越大越优先
	 * @layout 4,col-xs-6
	 */
	public $priority;
}