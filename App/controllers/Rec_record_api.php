<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 接受讯时  om的 请求  api请求的入口文件
 * @todo 解析数据
 */
class Rec_record_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        //加载模型信息
        $this->load->model('Rec_record_model', 'R');
    }

    /**
     * 接受om发送的请求
     * @param string $flag 标记是哪一个地方的请求   可能是济南的  也可能是河南的    可能分机号码是一样的
     * @access public
     * @todo 解析xml请求
     */
    public function rec($flag = 'jinan') {
        $xmldata = file_get_contents('php://input');
//        file_put_contents('a.txt', $xmldata, FILE_APPEND);
//        $xmldata = <<<xmldata
//<Cdr id="36120150806172024-0">
//  <callid>45215</callid>
//  <outer id="159" />
//  <TimeStart>20150806172007</TimeStart>
//  <Type>OU</Type>
//  <Route>IP</Route>
//  <CPN>211</CPN>
//  <CDPN>013698612744</CDPN>
//  <TimeEnd>20150806172023</TimeEnd>
//  <Duration>8</Duration>
//  <TrunkNumber>86429163</TrunkNumber>
//  <Recording>20150806/339_015552777889_20150806-172016_45215</Recording>
//</Cdr>
//xmldata;
        $this->_classify_query($xmldata, $flag);
    }

    /**
     * 根据请求来 调用函数
     * @param string $xmldata 获取到的xml数据
     * @access private
     */
    private function _classify_query($xmldata, $flag) {
        $xml_obj = simplexml_load_string($xmldata);
        //获取根节点
        $root_name = $xml_obj->getName();
        //根部节点的属性数值
        $root_attr = $xml_obj->attributes();
        switch ($root_name) {
            case 'Cdr':
                //调用 呼叫详细信息报告  解析控制器
                include_once APPPATH . '/controllers/Cdr_resolve.php';
                include_once APPPATH . '/controllers/Memcache_manage.php';
                $cdr_obj = new Cdr_resolve();
                //解析成为array
                $data = $cdr_obj->resolve($xml_obj, $flag);
                //获取   分机号码=>用户的id
                $telnum_userinfo = $this->mem_manage($root_name, $flag);
                //$cdr_data  表示 本次请求的数据  包含没有打通的电话   $stauts表示添加成功失败
                list($cdr_data, $status) = $this->R->insert_cdr_data($data, $telnum_userinfo, $flag);
                if ($status) {
                    //表示提价成功的   存储在数据库中
                    $user_id = $cdr_data['user_id'];
                    if ($user_id) {
                        //往memcache 中存储数据  
                        $mem = new Memcache_manage();
                        $mem->memcache();
                        $key = "{$user_id}";
                        $cdr_mem_data = $mem->get($key);
                        if ($cdr_mem_data) {
                            //最大的数值
                            $memdata['max_id'] = $status;
                            //今天的电话总的数量
                            $memdata['count'] = $cdr_mem_data['count'] + 1;
                        } else {
                            //获取本日的数据数量
                            $count = $this->R->get_usercdr_today_count($user_id);
                            $memdata['max_id'] = $status;
                            $memdata['count'] = $count;
                        }
                        //有效期限 8小时
                        $mem->set_expire($key, $memdata, 28800);
                    }
                }
            case 'Event':
                //然后根据当前的操作请求
                break;
            default:
                break;
        }
    }

    /**
     * memcache操作
     * @param string $root_name 根节点的信息
     * @param string $flag 标志是哪一个地方的请求  比如 是河南的或者是 济南的
     * @access private
     */
    private function mem_manage($root_name, $flag) {
        $mem = new Memcache_manage();
        $mem->memcache();
        if ($root_name == 'Cdr') {
            //首先从数据库中取出数据   看看是不是存在   不存在的话执行 sql 操作 然后添加到memcache中
            $key = $flag . '_telnum_userid_key';
            $data = array();
            $data = $mem->get($key);
            if (!$data) {
                $data = $this->R->get_user_info($flag);
                $mem->set($key, $data);
            }
            return $data;
        }
        return array();
    }

}
