    <!--左侧导航栏 begin-->
    <div class = "col-sub">
        <div class="api api-scrollbar">
            <ul class="APIgory">
                <li>
                    <div class="APIgory-title">
                        <a href = "/api_admin.php/api_admin/manage?cid=<?php echo $_REQUEST['cid']?>&cate_name=<?php echo $_REQUEST['cate_name'];?>" title="API说明文档">返回&nbsp;<span class = "APIgory-sub-title"> <?php echo $_REQUEST['cate_name'];?></span></a>
                    </div>
                </li>
                <?php foreach($api_method_list as $k=>$v){?>
                    <li >
                        <p class= "APIgory-list <?php if(isset($_GET['apiid']) && $_GET['apiid'] == $v['method_id']){ echo 'focus'; }else{ if(!isset($_GET['apiid']) && $k == '0'){ echo 'focus'; }}?>">
                            <a class= "link info-overflow" title="<?php echo $v['cate_name']?>" href="/api_admin/show?cid=<?php echo $v['cid']?>&cate_name=<?php echo $_REQUEST['cate_name'];?>&apiid=<?php echo $v['method_id']?>"><?php echo $v['method_name_en']?></a>
                        </p>
                        <s></s>
                    </li>
                <?php }?>
            </ul>
        </div>
    </div>
    <!--左侧导航栏 end-->
