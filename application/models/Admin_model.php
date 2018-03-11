<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 管理员模型
 * Created by PhpStorm.
 * User: TONY
 * Date: 13-12-26
 * Time: 下午9:43
 */

class Admin_Model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->model('common_model');
    }

    /**
     * 登录验证
     * @param $_login_name    用户名
     * @param $_login_pwd    用户名
     * @return mixed        返回结果集
     */
    function check_login($_login_name,$_login_pwd){
        $check_sql = "SELECT user_name,user_type,location_id  FROM t_user_info WHERE user_status=1 AND login_name='" . $_login_name . "' AND login_pwd='" . $_login_pwd . "'";
        $result = $this->common_model->getDataList($check_sql, 'default');
        return $result;
    }

    /**
     *验证是否为非法登录
     */
    function auth_check($_key){
//        var_dump(apache_request_headers());
//         = apache_request_headers;
//        var_dump($data);exit;
//        $token = apache_request_headers['authorization'];
        $headers = apache_request_headers();
//        if(!isset($headers['authorization'])){
//                echo 'fail';exit;
//        }
//        var_dump($headers);exit;
        if (!isset($headers['authorization'])) {
            return false;
        }
        return $this->verify_token($headers['authorization'], $_key);
    }

    /**
     * 验证是否为管理员
     */
    function check_is_manager(){
        if(!$_SESSION['is_manager']){
            redirect(site_url() . "/manager/device_list");
        }
    }

    /**
     * 修改密码
     * @param $_login_name      用户名
     * @param $_pwd             密码
     * @return bool             TRUE OR FALSE
     */
    function change_pwd($_login_name, $_pwd){
        $update_sql = "UPDATE t_user_info SET  login_pwd ='" . $_pwd . "' WHERE  login_name='" . $_login_name . "'";
//        echo ($update_sql);
        $result = $this->common_model->execQuery($update_sql,'default');
        return $result;
    }

    /**
     * 车辆信息状态审核
     * @param $_id      用户ID
     * @param $_op_name 操作用户
     * @return mixed
     */
    function user_verify($_id, $_op_name){
        $update_sql = "UPDATE t_vehicle_info SET  op_flag  ='1',op_time='" . date("Y-m-d H:i:s") . "',op_name='" . $_op_name . "' WHERE  id='" . $_id . "'";
        //echo $update_sql;
        $result = $this->common_model->execQuery($update_sql, 'default');
        return $result;
    }

    /**
     * 用户信息获取
     * @param $_page_size       数据显示条数
     * @param $_page_number     当前页码
     * @param $_search_info     模糊查询内容
     * $param $_location_id     所属区域ID
     * @return mixed
     */
    function find_data($_page_size, $_page_number, $_search_info,$_location_id){
        $find_sql = "SELECT * FROM t_vehicle_info WHERE 1=1";

        if ($_location_id != 0) {
            $find_sql .= ' AND location_id=' . $_location_id;
        }

        //模糊搜索
        $search_sql = "";
        if ($_search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(contact ,idcard ,address,contacttel) LIKE '%" . $_search_info . "%'";
        }

        //定义每页显示的页数：
        $_page_number = ($_page_number-1) * $_page_size;
        $limitSql = '';
        $limitFlag = isset($_page_number) && $_page_size != -1;
        if ($limitFlag) {
            $limitSql = " LIMIT " . intval($_page_number) . ", " . intval($_page_size);
        }

        //最终拼接后SQL
        $final_sql = $find_sql.$search_sql.$limitSql;
       // echo $final_sql;

        $result = $this->common_model->getDataList($final_sql, 'default');
        return $result;
    }

    /**
     * 日志查询接口
     * @param $_page_size
     * @param $_page_number
     * @param $_search_info
     * @return mixed
     */
    function find_log($_page_size, $_page_number, $_search_info){
        $find_sql = "SELECT * FROM t_log_info WHERE 1=1";

        //模糊搜索
        $search_sql = "";
        if ($_search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(op_content  , op_time  , op_user , ip_address ) LIKE '%" . $_search_info . "%'";
        }

        //定义每页显示的页数：
        $_page_number = ($_page_number-1) * $_page_size;
        $limitSql = '';
        $limitFlag = isset($_page_number) && $_page_size != -1;
        if ($limitFlag) {
            $limitSql = " LIMIT " . intval($_page_number) . ", " . intval($_page_size);
        }

        //排序SQL
        $orderSql = " ORDER BY id DESC";

        //最终拼接后SQL
        $final_sql = $find_sql.$search_sql.$orderSql.$limitSql;
//        echo $final_sql;

        $result = $this->common_model->getDataList($final_sql, 'default');
        return $result;
    }

    /**
     * 系统用户信息查询
     * @param $_page_size
     * @param $_page_number
     * @param $_search_info
     * @param $_user_type
     * @return mixed
     */
    function find_user($_page_size, $_page_number, $_search_info,$_user_type){
        $find_sql = "SELECT * FROM v_user_info_list WHERE 1=1";

        //模糊搜索
        $search_sql = "";
        if ($_search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(login_name,user_name,user_status) LIKE '%" . $_search_info . "%'";
        }

        //用户类型筛选
        $user_type_sql = "";
        if ($_user_type != 'all') {
            $user_type_sql = " AND user_type='" . $_user_type . "'";
        }

        //定义每页显示的页数：
        $_page_number = ($_page_number-1) * $_page_size;
        $limitSql = '';
        $limitFlag = isset($_page_number) && $_page_size != -1;
        if ($limitFlag) {
            $limitSql = " LIMIT " . intval($_page_number) . ", " . intval($_page_size);
        }

        //排序SQL
        $orderSql = " ORDER BY id ASC";

        //最终拼接后SQL
        $final_sql = $find_sql.$search_sql.$user_type_sql.$orderSql.$limitSql;
//        echo $final_sql;

        $result = $this->common_model->getDataList($final_sql, 'default');
        return $result;
    }

    /**
     * 系统用户添加
     * @param $_login_name
     * @param $_login_pwd
     * @param $_user_name
     * @param $_user_type
     * @param $_location_id
     * @return mixed
     */
    function add_user($_login_name, $_login_pwd, $_user_name, $_user_type, $_location_id){
        $add_sql = "INSERT INTO t_user_info(login_name,login_pwd,user_name,user_type,create_time,location_id  ) VALUES('" . $_login_name . "','" . $_login_pwd . "','" . $_user_name . "','" . $_user_type . "','" . date("Y-m-d H:i:s") . "','" . $_location_id . "')";
        $result = $this->common_model->execQuery($add_sql, 'default');
        return $result;
    }

    /**
     * 记录操作日志
     * @param $_ip              操作IP地址
     * @param $_ip_location     操作IP归属地
     * @param $_username        用户账号
     * @param $_log_content     操作内容
     * @return mixed
     */
    function add_log($_ip,$_username,$_log_content,$_ip_location){
        $log_sql = "INSERT INTO t_log_info(op_content , op_time , op_user , ip_address ,ip_location) VALUES('" . $_log_content . "','" . date("Y-m-d H:i:s") . "','" . $_username . "','" . $_ip . "','" . $_ip_location . "')";
        $result = $this->common_model->execQuery($log_sql, 'default', TRUE);
        return $result;
    }

    /**
     * 系统用户信息修改接口
     * @param $_id
     * @param $_user_name
     * @param $_user_type
     * @param $_user_status
     * $param $_location_id
     * @return mixed
     */
    function edit_user_info($_id, $_user_name, $_user_type, $_user_status,$_location_id){
        $update_sql = "UPDATE t_user_info SET  location_id='" . $_location_id . "',user_name='" . $_user_name . "', user_type ='" . $_user_type . "', user_status ='" . $_user_status . "' WHERE  id='" . $_id . "'";
        $result = $this->common_model->execQuery($update_sql, 'default');
        return $result;
    }

    /**
     * 通过ID获取用户信息
     * @param $_id
     * @return mixed
     */
    function get_user_info_by_id($_id){
        $find_sql = "SELECT id,login_name,user_name,user_type,user_status,location_id FROM t_user_info WHERE id='".$_id."'";
        $result = $this->common_model->getDataList($find_sql, 'default');
        return $result;
    }

    /**
     * 生成token
     * @param array $_payload
     * @param $_key
     * @param $_alg
     * @return string
     */
    function generate_token(array $_payload, $_key, $_alg){
        $_header = array(
            'typ' => 'JWT',
            'alg' => $_alg
        );
        /**
         * 利用JWT规则生成token，JWT详见：https://jwt.io/#libraries
         * base64_encode — 使用 MIME base64 对数据进行编码(PHP 4, PHP 5, PHP 7)
         * hash_hmac — 使用 HMAC 方法生成带有密钥的哈希值(PHP 5 >= 5.1.2, PHP 7, PECL hash >= 1.1)
         */
        $_jwt = base64_encode(json_encode($_header)) . '.' . base64_encode(json_encode($_payload));
        $_signature = hash_hmac($_alg, $_jwt, md5($_key));
        return $_jwt . '.' . $_signature;
//        $jwt = json_encode($header).'.'.json_encode($_payload);

    }

    /**
     * token验证
     * @param $_token
     * @param $_key
     * @return bool
     */
    function verify_token($_token, $_key){
        $tokens = explode('.', $_token);
//        print_r(count($tokens));exit;

        if (count($tokens) != 3)
            return false;

        list($header64, $payload64, $sign) = $tokens;
//        echo ($header64.'<br/>'.$payload64.'<br/>'.$sign);exit;

        $header = json_decode(base64_decode($header64));
//        print_r($header->alg);echo '<br>';
        if (empty($header->alg) || empty($header->typ))
            return false;
//        echo (hash_hmac($header->alg, $header64 . '.' . $payload64, md5($_key)) );exit;
        if (hash_hmac($header->alg, $header64 . '.' . $payload64, md5($_key)) !== $sign) {
            return false;
        }

        $payload = json_decode(base64_decode($payload64));
//        print_r($payload);

        //获取当前请求时间
        $time = $_SERVER['REQUEST_TIME'];
//        var_dump(isset($payload->cur_time));
        //如果token里面时间大于当前请求时间，返回false
        if (!isset($payload->cur_time) || $payload->cur_time > $time) {
//            echo 'fail';
            return false;
        }

        //如果过期时间小于当前过时间，说明token已过期，返回false
        if (!isset($payload->exp_time) || $payload->exp_time < $time) {
            return false;
        }
        return true;
    }

    /**
     * 补贴操作接口
     * @param $_id      用户ID
     * @param $_op_name 操作用户
     * @return mixed
     */
    function subsidy_op($_id, $_op_name){
        $update_sql = "UPDATE t_vehicle_info SET subsidy_flag  ='1',subsidy_time='" . date("Y-m-d H:i:s") . "',subsidy_name='" . $_op_name . "' WHERE  id='" . $_id . "'";
        //echo $update_sql;
        $result = $this->common_model->execQuery($update_sql, 'default');
        return $result;
    }

    /**
     * 区域范围查询接口
     * @param $_page_size
     * @param $_page_number
     * @param $_search_info
     * @return mixed
     */
    function find_location($_page_size, $_page_number, $_search_info){
        $find_sql = "SELECT * FROM t_location_info WHERE 1=1";

        //模糊搜索
        $search_sql = "";
        if ($_search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(id,location_name) LIKE '%" . $_search_info . "%'";
        }

        //定义每页显示的页数：
        $_page_number = ($_page_number-1) * $_page_size;
        $limitSql = '';
        $limitFlag = isset($_page_number) && $_page_size != -1;
        if ($limitFlag) {
            $limitSql = " LIMIT " . intval($_page_number) . ", " . intval($_page_size);
        }

        //最终拼接后SQL
        $final_sql = $find_sql.$search_sql.$limitSql;
        // echo $final_sql;

        $result = $this->common_model->getDataList($final_sql, 'default');
        return $result;
    }

    /**
     * 添加区域范围信息
     * @param $_location_name
     * @return mixed
     */
    function add_location_info($_location_name){
        $add_sql = "INSERT INTO t_location_info(location_name,create_time) VALUES('" . $_location_name . "','" . date("Y-m-d H:i:s") . "')";
        $result = $this->common_model->execQuery($add_sql, 'default');
        return $result;
    }

    /**
     * 区域范围修改接口
     * @param $_id
     * @param $_location_name
     * @return mixed
     */
    function edit_location_info($_id, $_location_name){
        $update_sql = "UPDATE t_location_info SET  location_name='" . $_location_name . "' WHERE  id='" . $_id . "'";
        $result = $this->common_model->execQuery($update_sql, 'default');
        return $result;
    }

    /**
     * 区域范围查询接口-用于下拉列表选项
     * @return mixed
     */
    function get_location_list(){
        $get_info_sql = "SELECT * FROM t_location_info";
        $result = $this->common_model->getDataList($get_info_sql, 'default');
        return $result;
    }

    /**
     * 区域数据导出
     * @param $_begin_time      开始时间
     * @param $_end_time        结束时间
     * @param $_location_id     区域范围
     * @return mixed
     */
    function export_info_list($_begin_time,$_end_time,$_location_id){
        //此处需要判断是否为超管，当$_location_id==0时为超管，导出已补贴数据；否则，根据所属区域范围导出
        if ($_location_id == 0) {
            $get_info_sql = "SELECT * FROM t_vehicle_info WHERE subsidy_flag='1' AND subsidy_time BETWEEN '" . $_begin_time . "' AND '" . $_end_time . "'";
        } else {
            $get_info_sql = "SELECT * FROM t_vehicle_info WHERE subsidy_flag='1' AND location_id='" . $_location_id . "' AND subsidy_time BETWEEN '" . $_begin_time . "' AND '" . $_end_time . "'";
        }
        $result = $this->common_model->getDataList($get_info_sql, 'default');
        return $result;
    }


    /**
     * 修改车辆信息所属区域接口
     * @param $_id              车辆ID
     * @param $_location_id     区域ID
     * @return mixed
     */
    function edit_vehicle_location_id($_id,$_location_id){
        $update_sql = "UPDATE t_vehicle_info SET  location_id='" . $_location_id . "' WHERE  id='" . $_id . "'";
        $result = $this->common_model->execQuery($update_sql, 'default');
        return $result;
    }

}

/* End of file admin_model.php */
/* Location: ./app/models/admin_model.php */