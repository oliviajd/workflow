<?php
//exit("teaching_resource_admin");

header('Content-Type:text/html; charset=utf-8');
/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
	define('ENVIRONMENT', 'development');
/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */

if (defined('ENVIRONMENT'))
{
	switch (ENVIRONMENT)
	{
		case 'development':
			error_reporting(E_ALL);
		break;

		case 'testing':
		case 'production':
			error_reporting(0);
		break;

		default:
			exit('The application environment is not set correctly.');
	}
}
ini_set('display_errors',true);
error_reporting(E_ERROR);
/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
	$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
	$application_folder = 'application';

/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here.  For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT:  If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller.  Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 *
 */
	// The directory name, relative to the "controllers" folder.  Leave blank
	// if your controller is not in a sub-folder within the "controllers" folder
	// $routing['directory'] = '';

	// The controller class file name.  Example:  Mycontroller
	// $routing['controller'] = '';

	// The controller function you wish to be called.
	// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 *
 */
	// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

	// Set the current directory correctly for CLI requests
	if (defined('STDIN'))
	{
		chdir(dirname(__FILE__));
	}

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path).'/';
	}

	// ensure there's a trailing slash
	$system_path = rtrim($system_path, '/').'/';

	// Is the system path correct?
	if ( ! is_dir($system_path))
	{
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
	}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

	// The PHP file extension
	// this global constant is deprecated.
	define('EXT', '.php');

	// Path to the system folder
	define('BASEPATH', str_replace("\\", "/", $system_path));

	// Path to the front controller (this file)
	define('FCPATH', str_replace(SELF, '', __FILE__));

	// Name of the "system folder"
	define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));


	// The path to the "application" folder
	if (is_dir($application_folder))
	{
		define('APPPATH', $application_folder.'/');
	}
	else
	{
		if ( ! is_dir(BASEPATH.$application_folder.'/'))
		{
			exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
		}

		define('APPPATH', BASEPATH.$application_folder.'/');
	}

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */
ini_set("session.save_handler", "memcache");  
ini_set("session.save_path", "tcp://127.0.0.1:11211");
require_once APPPATH . 'libraries/func.global.php';
session_start();
cookie_id();
define('STATIC_PATH', 'http://static.iasku.com/data/');
define('CSS_PATH', 'http://static.iasku.com/data/style/');
define('PAD_CSS_PATH', '/data/pad_style/');
define('APP_CSS_JS_PATH','/data/app/');
define('JS_PATH', 'http://static.iasku.com/data/js/');
define('PAD_JS_PATH', '/data/pad_js/');
define('CKEDITOR_PATH', '/data/ckeditor/');
define('KINDEDITOR_PATH', '/data/kindeditor/');
define('IMAGE_PATH', 'http://static.iasku.com/data/images/');
define('UPLOAD_PATH', 'http://static.iasku.com/data/upload/');
// define('UPLOAD_PATH', 'data/upload/');
define('AVATAR_PATH', 'http://static.iasku.com/data/avatar/');
define('VIDEO_IMAGE_PATH', 'http://video3.iasku.net/');
define('VIDEO_SET_IMAGE_PATH', 'http://video1.iasku.net:88/img/%E7%9F%A5%E8%AF%86%E7%82%B9/');
define('VIDEO_SET_IMAGE_PATH_LOCAL', '/data/video_set_image/');
define('PAPER_IMAGE_PATH', '/data/paper_image/');
//pc端css、js、图片路径
define('PC_JS_PATH', '/data/pc/js/');
define('PC_CSS_PATH', '/data/pc/css/');
define('PC_IMAGE_PATH', '/data/pc/image/');
define('IMAGE_QUESTION_PATH', 'http://video1.iasku.net:88/');
// define('QUESTION_API_URL','http://iasku.517idc.com:881/Service1.asmx?WSDL');
define('QUESTION_API_URL','http://video1.iasku.net:881/Service1.asmx?WSDL');
define('QUESTION_API_URL_2','http://iasku.net:881/wordquestion.asmx?WSDL');
// define('VIDEO_URL','http://1222259954.517idc.com/flv/');
define('VIDEO_URL','http://video3.iasku.net/flv/');
define('VIDEO_URL_SWF','http://video3.iasku.net/img/flash/');
define('VIDEO_URL_MP4','http://video3.iasku.net/videos/');
define('MEMCACHE_HOST','localhost');
define('MEMCACHE_PORT',11211);
define('OCR_URL','http://121.40.207.175:81/webservice.asmx?WSDL');  // new OCR识别服务器地址
//define('OCR_URL','http://122.225.99.54:81/webservice.asmx?WSDL');  // old OCR识别服务器地址
//define('OCR_URL','http://10.26.162.199:32/webservice.asmx?WSDL'); // test OCR测试服务器199
define('APP_URL','http://www.iasku.com:8080/');
//define('APP_URL','http://10.26.162.199:800/');
define('APP_URL_OLD', 'http://video1.iasku.net:881/Service1.asmx?WSDL'); // 原有注册用户API接口地址

define('IASKU_KEY', '0GgybMapFBgSHtJqlTji5dt1A9A5PZ2cADJDf2wLrqR'); // API 加密密钥 密钥泄漏，需更改密钥。密钥更改后，只影响长期登录用户,登录一次即可。
define('RAND_CHARS', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'); // 用做随机字符串

define('IS_POST', $_SERVER['REQUEST_METHOD'] == 'POST' ? true : false);
define('IS_AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false);
 

//伪静态
define('REWRITE_URL_QUESTION','/shiti/');
define('REWRITE_URL_PAPER','/shijuan/');
define('REWRITE_URL_SEARCH','/q_');

//缓存key值定义
define('CACHE_QUESTION','QUESTION_');
define('CACHE_VIDEO','VIDEO_');
define('CACHE_CATE','CATE_');
define('CACHE_USER_TEACHER','USER_TEACHER_');

//网站底部文字
define('FOOT_TEXT','Copyright©2011-2012 IASKU co.,ltd All Rights Reserved 浙ICP备 13036379号-1');

// define('TESSERACT_PATH', '"C:\Program Files\Tesseract-OCR\tesseract.exe"');

require_once BASEPATH.'core/CodeIgniter.php';

/* End of file index.php */
/* Location: ./index.php */
