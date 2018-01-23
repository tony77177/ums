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
        $check_sql = "SELECT user_name,user_type  FROM t_user_info WHERE user_status=1 AND login_name='" . $_login_name . "' AND login_pwd='" . $_login_pwd . "'";
        $result = $this->common_model->getDataList($check_sql, 'default');
        return $result;
    }

    /**
     *验证是否为非法登录
     */
    function auth_check(){
        if (!$this->session->userdata('login_name')) {
            redirect(site_url() . "/login");
        }
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
     * 用户信息状态审核
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
     * @return mixed
     */
    function find_data($_page_size, $_page_number, $_search_info){
        $find_sql = "SELECT * FROM t_vehicle_info WHERE 1=1";

        //模糊搜索
        $search_sql = "";
        if ($_search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(contact ,idcard ,address,contacttel) LIKE '%" . $_search_info . "%'";
        }

        //分页
//        $get_total_num  = "SELECT COUNT(*) FROM t_vehicle_info WHERE 1=1";
//        $page_count = $this->common_model->getDataList($get_total_num.$search_sql, 'default');
//        //$page_count = $pagecount['total'];
//        //获取页数,判断是否符合要求
//        if ($_page_number <= 0) {
//            $_page_number = 1;
//        } elseif ($_page_number > $page_count) {
//            $_page_number = $page_count;
//        }
        //定义每页显示的页数：
        $_page_number = ($_page_number-1) * $_page_size;
        //计算总页数:
//        $page_nums = ceil($page_count/$_page_size);   //向上取整；
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
        $find_sql = "SELECT id, login_name, user_name , user_type ,user_status FROM t_user_info WHERE 1=1";

        //模糊搜索
        $search_sql = "";
        if ($_search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT( login_name   ,  user_name   ,  user_status  ) LIKE '%" . $_search_info . "%'";
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
     * @return mixed
     */
    function add_user($_login_name, $_login_pwd, $_user_name, $_user_type){
        $add_sql = "INSERT INTO t_user_info( login_name  ,  login_pwd  ,  user_name  ,  user_type , create_time  ) VALUES('" . $_login_name . "','" . $_login_pwd . "','" . $_user_name . "','" . $_user_type . "','" . date("Y-m-d H:i:s") . "')";
        $result = $this->common_model->execQuery($add_sql, 'default');
        return $result;
    }

    /**
     * 记录操作日志
     * @param $_ip              操作IP地址
     * @param $_username        用户账号
     * @param $_log_content     操作内容
     * @return mixed
     */
    function add_log($_ip,$_username,$_log_content){
        $log_sql = "INSERT INTO t_log_info(op_content , op_time , op_user , ip_address ) VALUES('" . $_log_content . "','" . date("Y-m-d H:i:s") . "','" . $_username . "','" . $_ip . "')";
        $result = $this->common_model->execQuery($log_sql, 'default', TRUE);
        return $result;
    }

    /**
     * 系统用户信息修改接口
     * @param $_id
     * @param $_user_name
     * @param $_user_type
     * @param $_user_status
     * @return mixed
     */
    function edit_user_info($_id, $_user_name, $_user_type, $_user_status){
        $update_sql = "UPDATE t_user_info SET   user_name='" . $_user_name . "', user_type ='" . $_user_type . "', user_status ='" . $_user_status . "' WHERE  id='" . $_id . "'";
        $result = $this->common_model->execQuery($update_sql, 'default');
        return $result;
    }

    /**
     * 通过ID获取用户信息
     * @param $_id
     * @return mixed
     */
    function get_user_info_by_id($_id){
        $find_sql = "SELECT id, login_name, user_name , user_type ,user_status FROM t_user_info WHERE id='".$_id."'";
        $result = $this->common_model->getDataList($find_sql, 'default');
        return $result;
    }

}

/* End of file admin_model.php */
/* Location: ./app/models/admin_model.php */