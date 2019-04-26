<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5
 * Time: 11:18
 */

namespace app\api\controller;
use Firebase\JWT\JWT;
use app\api\service\LiaohaoService;
use think\Db;
use think\controller\Rest;
class Liaohao extends Rest{

    public function test(){
      $con=DB::connect([
          // 数据库类型
          'type'            => '\think\oracle\Connection',
          // 服务器地址
          'hostname'        => '192.111.111.222',
          // 数据库名
          'database'        => 'orcl',
          // 用户名
          'username'        => 'sales',
          // 密码
          'password'        => '123456',
          // 端口
          'hostport'        => '1521',
          // 连接dsn
          'dsn'             => '',
          // 数据库连接参数
          'params'          => [],
          // 数据库编码默认采用utf8
          'charset'         => 'utf8',
      ]);
        $sql = "select oeb01 from oeb_file";
        $orderInfo = $con->query($sql);
        dd($orderInfo);
    }

    public function getToken(){
        $jwt=new JWT();
       return retmsg(0,['token'=>$jwt->encode(['name'=>'cs','age'=>24],config('jwt_secret'))]);
    }

    /**
     * 根据订单查询料号
     * @param $searchString 订单号或工单号
     * @param $item 项次
     * @param $reload 0,1 1表示重新生成料号
     * @return array
     */
    public function getLiaohaoByOrder($searchString,$item,$reload=1,$uname='test'){
        $GLOBALS['uname'] = $uname;
        $oebColumn = getColumnName('oeb_file');
        $searchString = excel_trim($searchString);
        $item = excel_trim($item);
        $sql = "select $oebColumn from oeb_file where oeb01='$searchString' and oeb03='$item'";
        $orderInfo = DB::query($sql,true);
        if(empty($orderInfo)){
            return retmsg(-1,null,"$searchString 未查询到该单号信息！");
        }
        $orderInfo = $orderInfo[0];
        $oeaColumn = 'cast(OEA032 as  varchar2(255) ) as customer_name,order_db';
        $sql = "select $oeaColumn from oea_file where oea01='$searchString'";
        $oeaInfo = DB::query($sql, true);
        $orderInfo = array_merge($orderInfo, empty($oeaInfo[0])?[]:$oeaInfo[0]);
        //防火门的前门板厚度后门板厚度拆分
        if ($orderInfo['order_db'] == 'M8') {
            $orderInfo['dang_ci'] = $orderInfo['fhjibie'];//防火门档次取防火级别
        }
        $liaohaoService = new LiaohaoService();
        $orderProductMd5 = $liaohaoService->createOrderProductMd5($orderInfo);//产品配置信息生成的MD5
        $orderInfo['order_product_md5'] = $orderProductMd5;
        //判断该产品配置是否已经生成过料号
        $sql = "select id,liaohao,pinming as liaohao_pinming,guige as liaohao_guige  from bom_chengpin_liaohao where order_product_md5='$orderProductMd5' ";
        $chengpinlh = M()->query($sql,true);
        if(!empty($chengpinlh)){
            //删除已生成的料号信息
            if($reload){
                M()->execute("delete from bom_chengpin_liaohao where id=".$chengpinlh[0]['id']);
                M()->execute("delete from bom_banpin_liaohao where chengpin_liaohao_id=".$chengpinlh[0]['id']);
            }
            else{
                $chengpinlh = $chengpinlh[0];
                $banpinlh = M()->query("select * from bom_banpin_liaohao where chengpin_liaohao_id=".$chengpinlh['id'],true);
            }
        }
        //生成料号
        if(empty($chengpinlh)||$reload){
            $data = $liaohaoService->createLiaohaoByOrder($orderInfo);
            $chengpinlh = $data['chengpinlh'];
            $banpinlh = $data['banpinlh'];
        }
        $banpinlh = $liaohaoService->getBanpinTree($banpinlh);
        $liaohao = [
            'chengpinlh'=>$chengpinlh,
            'child'=>$banpinlh,
        ];
        //$data['order_info'] = $orderInfo;
        $getTitle = $this->getColumn($orderInfo,11);
        $result['liaohao_info'] = $liaohao;
        $result['order_info_download'] = $orderInfo;
        $result['order_info'] = $getTitle;
        //针对orderInfo重组标题栏
        //return $data;
        return retmsg(0,$result);
    }

    /**
     * 根据订单查询订单项次
     * @param $searchString 订单号或工单号
     * @return array
     */
    public function getOrderItem($searchString){
        $sql = "select DISTINCT oeb03 from oeb_file where oeb01='$searchString' or oeb_lot='$searchString' order by oeb03";
        $item = M()->query($sql,true);
        $result = array();
        foreach ($item as $k=>$v){
            $result[] = $v['oeb03'];
        }
        return retmsg(0,$result);
    }

    /**
     * 根据门类型获取属性名称
     * 控制一行显示的个数，默认为8个
     * @param $doorType
     * @param int $hang
     */
    public function getColumn($orderInfo, $hang = 11)
    {
//        print_r($orderInfo);
        $doorType = $orderInfo['order_db'];
        $columns = Db::query("select class_name as title,order_column_name as field from order_config_class where door_type='$doorType' order by sort_num", true);
        $hangs = ceil(count($columns)/$hang);
        $result = array();
        for ($i = 0; $i < $hangs; $i++) {
            $start_index = $i*$hang;
            if ($i == $hangs - 1) {
                $end_index = count($columns) - 1;
            } else {
                $end_index = ($i+1)*$hang - 1;
            }
            $temp = array();
            $temp['title'] = array();
            $temp['body'] = array();
            for ($j = $start_index; $j <=$end_index; $j++) {
                array_push($temp['title'], $columns[$j]['title']);
                array_push($temp['body'], $orderInfo[strtolower($columns[$j]['field'])]);
            }
            array_push($result, $temp);
        }
        return $result;
    }

}