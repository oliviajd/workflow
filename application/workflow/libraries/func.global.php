<?php


function parse_time($time)
{
    $s = $_SERVER['REQUEST_TIME'] - $time;
    if ($s < 10) {
        return "刚刚";
    } else 
        if ($s < 60) {
            return $s . "秒前";
        } else 
            if ($s < 60 * 60) {
                return intval($s / 60) . "分钟前";
            } else 
                if ($s < 60 * 60 * 24) {
                    return intval($s / (60 * 60)) . "小时前";
                } else {
                    return date('Y-m-d', $time);
                }
}

function curl_upload($post_url, $post_data, $filesize = 0)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $post_url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    // php version >= 5.5 需要 传filesize
    if ($filesize != 0)
        curl_setopt($curl, CURLOPT_INFILESIZE, $filesize);
    
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
    $result = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    return $error ? $error : $result;
}

function curl_upload1($post_url, $post_data)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $post_url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
    $result = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    return $error ? $error : $result;
}

// 转换成百度短链接
function shortBDUrl($url)
{
    $baseurl = 'http://dwz.cn/create.php';
    $strRes = curl_upload1($baseurl, array(
        'url' => $url
    ));
    $arrResponse = json_decode($strRes, true);
    return $arrResponse['tinyurl'];
}
// 判断手机号
function is_mobile($str)
{
    $pattern = "/^(13|15|18|17)\d{9}$/";
    return preg_match($pattern, $str) ? true : false;
}

// 判断是否为移动设备
function is_mobile_terminal()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

// 获得ip地址
function get_ip()
{
    $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
    return ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
}

// 判断是否为ajax请求
function is_ajax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

// 日志
function do_log()
{
    $argvs = func_get_args();
    $str = array();
    $str[] = 'date:' . date('Y-m-d H:i:s');
    foreach ($argvs as $v) {
        $str[] = print_r($v, true);
    }
    $str[] = "\n\n";
    return file_put_contents(APPPATH . 'logs/' . date('Y-m-d') . '.log', implode("\n", $str), FILE_APPEND);
}

// 判断session是否已经start
function is_session_start()
{
    return isset($_SESSION);
}

// 不重复启动session
function _session_start()
{
    if (! is_session_start()) {
        session_start();
    }
}

// 设置cookie_id
function cookie_id()
{
    if (! isset($_COOKIE['C_id'])) {
        _session_start();
        $cookie_id = md5(session_id());
        $expire = time() + 3600 * 24 * 365;
        setcookie('C_id', $cookie_id, $expire, '/');
    } else {
        $cookie_id = $_COOKIE['C_id'];
    }
    return $cookie_id;
}

function letter_rand($num)
{
    $pattern = "A|B|C|D|E|F|G|H|I|J|K|L|M|N|O|P|Q|R|S|T|U|V|W|S|Y|Z";
    $pattern_array = explode("|", $pattern);
    $seed = rand(0, 25);
    $letter = "";
    for ($i = 0; $i < $num; $i ++) {
        $letter .= $pattern_array[$seed];
    }
    return $letter;
}

// csv格式导入和导出
function input_csv($handle)
{
    $out = array();
    $n = 0;
    while ($data = fgetcsv($handle, 10000)) {
        $num = count($data);
        for ($i = 0; $i < $num; $i ++) {
            $out[$n][$i] = $data[$i];
        }
        $n ++;
    }
    return $out;
}

function export_csv($filename, $data)
{
    header("Content-type:text/csv");
    header("Content-Disposition:attachment;filename=" . $filename);
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    echo $data;
}

// 中文转拼音
// 用法：
// 第二个参数留空则为gb1232编码
// 第二个参数随意设置则为utf-8编码
function Pinyin($_String, $_Code = 'gb2312')
{
    $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha" . "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|" . "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er" . "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui" . "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang" . "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang" . "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue" . "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne" . "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen" . "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang" . "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|" . "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|" . "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu" . "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you" . "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|" . "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
    $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990" . "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725" . "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263" . "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003" . "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697" . "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211" . "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922" . "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468" . "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664" . "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407" . "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959" . "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652" . "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369" . "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128" . "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914" . "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645" . "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149" . "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087" . "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658" . "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340" . "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888" . "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585" . "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847" . "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055" . "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780" . "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274" . "|-10270|-10262|-10260|-10256|-10254";
    $_TDataKey = explode('|', $_DataKey);
    $_TDataValue = explode('|', $_DataValue);
    $_Data = (PHP_VERSION >= '5.0') ? array_combine($_TDataKey, $_TDataValue) : _Array_Combine($_TDataKey, $_TDataValue);
    arsort($_Data);
    reset($_Data);
    if ($_Code != 'gb2312')
        $_String = _U2_Utf8_Gb($_String);
    $_Res = '';
    for ($i = 0; $i < strlen($_String); $i ++) {
        $_L = substr($_String, $i, 1);
        $_P = ord($_L);
        if ($_P > 160) {
            $_Q = ord(substr($_String, ++ $i, 1));
            $_P = $_P * 256 + $_Q - 65536;
        }
        $_Res .= _Pinyin($_P, $_Data);
    }
    return preg_replace("/[^a-zA-Z0-9]*/", '', $_Res);
}

function _Pinyin($_Num, $_Data)
{
    if ($_Num > 0 && $_Num < 160)
        return chr($_Num);
    elseif ($_Num < - 20319 || $_Num > - 10247)
        return '';
    else {
        foreach ($_Data as $k => $v) {
            if ($v <= $_Num)
                break;
        }
        return $k;
    }
}

function _U2_Utf8_Gb($_C)
{
    $_String = '';
    if ($_C < 0x80) {
        $_String .= $_C;
    } elseif ($_C < 0x800) {
        $_String .= chr(0xC0 | $_C >> 6);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x10000) {
        $_String .= chr(0xE0 | $_C >> 12);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x200000) {
        $_String .= chr(0xF0 | $_C >> 18);
        $_String .= chr(0x80 | $_C >> 12 & 0x3F);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    }
    return iconv('UTF-8', 'GB2312', $_String);
}

function _Array_Combine($_Arr1, $_Arr2)
{
    for ($i = 0; $i < count($_Arr1); $i ++)
        $_Res[$_Arr1[$i]] = $_Arr2[$i];
    return $_Res;
}

function price($float)
{
    return sprintf('%.2f', $float);
}

function price_to_cents($float)
{ // 单位元转化为分
    return intval(price($float * 100));
}

// NODE TO ARRAY
function node_to_array($node)
{
    $array = false;
    
    if ($node->hasAttributes()) {
        foreach ($node->attributes as $attr) {
            $array[$attr->nodeName] = $attr->nodeValue;
        }
    }
    
    if ($node->hasChildNodes()) {
        if ($node->childNodes->length == 1) {
            $array[$node->firstChild->nodeName] = node_to_array($node->firstChild);
        } else {
            foreach ($node->childNodes as $childNode) {
                if ($childNode->nodeType != XML_TEXT_NODE) {
                    $array[$childNode->nodeName][] = node_to_array($childNode);
                }
            }
        }
    } else {
        return $node->nodeValue;
    }
    return $array;
}

function tree_get_children($tree, $id, $id_key = 'id', $pid_key = 'parent_id')
{
    $children = array();
    foreach ($tree as $k => $v) {
        if ($v[$pid_key] == $id) {
            $children[] = $v;
        }
    }
    return $children;
}

function tree_level($pid, $input, &$output, $level, &$levelsort)
{
    foreach ($input as $k => $v) {
        if ($v['knownpoint_parent_id'] == $pid) {
            tree_level($v['knownpoint_id'], $input, $outother, $level + 1, $levelsort);
            $levelsort[$level][$pid][] = $v;
            if (! empty($outother)) {
                $output[$pid] = $outother;
                $output[$pid . '_val'] = $v;
            } else {
                $output[$pid][$k] = $v;
            }
        }
    }
}

function postHandler($arr)
{ // post数据处理
    $data = array();
    
    foreach ($arr as $key => $val) {
        if (gettype($val) == "string") {
            $data[$key] = trim($val);
        } else {
            $data[$key] = $val;
        }
    }
    
    return $data;
}

function judgeQuestionType($type)
{ // 问题类型判断
    if ($type == 0)
        return "无类型";
    
    if ($type == 1 || $type == 2) {
        return "选择";
    } else {
        return "文本";
    }
}

function uploadFileHandler($upFile)
{
    require_once libfile('class/upload'); // 引入上传文件类
    $uphandler = new discuz_upload();
    // 获取上传文件格式
    $ext = explode(".", $upFile['file']['name']);
    $ext = $ext[count($ext) - 1];
    $docname = time() . $hash . "." . $ext;
    $storeRoad = "d:/www/www/img/wordquestion/doc/$docname";
    if ($ext == "doc" || $ext == "wps") {
        if ($upFile['file']['error'] > 0) {
            return 1;
        } else {
            if (file_exists($storeRoad)) {
                return 2;
            } else {
                // if(!move_uploaded_file($upFile['file']['tmp_name'],$storeRoad)){
                if (! $uphandler->save_to_local($upFile['file']['tmp_name'], $storeRoad)) { // 必须用这个函数才能上传
                    return 3;
                } else {
                    return $docname; // 上传成功
                }
                return 5; // 未成功上传
            }
        }
    } else {
        return 4;
    }
}

// 清除段落标签
function clearPtag($str)
{
    $str = preg_replace("/<p>/", "", $str);
    $str = preg_replace("/<\/p>/", "", $str);
    return $str;
}

function knowpoint_sort($data, $subject)
{
    $sort = array();
    foreach ($data as $k => $v) {
        if ($subject == $v['subject']) {
            $sortkey = ($v['knowpoint'] == "") ? "未分类" : $v['knowpoint'];
            $sort[$sortkey][] = $v['question_id'];
        }
    }
    return $sort;
}

// 秒数转为00:00:00格式
function seconds_to_str($n)
{
    $h = sprintf('%02d', floor($n / 3600));
    $i = sprintf('%02d', floor($n % 3600 / 60));
    $s = sprintf('%02d', floor($n % 60));
    return "{$h}:{$i}:{$s}";
}

// seo
function seo($title, $description, $keywords, $web_name = false)
{
    $CI = get_instance();
    $SEO['title'] = strip_tags($title);
    $SEO['description'] = strip_tags($description);
    $SEO['keywords'] = strip_tags($keywords);
    $CI->load->vars(array(
        '_SEO' => $SEO,
        '_SITE_CONF' => array(
            'web_name' => '问酷网'
        )
    ));
}

// 解析伪静态参数
function my_parse_query($str)
{
    $pos = strpos($str, '-', 0) ? strpos($str, '-', 0) : strlen($str);
    $query_str = trim(substr($str, 0, $pos), '/');
    $q = trim(strip_tags(trim(urldecode(substr($str, $pos + 2)), '/')));
    $arr = array();
    preg_match_all('/([a-z]+)(\d+)/', $query_str, $r);
    foreach ($r[0] as $k => $v) {
        $arr[$r[1][$k]] = $r[2][$k];
    }
    $arr['q'] = $q;
    return $arr;
}

// 解析伪静态参数
function my_create_query($arr)
{
    if ($arr['q']) {
        $q = urlencode($arr['q']);
        unset($arr['q']);
    }
    $arr2 = array();
    foreach ($arr as $k => $v) {
        if ($k && $v) {
            $arr2[] = $k;
            $arr2[] = $v;
        }
    }
    return implode('', $arr2) . ($q ? "-q{$q}" : '');
}

// 设置伪静态参数
function my_set_query($arr, $key, $value)
{
    $arr2 = array();
    foreach ($arr as $k => $v) {
        $arr2[$k] = $v;
    }
    if (! is_array($key)) {
        $arr2[$key] = $value;
    } else {
        foreach ($key as $k => $v) {
            $arr2[$k] = $v;
        }
    }
    return $arr2;
}

// 面包屑导航
function breadcrumb($arr)
{
    $tmp = array();
    foreach ($arr as $k => $v) {
        if ($v['url']) {
            $tmp[] = "<a href='{$v['url']}'>{$v['text']}</a>";
        } else {
            $tmp[] = "<h2>{$v['text']}</h2>";
        }
    }
    return implode('＞', $tmp);
}

function api_input($param)
{
    $input = array();
    foreach ($param as $k => $v) {
        if (isset($_REQUEST[$v['name']])) {
            $input[$v['name']] = $_REQUEST[$v['name']];
        } else {
            if ($v['necessary'] == 1) {
                api_output(false, $v['error_no'], $v['error_msg']);
            } else 
                if (isset($v['default'])) {
                    $input[$v['name']] = $v['default'];
                }
        }
    }
    return $input;
}

function api_output($result, $error_no = 0, $error_msg = false)
{
    $output = array();
    $output['error_no'] = intval($error_no);
    if ($error_no == 0) {
        $output['result'] = $result;
    } else {
        $output['error_msg'] = $error_msg;
    }
    exit(json_encode($output));
}

function my_implode($glue, $pieces)
{
    foreach ($pieces as $k => $v) {
        if (! $v) {
            unset($pieces[$k]);
        }
    }
    return implode($glue, $pieces);
}

/**
 * 加密:ENCODE 解密:DECODE
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4; // 随机密钥长度 取值 0-32;
                      // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
                      // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
                      // 当此值为 0 时，则不产生随机密钥
    
    $key = md5($key ? $key : 'iasku*com*999');
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), - $ckey_length)) : '';
    
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    
    $result = '';
    $box = range(0, 255);
    
    $rndkey = array();
    for ($i = 0; $i <= 255; $i ++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    
    for ($j = $i = 0; $i < 256; $i ++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    
    for ($a = $j = $i = 0; $i < $string_length; $i ++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    
    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

// 格式化打印
function dump($vars, $label = '', $return = false)
{
    ini_set('html_errors', 'On');
    if (ini_get('html_errors')) {
        $content = "<pre>\n";
        if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
        }
        $content .= htmlspecialchars(print_r($vars, true));
        $content .= "\n</pre>\n";
    } else {
        $content = $label . " :\n" . print_r($vars, true);
    }
    if ($return) {
        return $content;
    }
    echo $content;
    return null;
}

// 中文分词
function segment($str, $option = 0)
{
    $so = scws_new();
    $so->set_charset('utf-8');
    $so->set_dict(FCPATH . 'data/scws/dict.utf8.xdb');
    $so->send_text(trim($str));
    $keys = array();
    $tmp = $so->get_tops();
    foreach ($tmp as $k2 => $v2) {
        if (strlen(trim($v2['word'])) > 0) {
            $keys[] = $v2['word'];
        }
    }
    $matches = array();
    while ($tmp2 = $so->get_result()) {
        foreach ($tmp2 as $k2 => $v2) {
            if (strlen(trim($v2['word'])) > 0) {
                $matches[] = $v2['word'];
            }
        }
    }
    $so->close();
    switch (intval($option)) {
        case 1:
            return $keys;
        case 2:
            return $matches;
        default:
            return count($keys) > 0 ? $keys : $matches;
    }
}

// 数字 字符串 互相转换
class ARY
{

    private static $_64 = 'mKtu7GHWXef5lyBzcNUF4Dij8A9opLMOvTVYswx36hkJPnqISZad1CE0bgrQR2-_';

    private static $_64_hash = array(
        'm' => 0,
        'K' => 1,
        't' => 2,
        'u' => 3,
        '7' => 4,
        'G' => 5,
        'H' => 6,
        'W' => 7,
        'X' => 8,
        'e' => 9,
        'f' => 10,
        '5' => 11,
        'l' => 12,
        'y' => 13,
        'B' => 14,
        'z' => 15,
        'c' => 16,
        'N' => 17,
        'U' => 18,
        'F' => 19,
        '4' => 20,
        'D' => 21,
        'i' => 22,
        'j' => 23,
        '8' => 24,
        'A' => 25,
        '9' => 26,
        'o' => 27,
        'p' => 28,
        'L' => 29,
        'M' => 30,
        'O' => 31,
        'v' => 32,
        'T' => 33,
        'V' => 34,
        'Y' => 35,
        's' => 36,
        'w' => 37,
        'x' => 38,
        '3' => 39,
        '6' => 40,
        'h' => 41,
        'k' => 42,
        'J' => 43,
        'P' => 44,
        'n' => 45,
        'q' => 46,
        'I' => 47,
        'S' => 48,
        'Z' => 49,
        'a' => 50,
        'd' => 51,
        '1' => 52,
        'C' => 53,
        'E' => 54,
        '0' => 55,
        'b' => 56,
        'g' => 57,
        'r' => 58,
        'Q' => 59,
        'R' => 60,
        '2' => 61,
        '-' => 62,
        '_' => 63
    );

    public static function _10_to_64($num)
    {
        $_64 = &self::$_64;
        $num = (int) $num;
        if ($num < 0)
            return false;
        $string = '';
        while ($num !== 0) {
            // $i = $num - ((int) ($num >> 6) << 6);
            $i = $num & 0x3f;
            $string = $_64[$i] . $string;
            $num = (int) ($num >> 6);
        }
        return $string;
    }

    public static function _64_to_10($string)
    {
        $_64_hash = &self::$_64_hash;
        $len = strlen($string);
        $multiple = 0;
        $num = 0;
        for ($i = 0; $i < $len; $i ++) {
            if (! isset($_64_hash[$string[$len - $i - 1]]))
                return false; // 非法字符
            $num += $_64_hash[$string[$len - $i - 1]] << $multiple;
            $multiple += 6;
        }
        return $num;
    }
}

/**
 * 计算某个经纬度的周围某段距离的正方形的四个点
 *
 * @param
 *            lng float 经度
 * @param
 *            lat float 纬度
 * @param
 *            distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
 * @return array 正方形的四个点的经纬度坐标
 */
function return_square_point($lng, $lat, $distance = 0.5)
{
    $earth_radius = '6371';
    
    $dlng = 2 * asin(sin($distance / (2 * $earth_radius)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);
    
    $dlat = $distance / $earth_radius;
    $dlat = rad2deg($dlat);
    
    return array(
        'left-top' => array(
            'lat' => $lat + $dlat,
            'lng' => $lng - $dlng
        ),
        'right-top' => array(
            'lat' => $lat + $dlat,
            'lng' => $lng + $dlng
        ),
        'left-bottom' => array(
            'lat' => $lat - $dlat,
            'lng' => $lng - $dlng
        ),
        'right-bottom' => array(
            'lat' => $lat - $dlat,
            'lng' => $lng + $dlng
        )
    );
}

/**
 * 求两个已知经纬度之间的距离,单位为米
 *
 * @param
 *            lng1,lng2 经度
 * @param
 *            lat1,lat2 纬度
 * @return float 距离，单位米
 *        
 */
function getdistance($lng1, $lat1, $lng2, $lat2)
{
    // 将角度转为狐度
    $radLat1 = deg2rad($lat1); // deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
    return $s;
}

// 格式化mysql查询出来的数组为键值对的形式
function trans_arr($arr)
{
    $data = array();
    
    if (! empty($arr)) {
        $n = count($arr[0]);
        foreach ($arr as $k => $v) {
            if ($n < 2) {
                return false;
            } else {
                $_tmp = array();
                $v = array_values($v);
                
                if ($n == 2) {
                    $data[$v[0]] = $v[1];
                } else {
                    for ($i = 0; $i < $n - 1; $i ++) {
                        $_tmp[] = $v[$i + 1];
                    }
                    $data[$v[0]] = $_tmp;
                }
            }
        }
    }
    // dump($data);exit;
    return $data;
}

/**
 * 判断是否为基础数据类型
 * 如果存在返回true，如果不存在返回false
 *
 * @param unknown $data            
 */
function is_base_type($data)
{
    $base_type = array(
        'String',
        'Int',
        'FormInputFile',
        'Array'
    );
    
    if (in_array($data, $base_type, TRUE))
        return TRUE;
    return FALSE;
}

function get_time()
{
    $mtime = explode(' ', microtime());
    $startTime = $mtime[1] + $mtime[0];
    return $startTime;
}
