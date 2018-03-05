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
        //$this->load->library('Common_model');
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
        header("Access-Control-Allow-Credentials: true");
    }


//    public function get_token(){
//        $data = array(
//            'code'=>'1',
//            'cur_time'=>$_SERVER['REQUEST_TIME'],//当前请求时间，Unix时间戳
//            'exp_time'=>$_SERVER['REQUEST_TIME'] + 7200,//过期时间：两个小时过期
//            'user_name'=>'zhaoyu',
//            'login_name'=>'zhaoyu'
//        );
//
////        print_r($data);echo "<br>";
//        $data = json_encode($data);
//        //$result = $this->admin_model->generate_token($data, $this->config->config['token_key'],$this->config->config['token_algo']);
//        log_message('info', '生成token测试：' . $data);
//        echo $data;
//    }

//    public function verify_token(){
//        $token = $this->input->get('token', TRUE);
//        $result = $this->admin_model->verify_token($token, 'key_12345678');
//        print_r($result);
//    }

    //用户登录接口
    public function login()
    {

        //登录名及密码
        $login_name = $this->input->post('login_name', TRUE);
        $login_pwd = $this->input->post('login_pwd', TRUE);

//        var_dump($this->input->cookie());

        $ip_address = $this->input->cookie('ip');
        $ip_location = $this->input->cookie('ipName');

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
            //开始生成token
            $token_data = array(
                'cur_time' => $_SERVER['REQUEST_TIME'],//当前请求时间，Unix时间戳
                'exp_time' => $_SERVER['REQUEST_TIME'] + 7200,//过期时间：两个小时过期
                'user_name' => $result[0]['user_name'],
                'login_name' => $login_name,
                'location_id' => $result[0]['location_id']
            );
//            print_r($token_data);
            $token = $this->admin_model->generate_token($token_data, $this->config->config['token_key'], $this->config->config['token_algo']);

            $data = array(
                'code' => '0',
                'user_name' => $result[0]['user_name'],
                'user_type' => $result[0]['user_type'],
//                'location_id' => $result[0]['location_id'],
                'token' => $token
            );
            $this->session->set_userdata('user_name', $result[0]['user_name']); //记录用户名，用于判断是否登录
            $this->session->set_userdata('user_type', $result[0]['user_type']);
            $this->session->set_userdata('login_name', $login_name);
            log_message('info', '登录成功，用户名：' . $login_name);
            $this->admin_model->add_log($ip_address, $login_name, '用户登录',$ip_location); //记录登录日志
        }
        echo json_encode($data);
    }

    //系统用户密码修改接口
    public function change_pwd(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //登录名及新密码
        $login_name = $this->input->post('login_name', TRUE);
        $change_pwd = $this->input->post('change_pwd', TRUE);
        //echo $change_pwd."<br/>";

        //返回结果
        $result = $this->admin_model->change_pwd($login_name, md5($change_pwd));
        log_message('info', '修改密码返回值：' . $result . '，用户名为：' . $login_name);
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

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //登录名及新密码
        $id = $this->input->get('id', TRUE);
        $op_name = $this->input->get('op_name', TRUE);

        //返回结果
        $result = $this->admin_model->user_verify($id, $op_name);
        log_message('info', '用户审核操作结果：' . $result . '，审核人为：' . $op_name);
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
            $this->admin_model->add_log($this->input->cookie('ip'), $op_name, '数据审核，数据ID：'.$id,$this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }

    //车辆信息列表查询接口
    public function find_data(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //用户信息搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));

        //根据token获取用户区域码
        $headers = apache_request_headers();
        $tokens = explode('.', $headers['authorization']);
        list($header64, $payload64, $sign) = $tokens;
        $payload = json_decode(base64_decode($payload64));
        $location_id = $payload->location_id;
//        var_dump($payload);

        //返回结果
        $result = $this->admin_model->find_data($page_size, $page_number,$search_info,$location_id);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(contact ,idcard ,address,contacttel) LIKE '%" . $search_info . "%'";
        }
        if ($location_id != 0) {
            $search_sql .= ' AND location_id=' . $location_id;
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

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

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

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //添加的用户相关信息
        $login_name = $this->input->post('login_name', TRUE);
        $login_pwd = $this->input->post('login_pwd', TRUE);
        $user_name = $this->input->post('user_name', TRUE);
        $user_type = $this->input->post('user_type', TRUE);
        $location_id = $this->input->post('location_id', TRUE);

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
        $result = $this->admin_model->add_user($login_name, md5($login_pwd), $user_name, $user_type,$location_id);
        log_message('info', '添加用户操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));
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
            $this->admin_model->add_log($this->input->cookie('ip'), $this->session->userdata('login_name'), '添加用户：'.$login_name,$this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }

    //系统用户信息查询
    public function find_user(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

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

        //用户类型筛选
        $user_type_sql = "";
        if ($user_type != 'all') {
            $user_type_sql = " AND user_type='" . $user_type . "'";
        }

        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_user_info WHERE 1=1".$search_sql.$user_type_sql;
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

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //系统用户信息，ID、用户昵称、用户类型、用户状态及所属区域位置ID
        $id = $this->input->post('id', TRUE);
        $user_name = $this->input->post('user_name', TRUE);
        $user_type = $this->input->post('user_type', TRUE);
        $user_status = $this->input->post('user_status', TRUE);
        $location_id = $this->input->post('location_id', TRUE);

        //返回结果
        $result = $this->admin_model->edit_user_info($id, $user_name,$user_type,$user_status,$location_id);
        log_message('info', '修改用户信息操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));
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
            $this->admin_model->add_log($this->input->cookie('ip'), $this->session->userdata('login_name'), '用户信息更改，用户ID：' . $id, $this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }

    //系统用户信息接口
    public function get_user_info_by_id(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

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
                'id' => $result[0]['id'],
                'login_name' => $result[0]['login_name'],
                'user_name' => $result[0]['user_name'],
                'user_type' => $result[0]['user_type'],
                'user_status' => $result[0]['user_status'],
                'location_id' => $result[0]['location_id']
            );
        }
        echo json_encode($data);
    }


    //补贴操作接口
    public function subsidy_op(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //相关参数获取
        $id = $this->input->get('id', TRUE);
        $subsidy_name = $this->input->get('subsidy_name', TRUE);

        //返回结果
        $result = $this->admin_model->subsidy_op($id, $subsidy_name);
        log_message('info', '补贴操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));
        //print_r($result);
        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10011',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->cookie('ip'), $subsidy_name, '补贴操作，数据ID：' . $id, $this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }

    //后台批量添加车辆信息接口
    public function upload_files()
    {
        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //获取上传文件后缀类型
        /**
         * $_FILES['uploadedfile']
         * uploadedfile为前端上传内容名字，更改时以前端为准
         */
        $file_array = explode(".", $_FILES['uploadedfile']['name']);
//        print_r($file_array);exit;
        $file_extension = strtolower(array_pop($file_array));
        if ($file_extension != 'xls' && $file_extension != 'xlsx') {
            echo(json_encode(
                array(
                    "code"=>"10012",
                    "error_msg" => "文件类型错误，请上传excel文档(后缀名xls或xlsx)"
                ), JSON_UNESCAPED_UNICODE
            ));
        } else {
            //文件保存后的新名字及保存目录
            $file_new_Name = $this->config->config['upload_path'] . $_SESSION['login_name'] . '-' . time() . '.' . $file_extension;
            //保存文件，保存成功返回 TRUE 否则返回FALSE
            $flag = move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $file_new_Name);
            log_message('info', 'excel文件新名字：' . $file_new_Name);
            log_message('info', 'excel文件是否保存成功标志：' . $flag);
            if ($flag) {
                //加载Excel插件
                $this->load->library('excel');
                //文件保存成功，开始读文件
                $objPHPExcel = PHPExcel_IOFactory::load($file_new_Name);
                $cell_collection = $objPHPExcel->getActiveSheet()->getCellCollection();
                foreach ($cell_collection as $cell) {
                    $column = $objPHPExcel->getActiveSheet()->getCell($cell)->getColumn();
                    $row = $objPHPExcel->getActiveSheet()->getCell($cell)->getRow();
                    //获取具体值此处使用：getFormattedValue，因为部分excel中包含格式，所以包含格式一起读取
                    $data_value = trim($objPHPExcel->getActiveSheet()->getCell($cell)->getFormattedValue());
                    //header will/should be in row 1 only. of course this can be modified to suit your need.
                    if ($row == 1) {
                        $header[$row][$column] = $data_value;
                    } else {
                        $arr_data[$row][$column] = $data_value;
                    }
                }
//                print_r($arr_data);exit;
                //send the data in an array format
//                $data['header'] = $header;
//                $data['values'] = $arr_data;
//                $_community_info = $this->input->post('community_info', TRUE);//小区ID
//                $_sr_info = $this->input->post('sr_info', TRUE);//分前端ID
                $success_num = 0;//数据添加成功数量
                $fail_num = 0;//数据添加失败数量
                $fail_array = array();//添加失败内容
                foreach ($arr_data as $item) {
                    //检测数据是否存在
                    $check_info_is_exist_sql = "SELECT COUNT(*) AS num FROM t_vehicle_info WHERE idcard='" . $item['B'] . "'";
                    $check_result = $this->common_model->getTotalNum($check_info_is_exist_sql, 'default');
//                    print_r($check_result);exit;
                    log_message('info', '检测数据是否存在返回结果：：' . $check_result->num);

                    //如果不重复，则执行数据新增操作
                    if ($check_result->num == 0) {
                        //新增数据SQL
                        $add_sql = "INSERT INTO t_vehicle_info(location_id,contact,contacttel,idcard,licenseplate,frid,maincard,brand,color,cartype,address) VALUES ";
                        $add_sql .= "('" . $item['A'] . "','" . $item['B'] . "','" . $item['C'] . "','" . $item['D'] . "','" . $item['E'] . "','" . $item['F'] . "','" . $item['G'] . "','" . $item['H'] . "','" . $item['I'] . "','" . $item['J'] . "','" . $item['K'] . "')";
                        $result = $this->common_model->execQuery($add_sql, 'default');
                        //如果添加成功，则记录log
                        if ($result) {
                            $this->admin_model->add_log($this->input->cookie('ip'), $_SESSION['login_name'] . '  ' . $_SESSION['user_name'], '添加车辆信息，车主姓名为：' . $item['A'], $this->input->cookie('ipName')); //记录日志
                        }
                        $success_num++;
                    } else {
                        log_message('info', '数据添加失败，失败IdCard为：' . $item['B']);
                        $fail_num++;
                        array_push($fail_array, $item['B']);//记录失败ID
                    }
                }
                if ($success_num == 0) {
                    echo(json_encode(
                        array(
                            "code"=>"10012",
                            "error_msg" => "添加失败，请确认添加数据是否已存在！"
                        ), JSON_UNESCAPED_UNICODE
                    ));
                } else {
                    echo(json_encode(
                        array(
                            "code"=>"0",
                            "error_msg" => "",
                            "success_num" => $success_num,
                            "fail_num" => $fail_num,
                            "fail_data"=>$fail_array
                        ), JSON_UNESCAPED_UNICODE
                    ));
                }
            } else {
                log_message('info', '上传文件保存失败：' . $file_new_Name);
                echo(json_encode(
                    array(
                        "code"=>"10012",
                        "error_msg" => "上传文件保存失败，请联系管理员检查上传目录是否有读写权限"
                    ), JSON_UNESCAPED_UNICODE
                ));
            }
        }
    }

    //区域范围查询接口
    public function find_location(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //区域信息搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));



        //返回结果
        $result = $this->admin_model->find_location($page_size, $page_number, $search_info);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(id,location_name) LIKE '%" . $search_info . "%'";
        }
        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_location_info WHERE 1=1".$search_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10013',
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

    //区域范围添加接口
    public function add_location_info(){

        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //添加的区域相关信息
        $location_name = $this->input->post('location_name', TRUE);

        //返回结果
        $result = $this->admin_model->add_location_info($location_name);
        log_message('info', '添加区域：'.$location_name.'添加区域操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));

        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10014',
                'error_msg' => $result
            );
        } else {
            $this->session->unset_userdata('location_info');//删除原有地址列表session
            $this->admin_model->add_log($this->input->cookie('ip'), $this->session->userdata('login_name'), '添加社区：' . $location_name,$this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }


    //区域范围修改接口
    public function edit_location_info(){
        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //系统用户信息，ID、用户昵称、用户类型、用户状态及所属区域位置ID
        $id = $this->input->post('id', TRUE);
        $location_name = $this->input->post('location_name', TRUE);

        //返回结果
        $result = $this->admin_model->edit_location_info($id, $location_name);
        log_message('info', '修改区域范围操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));

        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10015',
                'error_msg' => $result
            );
        } else {
            $this->session->unset_userdata('location_info');//删除原有地址列表session
            $this->admin_model->add_log($this->input->cookie('ip'), $this->session->userdata('login_name'), '区域范围修改，新名称为：' . $location_name, $this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }


    //区域范围查询接口-用于下拉列表选项
    public function get_location_list(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //数据存入session，防止频繁请求数据库
        $result = $this->session->userdata('location_info');
        if (!isset($result)) {
            $result = $this->admin_model->get_location_list();
            $this->session->set_userdata('location_info', $result);
        } else {
            $result = $this->session->userdata('location_info');
        }

        //返回结果数组
        $data = array(
            'code' => '10016',
            'error_msg' => '数据读取失败，请稍后再试'
        );

        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'data' => $result
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