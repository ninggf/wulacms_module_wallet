<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td>{$row.id}</td>
        <td>{$row.user_id}</td>
        <td>{$row.amount}</td>
        <td>{$types[$row.order_type].name}</td>
        <td>{$row.channel}</td>
        <td>{$row.order_id}</td>
        <td>{$row.status}</td>
        <td>{$row.create_time|date_format:'Y-m-d H:i:s'}</td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="10" class="text-center">暂无数据!</td>
    </tr>
{/foreach}
</tbody>