    <!--左侧导航栏 begin-->
    <div class = "col-sub">
        <div class="api api-scrollbar">
            <ul class="APIgory">
                <li>
                    <div class="APIgory-title">
                        <a href = "" title="API说明文档">返回&nbsp;<span class = "APIgory-sub-title">API说明文档</span></a>
                    </div>
                </li>
                <?php foreach($api_category_list as $k=>$v){?>
                    <li >
                        <p class= "APIgory-list <?php if(isset($_GET['cid']) && $_GET['cid'] == $v['id']){ echo 'focus'; }else{ if(!isset($_GET['cid']) && $k == '0'){ echo 'focus'; }}?>">
                            <a class= "link info-overflow"  title="<?php echo $v['cate_name']?>" href="/api_admin/manage?cid=<?php echo $v['id']?>&cate_name=<?php echo $v['cate_name']?>"><?php echo $v['cate_name']?></a>
                        </p>
                        <s></s>
                    </li>
                <?php }?>
            </ul>
        </div>
    </div>
    <!--左侧导航栏 end-->
