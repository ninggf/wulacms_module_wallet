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
 * DEC : 现金红包
 * User: David Wang
 * Time: 2018/10/25 下午3:58
 */

namespace wallet\classes\card;

class CashCard extends CardBase {
    public $name;
    public $workDay;
    public $type;
    public $denomination;
    public $desc;

    public function __construct($name, $type, $workDay, $denomination, $desc) {
        $this->name         = $name;
        $this->workDay      = $workDay;
        $this->denomination = $denomination;
        $this->desc         = $desc;
        $this->type         = $type;
    }

    public function name(): string {
        return $this->name;
    }

    public function type(): string {
        return $this->type;
    }

    public function workDay(): int {
        return $this->workDay;
    }

    public function denomination(): int {
        return $this->denomination;
    }

    public function description(): string {
        return $this->desc;
    }

    public function ruleCheck(array $data): bool {
        return true;
    }

}