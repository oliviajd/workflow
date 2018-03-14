<!DOCTYPE HTML>
<html>
    <?php include APPPATH . 'views/api_admin/public/head.php'; ?>
<script>
$(function(){
    $('#form1').submit(function(){

        var field_id = $(':input[name="field_id"]').val();
        var cate_type = $(':input[name="cate_type"]').val();
        var obj_id = $(':input[name="obj_id"]').val();
        var item_id = $(':input[name="item_id"]').val();
        var field_name = $(':input[name="field_name"]').val();
        var is_necessary = $(':input[name="is_necessary"]').val();
        var example = $(':input[name="example"]').val();
        var default_value = $(':input[name="default_value"]').val();
        var description = $(':input[name="description"]').val();
        var sort = $(':input[name="sort"]').val();

        var _flag = false;

        if(field_name == '')
        {
            $('#field_name_alert').html('名称不能为空');
            $(':input[name="field_name"]').focus();
            return false;
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
    
        // ajax update api field data
        $.ajax({
            type : 'GET',
            url  : '/api_admin.php/api_admin/ajax?m=field_update',
            async : false,
            data : {field_id:field_id, obj_id:obj_id, field_name:field_name, is_necessary:is_necessary, example:example, default_value:default_value, description:description, sort:sort},
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
            location.href = '/api_admin.php/api_admin/obj_detail?cate_type='+cate_type+'&item_id='+item_id+'&field_id='+field_id;
            _flag = false;
        }

        return _flag;
    });
})

</script>
<style>
#_list thead {
	background-color: #FAFAFA;
	font-weight: 600;
}

#_list {
	border: 1px solid #ccc;
}

#_list tr {
	
}

#_list tr td {
	border: 1px solid #ccc;
	height: 30px;
	padding: 5px;
}
</style>

<!--api detail-->
<div class="container grid-s165m0 layout">

	<div class="sub_menu" style="padding: 10px;">
		<span><a
			href="/api_admin.php/api_admin/obj_detail?cate_type=<?php echo $_REQUEST['cate_type']?>&item_id=<?php echo $_REQUEST['item_id']?>"
			class="btn">分类列表</a></span> <span><a
			href="/api_admin.php/api_admin/obj_detail?cate_type=<?php echo $_REQUEST['cate_type']?>"
			class="btn">添加分类</a></span>
	</div>

	<div class="category_update">
		<form id="form1" action="" method="post">
			<p>
				<input type="hidden" name="field_id"
					value="<?php echo $field_detail['field_id'] ?>" />
			</p>
			<p>
				<input type="hidden" name="item_id"
					value="<?php echo $field_detail['item_id'] ?>" />
			</p>
			<p>
				<input type="hidden" name="cate_type"
					value="<?php echo $_REQUEST['cate_type'] ?>" />
			</p>
			<table style="width: 40%" id="_list">
				<tbody>
					<tr>
						<td align="right">名称:</td>
						<td align="left"><input type="text" name="field_name"
							value="<?php echo $field_detail['field_name'] ?>" /></td>
					</tr>
					<tr>
						<td align="right">类型:</td>
						<td align="left"><select name="obj_id">
                    <?php foreach($category_list as $k=>$v){?>
                        <option value="<?php echo $v['id']?>"
									<?php if($field_detail['obj_id']==$v['id']){echo 'selected="selected"'; }?>><?php echo $v['cate_name']?></option>
                    <?php }?>
                    </select></td>
					</tr>
					<tr>
						<td align="right">是否必须:</td>
						<td align="left"><input type="text" name="is_necessary"
							value="<?php echo $field_detail['is_necessary'] ?>" /></td>
					</tr>
					<tr>
						<td align="right">示例值:</td>
						<td align="left"><input type="text" name="example"
							value="<?php echo $field_detail['example'] ?>" /></td>
					</tr>
					<tr>
						<td align="right">默认值:</td>
						<td align="left"><input type="text" name="default_value"
							value="<?php echo $field_detail['default_value'] ?>" /></td>
					</tr>
					<tr>
						<td align="right">描述:</td>
						<td align="left"><input type="text" name="description"
							value="<?php echo $field_detail['description'] ?>" /></td>
					</tr>
					<tr>
						<td align="right">排序:</td>
						<td align="left"><input type="text" name="sort"
							value="<?php echo $field_detail['sort'] ?>" /></td>
					</tr>
					<tr>
						 
						<td align="center" colspan="2"><input type="submit" class="btn" name="submit"
							value="提交" /> <span style="padding-left: 20px;"><input
								type="reset" class="btn" name="reset" value="重置" /></td>
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
