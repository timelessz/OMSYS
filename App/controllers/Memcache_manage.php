<?php

/**
 * memcache 操作实现  因为要频繁的从数据库中获取用户的  user_id=>分机号码数据   所以现在要使用memcache 缓存减轻数据库的压力 
 * @author timeless
 */
class Memcache_manage {

    //CI原始的信息
    private $_ci;
    private $_memcache_prefix;
    private $host;
    private $port;
    private $expire;
    private $weight;

    /**
     * 读取配置文件信息然后更新
     * @access public
     */
    public function memcache($flag = 'default') {
        //要你自定义的类库中访问CodeIgniter的原始资源,你必须使用 get_instance() 函数.这个函数返回一个CodeIgniter super object.
        $this->_ci = &get_instance();
        //记载memcache 缓存配置
        $this->_ci->config->load('memcached', FALSE, TRUE);
        //获取配置文件
        $default_conf = $this->_ci->config->item('default');
        $this->host = $default_conf['hostname'];
        $this->port = $default_conf['port'];
        $this->expire = $default_conf['expire'];
        $this->weight = $default_conf['weight'];
        $this->_memcache_prefix = $default_conf['memcache_prefix'];
        $this->connected_server = array();
        $this->_connect();
    }

    /**
     * 连接memcache 数据库
     * @access private
     */
    private function _connect() {
        if (function_exists('memcache_connect')) {
            $this->cache = new Memcache;
            $this->_connect_memcached();
        }
    }

    /**
     * 添加memcache 服务器
     * @access private
     */
    private function _connect_memcached() {
        $error_display = ini_get('display_errors');
        $error_reporting = ini_get('error_reporting');
        if ($this->cache->addServer($this->host, $this->port, TRUE, $this->weight)) {
            $this->connected_server[] = $this->host;
        }
        ini_set('error_reporting', $error_reporting);
    }

    public function get($key) {
        if (empty($this->connected_server)) {
            return false;
        }
        return $this->cache->get($this->key_name($key));
    }

    public function set($key, $data) {
        if (empty($this->connected_server)) {
            return false;
        }
        return $this->cache->set($this->key_name($key), $data, 0, $this->expire);
    }

    public function set_expire($key, $data, $expire) {
        if (empty($this->connected_server)) {
            return false;
        }
        return $this->cache->set($this->key_name($key), $data, 0, $expire);
    }
    
    public function replace($key, $data) {
        if (empty($this->connected_server)) {
            return false;
        }
        return $this->cache->replace($this->key_name($key), $data, 0, $this->expire);
    }

    public function delete($key, $when = 0) {
        if (empty($this->connected_server)) {
            return false;
        }
        return $this->cache->delete($this->key_name($key), $when);
    }

    public function flush() {
        return $this->cache->flush();
    }

    /**
     * @Name: 生成md5加密后的唯一键值
     * @param:$key key
     * @return : md5 string
     * */
    private function key_name($key) {
        return md5(strtolower($this->_memcache_prefix . $key));
    }

}
