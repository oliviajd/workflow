/**
 * AdminLTE Demo Menu
 * ------------------
 * You should not use this file in production.
 * This file is for demo purposes only.
 */
(function ($, AdminLTE) {

  "use strict";

  /**
   * List of all the available skins
   *
   * @type Array
   */
  var my_skins = [
    "skin-blue",
    "skin-black",
    "skin-red",
    "skin-yellow",
    "skin-purple",
    "skin-green",
    "skin-blue-light",
    "skin-black-light",
    "skin-red-light",
    "skin-yellow-light",
    "skin-purple-light",
    "skin-green-light"
  ];

  //Create the new tab
  var tab_pane = $("<div />", {
    "id": "control-sidebar-theme-demo-options-tab",
    "class": "tab-pane active"
  });

  //Create the tab button
  var tab_button = $("<li />", {"class": "active"})
      .html("<a href='#control-sidebar-theme-demo-options-tab' data-toggle='tab'>"
      + "<i class='fa fa-wrench'></i>"
      + "</a>");

  //Add the tab button to the right sidebar tabs
  $("[href='#control-sidebar-home-tab']")
      .parent()
      .before(tab_button);

  //Create the menu
  var demo_settings = $("<div />");

  //Layout options
  demo_settings.append(
      "<h4 class='control-sidebar-heading'>"
      + "Layout Options"
      + "</h4>"
        //Fixed layout
      + "<div class='form-group'>"
      + "<label class='control-sidebar-subheading'>"
      + "<input type='checkbox' data-layout='fixed' class='pull-right'/> "
      + "Fixed layout"
      + "</label>"
      + "<p>Activate the fixed layout. You can't use fixed and boxed layouts together</p>"
      + "</div>"
        //Boxed layout
      + "<div class='form-group'>"
      + "<label class='control-sidebar-subheading'>"
      + "<input type='checkbox' data-layout='layout-boxed'class='pull-right'/> "
      + "Boxed Layout"
      + "</label>"
      + "<p>Activate the boxed layout</p>"
      + "</div>"
        //Sidebar Toggle
      + "<div class='form-group'>"
      + "<label class='control-sidebar-subheading'>"
      + "<input type='checkbox' data-layout='sidebar-collapse' class='pull-right'/> "
      + "Toggle Sidebar"
      + "</label>"
      + "<p>Toggle the left sidebar's state (open or collapse)</p>"
      + "</div>"
        //Sidebar mini expand on hover toggle
      + "<div class='form-group'>"
      + "<label class='control-sidebar-subheading'>"
      + "<input type='checkbox' data-enable='expandOnHover' class='pull-right'/> "
      + "Sidebar Expand on Hover"
      + "</label>"
      + "<p>Let the sidebar mini expand on hover</p>"
      + "</div>"
        //Control Sidebar Toggle
      + "<div class='form-group'>"
      + "<label class='control-sidebar-subheading'>"
      + "<input type='checkbox' data-controlsidebar='control-sidebar-open' class='pull-right'/> "
      + "Toggle Right Sidebar Slide"
      + "</label>"
      + "<p>Toggle between slide over content and push content effects</p>"
      + "</div>"
        //Control Sidebar Skin Toggle
      + "<div class='form-group'>"
      + "<label class='control-sidebar-subheading'>"
      + "<input type='checkbox' data-sidebarskin='toggle' class='pull-right'/> "
      + "Toggle Right Sidebar Skin"
      + "</label>"
      + "<p>Toggle between dark and light skins for the right sidebar</p>"
      + "</div>"
  );
  var skins_list = $("<ul />", {"class": 'list-unstyled clearfix'});

  //Dark sidebar skins
  var skin_blue =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-blue' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px; background: #367fa9;'></span><span class='bg-light-blue' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #222d32;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin'>Blue</p>");
  skins_list.append(skin_blue);
  var skin_black =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-black' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div style='box-shadow: 0 0 2px rgba(0,0,0,0.1)' class='clearfix'><span style='display:block; width: 20%; float: left; height: 7px; background: #fefefe;'></span><span style='display:block; width: 80%; float: left; height: 7px; background: #fefefe;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #222;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin'>Black</p>");
  skins_list.append(skin_black);
  var skin_purple =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-purple' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-purple-active'></span><span class='bg-purple' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #222d32;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin'>Purple</p>");
  skins_list.append(skin_purple);
  var skin_green =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-green' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-green-active'></span><span class='bg-green' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #222d32;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin'>Green</p>");
  skins_list.append(skin_green);
  var skin_red =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-red' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-red-active'></span><span class='bg-red' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #222d32;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin'>Red</p>");
  skins_list.append(skin_red);
  var skin_yellow =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-yellow' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-yellow-active'></span><span class='bg-yellow' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #222d32;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin'>Yellow</p>");
  skins_list.append(skin_yellow);

  //Light sidebar skins
  var skin_blue_light =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-blue-light' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px; background: #367fa9;'></span><span class='bg-light-blue' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #f9fafc;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin' style='font-size: 12px'>Blue Light</p>");
  skins_list.append(skin_blue_light);
  var skin_black_light =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-black-light' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div style='box-shadow: 0 0 2px rgba(0,0,0,0.1)' class='clearfix'><span style='display:block; width: 20%; float: left; height: 7px; background: #fefefe;'></span><span style='display:block; width: 80%; float: left; height: 7px; background: #fefefe;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #f9fafc;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin' style='font-size: 12px'>Black Light</p>");
  skins_list.append(skin_black_light);
  var skin_purple_light =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-purple-light' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-purple-active'></span><span class='bg-purple' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #f9fafc;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin' style='font-size: 12px'>Purple Light</p>");
  skins_list.append(skin_purple_light);
  var skin_green_light =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-green-light' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-green-active'></span><span class='bg-green' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #f9fafc;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin' style='font-size: 12px'>Green Light</p>");
  skins_list.append(skin_green_light);
  var skin_red_light =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-red-light' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-red-active'></span><span class='bg-red' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #f9fafc;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin' style='font-size: 12px'>Red Light</p>");
  skins_list.append(skin_red_light);
  var skin_yellow_light =
      $("<li />", {style: "float:left; width: 33.33333%; padding: 5px;"})
          .append("<a href='javascript:void(0);' data-skin='skin-yellow-light' style='display: block; box-shadow: 0 0 3px rgba(0,0,0,0.4)' class='clearfix full-opacity-hover'>"
          + "<div><span style='display:block; width: 20%; float: left; height: 7px;' class='bg-yellow-active'></span><span class='bg-yellow' style='display:block; width: 80%; float: left; height: 7px;'></span></div>"
          + "<div><span style='display:block; width: 20%; float: left; height: 20px; background: #f9fafc;'></span><span style='display:block; width: 80%; float: left; height: 20px; background: #f4f5f7;'></span></div>"
          + "</a>"
          + "<p class='text-center no-margin' style='font-size: 12px;'>Yellow Light</p>");
  skins_list.append(skin_yellow_light);

  demo_settings.append("<h4 class='control-sidebar-heading'>Skins</h4>");
  demo_settings.append(skins_list);

  tab_pane.append(demo_settings);
  $("#control-sidebar-home-tab").after(tab_pane);

  setup();

  /**
   * Toggles layout classes
   *
   * @param String cls the layout class to toggle
   * @returns void
   */
  function change_layout(cls) {
    $("body").toggleClass(cls);
    AdminLTE.layout.fixSidebar();
    //Fix the problem with right sidebar and layout boxed
    if (cls == "layout-boxed")
      AdminLTE.controlSidebar._fix($(".control-sidebar-bg"));
    if ($('body').hasClass('fixed') && cls == 'fixed') {
      AdminLTE.pushMenu.expandOnHover();
      AdminLTE.layout.activate();
    }
    AdminLTE.controlSidebar._fix($(".control-sidebar-bg"));
    AdminLTE.controlSidebar._fix($(".control-sidebar"));
  }

  /**
   * Replaces the old skin with the new skin
   * @param String cls the new skin class
   * @returns Boolean false to prevent link's default action
   */
  function change_skin(cls) {
    $.each(my_skins, function (i) {
      $("body").removeClass(my_skins[i]);
    });

    $("body").addClass(cls);
    store('skin', cls);
    return false;
  }

  /**
   * Store a new settings in the browser
   *
   * @param String name Name of the setting
   * @param String val Value of the setting
   * @returns void
   */
  function store(name, val) {
    if (typeof (Storage) !== "undefined") {
      localStorage.setItem(name, val);
    } else {
      window.alert('Please use a modern browser to properly view this template!');
    }
  }

  /**
   * Get a prestored setting
   *
   * @param String name Name of of the setting
   * @returns String The value of the setting | null
   */
  function get(name) {
    if (typeof (Storage) !== "undefined") {
      return localStorage.getItem(name);
    } else {
      window.alert('Please use a modern browser to properly view this template!');
    }
  }

  /**
   * Retrieve default settings and apply them to the template
   *
   * @returns void
   */
  function setup() {
    var tmp = get('skin');
    if (tmp && $.inArray(tmp, my_skins))
      change_skin(tmp);

    //Add the change skin listener
    $("[data-skin]").on('click', function (e) {
      if($(this).hasClass('knob'))
        return;
      e.preventDefault();
      change_skin($(this).data('skin'));
    });

    //Add the layout manager
    $("[data-layout]").on('click', function () {
      change_layout($(this).data('layout'));
    });

    $("[data-controlsidebar]").on('click', function () {
      change_layout($(this).data('controlsidebar'));
      var slide = !AdminLTE.options.controlSidebarOptions.slide;
      AdminLTE.options.controlSidebarOptions.slide = slide;
      if (!slide)
        $('.control-sidebar').removeClass('control-sidebar-open');
    });

    $("[data-sidebarskin='toggle']").on('click', function () {
      var sidebar = $(".control-sidebar");
      if (sidebar.hasClass("control-sidebar-dark")) {
        sidebar.removeClass("control-sidebar-dark")
        sidebar.addClass("control-sidebar-light")
      } else {
        sidebar.removeClass("control-sidebar-light")
        sidebar.addClass("control-sidebar-dark")
      }
    });

    $("[data-enable='expandOnHover']").on('click', function () {
      $(this).attr('disabled', true);
      AdminLTE.pushMenu.expandOnHover();
      if (!$('body').hasClass('sidebar-collapse'))
        $("[data-layout='sidebar-collapse']").click();
    });

    // Reset options
    if ($('body').hasClass('fixed')) {
      $("[data-layout='fixed']").attr('checked', 'checked');
    }
    if ($('body').hasClass('layout-boxed')) {
      $("[data-layout='layout-boxed']").attr('checked', 'checked');
    }
    if ($('body').hasClass('sidebar-collapse')) {
      $("[data-layout='sidebar-collapse']").attr('checked', 'checked');
    }

  }
})(jQuery, $.AdminLTE);

//重写jquery的ajax方法
(function($){  
    //备份jquery的ajax方法  
    var _ajax=$.ajax;  
      
    //重写jquery的ajax方法  
    $.ajax=function(opt){  
        //备份opt中error和success方法  
        var fn = {  
            error:function(XMLHttpRequest, textStatus, errorThrown){},  
            success:function(data, textStatus){}  
        }  
        if(opt.error){  
            fn.error=opt.error;  
        }  
        if(opt.success){  
            fn.success=opt.success;  
        }  
          
        //扩展增强处理  
        var _opt = $.extend(opt,{  
            error:function(XMLHttpRequest, textStatus, errorThrown){  
                //错误方法增强处理  
                  
                fn.error(XMLHttpRequest, textStatus, errorThrown);  
            },  
            success:function(data, textStatus){  
                //成功回调方法增强处理  
                if (opt.dataType == 'json') {
                    if (data.error_no == '401') {
                        USER.clear();
                        window.location.href = '/login.html'
                    }
                }
                fn.success(data, textStatus);  
            },
            cache:false,
        });  
        _ajax(_opt);  
    };  
})(jQuery);  
//获取url中的参数
function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r != null) return unescape(r[2]); return null; //返回参数值
}

function listToTree(data, options) {
    options = options || {};
    var ID_KEY = options.idKey || 'id';
    var PARENT_KEY = options.parentKey || 'parent';
    var CHILDREN_KEY = options.childrenKey || 'children';

    var tree = [],
        childrenOf = {},
        ids = {};
    var item, id, parentId;

    for (var i = 0, length = data.length; i < length; i++) {
        item = data[i];
        id = item[ID_KEY];
        parentId = item[PARENT_KEY] || 0;
        // every item may have children
        childrenOf[id] = childrenOf[id] || [];
        // init its children
        item[CHILDREN_KEY] = childrenOf[id];
        if (parentId != 0) {
            // init its parent's children object
            childrenOf[parentId] = childrenOf[parentId] || [];
            // push it into its parent's children object
            childrenOf[parentId].push(item);
        } else {
            tree.push(item);
        }
        ids[id] = true;
    };
    //有节点但是 无parent_id = 0 的情况
    if (Object.keys(childrenOf).length > 0 && tree.length == 0) {
        for(var i in childrenOf) {
            if (!ids[i]) {
                for(var j in childrenOf[i]) {
                    tree.push(childrenOf[i][j]);
                }
            }
        }
    }
    return tree;
}

var USER = {};
USER.login = function(option){
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/user/login',
        data: {loginname:option.loginname,password_md5:$.md5(option.password),from:'admin'},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}

USER.set_token = function(token) {
    if (1|| navigator.userAgent.match(/(iPhone|iPod|Android|ios|Windows Phone)/i)) {
        window.localStorage.setItem('token', token.token);
        window.localStorage.setItem('token_over_time', token.over_time);
    }
    window.sessionStorage.setItem('token', token.token);
    window.sessionStorage.setItem('token_over_time', token.over_time);
    return true;
}

USER.get_token = function() {
    if (1|| navigator.userAgent.match(/(iPhone|iPod|Android|ios|Windows Phone)/i)) {
        if (window.localStorage.getItem('token_over_time')) {
            return window.localStorage.getItem('token');
        } else {
            return false;
        }
    } else {
        if (window.sessionStorage.getItem('token_over_time')) {
            return window.sessionStorage.getItem('token');
        } else {
            return false;
        }
    }
}

USER.set_info = function(user) {
    if (1|| navigator.userAgent.match(/(iPhone|iPod|Android|ios|Windows Phone)/i)) {
        window.localStorage.setItem('user_nick', user.loginname);
    } else {
        window.sessionStorage.setItem('user_nick', user.loginname);
    }
    return true;
}

USER.get_info = function(key){
    if (1|| navigator.userAgent.match(/(iPhone|iPod|Android|ios|Windows Phone)/i)) {
        return window.localStorage.getItem('user_'+key);
    } else {
        return window.sessionStorage.getItem('user_'+key);
    }
}

USER.clear = function() {
    window.sessionStorage.removeItem('token');
    window.sessionStorage.removeItem('token_over_time');
    window.sessionStorage.removeItem('user_nick');
    window.localStorage.removeItem('token');
    window.localStorage.removeItem('token_over_time');
    window.localStorage.removeItem('user_nick');
    return true;
}

USER.find = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/user/find',
        data: {'token':option.token,loginnames:option.loginnames},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}

var ROLE = {};
ROLE.add = function (option) {
    var role = {
        
    }
    $.extend(role, option.role);
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/add',
        data: $.extend(role,{'token':option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.update = function (option) {
    var role = {
        
    }
    $.extend(role, option.role);
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/update',
        data: $.extend({role_id:option.role_id},role,{'token':option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.get = function (option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/get',
        data: $.extend({'role_id':option.role_id},{'token':option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.lists = function (option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/lists',
        data: $.extend({'role_id':option.role_id},{'token':option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.delete = function (option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/delete',
        data: $.extend({'role_id':option.role_id},{'token':option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r,option);
        }
    });
}
ROLE.permission_tree = function (option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/permission/tree',
        data: $.extend({'role_id':option.role_id},{'token':option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r,option);
        }
    });
}
ROLE.load_module = function(option){
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/lists/user/module/',
        data: {token:option.token},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.set_module = function(data) {
    window.localStorage.setItem('role_module', JSON.stringify(data));
}

ROLE.get_module = function() {
    return JSON.parse(window.localStorage.getItem('role_module'));
}
ROLE.load_method = function(option){
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/lists/user/method/',
        data: {token:option.token},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.set_method = function(data) {
    window.localStorage.setItem('role_method', JSON.stringify(data));
}

ROLE.get_method = function() {
    return JSON.parse(window.localStorage.getItem('role_method'));
}

ROLE.list_module = function(option){
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/lists/module/',
        data: {token:option.token,role_id:option.role_id},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.list_method = function(option){
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/lists/method/',
        data: {token:option.token,role_id:option.role_id},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
ROLE.delete_user = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/delete/user/',
        data: {token:option.token,role_id:option.role_id,user_id:option.user_id},
        dataType: 'json',
        success: function (r) {
            callback(r,option);
        }
    });
}
ROLE.add_user = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/add/user/',
        data: {token:option.token,role_ids:option.role_id,user_id:option.user_id},
        dataType: 'json',
        success: function (r) {
            callback(r,option);
        }
    });
}
ROLE.lists_user = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/role/lists/user/',
        data: {token:option.token,role_id:option.role_id,user_id:option.user_id},
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}

var FINANCE_ACCOUNT = {};
FINANCE_ACCOUNT.update = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/finance/account/update/',
        data: $.extend({},option.account,{token:option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r,option);
        }
    });
}
FINANCE_ACCOUNT.get = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/finance/account/get/',
        data: $.extend({},{token:option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r,option);
        }
    });
}
var FINANCE_BILL = {};
FINANCE_BILL.get = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/finance/bill/get/',
        data: $.extend({},{finance_bill_id:option.finance_bill_id},{token:option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
FINANCE_BILL.add = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/finance/bill/add/',
        data: $.extend({},option.bill,{token:option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
FINANCE_BILL.update = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/finance/bill/update/',
        data: $.extend({},option.bill,{finance_bill_id:option.finance_bill_id},{token:option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}
FINANCE_BILL.action_lists = function(option) {
    var callback = option.callback || function () {};
    $.ajax({
        url: API_HOST + '/finance/bill/action/lists/',
        data: $.extend({},option.bill,{finance_bill_id:option.finance_bill_id},{token:option.token}),
        dataType: 'json',
        success: function (r) {
            callback(r);
        }
    });
}

var FILE = {};
FILE.download = function(option) {
    window.open(API_HOST + '/file/download?token=' + option.token+'&file_id=' + option.file_id + '&download_name=' + (option.download_name||''));
}

if (window.location.host == 'rongtest.ifcar99.com') {
    var INIT = {
        'API_HOST': 'http://apitest.ifcar99.com',
        'SOURCE_HOST': 'http://apitest.ifcar99.com'
    };
} else if (window.location.host == '127.0.0.1:9000') {
    var INIT = {
        'API_HOST': 'http://api.car.com',
        'SOURCE_HOST': 'http://api.car.com'
    };
} else if (window.location.host == 'admintest.api_lsk.com') {
    var INIT = {
        'API_HOST': 'http://api_lsk.com/api.php',
        'SOURCE_HOST': 'http://api_lsk.com/api.php'
    };
}else {
    var INIT = {
        'API_HOST': 'http://api.ifcar99.com',
        'SOURCE_HOST': 'http://api.ifcar99.com'
    };
}
var API_HOST = INIT.API_HOST;
INIT.go = function(){
    if (window.location.pathname != '/login.html') {
        USER.get_token() || (window.location.href = '/login.html');
    } else {
        USER.get_token() && (window.location.href = '/index.html');
    }
    // 连接服务端
//    var socket = io('http://'+document.domain+':2120');
//    // 连接后登录
//    socket.on('connect', function(){
//    	socket.emit('login', USER.get_token());
//    });
//    // 后端推送来消息时
//    socket.on('new_msg', function(msg){
//        var num = parseInt(msg);
//        $('#count_new_order').text(parseInt($('#count_new_order').text()) + num);
//    });
    //菜单显示
    var modules = ROLE.get_module();
    for(var m in modules) {
        $('.sidebar-menu .treeview[data-mid="'+modules[m]['module_id']+'"]').removeClass('hide');
    }
    //菜单选中
    $('.sidebar-menu .treeview').find('a').each(function(){
        if ($(this).attr('href').replace(/(^\s*)|(\s*$)/g, "") == window.location.pathname) {
            $(this).parents('li').addClass('active');
        }
    });
    //用户信息设置
    $('.data-user-nick').text(USER.get_info('nick'));
}
function time_to_str(time) {
    var date = new Date(time*1000);
    var Y = date.getFullYear() + '-';
    var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
    var D = date.getDate() + ' ';
    var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
    var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
    var s = date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds();
    return Y + M + D + h + m + s; 
}
function time_to_str_ymd(time) {
    var date = new Date(time*1000);
    var Y = date.getFullYear() + '-';
    var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
    var D = date.getDate()< 10 ? '0' +date.getDate() : date.getDate();
    return Y + M + D; 
}
$(function(){
    INIT.go();
})