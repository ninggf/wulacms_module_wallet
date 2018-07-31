<section class="hbox stretch wulaui" id="core-coins-table">
    <aside class="aside aside-md-small b-r">
        <section class="vbox">
            <header class="header bg-light b-b">
                <button class="btn btn-icon btn-default btn-sm pull-right visible-xs m-r-xs" data-toggle="class:show"
                        data-target="#core-role-wrap">
                    <i class="fa fa-reorder"></i>
                </button>
                <p class="h4">币种列表</p>
            </header>
            <section class="hidden-xs scrollable w-f m-t-xs" id="core-role-wrap">
                <div id="core-role-list" >
                    <ul class="nav nav-pills nav-stacked no-radius" >
                        {foreach $currency as $k=>$list}
                            <li data-rid="{$k}">
                                <a href="javascript:void(0);" class="role-li">{$k}</a>
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </section>

        </section>
    </aside>
    <section>
        <section class="hbox stretch">
            <aside class="aside" id="admin-grid" >
                <section class="vbox wulaui" id="core-users-workset">
                    <header class="bg-light header b-b clearfix">
                        <div class="row m-t-sm">
                            <div class="col-sm-6 col-xs-12 m-b-xs text-left" >
                                <form class="form-inline" id="detail" style="display: none">
                                    <div  class="input-group input-group-sm">币种:<span id="c_name">11</span></div>
                                     <div  class="input-group input-group-sm">提现:<span id="c_with">11</span></div>
                                     <div  class="input-group input-group-sm">汇率:<span id="c_rate">11</span></div>
                                </form>
                            </div>
                            <div class="col-sm-6 col-xs-12 m-b-xs text-right">
                                <form data-table-form="#core-account-table" class="form-inline">
                                    <input type="hidden" id="form-type-val" name="type" value="{$type}"/>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="mid" class="input-sm form-control" placeholder="UID"/>
                                    </div>
                                    <div class="input-group input-group-sm">

                                        {*<input type="text" name="q" class="input-sm form-control" placeholder="类型名称"/>*}
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
                            <table id="core-account-table" data-auto data-table="{'wallet/data'|app}" data-sort="user_id,d"
                                   style="min-width: 800px">
                                <thead>
                                <tr>
                                    <th width="20">
                                        <input type="checkbox" class="grp"/>
                                    </th>
                                    <th width="50" data-sort="user_id,d">UID</th>
                                    <th width="50">币种</th>
                                    <th width="100" data-sort="amount,a">总额</th>
                                    <th width="100" data-sort="balance,a">余额</th>
                                    <th width="100" data-sort="balance1,a">可提现</th>
                                    <th width="100">冻结</th>
                                    <th width="100">创建时间</th>
                                    <th width="150">操作</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </section>
                    <footer class="footer b-t">
                        <div data-table-pager="#core-account-table"></div>
                    </footer>
                </section>
            </aside>
            <aside class="aside hidden" id="acl-space"></aside>
        </section>
    </section>
</section>


    <script>
		layui.use(['jquery', 'layer', 'wulaui'], function ($, layer) {
           $('ul>li:first-child').addClass('active');
			//对话框处理
			$('#core-coins-table').on('before.dialog', '.edit-admin', function (e) { // 增加编辑用户
				e.options.btn = ['保存', '取消'];
				e.options.yes = function () {
					$('#core-admin-form').on('ajax.success', function () {
						layer.closeAll()
					}).submit();
					return false;
				};
			}).on('click', 'a.role-li', function () {
				var me = $(this), mp = me.closest('li'), rid = mp.data('rid'), group = me.closest('ul');

				if (mp.hasClass('active')) {
					return;
				}
				group.find('li').not(mp).removeClass('active');
				mp.addClass('active');
				$('#form-type-val').val(rid ? rid : '');
				$('[data-table-form="#core-account-table"]').submit();
				return false;
			});
		})
		;
    </script>
