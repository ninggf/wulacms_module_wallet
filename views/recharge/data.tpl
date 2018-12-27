<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td>{$row.id}</td>
        <td>{$row.user_id}</td>
        <td>{$row.amount}</td>
        <td>{$row.order_type}</td>
        <td>{$row.channel}</td>
        <td>{$row.order_id}</td>
        <td>{$row.status}</td>
        <td>{$row.create_time|date_format:'Y-m-d H:i:s'}</td>
        <td class="text-right">
            {if $row.statusType=='P'&&$row.can_confirm}
                <a href="{'wallet/recharge/restoration'|app}/{$row.id}" data-confirm="你真的要对账吗?" data-ajax
                   class="btn btn-xs btn-primary">
                    <i class="fa fa-cny">对账</i>
                </a>
            {/if}
        </td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="10" class="text-center">暂无数据!</td>
    </tr>
{/foreach}
</tbody>