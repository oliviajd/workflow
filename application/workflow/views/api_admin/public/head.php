<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>API - 开发文档 - API说明文档 - 用户API</title>
    <link rel="stylesheet" href="/data/style/api_admin/editor-pkg-min-datauri.css" />
    <link rel="stylesheet" href="/data/style/api_admin/reset.css" />
    <link rel="stylesheet" href="/data/style/api_admin/common.css" />
    <link rel="stylesheet" href="/data/style/api_admin/open-base.css" />
    <link rel="stylesheet" href="/data/style/api_admin/docmtCenter.css" />
    <script type="text/javascript" src="/data/js/jquery-1.10.2.min.js"></script>
</head>
<style>

    .api-list p { margin-bottom: 10px; padding-left: 34em; }
    .api-list span.l { display: block; float: left; margin-left: -34em; }
    .api-list span.free { display: block; float: left; margin-left: -4em; color: green; background-color: #DDDDDD; }
    .api-list span.charge { display: block; float: left; margin-left: -4em; color: red; background-color: #DDDDDD; }
    .open-bg, .private-bg, .incre-bg { background: url("/data/images/T1gqqKFrpbXXad8o_i-55-67.png") no-repeat scroll -25px 3px rgba(0, 0, 0, 0); padding-left: 35px; }

</style>

<script>

    var folderPath = '/index_api.php';

</script>
<body>

    <!-- header start -->
    <div class="top-header-menu" data-spm="1998342671">
        <div class="top-menu-cont">
            <div class="top-menu-cont">
                <ul class="top-nav-menu clearfix">
                    <li <?php
                    if (!isset($_REQUEST['cate_type']) && !isset($_REQUEST['t'])) {
                        echo 'class="selected"';
                    }
                    ?>><a href="/api_admin/manage">首页</a></li>
                    <li <?php
                    if ($_REQUEST['cate_type'] == 2) {
                        echo 'class="selected"';
                    }
                    ?>><a href="/api_admin/category_list?cate_type=2">对象管理</a></li>
                        <?php if (isset($_COOKIE['type']) && $_COOKIE['type'] == 'admin') { ?>
                        <li <?php
                        if ($_REQUEST['cate_type'] == 1) {
                            echo 'class="selected"';
                        }
                        ?>><a href="/api_admin/category_list?cate_type=1">接口分类管理</a></li>
                        <li <?php
                        if ($_REQUEST['cate_type'] == 3) {
                            echo 'class="selected"';
                        }
                        ?>><a href="/api_admin/category_list?cate_type=3">系统输入管理</a></li>
                        <li <?php
                        if ($_REQUEST['cate_type'] == 4) {
                            echo 'class="selected"';
                        }
                        ?>><a href="/api_admin/category_list?cate_type=4">应用输入管理</a></li>
                        <li <?php
                        if ($_REQUEST['cate_type'] == 5) {
                            echo 'class="selected"';
                        }
                        ?>><a href="/api_admin/category_list?cate_type=5">输出管理</a></li>
                        <!-- <li <?php
                        if ($_REQUEST['t'] == 'sync') {
                            echo 'class="selected"';
                        }
                        ?>><a href="/api_admin/database_sync?t=sync">数据库同步</a></li>
                        <?php } ?>-->
                    <li <?php
                    if ($_REQUEST['t'] == 'logout') {
                        echo 'class="selected"';
                    }
                    ?>><a href="/api_admin/logout?t=logout">退出</a></li>
                    <li><a href="javascript:;">DB: <?php echo $_COOKIE['database'] ?></a></li>
                    <!--<li><a href="">DATABASE: <?php echo $_COOKIE['database'] ?><i class="reddot"></i></a></li>-->
                </ul>
            </div>
        </div>
    </div>
    <!-- header end -->
