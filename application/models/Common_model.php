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

    /*
     *提供上传用户信息接口使用，主要负责用户上传数据过滤等操作
     */
    function insertQuery($db_name, $table_name, $item)
    {
        $this->$db_name = $this->load->database($db_name, TRUE);
        $data = array(
            'location_id' => $this->$db_name->escape($item['A']),
            'contact' => $this->$db_name->escape($item['B']),
            'contacttel' => $this->$db_name->escape($item['C']),
            'idcard' => $this->$db_name->escape($item['D']),
            'licenseplate' => $this->$db_name->escape($item['E']),
            'frid' => $this->$db_name->escape($item['F']),
            'maincard' => $this->$db_name->escape($item['G']),
            'brand' => $this->$db_name->escape($item['H']),
            'color' => $this->$db_name->escape($item['I']),
            'cartype' => $this->$db_name->escape($item['J']),
            'address' => $this->$db_name->escape($item['K'])
        );
//        print_r($data);exit;
        return $this->$db_name->insert($table_name, $data);

//        if ($is_simple == TRUE) {
//            $query = $this->$db_name->simple_query($sql);
//        } else {
//            $query = $this->$db_name->query($sql);
//        }
//        return $query;
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

    function export_info_list($_search_info, $_result_info)
    {
        //新建Excel类
        $objPHPExcel = new PHPExcel();
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("dts")
            ->setLastModifiedBy("dts")
            ->setTitle("Office 2007 XLSX Document")
            ->setSubject("Office 2007 XLSX Document");


        // 设置标题栏
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '查询内容')
            ->setCellValue('B1', 'IP地址')
            ->setCellValue('C1', '国家')
            ->setCellValue('D1', '省会或直辖市')
            ->setCellValue('E1', '地区或城市')
            ->setCellValue('F1', '学校或单位')
            ->setCellValue('G1', '运营商')
            ->setCellValue('H1', '纬度')
            ->setCellValue('I1', '经度')
            ->setCellValue('J1', '时区一')
            ->setCellValue('K1', '时区二')
            ->setCellValue('L1', '中国行政区划代码')
            ->setCellValue('M1', '国际电话代码')
            ->setCellValue('N1', '国家二位代码')
            ->setCellValue('O1', '世界大洲代码');

        //设置字体加粗、字体大小及垂直居中
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:O1')->getFont()->setSize(12);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:O1')->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:O1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);////水平对齐
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:O1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);////垂直平对齐
        $objPHPExcel->setActiveSheetIndex(0)->getRowDimension(1)->setRowHeight(25);//行高

        //添加内容
        for ($i = 0; $i < count($_search_info); $i++) {
            if ($_result_info[$i]->ret == 'ok') {
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . ($i + 2), $_search_info[$i])
                    ->setCellValue('B' . ($i + 2), gethostbyname($_search_info[$i]))
                    ->setCellValue('C' . ($i + 2), $_result_info[$i]->data[0])
                    ->setCellValue('D' . ($i + 2), $_result_info[$i]->data[1])
                    ->setCellValue('E' . ($i + 2), $_result_info[$i]->data[2])
                    ->setCellValue('F' . ($i + 2), $_result_info[$i]->data[3])
                    ->setCellValue('G' . ($i + 2), $_result_info[$i]->data[4])
                    ->setCellValue('H' . ($i + 2), $_result_info[$i]->data[5])
                    ->setCellValue('I' . ($i + 2), $_result_info[$i]->data[6])
                    ->setCellValue('J' . ($i + 2), $_result_info[$i]->data[7])
                    ->setCellValue('K' . ($i + 2), $_result_info[$i]->data[8])
                    ->setCellValue('L' . ($i + 2), $_result_info[$i]->data[9])
                    ->setCellValue('M' . ($i + 2), $_result_info[$i]->data[10])
                    ->setCellValue('N' . ($i + 2), $_result_info[$i]->data[11])
                    ->setCellValue('O' . ($i + 2), $_result_info[$i]->data[12]);
            } else {
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . ($i + 2), $_search_info[$i])
                    ->setCellValue('B' . ($i + 2), gethostbyname($_search_info[$i]))
                    ->setCellValue('C' . ($i + 2), '查询失败，请确认数据正确性');
                $objPHPExcel->setActiveSheetIndex(0)->mergeCells('C' . ($i + 2) . ':O' . (($i + 2)));//合并单元格
            }

            //设置列宽度
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('A')->setAutoSize(true);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('B')->setAutoSize(true);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('D')->setWidth(17);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(14);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(15);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('J')->setWidth(15);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('M')->setWidth(18);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('N')->setWidth(18);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('O')->setWidth(18);

            //设置列垂直居中
//        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A' . ($i + 2) . ':O' . ($i + 2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('查询结果');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="批量查询结果-' . date("Ymdhis", time()) . '.xls"');
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

}

/* End of file common_model.php */
/* Location: ./app/models/common_model.php */