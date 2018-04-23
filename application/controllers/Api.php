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


        //通过cookie获取IP地址及归属地
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
            //获取用户登录相关session
            $this->session->set_userdata('user_name', $result[0]['user_name']); //记录用户名，用于判断是否登录
            $this->session->set_userdata('user_type', $result[0]['user_type']);
            $this->session->set_userdata('login_name', $login_name);

            //获取用户列表session，用于车辆信息处判断login_name对应的user_name
            $user_info_arr = $this->admin_model->get_all_user_info();
            $this->session->set_userdata('user_info_arr',$user_info_arr);
//            var_dump($this->session->userdata('user_info_arr'));exit;

            //获取区域列表session，用于用户列表所属区域判断
            $location_info_res = $this->admin_model->get_all_location_info();
            //进行格式整改，更改为：10000 => string '朱昌镇' (length=9)类型，方便后期直接读取
            $location_info_arr = '';
            if (is_array($location_info_res)) {
                foreach ($location_info_res as $key => $val) {
                    $location_info_arr[$val['id']] = $val['location_name'];
                }
            }
            $this->session->set_userdata('location_info_arr',$location_info_arr);

            //存日志
            log_message('info', '登录成功，用户名：' . $login_name);
            $this->admin_model->add_log($ip_address, $login_name, '用户登录', $ip_location); //记录登录日志
        }
        echo json_encode($data);
    }

    //系统用户密码修改接口
    public function change_pwd(){

        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //登录名及新密码
        $login_name = $this->input->post('login_name', TRUE);
        $change_pwd = $this->input->post('change_pwd', TRUE);

        //返回结果
        $result = $this->admin_model->change_pwd($login_name, md5($change_pwd));
        log_message('info', '修改密码返回值：' . $result . '，修改密码的用户名为：' . $login_name);

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

    //车辆信息审核接口
    public function user_verify(){

        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //审核ID及审核人
        $id = $this->input->get('id', TRUE);
        $op_name = $this->input->get('op_name', TRUE);

        //返回结果
        $result = $this->admin_model->user_verify($id, $op_name);
        log_message('info', '车辆信息审核操作结果：' . $result . '，审核人为：' . $op_name . '，操作IP地址为：' . $this->input->cookie('ip') . '，操作归属地为：' . $this->input->cookie('ipName'));
        log_message('info', '相关参数为：id：' . $id . '，op_name：' . $op_name);

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
            $this->admin_model->add_log($this->input->cookie('ip'), $op_name, '数据审核，数据ID：' . $id, $this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }

    //车辆信息列表查询接口
    public function find_data(){

        //验证token，防止恶意请求
//        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
//            $error_msg = array(
//                'code' => '10000',
//                'error_msg' => 'token校验失败'
//            );
//            echo json_encode($error_msg);
//            exit;
//        }

        //用户信息搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));

        $headers = '';
        //根据token获取用户区域码
        if(!function_exists('apache_request_headers')){
//            echo '111';exit;
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            //return $headers;
        }else{
            $headers = apache_request_headers();
        }

        $tokens = explode('.', $headers['authorization']);
        list($header64, $payload64, $sign) = $tokens;
        $payload = json_decode(base64_decode($payload64));
        $location_id = $payload->location_id;

        //返回结果
        $result = $this->admin_model->find_data($page_size, $page_number, $search_info, $location_id);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(contact ,idcard ,address,contacttel) LIKE '%" . $search_info . "%'";
        }
        if ($location_id != 0) {
            $search_sql .= ' AND location_id IN(' . $location_id . ')';
        }

        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_vehicle_info WHERE 1=1" . $search_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10004',
            'error_msg' => '数据读取失败，请稍后再试'
        );
        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'page_size' => $page_size,
                'page_number' => $page_number,
                'total_number' => $total_number->num,
                'error_msg' => '',
                'data' => $result
            );
        }
        echo json_encode($data);
    }

    //日志查询接口
    public function find_log(){

        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //日志搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));

        //返回结果
        $result = $this->admin_model->find_log($page_size, $page_number, $search_info);
        //print_r($result);

        //数据总条数
        //模糊搜索
        $search_sql = "";
        if ($search_info !== '') {
            //mysql CONCAT(str1,str2,…)
            //返回结果为连接参数产生的字符串。如有任何一个参数为NULL ，则返回值为 NULL。
            $search_sql = " AND CONCAT(op_content  , op_time  , op_user , ip_address ) LIKE '%" . $search_info . "%'";
        }
        $get_total_num_sql = "SELECT COUNT(*) as num FROM t_log_info WHERE 1=1" . $search_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10005',
            'error_msg' => '数据读取失败，请稍后再试'
        );
        if (!empty($result)) {//非空数组，数据获取成功
            $data = array(
                'code' => '0',
                'page_size' => $page_size,
                'page_number' => $page_number,
                'total_number' => $total_number->num,
                'error_msg' => '',
                'data' => $result
            );
        }
        echo json_encode($data);
    }

    //用户添加接口
    public function add_user(){

        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //添加的用户相关信息
        $login_name = $this->input->post('login_name', TRUE);
//        $login_pwd = $this->input->post('login_pwd', TRUE);密码逻辑更改自动生成随机密码
        $user_name = $this->input->post('user_name', TRUE);
        $user_type = $this->input->post('user_type', TRUE);
        $location_id = $this->input->post('location_id', TRUE);

        //自动生成随机密码，目前默认为8位
        $length = 8;//密码默认长度为8位
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';// 密码字符集，可任意添加你需要的字符
        $password = '';//随机生成的密码
        for ( $i = 0; $i < $length; $i++ ) {
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }

        //验证登录名唯一性
        $check_sql = "SELECT COUNT(*) as num FROM  t_user_info WHERE login_name='" . $login_name . "'";
        $check_result = $this->common_model->getTotalNum($check_sql, 'default');
        if ($check_result->num > 0) {
            $data = array(
                'code' => '10006',
                'error_msg' => '添加失败，登录名重复'
            );
            echo json_encode($data);
            exit;
        }

        /*
         * 返回添加用户操作结果
         * 注：此处改为后台自动生成密码，因前端登录时需要进行一次MD5加密、后台进行一次MD5，故在添加数据库时，需要进行两次MD5加密
         */
        $result = $this->admin_model->add_user($login_name, md5(md5($password)), $user_name, $user_type, $location_id);
        log_message('info', '添加用户操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name') . '，操作IP地址为：' . $this->input->cookie('ip') . '，操作归属地为：' . $this->input->cookie('ipName'));
        log_message('info', '相关参数为：login_name：' . $login_name . '，user_name：' . $user_name . '，user_type：' . $user_type . '，location_id：' . $location_id);

        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => '',
            'password' => $password
        );
        if (!$result) {
            $data = array(
                'code' => '10006',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->cookie('ip'), $this->session->userdata('login_name'), '添加用户：' . $login_name, $this->input->cookie('ipName')); //记录日志
        }
        echo json_encode($data);
    }

    //系统用户信息查询
    public function find_user(){

        //验证token，防止恶意请求
        if (!$this->admin_model->auth_check($this->config->config['token_key'])) {
            $error_msg = array(
                'code' => '10000',
                'error_msg' => 'token校验失败'
            );
            echo json_encode($error_msg);
            exit;
        }

        //日志搜索参数
        $page_size = $this->input->get('page_size', TRUE);
        $page_number = $this->input->get('page_number', TRUE);
        $search_info = trim($this->input->get('search_info', TRUE));
        $user_type = $this->input->get('user_type', TRUE);

        //返回结果
        $result = $this->admin_model->find_user($page_size, $page_number, $search_info, $user_type);
//        var_dump($result);exit;
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

        $get_total_num_sql = "SELECT COUNT(*) as num FROM v_user_info_list WHERE 1=1" . $search_sql . $user_type_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

        //返回结果数组
        $data = array(
            'code' => '10008',
            'error_msg' => '数据读取失败，请稍后再试'
        );
        if (!empty($result)) {//非空数组，数据获取成功

            //此处利用session数组，新增结果集对应的location_name
            for ($i = 0; $i < count($result); $i++) {
                $result[$i]['location_name'] = '';
                //判断是否属于过个区域，如果是，进行location_name的组装
                if (strstr($result[$i]['location_id'], ',')) {
                    $location_id_arr = explode(",", $result[$i]['location_id']);
                    for ($j = 0; $j < count($location_id_arr); $j++) {
                        if ($j + 1 == count($location_id_arr)) {
                            $result[$i]['location_name'] .= $this->session->userdata('location_info_arr')[$location_id_arr[$j]];
                        } else {
                            $result[$i]['location_name'] .= $this->session->userdata('location_info_arr')[$location_id_arr[$j]] . ',';
                        }
                    }
                } else {
//                    var_dump($this->session->userdata('location_info_arr'));
//                    var_dump($result[$i]['location_id']);
//                    var_dump();exit;
                    $result[$i]['location_name'] = $this->session->userdata('location_info_arr')[$result[$i]['location_id']];
//                    var_dump($result[$i]['location_name']);
                }
            }

            $data = array(
                'code' => '0',
                'page_size' => $page_size,
                'page_number' => $page_number,
                'total_number' => $total_number->num,
                'error_msg' => '',
                'data' => $result
            );
        }
        echo json_encode($data);
    }

    //系统用户信息修改接口
    public function edit_user_info(){

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
        $user_name = $this->input->post('user_name', TRUE);
        $user_type = $this->input->post('user_type', TRUE);
        $user_status = $this->input->post('user_status', TRUE);
        $location_id = $this->input->post('location_id', TRUE);

        //返回结果
        $result = $this->admin_model->edit_user_info($id, $user_name, $user_type, $user_status, $location_id);
        log_message('info', '修改用户信息操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name') . '，操作IP地址为：' . $this->input->cookie('ip') . '，操作归属地为：' . $this->input->cookie('ipName'));
        log_message('info', '相关参数为：id：' . $id . '，user_name：' . $user_name . '，user_type：' . $user_type . '，user_status：' . $user_status . '，location_id：' . $location_id);

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
//        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
//            $error_msg = array(
//                'code'=>'10000',
//                'error_msg'=>'token校验失败'
//            );
//            echo json_encode($error_msg);exit;
//        }

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
        log_message('info', '相关参数为：id：' . $id . '，subsidy_name：' . $subsidy_name);

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
                            $this->admin_model->add_log($this->input->cookie('ip'), $_SESSION['login_name'] . '  ' . $_SESSION['user_name'], '添加车辆信息，车主姓名为：' . $item['B'], $this->input->cookie('ipName')); //记录日志
                        }
                        $success_num++;
                    } else {
                        log_message('info', '数据添加失败，失败IdCard为：' . $item['D']);
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
        log_message('info', '添加区域：' . $location_name . '，添加区域操作结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));
        log_message('info', '相关参数为：location_name：' . $location_name);

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
        log_message('info', '相关参数为：id：' . $id . '，location_name：' . $location_name);

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


    //已补贴数据导出接口
    public function export_info_list(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

//        //验证token，防止恶意请求
//        if(!$this->admin_model->verify_token($this->input->get('token', TRUE),$this->config->config['token_key'])){
//            $error_msg = array(
//                'code'=>'10000',
//                'error_msg'=>'token校验失败'
//            );
//            echo json_encode($error_msg);exit;
//        }

        //获取导出开始时间及结束时间
        $begin_time = $this->input->get('begin_time', TRUE);
        $end_time = $this->input->get('end_time', TRUE);

        //根据token获取用户区域码
        $headers = apache_request_headers();
        $token = $headers['authorization'];
        $tokens = explode('.', $token);
//        var_dump($headers['authorization']);exit;
//        var_dump($tokens);
        list($header64, $payload64, $sign) = $tokens;
//        var_dump($payload64);
        $payload = json_decode(base64_decode($payload64));
//        var_dump($tokens);
        $location_id = $payload->location_id;

        /*
         * 获取条数数据，此处需要判断是否为超管
         */
        if($location_id==0){
            $get_total_num_sql = "SELECT COUNT(*) AS num FROM t_vehicle_info WHERE subsidy_flag='1' AND subsidy_time BETWEEN '" . date("Y-m-d H:i:s", $begin_time / 1000) . "' AND '" . date("Y-m-d H:i:s", $end_time / 1000) . "'";
        }else{
            $get_total_num_sql = "SELECT COUNT(*) AS num FROM t_vehicle_info WHERE subsidy_flag='1' AND location_id='".$location_id."' AND subsidy_time BETWEEN '" . date("Y-m-d H:i:s", $begin_time / 1000) . "' AND '" . date("Y-m-d H:i:s", $end_time / 1000) . "'";
        }
//        $get_total_num_sql = "SELECT COUNT(*) AS num FROM t_vehicle_info WHERE subsidy_flag='1' AND location_id='0' AND subsidy_time BETWEEN '" . date("Y-m-d H:i:s", $begin_time / 1000) . "' AND '" . date("Y-m-d H:i:s", $end_time / 1000) . "'";
//        echo $get_total_num_sql;
        $total_number = $this->common_model->getTotalNum($get_total_num_sql, 'default');

//        echo $total_number->num;exit;

//        $result = $this->admin_model->export_info_list(, , $location_id);
        if ($total_number->num == 0) {
            $error_msg = array(
                'code' => '10017',
                'error_msg' => '没有查询到数据'
            );
            echo json_encode($error_msg);
        }else{
            $data = array(
                'code' => '0',
                'error_msg' => '',
                'download_link' => site_url() . '/api/download_info?begin_time=' . $begin_time . '&end_time=' . $end_time . '&token=' . $token
            );
            log_message('info', '查询到数据，返回download_Info接口相应地址：');
            log_message('info', '相关参数为：download_link：' . $data['download_link']);
            echo json_encode($data);
        }
    }

    //生成Excel流
    public function download_info(){

        //验证token，防止恶意请求
//        var_dump($this->input->get());exit;
//        echo $this->input->get('token', TRUE);exit;
        if(!$this->admin_model->verify_token($this->input->get('token', TRUE),$this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //获取导出开始时间及结束时间
        $begin_time = $this->input->get('begin_time', TRUE);
        $end_time = $this->input->get('end_time', TRUE);

        //根据token获取用户区域码
        $tokens = explode('.', $this->input->get('token', TRUE));
//        var_dump($tokens);
        list($header64, $payload64, $sign) = $tokens;
//        var_dump($payload64);
        $payload = json_decode(base64_decode($payload64));
//        var_dump($tokens);
        $location_id = $payload->location_id;

        log_message('info', '进入download_Info接口：');
        log_message('info', '相关参数为：begin_time：' . $begin_time . '，end_time：' . $end_time . '，location_id：' . $location_id);

        //获取数据
        $result = $this->admin_model->export_info_list(date("Y-m-d H:i:s", $begin_time / 1000), date("Y-m-d H:i:s", $end_time / 1000), $location_id);

        //开始生成Excel数据
        //新建Excel类
        //加载Excel插件
        $this->load->library('excel');
        $objPHPExcel = new PHPExcel();
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("ums")
            ->setLastModifiedBy("ums")
            ->setTitle("Office 2007 XLSX Document")
            ->setSubject("Office 2007 XLSX Document");

        // 设置标题栏
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '车主姓名')
            ->setCellValue('B1', '用户住址')
            ->setCellValue('C1', '身份证')
            ->setCellValue('D1', '车主电话')
            ->setCellValue('E1', '补贴时间');

        //设置字体加粗、字体大小及垂直居中
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:E1')->getFont()->setSize(12);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:E1')->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:E1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);////水平对齐
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:E1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);////垂直平对齐
        $objPHPExcel->setActiveSheetIndex(0)->getRowDimension(1)->setRowHeight(25);//行高

        //添加内容
        for ($i = 0; $i < count($result); $i++) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . ($i + 2), $result[$i]['contact'])
                ->setCellValue('B' . ($i + 2), $result[$i]['address'])
                ->setCellValue('C' . ($i + 2), ' '.$result[$i]['idcard'])
                ->setCellValue('D' . ($i + 2), $result[$i]['contacttel'])
                ->setCellValue('E' . ($i + 2), $result[$i]['subsidy_time']);

            //设置列宽度
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('A')->setWidth(10);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('B')->setWidth(25);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('C')->setWidth(30);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('D')->setWidth(15);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(20);

            //设置列垂直居中
//        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A' . ($i + 2) . ':O' . ($i + 2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('已补贴数据');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="已补贴导出数据-' . date("Ymdhis", time()) . '.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter->save('php://output');
    }

    //修改车辆信息所属区域接口
    public function edit_vehicle_location_id(){

        //验证token，防止恶意请求
        if(!$this->admin_model->auth_check($this->config->config['token_key'])){
            $error_msg = array(
                'code'=>'10000',
                'error_msg'=>'token校验失败'
            );
            echo json_encode($error_msg);exit;
        }

        //ID及所属区域位置ID
        $id = $this->input->post('id', TRUE);
        $location_id = $this->input->post('location_id', TRUE);

        //返回结果
        $result = $this->admin_model->edit_vehicle_location_id($id, $location_id);
        log_message('info', '修改车辆信息区域码结果：' . $result . '，操作人为：' . $this->session->userdata('login_name'));
        log_message('info', '相关参数为：id：' . $id . '，location_id：' . $location_id);

        //返回结果数组
        $data = array(
            'code' => '0',
            'error_msg' => ''
        );
        if (!$result) {
            $data = array(
                'code' => '10018',
                'error_msg' => $result
            );
        } else {
            $this->admin_model->add_log($this->input->cookie('ip'), $this->session->userdata('login_name'), '车辆信息区域码修改，修改车辆ID为：' . $id . '，修改新区域码为：' . $location_id, $this->input->cookie('ipName')); //记录日志
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