<!DOCTYPE HTML>
<html>
	<?php include APPPATH . 'views/api_admin/public/head.php'; ?>

<div class="top-nav">
    <div class="option">
        <a href="" class="home-link home-link2">API</a>
    </div>
</div>

<!--api detail-->
<div class="container grid-s165m0 layout">
	<?php include APPPATH . 'views/api_admin/public/left.php'; ?>
	
	<!--正文区 begin-->
    <div class="col-main bg_line">
        <div class="main-wrap">
            <h1 class="title"><?php echo $_REQUEST['cate_name'];?>API</h1>
            <div class="sub-wrap" data-spm="1998342952">
                <h3 class="head-title">API列表</h3>
                <div class="api-list">

                <?php if(!empty($api_method_list)){?>
                <?php foreach($api_method_list as $k=>$v){?>
                    <p>
                        <span class="l">
                            <a class="link" href=" show?cate_name=<?php echo $_REQUEST['cate_name'];?>&cid=<?php echo $v['cid']?>&apiid=<?php echo $v['method_id']?>" title="<?php echo $v['method_name_en']?>"><?php echo $v['method_name_en']?></a>
                        </span>
                        <span><?php echo mb_substr($v['description'],0,40,'utf-8');?></span>
                    </p>
                <?php }}?>

                </div>
            </div>
        </div>
    </div>
    <!--正文区 end-->
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
