<!DOCTYPE HTML>
<html>
<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

<script src="/data/layer/layer.min.js" type="text/javascript"></script>

<style>
#obj_table {
	text-align: center;
}

#obj_table thead tr {
	height: 40px;
	background-color: #FAFAFA;
	font-weight: 600;
}

#obj_table thead tr td {
	border: 1px solid #c0c0c0;
}

#obj_table tbody tr {
	height: 40px;
}

#obj_table tbody tr td {
	border: 1px solid #c0c0c0;
}

.round15 {
	-moz-border-radius: 15px;
	-webkit-border-radius: 15px;
	border-radius: 15px;
}

.round5 {
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

#obj_input_form {
	width: 400px;
	height: 240px;
	padding-top: 20px;
}

#obj_input_form p {
	height: 40px;
}

#obj_input_form p span {
	display: inline-block;
	width: 80px;
	text-align: right;
	font-weight: bold;
	font-size: 12px;
	color: #000;
}

.btns {
	margin-top: 10px;
	margin-left: 100px;
	padding-bottom: 5px;
	width: 50px;
	height: 30px;
	float: left;
}

.btns2 {
	margin-top: 10px;
	margin-left: 30px;
	width: 50px;
	height: 30px;
	float: left;
}
</style>

<script>
var cate_type = '<?php echo $_REQUEST['cate_type']; ?>';
var item_id = '<?php echo $_REQUEST['item_id']; ?>';

// function field add
function func_field_add(field_id)
{
    // console.log(typeof field_id);
    var html_field_select = '<select id="obj_id" name="obj_id"><?php foreach($api_obj_list as $k=>$v){?><option value="<?php echo $k?>"><?php echo $v[1]?></option><?php }?></select>';

    var html = ' <div id="obj_input_form" >'
        +'<p><span>名称: </span> <input type="text" name="field_name" id="field_name" value="" /> </p>'
        +'<p><span>字段类型: </span> '+html_field_select+' </p>'
        +'<p><span>是否必须: </span> <input type="text" name="is_necessary" id="is_necessary" value="1" /> </p>'
        +'<p><span>示例值: </span> <input type="text" name="example" id="example" value="" /> </p>'
        +'<p><span>默认值: </span> <input type="text" name="default_value" id="default_value" value="" /> </p>'
        +'<p><span>描述: </span> <textarea name="description" id="description"></textarea> </p>'
        +'<p><span>排序: </span> <input type="text" name="sort" id="sort" value="0" /> </p>'
        +'<button id="submit_field" class="btns round5" onclick="">提交</button> <button id="close_field" class="btns2 round5" onclick="">关闭</button>'
        +'</div>' ;
    // obj list [select html]
    
    var page_field = $.layer({
        type: 1,
        title: false,
        area: ['auto', 'auto'],
        border: [5, 0.3, '#0c0c0c'], // 默认边框
        shade: [0.5, '#000'], // 遮罩
        closeBtn: [0, false], //去掉默认关闭按钮
        shift: 'false', //从左动画弹出
        page: { html:html }
    });

    $('#submit_field').on('click', function(){
        var d = {};
        // d.cate_type = cate_type;
        d.item_id = item_id;
        d.field_name = $('#field_name').val();
        d.obj_id = $('#obj_id').val();
        d.is_necessary = $('#is_necessary').val();
        d.example = $('#example').val();
        d.default_value = $('#default_value').val();
        d.description = $('#description').val();
        d.sort = $('#sort').val();

        var cate_name = $('#obj_id').find("option:selected").text();

        funcAjaxAddField(page_field, d, cate_name);
    });
    //自设关闭
    $('#close_field').on('click', function(){
        layer.close(page_field);
    });
}

// 上传并保存表单
function funcAjaxAddField(page_field, d, cate_name)
{
    // console.log(cate_type, item_id);
    // console.log(d);
    $.ajax({
        type : 'GET',
        url : '/api_admin.php/api_admin/ajax?m=field_update',
        async : false,
        data : d,
        dataType : 'json',
        success : function(data){
            d.field_id = data.field_id;
            // var d = data.detail;
            // console.log(data, typeof data.no, data.no);
            // return false;
            if(data.no == 1)
            {
                var html = ' <tr class="obj_field_'+d.field_id+' obj_list_tr">'
                   +' <td>'+d.field_name+'</td>'
                   +' <td>'+cate_name+'</td>'
                   +' <td>'+d.is_necessary+'</td>'
                   +' <td>'+d.example+'</td>'
                   +' <td>'+d.default_value+'</td>'
                   +' <td>'+d.description+'</td>'
                   +' <td>'+d.sort+'</td>'
                   +' <td> <span style="padding-left:10px;"> <a href="/api_admin.php/api_admin/obj_detail_update?cate_type='+cate_type+'&item_id='+item_id+'&field_id='+d.field_id+'" >修改</a> </span>'
                   +' <span style="padding-left:10px;"> <input type="button" class="btn" onclick="return func_field_delete('+d.field_id+')" name="del_tr" value="删除" /> </span> </td>'
                   +' </tr>';
                    // console.log(html);
                $('#obj_table tbody').append(html);
            }
            else
            {
                layer.msg('添加失败');
            }
        }
    });
    
    layer.close(page_field);
}

// field ajax delete
function func_field_delete(field_id)
{
    if(confirm('你确定要删除吗?'))
    {
        $.ajax({
            type: 'GET',
            url : '/api_admin.php/api_admin/ajax?m=field_delete',
            async : false,
            data : {field_id:field_id},
            dataType : 'json',
            success : function(data) {
                if(data.no == 1)
                {
                    $('.obj_field_'+field_id).remove();
                }
                else
                {
                    layer.msg('删除失败');
                }
            }
        });
    }
}

</script>

<!--api detail-->
<div class="container grid-s165m0 layout">
 <?php if(isset($_COOKIE['type'])  && $_COOKIE['type'] == 'admin'){?>
	<div class="sub_menu" style="padding: 10px;">
		<span><a
			href="/api_admin.php/api_admin/category_list?cate_type=<?php echo $_REQUEST['cate_type']?>"
			class="btn">分类列表</a></span>
		<!--<span><a href="/api_admin.php/api_admin/category_update?cate_type=<?php echo $_REQUEST['cate_type']?>" >添加分类</a></span>-->
		<span><a href="javascript:;" onclick="return func_field_add();"
			class="btn">添加新字段</a></span>
	</div>
	<?php }?>
	<table style="width: 100%">
		<tr>
			<td align="center"><span style="font-size: 24px;">名称：<font
					color="#0088dd"><?php echo $category["cate_name"];?></font></span></td>
		</tr>
	</table>
	<table style="width: 100%" id="obj_table">
		<thead>
			<tr style="border: 1px solid #ccc">
				<td width="6%">名称</td>
				<td width="6%">类型</td>
				<td width="6%">是否必须</td>
				<td width="12%">示例值</td>
				<td width="12%">默认值</td>
				<td width="12%">描述</td>
				<td width="4%">排序</td>
				<td width="10%">操作</td>
			</tr>
		</thead>
		<tbody>
            <?php if(!empty($api_obj_detail_list)){?>
                <?php foreach($api_obj_detail_list as $k=>$v){?>
                    <tr
				class="obj_field_<?php echo $v['field_id']?> obj_list_tr">
				<td><?php echo $v['field_name']?></td>
				<td><?php echo $v['cate_name']?></td>
				<td><?php echo $v['is_necessary']?></td>
				<td><?php echo $v['example']?></td>
				<td><?php echo $v['default_value']?></td>
				<td><?php echo $v['description']?></td>
				<td><?php echo $v['sort']?></td>
				<td>
				 <?php if(isset($_COOKIE['type'])  && $_COOKIE['type'] == 'admin'){?>
				<span style="padding-left: 10px;"> <a
						href="/api_admin.php/api_admin/obj_detail_update?cate_type=<?php echo $_REQUEST['cate_type']?>&item_id=<?php echo $_REQUEST['item_id']?>&field_id=<?php echo $v['field_id'];?>"
						class="btn">修改</a>
				</span> <span style="padding-left: 10px;"> <input type="button"
						class="btn" name="del_tr"
						onclick="return func_field_delete('<?php echo $v['field_id']?>')"
						value="删除" />
				</span>
				<?php }?>
				</td>
			</tr>
                <?php }?>
            <?php }?>
			</tbody>
	</table>
</div>

<!-- footer begin -->
<div id="footer">
	<div class="footer">
		<div class="contact"></div>
	</div>
</div>
<!-- footer end -->

</body>
</html>
