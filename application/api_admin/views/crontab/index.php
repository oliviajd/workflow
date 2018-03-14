<?php include APPPATH . 'views/appmanage/appmanage_header.php';?>
<!-- topbar ends -->
<div class="ch-container">
	<div class="row">

		<!-- left menu starts -->
        <?php include APPPATH.'views/appmanage/appmanage_left.php';?>
        <!--/span-->
		<!-- left menu ends -->

		<style>
.select-box {
	padding-left: 10px;
}

.s-box {
	float: left;
	width: 10%;
}

.clear {
	clear: both;
}
</style>

		<div id="content" class="col-lg-10 col-sm-10">

			<div class="row">
				<div class="box col-md-12">
					<div class="box-inner">

						<div class="box-header well" data-original-title="">
							<h2>
								<i class="glyphicon glyphicon-user"></i> 脚本管理
							</h2>

						</div>

						<div class="box-content">
							<table class="table table-bordered responsive">
								<thead>

									<tr>
										<th width="40%">脚本名称</th>
										<th width="40%">操作</th>
										<th width="10%">耗时</th>
										<th width="10%">状态</th>
									</tr>

								</thead>
								<tbody>
									<tr>
										<th width="40%">用户信息统计脚本</th>
										<th width="40%" ><span id="startButton" style="display: none;">正在执行</span><input type="button"
											value="开始执行" id="startMember"></th>
										<th width="10%" id="startTimes"> </th>
										<th width="10%" id="memberStatus">未执行</th>
									</tr>

								</tbody>
								<div>
							
							</table>
						</div>

					</div>
				</div>
			</div>
		</div>

		<script language="javascript">
		var times1 = 0;
		 var flag1 = false;
	 
		  $('#startMember').click(function(){
			 
			  times1 = 0;
			 flag1 = true;
			    $('#memberStatus').html('<font color=red>执行中...</font>');
			    $('#startTimes').html('<font color=red>'+times1+'s</font>');
			    $('#startButton').show();
			    $('#startMember').hide();
			    $.ajax({
			         type : 'GET',
			         url  : '/api_admin.php/crontab/ajax',
			         async : true,
			         data : {action:'memberCount'},
			         dataType : 'json',
			         success : function(data){
			             if(data.no == 1)
			             {
			            	  flag1 = false;
			            	    $('#startButton').hide();
			    			    $('#startMember').show();
			            	  $('#memberStatus').html(' 执行完成');
			            	 $('#startTimes').html('耗时'+times1+'秒');
				             }
			         }
			     }); 
			   });
		  
		  setInterval(times,1000); 
		  function times(){
			  if(flag1){
				  times1++;
				 $('#startTimes').html('<font color=red>'+times1+'s</font>');
			 }
		  }
			
		</script>
<?php include APPPATH . 'views/appmanage/appmanage_footer.php';?>
