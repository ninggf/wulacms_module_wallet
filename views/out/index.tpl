<section class="vbox wulaui">
    <header class="bg-light header b-b clearfix">
        <div class="row m-t-sm">
            <div class="col-sm-12 col-xs-12 m-b-xs text-right">
                <form data-table-form="#core-record-table" class="form-inline">
                    <div class="input-group">
                        <select name="subject" id="subject" class="form-control">
                            <option value="">-项目-</option>
                            {foreach $subjects as $k=>$sub}
                                {if $sub.outlay}
                                    <option value="{$k}">{$sub.name}</option>
                                {/if}
                            {/foreach}
                        </select>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" name="user_id" class="input-sm form-control" placeholder="UID"
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
            <table id="core-record-table" data-auto data-table="{'wallet/out/data'|app}/{$currency}" data-sort="id,d"
                   style="min-width: 800px">
                <thead>
                <tr>
                    <th width="160" data-sort="create_time,a">时间</th>
                    <th data-sort="id,d">支出流水号</th>
                    <th width="100" data-sort="user_id,a">UID</th>
                    <th width="100" data-sort="amount,a">金额</th>
                    <th width="120" data-sort="subject,a">项目</th>
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

