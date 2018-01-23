<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH."third_party/PHPExcel/PHPExcel.php";

/**
 * PHPExcel类
 * Created by PhpStorm.
 * User: TONY
 * Date: 2017-1-7
 * Time: 23:10
 */
class Excel extends PHPExcel
{
    public function __construct()
    {
        parent::__construct();
    }

}