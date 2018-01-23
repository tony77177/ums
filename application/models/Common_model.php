<?php
/**
 * 公共模型
 * Created by PhpStorm.
 * User: TONY
 * Date: 13-12-26
 * Time: 下午9:38
 */

class Common_Model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    /**
     * 执行SQL
     * @param string $sql       SQL
     * @param string $db_name   数据库名
     * @param string $is_simple 是否为simple_query
     * @return mixed            结果集
     */
    function execQuery($sql, $db_name, $is_simple = FALSE) {
        $this->$db_name = $this->load->database($db_name, TRUE);
        if ($is_simple == TRUE) {
            $query = $this->$db_name->simple_query($sql);
        } else {
            $query = $this->$db_name->query($sql);
        }
        return $query;
    }

    /**
     * 更新操作
     * @param string $db_name       数据库名
     * @param string $where_column  WHERE列名
     * @param string $where_data    WHERE数据
     * @param string $upd_table     表名
     * @param string $upd_data      更新数据
     * @return boolean              TRUE OR FALSE
     */
    function updateData($db_name, $where_column, $where_data, $upd_table, $upd_data) {
        $this->$db_name = $this->load->database($db_name, TRUE);
        $this->$db_name->where($where_column, $where_data);
        $query = $this->$db_name->update($upd_table, $upd_data);
        return $query;
    }

    /**
     * 获取数据列表
     * @param string $sql       SQL
     * @param string $db_name   数据库名
     * @return mixed            结果集
     */
    public function getDataList($sql, $db_name) {
        $query = $this->execQuery($sql, $db_name);
        if ($query->num_rows()) {
            return $query->result_array();
        } else {
            return array();
        }
    }

    /**
     * 获取数据总条数
     * @param string $sql       SQL
     * @param string $db_name   数据库名
     * @return int              数据条数
     */
    public function getTotalNum($sql, $db_name) {
        $query = $this->execQuery($sql, $db_name);
//        print_r($query);
        $count = $query->row();
        return $count;
    }

}

/* End of file common_model.php */
/* Location: ./app/models/common_model.php */