<section class="hbox stretch wulaui" id="workspace">
    <aside class="aside aside-sm b-r">
        <header class="bg-light header b-b clearfix">
            <p class="h4">收入类型</p>
        </header>
        <section class="scrollable w-f m-t-xs">
            <ul class="nav nav-pills nav-stacked no-radius" data-pop-menu="#core-role-pop-menu">
                <li class="active">
                    <a href="javascript:void(0);" class="item-li">全部</a>
                </li>
                {foreach $types as $tid => $type}
                    <li data-rid="{$tid}">
                        <a href="javascript:void(0);" class="item-li">{$type.name}</a>
                    </li>
                {/foreach}
            </ul>
        </section>
    </aside>
    <section id="admin-grid">
        <section class="vbox">
            <header class="bg-light header b-b clearfix">
                <div class="row m-t-sm">
                    <div class="col-sm-12 col-xs-12 m-b-xs text-right">
                        <form data-table-form="#core-record-table" class="form-inline">
                            <input type="hidden" id="ctype" name="ctype" value=""/>
                            <div class="input-group">
                                <select name="subject" id="subject" class="form-control">
                                    <option value="">-项目-</option>
                                    {foreach $subjects as $k=>$sub}
                                        {if $sub.income}
                                            <option value="{$k}">{$sub.name}</option>
                                        {/if}
                                    {/foreach}
                                </select>
                            </div>

                            <div class="input-group input-group-sm">
                                <input type="text" name="user_id" class="input-sm form-control" placeholder="user_id"
                                       value="{$user_id}"/>
                                <span class="input-group-btn">
                            <button class="btn btn-sm btn-info" id="btn-do-search" type="submit">Go!</button>
                        </span>
                            </div>
                        </form>
                    </div>
                </div>
            </header>
            <section class="w-f bg-white">
                <div class="table-responsive">
                    <table id="core-record-table" data-auto data-table="{'wallet/deposit/data'|app}/{$currency}"
                           data-sort="id,d" style="min-width: 800px">
                        <thead>
                        <tr>
                            <th width="160" data-sort="create_time,a">时间</th>
                            <th data-sort="id,d">收入流水号</th>
                            <th width="100" data-sort="user_id,a">UID</th>
                            <th width="100" data-sort="type,a">类型</th>
                            <th width="100" data-sort="amount,a">金额</th>
                            <th width="100" data-sort="subject,a">项目</th>
                            <th data-sort="subjectid,a">项目ID</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </section>
            <footer class="footer b-t">
                <div data-table-pager="#core-record-table"></div>
            </footer>
        </section>
    </section>
</section>
<script type="text/javascript">
	layui.use(['jquery', 'layer', 'wulaui'], ($) => {
		$('#workspace').on('click', 'a.item-li', function () { //分角色查看用户
			var me = $(this), mp = me.closest('li'), rid = mp.data('rid'), group = me.closest('ul');
			if (mp.hasClass('active')) {
				return;
			}
			group.find('li').not(mp).removeClass('active');
			mp.addClass('active');
			$('#ctype').val(rid ? rid : '');
			$('#btn-do-search').click();
			return false;
		}).on('change', '#subject', function () { //按状态查看用户
			$('#btn-do-search').click();
		});
	})
</script>