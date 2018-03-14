<!DOCTYPE html>
<html>
<head>
<title>接口操作后台</title>
<style>
.d_list_option {width:20%;}
</style>
</head>
<body>
<div>
<form action="/api_admin_anjie.php/api_admin/index" method="post" >
<p>
    <fieldset class="d_list_option">
        <legend> 请选择数据库 </legend>
        <select style="width:90%;" name="database_from">
        <?php foreach($database_lists as $k=>$v){?>
            <option value="<?php echo $k?>" <?php if($k=='test199'){echo 'selected="selected"';}?> ><?php echo $v?></option>
        <?php }?>
        </select>
    </fieldset>
</p>
<p>
	<fieldset class="d_list_option">
        <legend> 请输入管理密码 </legend>
		<input style="width:90%;" type="password" name="passwd" /> 
	</fieldset>
</p>
<input type="submit" name="submit" value="提交" />
</form>
</div>
</body>
</html>
