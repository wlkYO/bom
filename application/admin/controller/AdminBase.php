<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/27
 * Time: 16:56
 */

namespace app\admin\controller;

use think\controller\Rest;

class AdminBase extends Rest
{
    protected $user;
    public function __construct(){
        $this->user=$GLOBALS['user'];
    }
}