<!DOCTYPE HTML>
<html>
<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

<script src="/data/layer/layer.min.js" type="text/javascript"></script>

<style>
<!--
table list -->#method_list {
	border: 1px solid #ccc;
}

#method_list tr td {
	border: 1px solid #ccc;
	height: 40px;
	text-align: center;
}

#method_list thead tr {
	height: 40px;
	background-color: #FAFAFA;
	font-weight: 600;
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

#method_input_form {
	width: 500px;
	height: 240px;
	padding-top: 20px;
}

#method_input_form p {
	height: 40px;
}

#method_input_form p span {
	display: inline-block;
	width: 80px;
	text-align: right;
	font-weight: bold;
	font-size: 12px;
	color: #000;
}

#method_input_form p i {
	padding-left: 20px;
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

#input_select {
	width: 320px;
	height: 140px;
	padding-top: 30px;
}

.input_select {
	padding-left: 60px;
	padding-bottom: 30px;
}

.input_select select {
	margin-left: 20px;
}
</style>

<script>

var item_id = '<?php echo $_REQUEST['item_id']?>';

// method add
function func_method_add()
{
    var page_field = $.layer({
        type: 1,
        title: false,
        area: ['auto', 'auto'],
        border: [5, 0.3, '#0c0c0c'], // 默认边框
        shade: [0.5, '#000'], // 遮罩
        closeBtn: [0, false], //去掉默认关闭按钮
        shift: 'false', //从左动画弹出
        page: {
            html : 
                '<div id="method_input_form" style="margin:10px;">'
                +'    <p><span>接口名(中文): </span> <input type="text" name="method_name_cn" value="" /> <i class="method_desc">接口名称 eg.: 题目.详情 </i></p>'
                +'    <p><span>接口名: </span> <input type="text" name="method_name_en" value="" /> <i class="method_desc">接口名 eg.: xxx.user.login </i></p>'
                // +'    <p><span>接口url: </span> <input type="text" name="url" value="" /> <i class="method_desc">API接口url</i></p>'
                +'    <p><span>系统输入: </span> <input type="text" name="system_input_id" value="0" /> <i class="method_desc">系统输入参数</i></p>'
                +'    <p><span>应用输入: </span> <input type="text" name="application_input_id" value="0" /> <i class="method_desc">应用传入参数</i></p>'
                +'    <p><span>返回: </span> <input type="text" name="response_id" value="0" /> <i class="method_desc">返回</i></p>'
                +'    <p><span>method: </span> <input type="text" name="post_method" value="0" /> <i class="method_desc">post,get</i></p>'
                +'    <p><span>接口状态: </span> <input type="text" name="status" value="1" /> <i class="method_desc">是否可用</i></p>'
                +'    <p><span>描述: </span> <textarea name="description" value="" id="description"></textarea><i class="method_desc">接口文字描述</i></p>'
                +'    <p><span>排序: </span> <input type="text" name="sort" value="0" /> <i class="method_desc">排序</i></p>'
                +'    <button id="submit_field" class="btns round5" onclick="">提交</button> <button id="close_field" class="btns2 round5" onclick="">关闭</button>'
                +'</div>'
        }
    });
    $('#submit_field').on('click', function(){
        var d = {};
        d.cid = item_id;
   
        var method_name_cn = $('input[name="method_name_cn"]').val();
        if(method_name_cn == '')
        {
            layer.msg('中文接口名不能为空');
            return false;
        }
        else
        {
            d.method_name_cn = method_name_cn;
        }

        var method_name_en = $('input[name="method_name_en"]').val();
        if(method_name_en == '')
        {
            layer.msg('接口名不能为空');
            return false;
        }
        else
        {
            d.method_name_en = method_name_en;
        }

        // var url = $('input[name="url"]').val();
        // d.url = (url == '') ? '' : url;

        var system_input_id = $('input[name="system_input_id"]').val();
        d.system_input_id = (system_input_id == '') ? 0 : system_input_id;

        var application_input_id = $('input[name="application_input_id"]').val();
        d.application_input_id = (application_input_id != '') ? application_input_id : 0;

        var response_id = $('input[name="response_id"]').val();
        d.response_id = (response_id != '') ? response_id : 0;

        var post_method = $('input[name="post_method"]').val();
        d.post_method = (post_method != '') ? post_method : 0;

        var status = $('input[name="status"]').val();
        d.status = (status != '') ? status : 0;

        var sort = $('input[name="sort"]').val();
        d.sort = (sort != '') ? sort : 0;
        var description= $('#description').val();
        d.description = (description != '') ? description : '';
         
        funcAjaxAddMethod(page_field, d);
    });
    //自设关闭
    $('#close_field').on('click', function(){
        layer.close(page_field);
    });
}

// 上传并保存表单
function funcAjaxAddMethod(page_field, d)
{
    // console.log(d);
    
    $.ajax({
        type : 'GET',
        url : '/api_admin.php/api_admin/ajax?m=method_update',
        async : false,
        data : d,
        dataType : 'json',
        success : function(data){
            if(data.no == 1)
            {
                
                var html = '<tr class="method_'+data.insert_id+'">'
                 +'   <td>'+data.insert_id+'</td>'
                 +'   <td>'+d.method_name_cn+'</td>'
                 +'   <td>'+d.method_name_en+'</td>'
                 +'   <td><a href="javascript:;" id="system_'+data.insert_id+'" onclick="return func_input_select(this, 3, '+data.insert_id+')" >'+d.system_input_id+' </a></td>'
                 +'   <td><a href="javascript:;" id="system_'+data.insert_id+'" onclick="return func_input_select(this, 4, '+data.insert_id+')" >'+d.application_input_id+' </a></td>'
                 +'   <td><a href="javascript:;" id="system_'+data.insert_id+'" onclick="return func_input_select(this, 5, '+data.insert_id+')" >'+d.response_id+' </a></td>'
                 +'   <td>'+d.post_method+'</td>'
                 +'   <td>'+d.status+'</td>'
                 +'   <td>'+data.request_time+'</td>'
                 +'   <td>'+d.sort+'</td>'
                 +'  <td> <a href="javascript:;" onclick="func_method_delete('+data.insert_id+')" class="btn">删除</a> </td>'
                +'</tr>';
                $('#method_list tbody').append(html);
            //	window.location.reload(); 
            }
            else
            {
                layer.msg('添加失败');
            }
        }
    });
    
    layer.close(page_field);
}

// method delete by ajax
function func_method_delete(method_id)
{
    if(confirm('你确定要删除吗 ?'))
    {
        $.ajax({
            type : 'GET',
            url : '/api_admin.php/api_admin/ajax?m=method_delete',
            async : false,
            data : {method_id:method_id},
            dataType : 'json',
            success : function(data){
                if(data.no == 1)
                    $('.method_'+method_id).remove();
                else
                    layer.msg('删除失败');
            }
        });
    }
}



// method select
function func_input_select(_this, cate_type, method_id)
{
    // console.log(typeof _this.text);
    var str = '';
    // alert(id)
    $.ajax({
        type : 'GET',
        url : '/api_admin.php/api_admin/ajax?m=input_list',
        async : false,
        data : {method_id:method_id, cate_type:cate_type},
        dataType : 'json',
        success : function(data){
            if(data.no == 1)
            {
                var _list = data.input_list;
                for(var i=0; i<_list.length; i++)
                {
                    var _tmp = _list[i];

                    if(_tmp.id == _this.text)
                        str += '<option value="'+_tmp.id+'" selected="selected">'+_tmp.cate_name+'</option>';
                    else
                        str += '<option value="'+_tmp.id+'" >'+_tmp.cate_name+'</option>';
                }
            }
        }
    });

    var page_input = $.layer({
        type: 1,
        title: false,
        area: ['auto', 'auto'],
        border: [5, 0.3, '#0c0c0c'], // 默认边框
        shade: [0.5, '#000'], // 遮罩
        closeBtn: [0, false], //去掉默认关闭按钮
        shift: 'false', //从左动画弹出
        page: {
            html : 
                '<div id="input_select">'
                    +'<div class="input_select">'
                        +'<span>请选择:</span>'
                        +'<select name="select_input">'
                            + str
                        +'</select>'
                    +'</div>'
                    +'<div> <button id="submit_field" class="btns round5" onclick="">提交</button> <button id="close_field" class="btns2 round5" onclick="">关闭</button> </div>'
                +'</div>'
        }
    });

    $('#submit_field').click(function(){
        var input_id = $('select[name="select_input"]').val();

        var d = {method_id:method_id};  // 提交的数据
// console.log(cate_type, typeof cate_type);
        switch(cate_type)
        {
            case '3':
                d.system_input_id = input_id;
                break;
            case '4':
                d.application_input_id = input_id;
                break;
            case '5':
                d.response_id = input_id;
                break;
            default:
                //
        }
        
        // console.log(d);

        $.ajax({
            type : 'GET',
            url : '/api_admin.php/api_admin/ajax?m=input_update',
            async : false,
            data : d,
            dataType : 'json',
            success : function(data){
                if(data.no == 1)
                {
                    switch(cate_type)
                    {
                        case '3':
                            $('#system_'+method_id).text(input_id);
                            break;
                        case '4':
                            $('#application_'+method_id).text(input_id);
                            break;
                        case '5':
                            $('#response_'+method_id).text(input_id);
                            break;
                        default:
                            //
                    }

                    layer.close(page_input);
                }
            }
        });
    });
    
    // 关闭
    $('#close_field').on('click', function(){
        layer.close(page_input);
    });
}
</script>

<!--api detail-->
<div class="container grid-s165m0 layout">

	<div class="sub_menu" style="padding: 10px;">
		<span><a
			href="/api_admin.php/api_admin/method_list?cate_type=<?php echo $_REQUEST['cate_type']?>&item_id=<?php echo $_REQUEST['item_id'] ?>"
			class="btn">接口名列表</a></span> <span><a href="javascript:void(0);"
			onclick="return func_method_add()" class="btn">添加接口</a></span>
	</div>

	<table style="width: 100%" id="method_list">
		<thead>
			<tr>
				<td width="3%">ID</td>
				<td width="10%">接口名称</td>
				<td width="10%">接口</td>
				<td width="5%">系统输入</td>
				<td width="5%">应用输入</td>
				<td width="5%">返回</td>
				<td width="5%">post/get</td>
				<td width="5%">接口状态</td>
				<td width="10%">创建时间</td>
				<td width="3%">排序</td>
				<td width="10%">操作</td>
			</tr>
		</thead>
		<tbody>
            <?php foreach($api_method_list as $k=>$v){?>
                <tr class="method_<?php echo $v['method_id']?>">
				<td><?php echo $v['method_id']?></td>
				<td><?php echo $v['method_name_cn'] ?></td>
				<td><?php echo $v['method_name_en'] ?></td>
				<td><a href="javascript:;" id="system_<?php echo $v['method_id']?>"
					onclick="return func_input_select(this, '3', '<?php echo $v['method_id']?>')"><?php echo $v['system_input_id'] ?></a></td>
				<td><a href="javascript:;"
					id="application_<?php echo $v['method_id']?>"
					onclick="return func_input_select(this, '4', '<?php echo $v['method_id']?>')"><?php echo $v['application_input_id'] ?></a></td>
				<td><a href="javascript:;"
					id="response_<?php echo $v['method_id']?>"
					onclick="return func_input_select(this, '5', '<?php echo $v['method_id']?>')"><?php echo $v['response_id'] ?></a></td>
				<td> <?php if($v['post_method'] == 0) { echo 'ALL'; } elseif($v['post_method'] == 1) { echo 'post'; } else { echo 'get'; } ?></td>
				<td><?php if($v['status'] == 1){echo '完成';}else{echo '未完成';}?></td>
				<td><?php echo date("Y-m-d H:i:s",$v['create_time']) ?></td>
				<td><?php echo $v['sort']?></td>
				<td><button
						onclick="func_method_show('<?php echo $v['method_id']?>')"
						class="btn">修改</button> <a href="javascript:;"
					onclick="func_method_delete('<?php echo $v['method_id']?>')"
					class="btn">删除</a></td>
			</tr>
            <?php }?>
            </tbody>
	</table>
</div>

<script type="text/javascript">

 

//method delete by ajax
function func_method_show(method_id)
{
        $.ajax({
            type : 'GET',
            url : '/api_admin.php/api_admin/ajax?m=method_show',
            async : false,
            data : {method_id:method_id},
            dataType : 'json',
            success : function(data){
                if(data.no == 1){
                	var page_field = $.layer({
                        type: 1,
                        title: false,
                        area: ['auto', 'auto'],
                        border: [5, 0.3, '#0c0c0c'], // 默认边框
                        shade: [0.5, '#000'], // 遮罩
                        closeBtn: [0, false], //去掉默认关闭按钮
                        shift: 'false', //从左动画弹出
                        page: {
                            html : 
                                '<div id="method_input_form" style="margin:10px;">'
                                +'    <p><span>接口名(中文): </span> <input type="text" name="method_name_cn" value="'+data.method_name_cn+'" /> <i class="method_desc">接口名称 eg.: 题目.详情 </i></p>'
                                +'    <p><span>接口名: </span> <input type="text" name="method_name_en" value="'+data.method_name_en+'" /> <i class="method_desc">接口名 eg.: xxx.user.login </i></p>'
                                // +'    <p><span>接口url: </span> <input type="text" name="url" value="" /> <i class="method_desc">API接口url</i></p>'
                                +'    <p><span>系统输入: </span> <input type="text" name="system_input_id" value="'+data.system_input_id+'" /> <i class="method_desc">系统输入参数</i></p>'
                                +'    <p><span>应用输入: </span> <input type="text" name="application_input_id" value="'+data.application_input_id+'" /> <i class="method_desc">应用传入参数</i></p>'
                                +'    <p><span>返回: </span> <input type="text" name="response_id" value="'+data.response_id+'" /> <i class="method_desc">返回</i></p>'
                                +'    <p><span>method: </span> <input type="text" name="post_method" value="'+data.post_method+'" /> <i class="method_desc">post,get</i></p>'
                                +'    <p><span>接口状态: </span> <input type="text" name="status" value="'+data.status+'" /> <i class="method_desc">是否可用</i></p>'
                                +'    <p><span>描述: </span> <textarea name="description" value="'+data.description+'" id="description"> '+data.description+'</textarea><i class="method_desc">接口文字描述</i></p>'
                                +'    <p><span>排序: </span> <input type="text" name="sort" value="'+data.sort+'" /><input type="hidden" name="method_id" value="'+data.method_id+'" /> <i class="method_desc">排序</i></p>'
                                +'    <button id="submit_field" class="btns round5" onclick="">提交</button> <button id="close_field" class="btns2 round5" onclick="">关闭</button>'
                                +'</div>'
                        }
                    });
               	 $('#submit_field').on('click', function(){
         	        var d = {};
         	        d.cid = item_id;
         	   
         	        var method_name_cn = $('input[name="method_name_cn"]').val();
         	        if(method_name_cn == '')
         	        {
         	            layer.msg('中文接口名不能为空');
         	            return false;
         	        }
         	        else
         	        {
         	            d.method_name_cn = method_name_cn;
         	        }

         	        var method_name_en = $('input[name="method_name_en"]').val();
         	        if(method_name_en == '')
         	        {
         	            layer.msg('接口名不能为空');
         	            return false;
         	        }
         	        else
         	        {
         	            d.method_name_en = method_name_en;
         	        }

         	        // var url = $('input[name="url"]').val();
         	        // d.url = (url == '') ? '' : url;

         	        var system_input_id = $('input[name="system_input_id"]').val();
         	        d.system_input_id = (system_input_id == '') ? 0 : system_input_id;

         	        var application_input_id = $('input[name="application_input_id"]').val();
         	        d.application_input_id = (application_input_id != '') ? application_input_id : 0;

         	        var response_id = $('input[name="response_id"]').val();
         	        d.response_id = (response_id != '') ? response_id : 0;

         	        var post_method = $('input[name="post_method"]').val();
         	        d.post_method = (post_method != '') ? post_method : 0;

         	        var status = $('input[name="status"]').val();
         	        d.status = (status != '') ? status : 0;

         	        var sort = $('input[name="sort"]').val();
         	        d.sort = (sort != '') ? sort : 0;
         	        var description= $('#description').val();
         	        d.description = (description != '') ? description : '';

          	       var method_id = $('input[name="method_id"]').val();
       	           d.method_id = (method_id != '') ? method_id : 0;
          	    
         	         
         	        funcAjaxAddMethod(page_field, d);

          	       
       	        
         	    });
              	  //自设关闭
                    $('#close_field').on('click', function(){
                        layer.close(page_field);
                    });
                 
                }
                 
            }
        });
     
}
</script>

<!-- footer begin -->
<div id="footer">
	<div class="footer">
		<div class="contact"></div>
	</div>
</div>
<!-- footer end -->

</body>
</html>
