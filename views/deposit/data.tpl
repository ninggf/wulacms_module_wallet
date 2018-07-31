<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td>
            <input type="checkbox" value="{$row.id}" class="grp"/>
        </td>
        <td>{$row.id}</td>
        <td>{$row.user_id}</td>
        <td>{$row.currency}</td>
        <td>{$row.type}</td>
        <td>{$row.amount}</td>
        <td>{$row.subject}</td>
        <td>{$row.subjectid}</td>
        <td>{$row.create_time|date_format:'Y-m-d H:i:s'}</td>
        <td>{$row.ip}</td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="{'core.admin.table'|tablespan:5}" class="text-center">暂无相关数据!</td>
    </tr>
{/foreach}
</tbody>