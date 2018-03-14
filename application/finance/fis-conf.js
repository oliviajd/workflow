//// 所有的 js
//
//fis.match('*.js', {
//    //发布到/static/js/xxx目录下
//    url : '/data/data_admin/$0'
//});
//
//// 所有的 css
//fis.match('*.css', {
//    //发布到/static/css/xxx目录下
//    url : '/data/data_admin/$0'
//});
//
//// 所有image目录下的.png，.gif文件
//fis.match('*.{png,gif,jpg,jpeg}', {
//    url: '/data/data_admin/$0'
//});
fis.match('*.{js,css}', {
  useHash: true
});