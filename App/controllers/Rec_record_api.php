<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 接受讯时  om的 请求
 * @todo 解析数据实现
 */
class Rec_record_api extends CI_Controller {

    /**
     * 接受om发送的请求 
     * @access public
     * @todo 解析xml请求
     */
    public function rec() {
        $raw_post_data = file_get_contents('php://input');
        file_put_contents('a.txt', $raw_post_data, FILE_APPEND);
    }
       

}
