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

.d_list_option{ float:left; width:20%; height:50px;}
.input_msg {height:30px;padding-left:10%;color:red;}
.input_button {height:30px;padding-left:20%;}

.clear {clear:both;}

</style>

<script>
$(function(){

    $('input[name="submit_sync"]').click(function(){
        var database_from = $('select[name="database_from"]').val();
        var database_to = $('select[name="database_to"]').val();
        $('.input_msg').html('同步中...');

        $.ajax({
            type : 'GET',
            url : '/api_admin/ajax?m=database_sync',
            async : 'false',
            data : {database_from:database_from, database_to:database_to},
            dataType : 'json',
            success : function(data){
                $('.input_msg').html('');
                if(data.no == 1)
                {
                    $('.input_msg').html('同步成功');
                }
                else
                {
                    $('.input_msg').html('同步失败');
                }
            }
        });

    });

});
</script>

<!--api detail-->
<div class="container grid-s165m0 layout">
	<div class="sub_menu" style="padding: 10px;">

    <fieldset class="d_list_option">
        <legend> 源数据库 </legend>
        <select name="database_from">
        <?php foreach($database_lists as $k=>$v){?>
            <option value="<?php echo $k?>" <?php if($k=='test'){echo 'selected="selected"';}?> ><?php echo $v?></option>
        <?php }?>
        </select>
    </fieldset>

    <fieldset class="d_list_option">
        <legend> 目的数据库 </legend>
        <select name="database_to">
        <?php foreach($database_lists as $k=>$v){?>
            <option value="<?php echo $k?>"><?php echo $v?></option>
        <?php }?>
        </select>
    </fieldset>

	</div>
    <div class="clear"></div>
    <div class="input_msg"> </div>
    <div class="input_button"><input type="button" value="同步" class="btn" name="submit_sync" id="submit_sync" ></div>
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
