<?php

if (!function_exists("array_column")) {

    function array_column($array, $column_name) {

        return array_map(function($element) use($column_name) {
            return $element[$column_name];
        }, $array);
    }

}

function curl_upload($post_url, $post_data) {
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

function curl_upload_https($url, $data) { // 模拟提交数据函数
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        return 'error.' . curl_error($curl); // 捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

function curl_get($url, $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url . '?' . http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // php version >= 5.5 需要 传filesize

    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
    $result = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    if ($error) {
        do_log('CURL_GET_ERROR:', $error);
        return false;
    } else {
        return $result;
    }
}

//非utf8编码转换成utf8
function charsetToUTF8($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $k => $v) {
            if (is_array($v)) {
                $mixed[$k] = charsetToUTF8($v);
            } else {
                $encode = mb_detect_encoding($v, array(
                    'ASCII',
                    'UTF-8',
                    'GB2312',
                    'GBK',
                    'BIG5'
                ));
                if ($encode == 'EUC-CN') {
                    $mixed[$k] = iconv('GBK', 'UTF-8', $v);
                }
            }
        }
    } else {
        $encode = mb_detect_encoding($mixed, array(
            'ASCII',
            'UTF-8',
            'GB2312',
            'GBK',
            'BIG5'
        ));
        if ($encode == 'EUC-CN') {
            $mixed = iconv('GBK', 'UTF-8', $mixed);
        }
    }
    return $mixed;
}

// 生成不重复的随机数
function random($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
    $hash = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i ++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}

// 将秒数转换成天或者月
function convertTimes($times) {
    $day = intval($times / (3600 * 24));
    if ($day > 30) {
        $month = intval($day / 30);
        return $month . '个月';
    }
    return $day . '天';
}

/**
 * 压缩文件
 *
 * @param unknown $filename            
 * @param unknown $zipname            
 * @param unknown $file_dir            
 */
function tozip($filename, $zipname, $file_dir) {
    $zip = new ZipArchive();
    $zipname = $file_dir . $zipname;
    // echo $filename,'<br>';
    if (!file_exists($zipname)) {
        $zip->open($zipname, ZipArchive::OVERWRITE); // 创建一个空的zip文件
        $zip->addFile($file_dir . $filename, $filename); // 添加到压缩文件
        // @unlink($file_dir . $filename); // 删除源文件
    } else {
        $zip->open($zipname);
        $zip->addFile($file_dir . $filename, $filename);
        // @unlink($file_dir . $filename); // 删除源文件
    }
    // dump($zip);

    $zip->close();
}

// 删除文件
function delFile($arr_filename, $file_dir) {
    foreach ($arr_filename as $val) {
        @unlink($file_dir . $val); // 删除源文件
    }
}

// 十进制转换32进制
function transTo32($num) {
    $result = "";
    while ($num != 0) {
        $temp = $num % 32;
        if ($temp >= 10) {
            $result .= chr(($temp + 55));
        } else {
            $result .= $temp;
        }
        $num = intval($num / 32);
    }
    return strrev($result); // 反转
}

// 判断手机号
function is_mobile($str) {
    return true;
    //$pattern = "/^(1)\d{10}$/";
    $pattern = '/^13[0-9]{9}$|14[0-9]{9}$|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$/';
    return preg_match($pattern, $str) ? true : false;
}

// 二维数组去掉重复值
function array_unique_fb($array2D) {
    foreach ($array2D as $v) {
        $v = implode(',', $v); // 降维,也可以用implode,将一维数组转换为用逗号连接的字符串
        $temp[] = $v;
    }
    $temp = array_unique($temp); // 去掉重复的字符串,也就是重复的一维数组
    foreach ($temp as $k => $v) {
        $temp[$k] = explode(',', $v); // 再将拆开的数组重新组装
    }
    return $temp;
}

// 判断是否为移动设备
function is_mobile_terminal() {
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
function get_ip() {
    $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
    return ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
}

// 判断是否为ajax请求
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

// 日志
function do_log() {
    $argvs = func_get_args();
    $str = array();
    $str[] = 'date:' . date('Y-m-d H:i:s');
    foreach ($argvs as $v) {
        $str[] = print_r($v, true);
    }
    $str[] = "\n\n";
    return file_put_contents(APPPATH . 'logs/' . date('Y-m-d') . '.log', implode("\n", $str), FILE_APPEND);
}

function do_script_log() {
    $argvs = func_get_args();
    $str = array();
    $str[] = 'date:' . date('Y-m-d H:i:s');
    foreach ($argvs as $v) {
        $str[] = print_r($v, true);
    }
    $str[] = "\n\n";
    return file_put_contents(APPPATH . 'logs/script_' . date('Y-m-d') . '.log', implode("\n", $str), FILE_APPEND);
}

function do_db_log() {
    $argvs = func_get_args();
    $str = array();
    foreach ($argvs as $v) {
        $str[] = print_r($v, true);
    }
    $CI = get_instance();
    $CI->db->insert('api2_txtlog',array('msg'=>implode("\n", $str),'ymd'=>date('Ymd')));
}

// 设置cookie_id
function cookie_id() {
    if (!isset($_COOKIE['C_id'])) {
        _session_start();
        $cookie_id = md5(session_id());
        $expire = time() + 3600 * 24 * 365;
        setcookie('C_id', $cookie_id, $expire, '/');
    } else {
        $cookie_id = $_COOKIE['C_id'];
    }
    return $cookie_id;
}

function letter_rand($num) {
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
function input_csv($handle) {
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

function export_csv($filename, $data) {
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
function Pinyin($_String, $_Code = 'gb2312') {
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
            $_Q = ord(substr($_String, ++$i, 1));
            $_P = $_P * 256 + $_Q - 65536;
        }
        $_Res .= _Pinyin($_P, $_Data);
    }
    return preg_replace("/[^a-zA-Z0-9]*/", '', $_Res);
}

function _Pinyin($_Num, $_Data) {
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

function _U2_Utf8_Gb($_C) {
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

function _Array_Combine($_Arr1, $_Arr2) {
    for ($i = 0; $i < count($_Arr1); $i ++)
        $_Res[$_Arr1[$i]] = $_Arr2[$i];
    return $_Res;
}

function price($float) {
    return sprintf('%.2f', $float);
}

function price_to_cents($float) { // 单位元转化为分
    return intval(price($float * 100));
}

// NODE TO ARRAY
function node_to_array($node) {
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

function postHandler($arr) { // post数据处理
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

// 秒数转为00:00:00格式
function seconds_to_str($n) {
    $h = sprintf('%02d', floor($n / 3600));
    $i = sprintf('%02d', floor($n % 3600 / 60));
    $s = sprintf('%02d', floor($n % 60));
    return "{$h}:{$i}:{$s}";
}

// seo
function seo($title, $description, $keywords, $web_name = false) {
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
function my_parse_query($str) {
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
function my_create_query($arr) {
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
function my_set_query($arr, $key, $value) {
    $arr2 = array();
    foreach ($arr as $k => $v) {
        $arr2[$k] = $v;
    }
    if (!is_array($key)) {
        $arr2[$key] = $value;
    } else {
        foreach ($key as $k => $v) {
            $arr2[$k] = $v;
        }
    }
    return $arr2;
}

// 面包屑导航
function breadcrumb($arr) {
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

function api_input($param) {
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

function api_output($result, $error_no = 0, $error_msg = false) {
    $output = array();
    $output['error_no'] = intval($error_no);
    if ($error_no == 0) {
        $output['result'] = $result;
    } else {
        $output['error_msg'] = $error_msg;
    }
    exit(json_encode($output));
}

/**
 * 加密:ENCODE 解密:DECODE
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4; // 随机密钥长度 取值 0-32;
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
    // 当此值为 0 时，则不产生随机密钥

    $key = md5($key ? $key : 'asdfas*sdfas*999');
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

// 中文分词
function segment($str, $option = 0) {
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
class ARY {

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

    public static function _10_to_64($num) {
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

    public static function _64_to_10($string) {
        $_64_hash = &self::$_64_hash;
        $len = strlen($string);
        $multiple = 0;
        $num = 0;
        for ($i = 0; $i < $len; $i ++) {
            if (!isset($_64_hash[$string[$len - $i - 1]]))
                return false; // 非法字符
            $num += $_64_hash[$string[$len - $i - 1]] << $multiple;
            $multiple += 6;
        }
        return $num;
    }

}

class ARY2 {

    private static $_38 = 'mtu7_ef5lyzc4ij89opvswx36hknqad10bgr2-';
    private static $_38_hash = array(
        'm' => 0,
        't' => 1,
        'u' => 2,
        '7' => 3,
        '_' => 4,
        'e' => 5,
        'f' => 6,
        '5' => 7,
        'l' => 8,
        'y' => 9,
        'z' => 10,
        'c' => 11,
        '4' => 12,
        'i' => 13,
        'j' => 14,
        '8' => 15,
        '9' => 16,
        'o' => 17,
        'p' => 18,
        'v' => 19,
        's' => 20,
        'w' => 21,
        'x' => 22,
        '3' => 23,
        '6' => 24,
        'h' => 25,
        'k' => 26,
        'n' => 27,
        'q' => 28,
        'a' => 29,
        'd' => 30,
        '1' => 31,
        '0' => 32,
        'b' => 33,
        'g' => 34,
        'r' => 35,
        '2' => 36,
        '-' => 37,
    );

    public static function _10_to_38($num) {
        $_38 = &self::$_38;
        $num = (int) $num;
        if ($num < 0)
            return false;
        $string = '';
        while ($num !== 0) {
            // $i = $num - ((int) ($num >> 6) << 6);
            $i = $num % 38;
            $string = $_38[$i] . $string;
            $num = (int) ($num / 38);
        }
        return $string;
    }

    public static function _38_to_10($string) {
        $_38_hash = &self::$_38_hash;
        $len = strlen($string);
        $multiple = 1;
        $num = 0;
        for ($i = 0; $i < $len; $i ++) {
            if (!isset($_38_hash[$string[$len - $i - 1]]))
                return false; // 非法字符
            $num += $_38_hash[$string[$len - $i - 1]] * $multiple;
            $multiple *= 38;
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
function return_square_point($lng, $lat, $distance = 0.5) {
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
function getdistance($lng1, $lat1, $lng2, $lat2) {
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

/**
 * fixHtmlTag
 *
 * HTML标签修复函数，此函数可以修复未正确闭合的 HTML 标签
 *
 * 由于不确定性因素太多，暂时提供两种模式“嵌套闭合模式”和
 * “就近闭合模式”，应该够用了。
 *
 * 这两种模式是我为了解释清楚此函数的实现而创造的两个名词，
 * 只需明白什么意思就行。
 * 1，嵌套闭合模式，NEST，为默认的闭合方式。即 "<body><div>你好"
 * 这样的 html 代码会被修改为 "<body><div>你好</div></body>"
 * 2，就近闭合模式，CLOSE，这种模式会将形如 " 你好 为什么没有
 * 闭合呢" 的代码修改为 " 你好 为什么没有闭合呢 "
 *
 * 在嵌套闭合模式（默认，无需特殊传参）下，可以传入需要就近闭合的
 * 标签名，通过这种方式将类似 "<body> 你好 我也好" 转换为
 * "<body> 你好 我也好 </body>"的形式。
 * 传参时索引需要按照如下方式写，不需要修改的设置可以省略
 *
 * $param = array(
 * 'html' => '', //必填
 * 'options' => array(
 * 'tagArray' => array();
 * 'type' => 'NEST',
 * 'length' => null,
 * 'lowerTag' => TRUE,
 * 'XHtmlFix' => TRUE,
 * )
 * );
 * fixHtmlTag($param);
 *
 * 上面索引对应的值含义如下
 * string $html 需要修改的 html 代码
 * array $tagArray 当为嵌套模式时，需要就近闭合的标签数组
 * string $type 模式名，目前支持 NEST 和 CLOSE 两种模式，如果设置为 CLOSE，将会忽略参数 $tagArray 的设置，而全部就近闭合所有标签
 * ini $length 如果希望截断一定长度，可以在此赋值，此长度指的是字符串长度
 * bool $lowerTag 是否将代码中的标签全部转换为小写，默认为 TRUE
 * bool $XHtmlFix 是否处理不符合 XHTML 规范的标签，即将 <br> 转换为 <br />
 *
 * @author IT不倒翁 <itbudaoweng@gmail.com>
 * @version 0.2
 * @link http://yungbo.com IT不倒翁
 * @link http://enenba.com/?post=19 某某
 * @param array $param
 *            数组参数，需要赋予特定的索引
 * @return string $result 经过处理后的 html 代码
 * @since 2012-04-14
 */
function fixHtmlTag($param = array()) {
    // 参数的默认值
    $html = '';
    $tagArray = array();
    $type = 'NEST';
    $length = null;
    $lowerTag = TRUE;
    $XHtmlFix = TRUE;

    // 首先获取一维数组，即 $html 和 $options （如果提供了参数）
    extract($param);

    // 如果存在 options，提取相关变量
    if (isset($options)) {
        extract($options);
    }

    $result = ''; // 最终要返回的 html 代码
    $tagStack = array(); // 标签栈，用 array_push() 和 array_pop() 模拟实现
    $contents = array(); // 用来存放 html 标签
    $len = 0; // 字符串的初始长度
    // 设置闭合标记 $isClosed，默认为 TRUE, 如果需要就近闭合，成功匹配开始标签后其值为 false,成功闭合后为 true
    $isClosed = true;

    // 将要处理的标签全部转为小写
    $tagArray = array_map('strtolower', $tagArray);

    // “合法”的单闭合标签
    $singleTagArray = array(
        '<meta',
        '<link',
        '<base',
        '<br',
        '<hr',
        '<input',
        '<img'
    );

    // 校验匹配模式 $type，默认为 NEST 模式
    $type = strtoupper($type);
    if (!in_array($type, array(
                'NEST',
                'CLOSE'
            ))) {
        $type = 'NEST';
    }

    // 以一对 < 和 > 为分隔符，将原 html 标签和标签内的字符串放到数组中
    $contents = preg_split("/(<[^>]+?>)/si", $html, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    foreach ($contents as $tag) {
        if ('' == trim($tag)) {
            $result .= $tag;
            continue;
        }

        // 匹配标准的单闭合标签，如<br />
        if (preg_match("/<(\w+)[^\/>]*?\/>/si", $tag)) {
            $result .= $tag;
            continue;
        }

        // 匹配开始标签，如果是单标签则出栈
        else
        if (preg_match("/<(\w+)[^\/>]*?>/si", $tag, $match)) {
            // 如果上一个标签没有闭合，并且上一个标签属于就近闭合类型
            // 则闭合之，上一个标签出栈
            // 如果标签未闭合
            if (false === $isClosed) {
                // 就近闭合模式，直接就近闭合所有的标签
                if ('CLOSE' == $type) {
                    $result .= '</' . end($tagStack) . '>';
                    array_pop($tagStack);
                }  // 默认的嵌套模式，就近闭合参数提供的标签
                else {
                    if (in_array(end($tagStack), $tagArray)) {
                        $result .= '</' . end($tagStack) . '>';
                        array_pop($tagStack);
                    }
                }
            }

            // 如果参数 $lowerTag 为 TRUE 则将标签名转为小写
            $matchLower = $lowerTag == TRUE ? strtolower($match[1]) : $match[1];

            $tag = str_replace('<' . $match[1], '<' . $matchLower, $tag);
            // 开始新的标签组合
            $result .= $tag;
            array_push($tagStack, $matchLower);

            // 如果属于约定的的单标签，则闭合之并出栈
            foreach ($singleTagArray as $singleTag) {
                if (stripos($tag, $singleTag) !== false) {
                    if ($XHtmlFix == TRUE) {
                        $tag = str_replace('>', ' />', $tag);
                    }
                    array_pop($tagStack);
                }
            }

            // 就近闭合模式，状态变为未闭合
            if ('CLOSE' == $type) {
                $isClosed = false;
            }  // 默认的嵌套模式，如果标签位于提供的 $tagArray 里，状态改为未闭合
            else {
                if (in_array($matchLower, $tagArray)) {
                    $isClosed = false;
                }
            }
            unset($matchLower);
        }

        // 匹配闭合标签，如果合适则出栈
        else
        if (preg_match("/<\/(\w+)[^\/>]*?>/si", $tag, $match)) {

            // 如果参数 $lowerTag 为 TRUE 则将标签名转为小写
            $matchLower = $lowerTag == TRUE ? strtolower($match[1]) : $match[1];

            if (end($tagStack) == $matchLower) {
                $isClosed = true; // 匹配完成，标签闭合
                $tag = str_replace('</' . $match[1], '</' . $matchLower, $tag);
                $result .= $tag;
                array_pop($tagStack);
            }
            unset($matchLower);
        }

        // 匹配注释，直接连接 $result
        else
        if (preg_match("/<!--.*?-->/si", $tag)) {
            $result .= $tag;
        }

        // 将字符串放入 $result ，顺便做下截断操作
        else {
            if (is_null($length) || $len + mb_strlen($tag) < $length) {
                $result .= $tag;
                $len += mb_strlen($tag);
            } else {
                $str = mb_substr($tag, 0, $length - $len + 1);
                $result .= $str;
                break;
            }
        }
    }

    // 如果还有将栈内的未闭合的标签连接到 $result
    while (!empty($tagStack)) {
        $result .= '</' . array_pop($tagStack) . '>';
    }
    return $result;
}

//打包整个文件夹，需要ZipArchive 支持
class HZip {

    /**
     * Add files and sub-directories in a folder to zip file. 
     * @param string $folder 
     * @param ZipArchive $zipFile 
     * @param int $exclusiveLength Number of text to be exclusived from the file path. 
     */
    public static function folderToZip($folder, &$zipFile, $exclusiveLength) {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip. 
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory. 
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Zip a folder (include itself). 
     * Usage: 
     *   HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip'); 
     * 
     * @param string $sourcePath Path of directory to be zip. 
     * @param string $outZipPath Path of output zip file. 
     */
    public static function zipDir($sourcePath, $outZipPath) {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $z = new ZipArchive();
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }

}

//数组转字典
function array_to_map($array, $primary_key) {
    $r = array();
    foreach ($array as $k => $v) {
        $r[$v[$primary_key]] = $v;
    }
    return $r;
}

//
function array_to_tree($array, $primary_key) {
    $r = array();
    foreach ($array as $k => $v) {
        $r[$v[$primary_key]][] = $v;
    }
    return $r;
}

//判断多级树中某个值是否存在
class Array_Map {

    private $_map = array();

    public function __construct($array) {
        $this->_map = $array;
    }

    public function find() {
        $map = $this->_map;
        $func_argv = func_get_args();
        foreach ($func_argv as $k => $v) {
            $key = $v;
            if (is_array($map) && isset($map[$key])) {
                $map = $map[$key];
                unset($func_argv[$k]);
            } else {
                return false;
            }
        }
        return $map;
    }

}

class ZIP_AS_DIR {

    private $_zip;
    private $_dir = array();

    public function __construct($zipname) {
        $this->_zip = new ZipArchive();
        $this->_zip->open($zipname);
        $this->to_dir();
    }

    private function to_dir() {
        for ($i = 0; $i < $this->_zip->numFiles; $i++) {
            $file = $this->_zip->getNameIndex($i);
            $pathinfo = pathinfo($file);
            $pathinfo['index'] = $i;
            $pathinfo['path'] = $file;
            $pathinfo['is_dir'] = $file[strlen($file) - 1] == '/' ? true : false; //判断是否是文件夹
            $pathinfo['level'] = count(explode('/', rtrim($file, '/'))) - 1; //层级，根据“/”计算
            $this->_dir[] = $pathinfo;
        }
    }

    //$condition = array('dirname'=>'','basename'=>'','filename'=>'','path'=>'','is_dir'=>'','level'=>'','extension'=>'','index'=>'',)
    public function find($condition) {
        $r = array();
        foreach ($this->_dir as $k => $v) {
            $match = true;
            foreach ($condition as $k2 => $v2) {
                if ($v[$k2] != $v2) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $r[] = $v;
            } else {
                continue;
            }
        }
        return $r;
    }

    public function read($path_or_index) {
        if (is_int($path_or_index)) {
            return $this->_zip->getFromIndex($path_or_index);
        } else {
            return $this->_zip->getFromName($path_or_index);
        }
    }

    public function close() {
        $this->_zip->close();
    }

}

/**
 * 将返回的数据集转换成树
 * @param  array   $list  数据集
 * @param  string  $pk    主键
 * @param  string  $pid   父节点名称
 * @param  string  $child 子节点名称
 * @param  integer $root  根节点ID
 * @return array          转换后的树
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
    $tree = array(); // 创建Tree
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = & $list[$key];
        }

        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] = & $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = & $refer[$parentId];
                    $parent[$child][] = & $list[$key];
                }
            }
        }
    }
    return $tree;
}

/*
 * 判断中文姓名
 */
function is_chinese_name($name) {
    if (preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/', $name)) {
        return true;
    } else {
        return false;
    }
}

/*
 * AES加密
 * $aes = new aes();
 * $aes->setKey('key');
 * 加密
 * $string = $aes->encode('string');
 * 解密
 * $aes->decode($string);
 */
class aes {
 
    // CRYPTO_CIPHER_BLOCK_SIZE 32
     
    private $_secret_key = 'default_secret_key';
     
    public function setKey($key) {
        $this->_secret_key = $key;
    }
     
    public function encode($data) {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CBC,'');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
        mcrypt_generic_init($td,$this->_secret_key,$iv);
        $encrypted = mcrypt_generic($td,$data);
        mcrypt_generic_deinit($td);
         
        return $iv . $encrypted;
    }
     
    public function decode($data) {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256,'',MCRYPT_MODE_CBC,'');
        $iv = mb_substr($data,0,32,'latin1');
        mcrypt_generic_init($td,$this->_secret_key,$iv);
        $data = mb_substr($data,32,mb_strlen($data,'latin1'),'latin1');
        $data = mdecrypt_generic($td,$data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
         
        return trim($data);
    }
}

//增大并发数需要增加位数
function create_order_sn($oid, $prefix = '') {
    $order_sn_left = date('ymd') . (time() % 86400);
    if ($oid > 1000) {
        //方法1,每秒不超过100w的订单数,
        $order_sn_right = str_pad($oid % 1000000, 6, '0', STR_PAD_LEFT);
    } else {
        //方法2,每毫秒不超过1000个订单，修饰订单号
        $str = str_pad(microtime(1) * 1000 % 1000, 3, '0', STR_PAD_LEFT);
        $order_sn_right = $str . str_pad($oid % 1000, 3, '0', STR_PAD_LEFT);
    }
    return $prefix . $order_sn_left . $order_sn_right;
}

//秒数转时间
function secs_to_str($secs) {
    if ($secs >= 86400) {
        $days = floor($secs / 86400);
        $secs = $secs % 86400;
        $r = $days . '天';
    }
    if ($secs >= 3600) {
        $hours = floor($secs / 3600);
        $secs = $secs % 3600;
        $r.=$hours . '小时';
    }
    if ($secs >= 60) {
        $minutes = floor($secs / 60);
        $secs = $secs % 60;
        $r.=$minutes . '分';
    }
    $r.=$secs . ' second';
    if ($secs <> 1) {
        $r.='秒';
    }
    return $r;
}

function isIdCard($number) {
    $number = strtoupper($number);
    //加权因子 
    $wi = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
    //校验码串 
    $ai = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
    //按顺序循环处理前17位 
    for ($i = 0; $i < 17; $i++) {
        //提取前17位的其中一位，并将变量类型转为实数 
        $b = (int) $number{$i};

        //提取相应的加权因子 
        $w = $wi[$i];

        //把从身份证号码中提取的一位数字和加权因子相乘，并累加 
        $sigma += $b * $w;
    }
    //计算序号 
    $snumber = $sigma % 11;

    //按照序号从校验码串中提取相应的字符。 
    $check_number = $ai[$snumber];

    if ($number{17} == $check_number) {
        return true;
    } else {
        return false;
    }
}

 function SetCookies($data = array()){
        $_session_id = !isset($data['session_id'])?md5("dwcms_userid"):md5($data['session_id']);
         $_time = !isset($data['time'])?60*60:$data['time']; 
        if (isset($data['cookie_status'])!=false &&$data['cookie_status'] == 1){ 
            setcookie($_session_id,authcode2($data['user_id'].",".time(),"ENCODE"),time()+$_time);

         }else{ 
            $_SESSION[$_session_id] = authcode2($data['user_id'].",".time(),"ENCODE"); 
            $_SESSION['login_endtime'] = time()+60*60; 
        } 
    }

    function DelCookies($data = array()){ 
        $_session_id = !isset($data['session_id'])?md5("dwcms_userid"):md5($data['session_id']);
         if (isset($data['cookie_status'])!=false &&$data['cookie_status'] == 1){ 
            setcookie($_session_id,"",time()); 
        }else{ 
            $_SESSION[$_session_id] = ""; 
            $_SESSION['login_endtime'] = ""; 
        } 
    } 
    
    function GetCookies($data = array()){ 
	$_session_id = !isset($data['session_id'])?md5("dwcms_userid"):md5($data['session_id']);
	$_time = !isset($data['time'])?60*60:$data['time']; 
	$_user_id = array(0); 
	if (isset($data['cookie_status']) &&$data['cookie_status'] == 1){ 
		$_user_id = explode(",",authcode2(isset($_COOKIE[$_session_id])?$_COOKIE[$_session_id]:"","DECODE"));
	}else{ 
		$_user_id = explode(",",authcode2(isset($_SESSION[$_session_id])?$_SESSION[$_session_id]:"","DECODE"));
	} 
        //var_dump($_user_id);
        //var_dump(authcode(isset($_SESSION[$_session_id])?$_SESSION[$_session_id]:"","DECODE"));
	return $_user_id[0]; 
    }

    function authcode2($string,$operation = 'DECODE',$key = '',$expiry = 0) { 
        $ckey_length = 4; 
        $key = md5($key ?$key : "dw10c20m05w18"); 
        $keya = md5(substr($key,0,16)); 
        $keyb = md5(substr($key,16,16)); 
        $keyc = $ckey_length ?($operation == 'DECODE'?substr($string,0,$ckey_length): substr(md5(microtime()),-$ckey_length)) : '';
         $cryptkey = $keya.md5($keya.$keyc); 
        $key_length = strlen($cryptkey); 
        $string = $operation == 'DECODE'?base64_decode(substr($string,$ckey_length)) : sprintf('%010d',$expiry ?$expiry +time() : 0).substr(md5($string.$keyb),0,16).$string;
         $string_length = strlen($string); 
        $result = ''; 
        $box = range(0,255); 
        $rndkey = array(); 
        for($i = 0;$i <= 255;$i++) { 
        $rndkey[$i] = ord($cryptkey[$i %$key_length]); 
        } 
        for($j = $i = 0;$i <256;$i++) { 
        $j = ($j +$box[$i] +$rndkey[$i]) %256; 
        $tmp = $box[$i]; 
        $box[$i] = $box[$j]; 
        $box[$j] = $tmp; 
        } 
        for($a = $j = $i = 0;$i <$string_length;$i++) { 
        $a = ($a +1) %256; 
        $j = ($j +$box[$a]) %256; 
        $tmp = $box[$a]; 
        $box[$a] = $box[$j]; 
        $box[$j] = $tmp; 
        $result .= chr(ord($string[$i]) ^($box[($box[$a] +$box[$j]) %256])); 
        } 
        if($operation == 'DECODE') { 
        if((substr($result,0,10) == 0 ||substr($result,0,10) -time() >0) &&substr($result,10,16) == substr(md5(substr($result,26).$keyb),0,16)) {
         return substr($result,26); 
        }else { 
        return ''; 
        } 
        }else { 
        return $keyc.str_replace('=','',base64_encode($result)); 
        } 
    }
    
    function ip_address() { 
        if(!empty($_SERVER["HTTP_CLIENT_IP"])) { 
        $ip_address = $_SERVER["HTTP_CLIENT_IP"]; 
        }else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){ 
        $ip_address = array_pop(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])); 
        }else if(!empty($_SERVER["REMOTE_ADDR"])){ 
        $ip_address = $_SERVER["REMOTE_ADDR"]; 
        }else{ 
        $ip_address = ''; 
        } 
        return $ip_address; 
        } 
        function IsExiest($val){ 
        if (isset($val) &&($val!=""||$val==0)){ 
        return $val; 
        }else{ 
        return false; 
        } 
    }
