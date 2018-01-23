<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 公共方法类
 * Created by PhpStorm.
 * User: TONY
 * Date: 14-1-1
 * Time: 下午1:08
 */

class Common_class {

    function __construct() {
        $CI =& get_instance();
        $this->config = $CI->config;
        $this->load = $CI->load;
    }

    /**
     * 分页生成
     * @param string $base_url          BASE_URL
     * @param int $total_rows           数据总条数
     * @param int $per_page             每页显示条数
     * @param int $uri_segment          获取参数的段
     * @return array                    配置数组
     */
    public function getPageConfigInfo($base_url = NULL, $total_rows = 0, $per_page = 0, $uri_segment = 0) {
        $config = array();

        $config['use_page_numbers'] = TRUE;

        $config['enable_query_strings'] = TRUE;

        $config['page_query_string'] = TRUE;

        $config['base_url'] = site_url() . $base_url;
        $config['total_rows'] = $total_rows;

        $config['per_page'] = $per_page;
        $config['uri_segment'] = $uri_segment;

        $config['full_tag_open'] = "<ul class=\"pagination\">";
        $config['full_tag_close'] = "</ul>";

        $config['first_link'] = '首页';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li> ';

        $config['last_link'] = '尾页';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = '下一页';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li> ';

        $config['prev_link'] = '上一页';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active"><a>';
        $config['cur_tag_close'] = ' <span class="sr-only">(current)</span></a></li>';
        return $config;
    }

    /**
     * 格式化时间，进行友好显示
     * @param $ptime
     * @return string
     */
    function getFormatTime( $ptime ) {
        $ptime = strtotime($ptime);
        $etime = time() - $ptime;
        if ($etime < 1) return '刚刚';
        $interval = array (
            12 * 30 * 24 * 60 * 60 => '年前 ('.date('Y-m-d', $ptime).')',
            30 * 24 * 60 * 60 => '个月前 ('.date('m-d', $ptime).')',
            7 * 24 * 60 * 60 => '周前 ('.date('m-d', $ptime).')',
            24 * 60 * 60 => '天前',
            60 * 60 => '小时前',
            60 => '分钟前',
            1 => '秒前'
        );
        foreach ($interval as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . $str;
            }
        };
    }


    /**
     * 获取字符串长度
     * @param $str
     * @return int
     */
    function strlen_UTF8($str){
        $len = strlen($str);
        $n = 0;
        for($i = 0; $i < $len; $i++) {
            $x = substr($str, $i, 1);
            $a  = base_convert(ord($x), 10, 2);
            $a = substr('00000000'.$a, -8);
            if (substr($a, 0, 1) == 0) {
            }elseif (substr($a, 0, 3) == 110) {
                $i += 1;
            }elseif (substr($a, 0, 4) == 1110) {
                $i += 2;
            }
            $n++;
        }
        return $n;
    } // End strlen_UTF8;

    /**
     * 截取字符串
     * @param $contents
     * @param $length
     * @return string
     */
    function SubContents($contents, $length = 10){
        $lx = $this->strlen_UTF8($contents);
        if ($lx > $length) {
            return mb_substr($contents, 0, $length, 'UTF-8') . "...";
        } else {
            return $contents;
        }
    }

    /**
     * CURL请求
     * @param $_url     目标地址
     * @param $_data    POST数据
     * @return mixed
     */
    public function curl_request($_url, $_data){
        $ch = curl_init();

        //伪造成魅族的UA
        $user_agent = 'User-Agent,Mozilla/5.0 (Linux; Android 5.1; MZ-MX4 Build/LMY47I) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/45.0.2454.94 Mobile Safari/537.36';
        curl_setopt($ch, CURLOPT_URL, $_url);

        //伪造UA及客户端IP地址，防止被服务器端封IP   注：但是 REMOTE_ADDR 无法伪造，服务器仍然可以获取此地址
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:111.85.211.178', 'CLIENT-IP:111.85.211.178'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_data));

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }



    /**
     * 获取userdefine内容
     * @param string $key   userdefine键值
     * @return array        数组
     */
    public function getUserConfInfo($key = NULL){
        $this->config->load('user_define', TRUE);
        if (isset($key)) {
            return $this->config->config['user_define'][$key];
        } else {
            return $this->config->config['user_define'];
        }
    }

    /**
     * 设置发送邮件相应参数
     * @param null $_protocol           邮件发送协议，默认SMTP
     * @param null $_smtp_host      SMTP 服务器地址
     * @param null $_smtp_user      SMTP 用户账号
     * @param null $_smtp_pass      SMTP 密码
     * @param string $_charset          字符集，默认UTF-8
     * @param bool $_wordwrap       开启自动换行。
     * @param string $_mailtype     邮件类型，默认HTML
     * @return array                        配置相关信息
     */
    public function getEmailConfigInfo($_smtp_host = NULL, $_smtp_user = NULL, $_smtp_pass = NULL, $_wordwrap = TRUE, $_mailtype = "html", $_protocol = "smtp", $_charset = "utf-8"){
        $config = array();

        $config['protocol'] = $_protocol;
        $config['smtp_host'] = $_smtp_host;
        $config['smtp_user'] = $_smtp_user;
        $config['smtp_pass'] = $_smtp_pass;
        $config['charset'] = $_charset;
        $config['wordwrap'] = $_wordwrap;
        $config['mailtype'] = $_mailtype;

        return $config;
    }
}

/* End of file common_class.php */
/* Location: ./app/libraries/common_class.php */