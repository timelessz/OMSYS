<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 1 呼叫的详细记录报告话单 解析记录  
 * 2 事件类型的记录解析
 * @author timeless
 */
class Xml_resolve {

    /**
     * 解析Xml数据   形成数组    
     * @param obj $xml_obj  要解析的xml文档对象
     * @param flag $flag 标签表示是什么地方的
     * @access public
     * @return array 返回的数值类型如下
     */
//        $demo = Array(
//            'callid' => Array('val' => 8216),
//            'outer' => Array('val' => '', 'attr' => Array('id' => 24)),
//            //标志是济南还是郑州
//            'flag' => 'jinan'
//        );
    public function resolve($xml_obj, $flag) {
        $data = array();
        foreach ($xml_obj->children() as $child) {
            //获取子元素的名称
            $name = $child->getName();
            $val = (string) $child;
            $data[$name]['val'] = empty($val) ? '' : $val;
            //循环获取子元素的属性信息 
            $attr = array();
            foreach ($child->attributes() as $k => $v) {
                $attr[$k] = (string) $v;
            }
            empty($attr) ? '' : $data[$name]['attr'] = $attr;
        }
        $data['flag'] = $flag;
        return $data;
    }
    
}
