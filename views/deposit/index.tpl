<section class="hbox stretch wulaui" id="core-coins-table">
    <section>
        <section class="hbox stretch">
            <aside class="aside" id="admin-grid" >
                <section class="vbox wulaui" id="core-users-workset">
                    <header class="bg-light header b-b clearfix">
                        <div class="row m-t-sm">
                            <div class="col-sm-12 col-xs-12 m-b-xs text-right">
                                <form data-table-form="#core-record-table" class="form-inline">
                                    <div  class="input-group" >
                                        <select name="ctype" id="">
                                            <option value="">类型筛选</option>
                                            {foreach $currency_list as $k=>$list}
                                                {foreach $list['types'] as $key=>$type}
                                                    <option value="{$key}">{$type['name']}</option>
                                                {/foreach}
                                            {/foreach}
                                        </select>
                                    </div>
                                    <input type="hidden" name="type" class="input-sm form-control" value="{$currency}"/>
                                    <div class="input-group input-group-sm">
                                       <input type="text" name="user_id" class="input-sm form-control" placeholder="user_id" value="{$user_id}"/>
                                    </div>
                                    <div class="input-group input-group-sm">

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
                            <table id="core-record-table" data-auto data-table="{'wallet/deposit/data'|app}" data-sort="id,d"
                                   style="min-width: 800px">
                                <thead>
                                <tr>
                                    <th width="20">
                                        <input type="checkbox" class="grp"/>
                                    </th>
                                    <th width="80" data-sort="id,d">ID</th>
                                    <th width="80" data-sort="user_id,a">UID</th>
                                    <th width="80" data-sort="currency,a">币种</th>
                                    <th width="80" data-sort="type,a">收入类型</th>
                                    <th width="80" data-sort="amount,a">金额</th>
                                    <th width="80" data-sort="subject,a">业务</th>
                                    <th width="80" data-sort="subjectid,a">业务ID</th>
                                    <th width="80" data-sort="create_time,a">时间</th>
                                    <th width="100">ip</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </section>
                    <footer class="footer b-t">
                        <div data-table-pager="#core-record-table"></div>
                    </footer>
                </section>
            </aside>
            <aside class="aside hidden" id="acl-space"></aside>
        </section>
    </section>
</section>

