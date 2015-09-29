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
     *  
      打出电话
      //电话状态变为忙碌
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="BUSY">
      <ext id="318" />
      </Event>
      //回铃事件
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="ALERT">
      <outer id="186" from="326" to="013693848899" trunk="568116531" callid="20666" />
      <ext id="326" />
      </Event>
      //呼叫应答
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="ANSWERED">
      <outer id="186" from="326" to="013693848899" trunk="568116531" callid="20666" />
      <ext id="326" />
      </Event>
      //通话结束事件
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="BYE">
      <ext id="326" />
      <outer id="186" from="326" to="013693848899" trunk="568116531" callid="20666" />
      <recording>20150824/326_013693848899_20150824-104404_20666</recording>
      </Event>
      //通话结束
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="BYE">
      <outer id="186" from="326" to="013693848899" trunk="568116531" callid="20666" />
      </Event>
      //话单数据
      <?xml version="1.0" encoding="utf-8" ?>
      <Cdr id="14420150824104517-0">
      <callid>20666</callid>
      <outer id="186" />
      <TimeStart>20150824104339</TimeStart>
      <Type>OU</Type>
      <Route>IP</Route>
      <CPN>326</CPN>
      <CDPN>013693848899</CDPN>
      <TimeEnd>20150824104517</TimeEnd>
      <Duration>73</Duration>
      <TrunkNumber>568116531</TrunkNumber>
      <Recording>20150824/326_013693848899_20150824-104404_20666</Recording>
      </Cdr>
      //分机由忙碌变为正在忙的时候
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="IDLE">
      <ext id="326" />
      </Event>
     * 
      //打入的电话
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="RING">
      <ext id="316" />
      <visitor from="13698612743" />
      </Event>
      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="BUSY">
      <ext id="316" />
      </Event>

      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="ANSWER">
      <ext id="316" />
      <visitor from="13698612743" />
      </Event>

      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="BYE">
      <ext id="316" />
      <visitor from="13698612743" />
      </Event>

      <?xml version="1.0" encoding="utf-8" ?>
      <Event attribute="IDLE">
      <ext id="316" />
      </Event>

      <?xml version="1.0" encoding="utf-8" ?>
      <Cdr id="21020150929140022-0">
      <callid>49395</callid>
      <TimeStart>20150929140015</TimeStart>
      <Type>IN</Type>
      <Route>XO</Route>
      <CPN>13698612743</CPN>
      <CDPN>316</CDPN>
      <TimeEnd>20150929140022</TimeEnd>
      <Duration>4</Duration>
      <TrunkNumber>88554123</TrunkNumber>
      </Cdr>

      <?xml version="1.0" encoding="utf-8" ?>
      <Cdr id="20920150929140022-0">
      <callid>49394</callid>
      <TimeStart>20150929135957</TimeStart>
      <Type>OU</Type>
      <Route>OP</Route>
      <CPN>13698612743</CPN>
      <CDPN>316</CDPN>
      <TimeEnd>20150929140022</TimeEnd>
      <Duration>4</Duration>
      <TrunkNumber>13698612743</TrunkNumber>
      <Recording>20150929/13698612743_316_20150929-140018_49394</Recording>
      </Cdr>
     */
    /**
     * 思路解析
     * 首先 拨打电话  接通之后 会接收到 event xml 类型是 answered 解析之后 添加到memcached 中 然后 挂断之后 会发送cdr 话单数据 
     * Array
      (
      [user_id] => 28
      [callid] => 20666
      [tel_num] => 13698612743
      [ext_num] => 326
      [type] => IN
      [cdr_info] => Array
      (
      [callid] => 20666
      [type] => OU
      [timestart] => 1440384219
      [timeend] => 1440384317
      [route] => IP
      [telnum] => 013693848899
      [ext_num] => 326
      [user_id] => 28
      [duration] => 73
      [trunknum] => 568116531
      [rec_name] => 20150824/326_013693848899_20150824-104404_20666
      [flag] => jinan
      [addtime] => 1440488492
      )
      )
     */

    /**
     * 接受om发送的请求
     * @param string $flag 标记是哪一个地方的请求   可能是济南的  也可能是河南的    可能分机号码是一样的
     * @access public
     * @todo 解析xml请求
     */
    public function rec($flag = 'jinan') {
        $xmldata = file_get_contents('php://input');
//        file_put_contents('a.txt', $xmldata, FILE_APPEND);
//        //打出去的 
//        $xmldata = <<<xmldata
//<Cdr id="14420150824104517-0">
//    <callid>20666</callid>
//    <outer id="186" />
//    <TimeStart>20150824104339</TimeStart>
//    <Type>OU</Type>
//    <Route>IP</Route>
//    <CPN>208</CPN>
//    <CDPN>013698612748</CDPN>
//    <TimeEnd>20150824104517</TimeEnd>
//    <Duration>73</Duration>
//    <TrunkNumber>568116531</TrunkNumber>
//    <Recording>20150824/326_013693848899_20150824-104404_20666</Recording>
//    </Cdr>
//xmldata;
//        $xmldata = <<<xmldata
//<Event attribute="ANSWERED">
//   <outer id="186" from="013698612748" to="208" trunk="568116531" callid="20666" />
//   <ext id="208" />
//</Event>
//xmldata;

        
        
//打进来的情况 会有两个cdr 数据
//这是总机状态信息
//<Cdr id="28220150929145023-0">
//  <callid>49220</callid>
//  <TimeStart>20150929145005</TimeStart>
//  <Type>OU</Type>
//  <Route>OP</Route>
//  <CPN>13698612743</CPN>
//  <CDPN>316</CDPN>
//  <TimeEnd>20150929145023</TimeEnd>
//  <Duration>2</Duration>
//  <TrunkNumber>13698612743</TrunkNumber>
//  <Recording>20150929/13698612743_316_20150929-145021_49220</Recording>
//</Cdr>
        
        
//xo  表示的是模拟中继   这种可以不用解析 直接跳出来
//<Cdr id="28320150929145023-0">
//  <callid>49222</callid>
//  <TimeStart>20150929145018</TimeStart>
//  <Type>IN</Type>
//  <Route>XO</Route>
//  <CPN>13698612743</CPN>
//  <CDPN>316</CDPN>
//  <TimeEnd>20150929145023</TimeEnd>
//  <Duration>2</Duration>
//  <TrunkNumber>88554123</TrunkNumber>
//</Cdr>
        
        
//打进来的
//        $xmldata = <<<xmldata
//<Cdr id="20920150929140022-0">
//      <callid>49394</callid>
//      <TimeStart>20150929135957</TimeStart>
//      <Type>OU</Type>
//      <Route>OP</Route>
//      <CPN>13698612743</CPN>
//      <CDPN>208</CDPN>
//      <TimeEnd>20150929140022</TimeEnd>
//      <Duration>4</Duration>
//      <TrunkNumber>13698612743</TrunkNumber>
//      <Recording>20150929/13698612743_316_20150929-140018_49394</Recording>
//      </Cdr>
//xmldata;
//        $xmldata = <<<xmldata
//          <Event attribute="ANSWER">
//          <ext id="208" />
//          <visitor from="13698612743" />
//          </Event>
//xmldata;
        $this->_classify_query($xmldata, $flag);
    }

    /**
     * 根据请求来 调用函数
     * @param string $xmldata 获取到的xml数据
     * @access private
     */
    private function _classify_query($xmldata, $flag) {
        include_once APPPATH . '/controllers/Memcache_manage.php';
        include_once APPPATH . '/controllers/Xml_resolve.php';
        $xml_obj = simplexml_load_string($xmldata);
        //获取根节点
        $root_name = $xml_obj->getName();
        //根部节点的属性数值
        $root_attr = $xml_obj->attributes();
        switch ($root_name) {
            case 'Cdr':
                //调用 呼叫详细信息报告  解析控制器
                $cdr_obj = new Xml_resolve();
                //解析成为array
                $data = $cdr_obj->resolve($xml_obj, $flag);
                //获取   分机号码=>用户的id
                $telnum_userinfo = $this->get_telnum_user_info($flag);
                //$cdr_data  表示 本次请求的数据  包含没有打通的电话   $stauts表示添加成功失败
                $cdr_data = $this->R->resolve_cdr_data($data, $telnum_userinfo, $flag);
                //表示提交成功的   存储在数据库中
                $user_id = $cdr_data['user_id'];
                if (!$user_id) {
                    file_put_contents('error.log', "{$cdr_data['ext_num']}没有绑定职员");
                    return;
                }
                //往memcache 中存储数据  
                $mem = new Memcache_manage();
                $mem->memcache();
                //获取应答的模式下的信息
                $answered_data = $mem->get($user_id);
                //根据answered_data数据获取数据
                $cdr_data['cus_id'] = $answered_data['cus_id'];
                $cdr_data['contact_id'] = $answered_data['contact_id'];
                if ($cdr_data['duration']) {
                    //成功的话 这个返回值是 $status
                    $status = $this->R->insert_cdr_data($cdr_data);
                    if (!$status) {
                        file_put_contents('error.log', "cdr数据解析添加到数据库失败  $xmldata");
                        return;
                    } else {
                        $cdr_data['id'] = $status;
                    }
                }
                //获取 answered 数据之后
                $cdr_callid = $cdr_data['callid'];
                $answered_callid = $answered_data['callid'];
                if ($answered_callid) {
                    //打出的电话数据分析
                    if ($cdr_callid == $answered_callid) {
                        $answered_data['cdr_info'] = $cdr_data;
                        $mem->set_expire($user_id, $answered_data, 28800);
                        print_r($answered_data);
                    } else {
                        //如果不相等的 删除该键值
                        $mem->delete($user_id);
                    }
                } else {
                    //这个是打进来的电话   没有callid的情况
                    $answered_data['cdr_info'] = $cdr_data;
                    $mem->set_expire($user_id, $answered_data, 28800);
                    print_r($answered_data);
                }
            case 'Event':
                //表明时事件请求
                $event_obj = new Xml_resolve();
                $data = $event_obj->resolve($xml_obj, $flag);
//                print_r($data);
                switch ($root_attr) {
                    case'ANSWERED':
                        //获取   分机号码=>用户的id  分机打出去的
                        $telnum_userinfo = $this->get_telnum_user_info($flag);
                        //应答类型的操作   需要匹配出来用户的id
                        $answered_data = $this->R->resolve_answered_data($data, $telnum_userinfo, $flag);
                        //然后存储数据到memcache
                        if (!$answered_data['user_id']) {
                            //表示没有匹配到数据
                            file_put_contents('error.log', "{$answered_data['ext_num']}没有绑定职员");
                            return;
                        }
                        $mem = new Memcache_manage();
                        $mem->memcache();
                        $key = $answered_data['user_id'];
                        $mem->set_expire($key, $answered_data, 28800);
                        //还要把每一天的第一个数据添加到数据库中
                        //数据库中存储第一个信息
                        $first_tel_key = $key . 'first_tel';
                        $first_tel = $mem->get($first_tel_key);
                        if (!$first_tel) {
                            //有种可能是已经添加到数据库中但是mem中没有
                            //从数据库中查看是不是含有该条记录  根据user_id 还有 根据时间
                            $is_add = $this->R->get_first_tel_data($key);
                            if ($is_add) {
                                //之前数据库中添加了 只是在mem中不存在
                                $mem->set_expire($first_tel_key, '1', 28800);
                            } else {
                                //数据库中没有添加  今天的第一次
                                $status = $this->R->insert_first_tel_data($answered_data);
                                //如果没有
                                if (!$status) {
                                    file_put_contents('error.log', "第一个电话添加失败 $xmldata");
                                    return;
                                }
                                $mem->set_expire($first_tel_key, '1', 28800);
                            }
                        }
                        break;
                    case 'ANSWER':
                        //这个请求是外部给分机的电话号码
                        //获取   分机号码=>用户的id
                        $telnum_userinfo = $this->get_telnum_user_info($flag);
                        //应答类型的操作   需要匹配出来用户的id
                        $answer_data = $this->R->resolve_answer_data($data, $telnum_userinfo, $flag);
                        //然后存储数据到memcache
                        if (!$answer_data['user_id']) {
                            //表示没有匹配到数据
                            file_put_contents('error.log', "{$answer_data['ext_num']}没有绑定职员");
                            return;
                        }
                        $mem = new Memcache_manage();
                        $mem->memcache();
                        $key = $answer_data['user_id'];
                        $mem->set_expire($key, $answer_data, 28800);
                        break;
                    default:
                        //不执行操作
                        return;
                        break;
                }
                break;
            default:
                break;
        }
    }

    /**
     * memcache操作   获取 分机号码=> user_id 数据  用于匹配分机号码的id       首先从memcached 中获取数据  如果没有的  从数据库中获取  
     * @param string $flag 标志是哪一个地方的请求  比如 是河南的或者是 济南的
     * @access private
     */
    private function get_telnum_user_info($flag) {
        $mem = new Memcache_manage();
        $mem->memcache();
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

}
