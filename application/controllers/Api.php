<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: zhaoyu
 * Date: 2018-1-19
 * Time: 17:48
 */
class Api extends CI_Controller{
    function __construct(){
        parent::__construct();
        $this->load->library('Common_class');
//        $this->admin_model->auth_check();
        //$this->load->library('Common_model');
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
        header("Access-Control-Allow-Credentials: true");
    }

    //用户登录接口
    public function login(){

        //登录名及密码
        $login_name = $this->input->post('login_name', TRUE);
        $login_pwd = $this->input->post('login_pwd', TRUE);

        //返回结果数组
        $data = array(
            'code' => '10001',
            'error_msg' => '账号密码不正确，或者账号已被停用'
        );

        //判断用户名或者密码是否为空
        if (trim($login_name) == '' || trim($login_pwd) == '') {
            echo json_encode($data);
            exit;
        }

        //返回结果
        $result = $this->admin_model->check_login($login_name, md5($login_pwd));

        if (!empty($result)) {//非空数组，登录成功
            $data = array(
                'code' => '0',
                'user_name' => $result[0]['user_name'],
                'user_type' => $result[0]['user_type']
            );
            $this->session->set_userdata('user_name', $result[0]['user_name']); //记录用户名，用于判断是否登录
            $this->session->set_userdata('user_type', $result[0]['user_type']);
            $this->session->set_userdata('login_name', $login_name);
            $this->admin_model->add_log($this->input->ip_address(), $login_name ,'用户登录'); //记录登录日志
        }
        echo json_encode($data);
    }

    //系统用户密码修改接口
    public function change_pwd(){

        //$this->admin_model->auth_check();

        //登录名及新密码
        $login_name = $this->input->post('login_name', TRUE);
        $change_pwd = $this->input->post('change_pwd', TRUE);
        //echo $change_pwd."<br/>";

        //返回结果
        $result = $this->admin_model->change_pwd($login_name, md5($change_pwd));
        //print_r($result);
        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10002',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->ip_address(), $login_name, '用户修改密码'); //记录日志
        }
        echo json_encode($data);
    }

    //用户信息审核接口
    public function user_verify(){

        //$this->admin_model->auth_check();

        //登录名及新密码
        $id = $this->input->get('id', TRUE);
        $op_name = $this->input->get('op_name', TRUE);

        //返回结果
        $result = $this->admin_model->user_verify($id, $op_name);
        //print_r($result);
        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10003',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->ip_address(), $op_name, '数据审核，数据ID：'.$id); //记录日志
        }
        echo json_encode($data);
    }

    //用户信息列表查询接口
    public function find_data(){

        //$this->admin_model->auth_check();

        //用户信息搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));

        //返回结果
        $result = $this->admin_model->find_data($page_size, $page_number,$search_info);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(contact ,idcard ,address,contacttel) LIKE '%" . $search_info . "%'";
        }
        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_vehicle_info WHERE 1=1".$search_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10004',
            'error_msg' => '数据读取失败，请稍后再试'
        );
        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'page_size'=>$page_size,
                'page_number'=>$page_number,
                'total_number'=>$total_number->num,
                'error_msg' => '',
                'data' => $result
            );
        }
        echo json_encode($data);
    }

    //日志查询接口
    public function find_log(){

        //$this->admin_model->auth_check();

        //日志搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));

        //返回结果
        $result = $this->admin_model->find_log($page_size, $page_number,$search_info);
        //print_r($result);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(op_content  , op_time  , op_user , ip_address ) LIKE '%" . $search_info . "%'";
        }
        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_log_info WHERE 1=1".$search_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10005',
            'error_msg' => '数据读取失败，请稍后再试'
        );
        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'page_size'=>$page_size,
                'page_number'=>$page_number,
                'total_number'=>$total_number->num,
                'error_msg' => '',
                'data' => $result
            );
        }
        echo json_encode($data);
    }

    //用户添加接口
    public function add_user(){

        //$this->admin_model->auth_check();

        //添加的用户相关信息
        $login_name = $this->input->post('login_name', TRUE);
        $login_pwd = $this->input->post('login_pwd', TRUE);
        $user_name = $this->input->post('user_name', TRUE);
        $user_type = $this->input->post('user_type', TRUE);

        //验证登录名唯一性
        $check_sql = "SELECT COUNT(*) as num FROM  t_user_info WHERE login_name='" . $login_name . "'";
        $check_result = $this->common_model->getTotalNum($check_sql, 'default');
//        print($check_result->num);
        if ($check_result->num>0) {
            $data = array(
                'code' => '10006',
                'error_msg' => '添加失败，登录名重复'
            );
            echo json_encode($data);
            exit;
        }

        //返回结果
        $result = $this->admin_model->add_user($login_name, md5($login_pwd), $user_name, $user_type);
        //print_r($result);
        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10006',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->ip_address(), $this->session->userdata('login_name'), '添加用户：'.$login_name); //记录日志
        }
        echo json_encode($data);
    }

    //系统用户信息查询
    public function find_user(){

        //$this->admin_model->auth_check();

        //日志搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));
        $user_type = $this->input->get('user_type', TRUE);

        //返回结果
        $result = $this->admin_model->find_user($page_size, $page_number,$search_info,$user_type);
        //print_r($result);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT( login_name   ,  user_name   ,  user_status  ) LIKE '%" . $search_info . "%'";
        }
        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_user_info WHERE 1=1".$search_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10008',
            'error_msg' => '数据读取失败，请稍后再试'
        );
        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'page_size'=>$page_size,
                'page_number'=>$page_number,
                'total_number'=>$total_number->num,
                'error_msg' => '',
                'data' => $result
            );
        }
        echo json_encode($data);
    }

    //系统用户信息修改接口
    public function edit_user_info(){

        //系统用户信息，ID、用户昵称、用户类型及用户状态
        $id = $this->input->post('id', TRUE);
        $user_name = $this->input->post('user_name', TRUE);
        $user_type = $this->input->post('user_type', TRUE);
        $user_status = $this->input->post('user_status', TRUE);

        //返回结果
        $result = $this->admin_model->edit_user_info($id, $user_name,$user_type,$user_status);
        //print_r($result);
        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10009',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->ip_address(), $this->session->userdata('login_name'), '用户信息更改，用户ID：'.$id); //记录日志
        }
        echo json_encode($data);
    }

    //系统用户信息接口
    public function get_user_info_by_id(){

        //$this->admin_model->auth_check();

        //用户ID
        $id = $this->input->get('id', TRUE);

        //返回结果
        $result = $this->admin_model->get_user_info_by_id($id);

        //返回结果数组
        $data = array(
            'code' => '10010'
        );
        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'id'=>$result[0]['id'],
                'login_name'=>$result[0]['login_name'],
                'user_name'=>$result[0]['user_name'],
                'user_type'=>$result[0]['user_type'],
                'user_status'=>$result[0]['user_status']
            );
        }
        echo json_encode($data);
    }

    //退出系统接口
    public function logout(){
        $this->session->sess_destroy();
        $data = array(
            'code' => '0'
        );
        echo json_encode($data);
    }

}

/* End of file Api.php */
/* Location: ./app/controllers/api.php */