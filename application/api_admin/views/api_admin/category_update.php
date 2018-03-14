<!DOCTYPE HTML>
<html>
    <?php include APPPATH . 'views/api_admin/public/head.php'; ?>
<script>
$(function(){
    $('#form1').submit(function(){

        var cate_name = $(':input[name="cate_name"]').val();
        var sort = $(':input[name="sort"]').val();
        var id = $(':input[name="id"]').val();
        var cate_type = $(':input[name="cate_type"]').val();
        // console.log(cate_type, typeof cate_type);
        // return false;
        var _flag = false;

        if(cate_name == '')
        {
            $('#cate_name_alert').html('分类名称不能为空');
            $(':input[name="cate_name"]').focus();
            return false;
        }
        else
        {
            if(id == '')
            {
                $.ajax({
                    type : 'post',
                    url  : '/api_admin.php/api_admin/ajax?m=category_is_exists',
                    async : false,
                    data : {cate_name:cate_name, cate_type:cate_type},
                    dataType : 'json',
                    success : function(data){
                        // console.log(data);
                        // return false;
                        if(data.no == 1)
                        {
                            $('#cate_name_alert').html('分类名称已经存在');
                            $(':input[name="cate_name"]').focus();
                            return false;
                        }
                        else
                        {
                            $('#cate_name_alert').html('');
                        }
                    }
                });
            }
        }

        // return false;

        // sort 处理
        if(sort == '')
        {
            sort = 0;
        }
        else
        {
            sort = parseInt(sort);
            if(sort == NaN)
            {
                sort = 0;
            }
        }
    
        // ajax update api category data
        $.ajax({
            type : 'GET',
            url  : '/api_admin.php/api_admin/ajax?m=category_update',
            async : false,
            data : {cate_name:cate_name, cate_type:cate_type, sort:sort, id:id},
            dataType : 'json',
            success : function(data){
                if(data.no == 1)
                {
                    _flag = true;
                }
            }
        });

        // console.log(_flag, typeof _flag);
        // return false;
        if(_flag == true)
        {
            location.href = '/api_admin.php/api_admin/category_list?cate_type='+cate_type;
            _flag = false;
        }

        return _flag;
    });
})

</script>
<style>

#_list thead {background-color:#FAFAFA; font-weight:600; }
#_list { border:1px solid #ccc; }
#_list tr { }
#_list tr td { border:1px solid #ccc; height:30px;padding: 5px;}

</style>
<!--api detail-->
<div class="container grid-s165m0 layout">

	<div class="sub_menu" style="padding: 10px;">
		<span><a
			href="/api_admin.php/api_admin/category_list?cate_type=<?php echo $_REQUEST['cate_type']?>"
			class="btn">分类列表</a></span> <span><a
			href="/api_admin.php/api_admin/category_update?cate_type=<?php echo $_REQUEST['cate_type']?>"
			class="btn">添加分类</a></span>
	</div>

	<div class="category_update">
		<form id="form1" action="" method="post">
			<p>
				<input type="hidden" name="id"
					value="<?php echo $api_category_detail['id'] ?>" />
			</p>
			<p>
				<input type="hidden" name="cate_type"
					value="<?php echo $_REQUEST['cate_type'] ?>" />
			</p>
			<table style="width: 40%" id="_list">
				<tbody>
					<tr  >
						<td align="right">名称:</td>
						<td align="left"><input type="text" name="cate_name"
							value="<?php echo $api_category_detail['cate_name'] ?>" /></td>
					</tr>
					<tr  >
						<td align="right">排序:</td>
						<td align="left"><input type="text" name="sort"
							value="<?php echo $api_category_detail['sort'] ?>" /></td>
					</tr>
					<tr >
						<td colspan="2" align="center"><input type="submit" class="btn" name="submit" value="提交" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="reset" class="btn" name="reset" value="重置" /></td>
					</tr>
				</tbody>
			</table>



		</form>
	</div>

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
