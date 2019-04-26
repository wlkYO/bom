<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/28
 * Time: 16:24
 */

namespace app\behavior;

class SendHeader{

    public function appInit (){
        header("Access-Control-Allow-Origin: *");
    }
}
