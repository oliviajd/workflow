<!DOCTYPE HTML>
<html>
	<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

<div class="top-nav">
	<div class="option">
		<a href="/" class="home-link home-link2">API 测试工具</a>
	</div>
</div>
<style>
#_list thead {
	background-color: #FAFAFA;
	font-weight: 600;
}

#_list {
	
}

#_list tr {
	
}

#_list tr td {
	height: 40px;
	font-size: 16px;
	padding: 5px;
}

#json {
	border: 1px solid #ccc;
	padding: 10px;
	background: #EFEFEF;
	-moz-border-radius: 10px; /* Gecko browsers */
	-webkit-border-radius: 10px; /* Webkit browsers */
	border-radius: 10px; /* W3C syntax */
}

.grid-s165m0 .col-sub {
	width: 1000px
}

.grid-s165m0 .col-main {
	width: 455px;
	float: right;
	margin-right: 0;
	_margin-right: -50px;
	overflow: hidden
}
</style>
<!--api detail-->
<div class="container grid-s165m0 layout">

	<div class="col-sub">
		<form action="" name="form1" id="form1" method="post"
			enctype="multipart/form-data" target="_bank">
			<table style="width: 100%" id="_list">
				<tr>
					<td align="right">API类目:</td>
					<td align="left"><select id="select_cate" onchange="getAPI();">
							<option value="0">--请选择API类目--</option>
						<?php foreach($api_category_list as $k=>$v){?>
						<option value="<?php echo $v['id'] ?>"><?php echo $v['cate_name'] ?></option>
						<?php }?>
				</select></td>
				</tr>
				<tr>
					<td align="right">API名称:</td>
					<td align="left"><select id="select_api" onchange="getMethod();">
							<option value="0">--请选择API--</option>
						<?php foreach($api_method_list as $k=>$v){?>
						<option value="<?php echo $v['method_id'] ?>"><?php echo $v['method_name_en'] ?></option>
						<?php }?>
				</select></td>
				</tr>

				<tr>
					<td align="right">返回格式:</td>
					<td align="left"><input type="radio" name="t" value="a"
						checked="checked">Array(数组) <input type="radio" name="t" value="j">JSON(字符)</td>
				</tr>
				<tr>
					<td align="right">提交方式:</td>
					<td align="left"><input type="radio" name="method" value="post"
						checked="checked">POST <input type="radio" name="method"
						value="get">GET</td>
				</tr>
				<tr>
					<td colspan="2" ><font size="6px" color="">系统输入</font></td>
					 
				</tr>
				<tr>
					<td colspan="2">
						<table style="width: 100%" id="_list">
							<tbody id="method1">
		 	<?php
    foreach ($api_obj_detail_sys_list as $k => $v) {
        $temp_method.= $v['field_name'] . ",";
        $is_necessary = $v['is_necessary'] ? '<font color="red">*</font>' : '&nbsp;';
        ?>
							<tr>
									<td align="right"><?php echo $v['field_name'];?>:</td>
									<td align="left">
								<?php if($v['cate_name'] == 'File'){?>
										<input type="file" name="<?php echo $v['field_name'];?>">&nbsp;&nbsp;<?php echo $is_necessary ;?><span
										title="<?php echo $v['description'];?>"
										style="color: #00aaff; cursor: pointer;">
									
								
								<?php }else{?>
								<input type="text" name="<?php echo $v['field_name'];?>"
											value="<?php echo $v['default_value'];?>">&nbsp;&nbsp;<?php echo $is_necessary ;?><span
											title="<?php echo $v['description'];?>"
											style="color: #00aaff; cursor: pointer;">
								<?php }?>
								<?php echo $v['description'];?></span>
									
									</td>
								</tr>
			<?php }?>
			
						</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2" ><font size="6px" color="">应用输入</font></td>
					 
				</tr>
				<tr>
					<td align="center" colspan="2">将鼠标移至说明上，查看参数介绍；<font color="red">*</font>
						表示必填；<a
						href="/api_admin/show?cid=<?php echo $api_method_detail['cid']?>&cate_name=<?php echo $api_cate_detail['cate_name'];?>&apiid=<?php echo $api_method_detail['method_id']?>"
						target="_blank">查看API详情</a></td>
				</tr>

				<tr>
					<td colspan="2">
						<table style="width: 100%" id="_list">
							<tbody id="method">
							
		 	<?php
    foreach ($api_obj_detail_list as $k => $v) {
        $temp_method .= $v['field_name'] . ",";
        $is_necessary = $v['is_necessary'] ? '<font color="red">*</font>' : '&nbsp;';
        ?>
							<tr>
									<td align="right"><?php echo $v['field_name'];?>:</td>
									<td align="left">
								<?php if($v['cate_name'] == 'FormInputFile'){?>
										<input type="file" name="<?php echo $v['field_name'];?>">&nbsp;&nbsp;<?php echo $is_necessary ;?><span
										title="<?php echo $v['description'];?>"
										style="color: #00aaff; cursor: pointer;">
									
								
								<?php }else{?>
                                <input type="text" size="<?php if($v['field_name'] == 'fields'){echo '80';}else{ echo '20'; }?>" name="<?php echo $v['field_name'];?>"
											value="<?php echo $v['default_value'];?>">&nbsp;&nbsp;<?php echo $is_necessary ;?><span
											title="<?php echo $v['description'];?>"
											style="color: #00aaff; cursor: pointer;">
								<?php }?>
								<?php echo $v['description'];?></span>
									
									</td>
								</tr>
			<?php }?>
			
						</tbody>
						</table>
					</td>
				</tr>



				<tr>
					<td colspan="2" align="center"><button class="btn"
							onclick="submit_test();">提交测试</button></td>
				</tr>
			</table>
		</form>
	</div>
	<!--正文区 begin-->
	<div class="col-main bg_line"></div>
	<!--正文区 end-->
</div>

<!-- footer begin -->
<div id="footer">
	<div class="footer">
		<div class="contact"></div>
	</div>
</div>
<!-- footer end -->

<script type="text/javascript">

  var  temp_method = "<?php echo $temp_method;?>";

function getAPI(){
	 
	var cid = $("#select_cate").val();

	   $.ajax({
         type : 'GET',
         url  : '/api_admin/ajax?m=get_api',
         async : false,
         data : {cid:cid},
         dataType : 'json',
         success : function(data){
             if(data.no == 1)
             {
                 $('#select_api').html(data.msg);
             }
         }
     }); 
}


function getMethod(){
	var mid =   $('#select_api').val();
	 $.ajax({
         type : 'GET',
         url  : '/api_admin/ajax?m=get_method',
         async : false,
         data : {mid:mid},
         dataType : 'json',
         success : function(data){
             if(data.no == 1)
             {
                    $('#method').html(data.msg);
                    $('#method1').html(data.msg1);
                    temp_method = data.method;
             }
         }
     }); 
}

function submit_test(){
	var api = $("#select_api").find("option:selected").text();
	var apiArr = api.split(".");
	if (apiArr[0] == 'admin') {
		apiArr[0] = 'Admin'; 
	} else {
	    apiArr[0] = 'Api';
	}
	var url = '<?php echo API_HOST;?>';
	for(var i=0;i<apiArr.length;i++){
		url= url+apiArr[i]+"/";
	}
    var method_arr = temp_method.split(",");
    var len = method_arr.length-1;

    var val="";
    if(len>0){
        for(var i = 0 ;i<len;i++){
        	if($("input[name='"+method_arr[i]+"']"). attr("type")=="file"){
            	}else{
           		 val = val+method_arr[i]+"="+$("input[name='"+method_arr[i]+"']").  val()+"&";
            	}
     	 
            }
     }
    url = url+"?"+val;
    var t = $("input[name='t']:checked").val();
    if(t=="a")url=url+"t="+t;
/*     $.get(url,function(data){
 	   $("#json").html(data);
        }); */
  form1.action=url;
  form1.submit();
  //  window.open(url,'newwindow');
	 
}
    
$(document).ready(function(){
    $("#select_cate").val('<?php echo $api_method_detail['cid']; ?>');
    $("#select_api").val('<?php echo $api_method_detail['method_id']; ?>');

	
});
</script>

</body>
</html>
