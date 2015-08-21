<?php

/**
 * 记录数据
 * @author timeless
 */
class Rec_record_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_user_info($flag) {
        $this->db->select('user_id,telnum');
        $query = $this->db->get('user_account');
        $this->config->load('mydefine', FALSE, TRUE);
        //获取配置文件
        $telnum_prefix = $this->config->item($flag . '_telnum_prefix');
        $info = array();
        foreach ($query->result() as $row) {
            $user_id = $row->user_id;
            $telnum = $row->telnum;
            if (strpos($telnum, $telnum_prefix) !== FALSE) {
                $arr = explode('-', $telnum);
                if (count($arr) == 3 && $arr[2] != '') {
                    $info[$arr[2]] = $user_id;
                }
            }
        }
        //标志是哪一个地方的问题
        $data[$flag] = $info;
        return $data;
    }

    /**
     * 解析Cdr话单数据然后添加到数据库
     * @param array $data 数组形式的 话单信息
     * @param array $telnum_userinfo   电话号码=>用户的id 
     * @param string $flag 标记属于什么地方 
     * @access public
     */
    public function insert_cdr_data($data, $telnum_userinfo, $flag) {
        $num_userinfo = $telnum_userinfo[$flag];
        if (!empty($num_userinfo)) {
            //通话的唯一标识
            $cdr_data['callid'] = $data['callid']['val'];
            //$cdr_data['visitor'] = $data['visitor']['attr']['id'];
            //类型 IN 打入 OU 打出  FI 呼叫转移入 FW 呼叫转移出 LO 内部通话 CB 双向外呼
            $cdr_data['type'] = $data['Type']['val'];
            $cdr_data['timestart'] = strtotime($data['TimeStart']['val']);
            $cdr_data['timeend'] = strtotime($data['TimeEnd']['val']);
            $cdr_data['route'] = $data['Route']['val'];
            if ($cdr_data['type'] == 'OU' || $cdr_data['type'] == 'FW') {
                //telnum  打入的或者打出的客户    
                $cdr_data['telnum'] = $data['CDPN']['val'];
                //分机号码
                $cdr_data['ext_num'] = $data['CPN']['val'];
            } else {
                //telnum  打入的或者打出的客户       打入打出的电话要交换顺序
                $cdr_data['telnum'] = $data['CPN']['val'];
                //分机号码
                $cdr_data['ext_num'] = $data['CDPN']['val'];
            }
            //还需要判断一下是不是存在这个信息
            $ext_num = $cdr_data['ext_num'];
            $cdr_data['user_id'] = array_key_exists($ext_num, $num_userinfo) ? $num_userinfo[$ext_num] : 0;
            //通话的时间长度
            $cdr_data['duration'] = $data['Duration']['val'];
            //中继号码
            $cdr_data['trunknum'] = $data['TrunkNumber']['val'];
            //记录文件的名字
            $cdr_data['rec_name'] = $data['Recording']['val'];
            $cdr_data['flag'] = $data['flag'];
            $cdr_data['addtime'] = time();
        } else {
            //异常问题
            file_put_contents('error.log', "memcache 或者 数据库获取分机号码=>user_id  失败。\r\n", FILE_APPEND);
            //以后可以发送邮件报警  说明问题。
            exit;
        }
        if ($cdr_data['duration']) {
            //这个地方要实现匹配客户联系人 还有客户信息需要切割客户
            $telnum = $this->get_telnum($cdr_data['telnum']);
            $this->get_customerinfo_bytelnum($cdr_data, $telnum);
//            print_r($cdr_data);
            //新的添加的数据  contact_id  cus_id
             $this->db->insert('voice_cdr', $cdr_data);
            return array($cdr_data, $this->db->insert_id());
        }
        return array($cdr_data, false);
    }

    /**
     * 截取手机号码
     * @param string $telnum 根据规则截取手机号码   因为手机号码有可能会重复所以现在要根据分机号码来匹配
     * @access private
     * @return telnum 截取之后的手机号码;
     */
    private function get_telnum($telnum) {
        //切割手机号码
        //首先判断长度
        $len = strlen($telnum);
        switch ($len) {
            case 12:
                //手机号码  或者是带着4位区号的号码
                //开头是 01的  是 手机号码    其他的截取后边的八位  
                if (strpos($telnum, '01') === 0) {
                    $telnum = substr($telnum, -11);
                } else {
                    $telnum = substr($telnum, -8);
                }
                break;
            case 11:
                // 北京010，广州020，上海021，天津022，重庆023，沈阳024，南京025，武汉027，成都028，西安029  
                //1 手机号码   或者是区号+号码   判断开头是不是0    2 还有可能是八位的 北京号码 天津号码 或者是其他的大城市号码 +三位数的区号   还有北京的;  3还有  一般的地级市   0538-8898056 
                //4还有400号码
                //开头时判断开头时候是不是零      是零 的花然后判断开头是 01 或者 02 的  01 02表示是三位的区号  截取后边八位   其他的 截取后边的 七位
                if (strpos($telnum, '0') === 0) {
                    if (strpos($telnum, '01') === 0 || strpos($telnum, '02') === 0) {
                        $telnum = substr($telnum, -8);
                    } else if (strpos($telnum, '04') !== 0) {
                        $telnum = substr($telnum, -7);
                    } else {
                        $telnum = substr($telnum, -10);
                    }
                }
                break;
            case 9:
                //网络电话  不处理请求
                break;
            case 8:
                //直接就是电话 不处理
                break;
            case 7:
            //直接也是电话
            default:
                exit;
                break;
        }
        //返回电话号码
        file_put_contents('tel.txt', $telnum . '|', FILE_APPEND);
        return $telnum;
    }

    /**
     * 根据电话号码获取 客户联系人_id 还有客户id
     * @param array $cdr_data 受到的话单请求数组
     * @param string $telnum 截取到的电话号码
     * @return 
     */
    private function get_customerinfo_bytelnum(&$data, $telnum) {
        $this->db->like('tel', $telnum);
        $this->db->or_like('phone', $telnum);
        $this->db->limit(0, 1);
        $this->db->select('id,cus_id');
        $query = $this->db->get('customer_contact');
        foreach ($query->result() as $row) {
            if ($row) {
                $data['cus_id'] = $row->cus_id;
                $data['contact_id'] = $row->id;
            }
        }
    }

    /**
     * 获取cdr记录最大的数据
     * @param int $user_id 用户_id
     * @return int 最大的数值   没有查询到的返回0
     */
    public function get_usercdr_max_id($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->select_max('id');
        $max_id = $this->db->get('voice_cdr');
        foreach ($max_id->result() as $rows) {
            $max = $rows->id;
        }
        return empty($max) ? 0 : $max;
    }

    /**
     * 获取本日的数据数量
     * @param int $user_id 用户_id
     * @return int 数量
     */
    public function get_usercdr_today_count($user_id) {
        $starttime = strtotime(date('Y-m-d', time()));
        $endtime = $starttime + 86400;
        $where = "addtime > $starttime and addtime < $endtime and user_id=$user_id";
        $this->db->where($where);
        $this->db->from('voice_cdr');
        return $this->db->count_all_results();
    }

//    function get_last_ten_entries() {
//        $query = $this->db->get('entries', 10);
//        return $query->result();
//    }
//
//    function insert_entry() {
//        $this->title = $_POST['title']; // 请阅读下方的备注
//        $this->content = $_POST['content'];
//        $this->date = time();
//        $this->db->insert('entries', $this);
//    }
//
//    function update_entry() {
//        $this->title = $_POST['title'];
//        $this->content = $_POST['content'];
//        $this->date = time();
//        $this->db->update('entries', $this, array('id' => $_POST['id']));
//    }
}
