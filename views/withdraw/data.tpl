<tbody data-total="{$total}">
{foreach $items as $item}
    <tr class="{$tdCls[$item.status]}" {if $item.msg && $item.status == 'E'}rel="{$item.id}"{/if}>
        <td></td>
        <td>
            <input type="checkbox" value="{$item.id}" class="grp"/>
        </td>
        <td class="st">{$item.id}</td>
        <td>{$item.user_id}</td>
        <td>{$item.currency}</td>
        <td>{$item.amount}</td>
        <td>{$item.bank_name}</td>
        <td>{$item.bank_account}</td>
        <td>{$item.user_name}</td>
        <td>{$item.status_th}</td>
        <td>{$item.create_time|date_format:'Y-m-d H:i:s'}</td>
        <td>
            {if $item.status=='P' && $canApprove}
                <a href="{'wallet/withdraw/change/pass'|app}/{$item.id}" data-ajax data-confirm="确定『通过审核』吗?"
                   class="btn btn-xs btn-info" title="通过">通过</a>
            {/if}
            {if ($item.status=='P')&&$canRefuse}
                <a href="{'wallet/withdraw/refuse'|app}/{$item.id}" data-ajax="dialog"
                   data-title="拒绝[{$item.user_id}-{$item.user_name}] 提现" data-area="400px,auto"
                   class=" edit-task btn btn-xs btn-danger">拒绝</a>
            {/if}
            {if $item.status=='A' && $canPay}
                <a href="{'wallet/withdraw/change/pay'|app}/{$item.id}" data-ajax data-confirm="你真的要『支付』吗?"
                   class="btn btn-xs btn-warning" title="支付">支付</a>
            {/if}
            {if $item.status=='R'}
                已拒绝 :{$item.reject_msg}
            {/if}
            {if $item.status=='D'}
                已支付
            {/if}

        </td>
    </tr>
    {if $item.msg && $item.status == 'E'}
        <tr class="danger hidden">
            <td colspan="2"></td>
            <td colspan="8">{$item.msg}</td>
        </tr>
    {/if}
    {foreachelse}
    <tr>
        <td colspan="12" class="text-center">无数据</td>
    </tr>
{/foreach}
</tbody>