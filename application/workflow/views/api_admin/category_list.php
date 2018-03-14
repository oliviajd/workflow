<!DOCTYPE HTML>
<html>
<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

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
	height: 40px;
	text-align: center;
}
</style>

<script>
function category_delete(id)
{
    if(confirm('你确定要删除吗?'))
    { 
        // alert(id);
        $.ajax({
            type : 'GET',
            url : '/api_admin/ajax?m=category_delete',
            async : 'false',
            data : {id:id},
            dataType : 'json',
            success : function(data){
                if(data.no == 1)
                {
                    $('.category_'+id).remove();
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

	<div class="sub_menu" style="padding: 10px;">
	 <?php if(isset($_COOKIE['type'])  && $_COOKIE['type'] == 'admin'){?>
		<span><a
			href="/api_admin/category_list?cate_type=<?php echo $_REQUEST['cate_type']?>"
			class="btn">分类列表</a></span> <span><a
			href="/api_admin/category_update?cate_type=<?php echo $_REQUEST['cate_type']?>"
			class="btn">添加分类</a></span>
			<?php }?>
	</div>

	<table style="width: 80%" id="_list">
		<thead>
			<tr>
				<td width="10%">ID</td>
				<td width="30%">名称</td>
				<td width="10%">排序</td>
				<td width="30%">操作</td>
			</tr>
		</thead>
		<tbody>
            <?php foreach($api_category_list as $k=>$v){?>
                <tr class="category_<?php echo $v['id']?>">
				<td><?php echo $v['id']?></td>
				<td><?php echo $v['cate_name']?>(<?php echo $v['children'] ?  "<font color=green>{$v['children']}</font>" : "<font color=red>{$v['children']}</font>"?>)</td>
				<td><?php echo $v['sort']?></td>
				<td>
                   <?php if(isset($_COOKIE['type'])  && $_COOKIE['type'] == 'admin'){?>
                    <a href="/api_admin/category_update?cid=<?php echo $v['id']?>&cate_type=<?php echo $v['cate_type']?>" class="btn">编辑</a> <a href="javascript:;" onclick="category_delete('<?php echo $v['id']?>')" class="btn">删除</a>  
                    <?php if($_REQUEST['cate_type'] == 1){?> <a href="/api_admin/method_list?cate_type=<?php echo $v['cate_type']?>&item_id=<?php echo $v['id']?>" class="btn">查看接口列表</a> 
                    <?php }?>
                    <?php if($_REQUEST['cate_type'] == 2){?>
                        <a
					href="/api_admin/obj_detail?cate_type=<?php echo $v['cate_type']?>&item_id=<?php echo $v['id']?>"
					class="btn">添加对象内容</a>
                    <?php }?>
                    <?php if($_REQUEST['cate_type'] == 3){?>
                        <a
					href="/api_admin/obj_detail?cate_type=<?php echo $v['cate_type']?>&item_id=<?php echo $v['id']?>"
					class="btn">添加系统输入内容</a>
                    <?php }?>
                    <?php if($_REQUEST['cate_type'] == 4){?>
                        <a
					href="/api_admin/obj_detail?cate_type=<?php echo $v['cate_type']?>&item_id=<?php echo $v['id']?>"
					class="btn">添加应用输入内容</a>
                    <?php }?>
                    <?php if($_REQUEST['cate_type'] == 5){?>
                        <a
					href="/api_admin/obj_detail?cate_type=<?php echo $v['cate_type']?>&item_id=<?php echo $v['id']?>"
					class="btn">添加输出内容</a>
                    <?php }?>
                    <?php }else{?>
                      <a
					href="/api_admin/obj_detail?cate_type=<?php echo $v['cate_type']?>&item_id=<?php echo $v['id']?>"
					class="btn">查看</a>
                    <?php }?>
                </td>
			</tr>
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
