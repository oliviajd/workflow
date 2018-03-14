<!DOCTYPE HTML>
<html>
	<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

<div class="top-nav">
	<div class="option">
		<a href="" class="home-link home-link2">API</a>
	</div>
</div>
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

#json {
	border: 1px solid #ccc;
	padding: 10px;
	background: #EFEFEF;
	-moz-border-radius: 10px; /* Gecko browsers */
	-webkit-border-radius: 10px; /* Webkit browsers */
	border-radius: 10px; /* W3C syntax */
}
</style>
<!--api detail-->
<div class="container grid-s165m0 layout">
	<?php include APPPATH . 'views/api_admin/public/left1.php'; ?>
	
	<!--正文区 begin-->
	<div class="col-main bg_line">
		<div class="main-wrap">
			<h1 class="title"><?php echo $api_method_detail['method_name_cn'];?>(<?php echo $api_method_detail['method_name_en'];?>) 接口</h1>

			<div class="sub-wrap" data-spm="1998342952">
				<h3 class="head-title">接口描述</h3>
				<div class="api-list" style="border: 1px solid #ccc; padding: 10px;">
	<?php echo $api_method_detail['description'];?>
				</div>
			</div>

			<div class="sub-wrap" data-spm="1998342952">
				<h3 class="head-title">系统输入参数列表</h3>
				<div class="api-list">
					<table style="width: 100%" id="_list">
						<thead>
							<tr style="border: 1px solid #ccc">
								<td width="6%">名称</td>
								<td width="6%">类型</td>
								<td width="6%">是否必须</td>
								<td width="12%">示例值</td>
								<td width="12%">默认值</td>
								<td width="12%">描述</td>
								<td width="4%">排序</td>
							</tr>
						</thead>
						<tbody>
						<?php
    foreach ($system_input_list as $k => $v) {
        $link_cate_name = is_base_type($v['cate_name']) ? $v['cate_name'] : '<a
									href="/api_admin.php/api_admin/obj_show?cid=' . $_REQUEST['cid'] . '&item_id=' . $v['obj_id'] . '&cate_name=' . $_REQUEST['cate_name'] . '&method_name=' . $v['cate_name'] . '&apiid=' . $_REQUEST['apiid'] . '">' . $v['cate_name'] . '</a>';
        ?>
							<tr>
								<td><?php echo $v['field_name']?></td>
								<td><?php echo $link_cate_name ;?></td>
								<td><?php echo $v['is_necessary']?></td>
								<td><?php echo $v['example']?></td>
								<td><?php echo $v['default_value']?></td>
								<td><?php echo $v['description']?></td>
								<td><?php echo $v['sort']?></td>
							</tr>
							<?php }?>
						</tbody>
					</table>


				</div>
			</div>
			<div class="sub-wrap" data-spm="1998342952">
				<h3 class="head-title">应用输入参数列表</h3>
				<div class="api-list">

					<table style="width: 100%" id="_list">
						<thead>
							<tr style="border: 1px solid #ccc">
								<td width="6%">名称</td>
								<td width="6%">类型</td>
								<td width="6%">是否必须</td>
								<td width="12%">示例值</td>
								<td width="12%">默认值</td>
								<td width="12%">描述</td>
								<td width="4%">排序</td>
							</tr>
						</thead>
						<tbody>
						<?php
    
    foreach ($application_input_list as $k => $v) {
        $link_cate_name = is_base_type($v['cate_name']) ? $v['cate_name'] : '<a
									href="/api_admin.php/api_admin/obj_show?cid=' . $_REQUEST['cid'] . '&item_id=' . $v['obj_id'] . '&cate_name=' . $_REQUEST['cate_name'] . '&method_name=' . $v['cate_name'] . '&apiid=' . $_REQUEST['apiid'] . '">' . $v['cate_name'] . '</a>';
        
        ?>
							<tr>
								<td><?php echo $v['field_name']?></td>
								<td> <?php echo $link_cate_name;?></td>
								<td><?php echo $v['is_necessary']?></td>
								<td><?php echo $v['example']?></td>
								<td><?php echo $v['default_value']?></td>
								<td><?php echo $v['description']?></td>
								<td><?php echo $v['sort']?></td>
							</tr>
							<?php }?>
						</tbody>
					</table>

				</div>
			</div>

			<div class="sub-wrap" data-spm="1998342952">
				<h3 class="head-title">返回结果</h3>
				<div class="api-list">

					<table style="width: 100%" id="_list">
						<thead>
							<tr style="border: 1px solid #ccc">
								<td width="6%">名称</td>
								<td width="6%">类型</td>
								<td width="6%">是否必须</td>
								<td width="12%">示例值</td>
								<td width="12%">默认值</td>
								<td width="12%">描述</td>
								<td width="4%">排序</td>
							</tr>
						</thead>
						<tbody>
						<?php
    
    foreach ($application_output_list as $k => $v) {
        $link_cate_name = is_base_type($v['cate_name']) ? $v['cate_name'] : '<a
									href="/api_admin.php/api_admin/obj_show?cid=' . $_REQUEST['cid'] . '&item_id=' . $v['obj_id'] . '&cate_name=' . $_REQUEST['cate_name'] . '&method_name=' . $v['cate_name'] . '&apiid=' . $_REQUEST['apiid'] . '">' . $v['cate_name'] . '</a>';
        
        ?>
							<tr>
								<td><?php echo $v['field_name']?></td>
								<td> <?php echo $link_cate_name;?></td>
								<td><?php echo $v['is_necessary']?></td>
								<td><?php echo $v['example']?></td>
								<td><?php echo $v['default_value']?></td>
								<td><?php echo $v['description']?></td>
								<td><?php echo $v['sort']?></td>
							</tr>
							<?php }?>
						</tbody>
					</table>

				</div>
			</div>

			<div class="sub-wrap" data-spm="1998342952">
				<h3 class="head-title">JSON示例</h3>
				<div class="api-list"  id="json">
				功能开发中...
				</div>
			</div>
			<div class="sub-wrap" data-spm="1998342952">
				<h3 class="head-title">API工具</h3>
				<div class="api-list"  >
				 <button class="btn"  id="json" onclick="window.open('/api_admin.php/api_admin/api_tools?apiid=<?php echo $_REQUEST['apiid'];?>');">API TOOLS 在线测试工具</button>
				</div>
			</div>
			
		</div>
	</div>
	<!--正文区 end-->
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
