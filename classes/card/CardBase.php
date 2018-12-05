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
 * DEC : 卡券抽象类
 * User: David Wang
 * Time: 2018/10/25 下午3:18
 */

namespace wallet\classes\card;

abstract class CardBase {

    public $error = '';

    /**
     * 卡券名称
     *
     * @param string $name
     *
     * @return string
     */
    public abstract function name(): string;

    /**
     * @return string
     */
    public abstract function type(): string;

    /**
     * 有效天数
     * @return int
     */
    public abstract function workDay(): int;

    /**
     * 面额
     * @return int
     */
    public abstract function denomination(): int;

    /**
     * 描述一下吧
     * @return string
     */
    public abstract function description(): string;

    /**
     * 校验能否使用
     *
     * @param array $data
     *
     * @return bool
     */
    public abstract function ruleCheck(array $data): bool;

    /**
     * 错误信息
     * @return string
     */
    public function error(): string {
        return $this->error;
    }
}