<section class="vbox wulaui" id="tpl-list">
    <section>
        <div class="table-responsive">
            <table data-table>
                <thead>
                <tr>
                    <th width="20"></th>
                    <th width="120">渠道ID</th>
                    <th width="120">渠道名称</th>
                    <th width="100"></th>
                </tr>
                </thead>
                <tbody>
                {foreach $channels as $channel}
                    <tr>
                        <td></td>
                        <td>{$channel['id']}</td>
                        <td>{$channel['name']}</td>
                        <td>
                            <a href="{'wallet/paychannel/tpl'|app}/{$channel['id']}" data-tab="&#xe63c;" class="btn btn-xs btn-primary"
                               title="渠道账号:{$channel['name']}">
                                <i class="fa fa-pencil-square-o"></i>
                            </a>
                        </td>
                    </tr>
                {/foreach}

                </tbody>
            </table>
        </div>
    </section>
</section>
