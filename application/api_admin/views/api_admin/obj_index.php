<!DOCTYPE HTML>
<html>
<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

<script>
function category_delete(id)
{
    // alert(id);
    $.ajax({
        type : 'GET',
        url : '/api_admin.php/api_admin/ajax?m=category_delete',
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
                $('.category_'+id).remove();
            }
        }

    });
}
</script>

    <!--api detail-->
    <div class="container grid-s165m0 layout">

        <div class="sub_menu" style="padding: 10px;">
            <span><a href="/api_admin.php/api_admin/category_list"  class="btn">分类列表</a></span>
            <span><a href="/api_admin.php/api_admin/category_update"  class="btn">添加分类</a></span>
        </div>

        <table style="width:60%">
            <tr>
                <td width="20%">ID</td>
                <td width="50%">模块名称</td>
                <td width="10%">排序</td>
                <td width="20%">操作</td>
            </tr>
        <?php foreach($api_category_list as $k=>$v){?>
            <tr class="category_<?php echo $v['id']?>">
            <td><?php echo $v['id']?></td>
            <td><?php echo $v['cate_name']?></td>
            <td><?php echo $v['sort']?></td>
            <td><a href="/api_admin.php/api_admin/category_update?cid=<?php echo $v['id']?>" class="btn">编辑</a>  <a href="javascript:;" onclick="category_delete('<?php echo $v['id']?>')" class="btn">删除</a></td>
            </tr>
        <?php }?>
        </table>
    </div>

    <!-- footer begin -->
    <div id="footer">
        <div class="footer">
            <div class="contact"> </div>
        </div>
    </div>
    <!-- footer end -->

</body>
</html>
