<section class="vbox wulaui">
    <header class="bg-light header b-b clearfix">
        <div class="row m-t-sm">
            <div class="col-sm-6 col-xs-12 m-b-xs text-left"></div>
            <div class="col-sm-6 col-xs-12 m-b-xs text-right">
                <form data-table-form="#core-account-table" class="form-inline">
                    <div class="input-group input-group-sm">
                        <input type="text" name="mid" class="input-sm form-control" placeholder="UID"/>
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
            <table id="core-account-table" data-auto data-table="{'wallet/account/data'|app}/{$currency}"
                   data-sort="user_id,d" style="min-width: 800px">
                <thead>
                <tr>
                    <th width="20">
                        <input type="checkbox" class="grp"/>
                    </th>
                    <th width="100" data-sort="user_id,d">UID</th>
                    <th data-sort="amount,a">总额</th>
                    <th width="120" data-sort="balance,a">余额</th>
                    <th width="120" data-sort="balance1,a">可提现</th>
                    <th width="120">冻结</th>
                    <th width="150">创建时间</th>
                    <th width="100"></th>
                </tr>
                </thead>
            </table>
        </div>
    </section>
    <footer class="footer b-t">
        <div data-table-pager="#core-account-table"></div>
    </footer>
</section>