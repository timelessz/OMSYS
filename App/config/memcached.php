<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  | -------------------------------------------------------------------------
  | Memcached settings
  | -------------------------------------------------------------------------
  | Your Memcached servers can be specified below.
  | See: http://codeigniter.com/user_guide/libraries/caching.html#memcached
  |
 */
$config = array(
    //现在是单独的memcache 服务器  以后可以添加多个  只需要 mem 对象添加 addserver
    'default' => array(
        'hostname' => '127.0.0.1',
        'port' => '11211',
        'weight' => '1',
        //100分钟
        'expire' => '6000',
        'memcache_prefix'=>'',
    ),
);
