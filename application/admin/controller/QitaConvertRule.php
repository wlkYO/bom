<?php
namespace app\admin\controller;

use app\admin\service\BaseCodeService;
use app\api\service\Test;
use Firebase\JWT\JWT;
use think\Db;

/**
 * bom系统各转换规则表
 * Class QitaConvertRule
 * @package app\admin\controller
 */
class QitaConvertRule extends AdminBase
{
    public $ruleArr = array();
    public function __construct()
    {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        $sql = "select distinct(rule_name) as rule_name,order_column_name from bom_orderval_convert";
        $result=DB::query($sql,true);
        foreach ($result as $key => $val) {
            $this->ruleArr[$val['rule_name']]['column'] = $val['order_column_name'];
        }
        /*$this->ruleArr['底框材料']['column']='dkcailiao,底框';
        $this->ruleArr['后门板厚度']['column']='hmenbhd';
        $this->ruleArr['门框材料']['column']='门框';
        $this->ruleArr['底框厚度']['column']='底框厚度';
        $this->ruleArr['门框铰链']['column']='门框铰链';
        $this->ruleArr['锁具']['column']='suoju';
        $this->ruleArr['门框门扇材质']['column']='menkuangcz,menshancz';
        $this->ruleArr['窗花']['column']='chuanghua';
        $this->ruleArr['门框厚度']['column']='mkhoudu';
        $this->ruleArr['门扇铰链']['column']='门扇铰链';
        $this->ruleArr['门扇半品门框']['column']='门扇半品门框';
        $this->ruleArr['前门板厚度']['column']='qmenbhd';
        $this->ruleArr['表面特殊要求']['column']='biaomiantsyq';
        $this->ruleArr['门框要求']['column']='menkuangyq';
        $this->ruleArr['包装品牌方式']['column']='baozhpack,baozhuangfs';*/
    }

    //http://192.111.111.127/bom/public/index.php/admin/qita_convert_rule/getConvertRuleName
    /**
     * 获取包装方式等的规则名称
     * @return array
     */
    public function getConvertRuleName($doorType='M9')
    {
//        $sql="select distinct(rule_name) as rule_name,order_column_name from bom_orderval_convert where door_type='$doorType' order by rule_name";
//        $result=DB::query($sql,true);
        //M9
        $data1=[
            ['rule_name'=>'包装对应关系','order_column_name'=>'baozhpack,baozhuangfs'],
            ['rule_name'=>'材质显示规则','order_column_name'=>'menkuangcz,menshancz'],
            ['rule_name'=>'窗花显示规则','order_column_name'=>'chuanghua'],
            ['rule_name'=>'底框材料显示规则','order_column_name'=>'dkcailiao'],
            ['rule_name'=>'底框厚度显示规则','order_column_name'=>'mkhoudu'],
            ['rule_name'=>'底框显示规则','order_column_name'=>'底框'],
            ['rule_name'=>'后门板厚度显示规则','order_column_name'=>'hmenbhd'],
            ['rule_name'=>'门框铰链显示规则','order_column_name'=>'门框铰链'],
            ['rule_name'=>'门框显示规则','order_column_name'=>'门框'],
            ['rule_name'=>'门框要求显示规则','order_column_name'=>'menkuangyq'],
            ['rule_name'=>'门扇半品门框显示规则','order_column_name'=>'门扇半品门框'],
            ['rule_name'=>'门扇铰链显示规则','order_column_name'=>'门扇铰链'],
            ['rule_name'=>'前门板厚度显示规则','order_column_name'=>'qmenbhd'],
            ['rule_name'=>'锁具显示规则','order_column_name'=>'suoju'],
        ];
        //M8
        $data2=[
            ['rule_name'=>'包装对应关系','order_column_name'=>'baozhpack,baozhuangfs'],
            ['rule_name'=>'表面代码对应中文名称规则','order_column_name'=>'biaomiantsyq'],
            ['rule_name'=>'材质显示规则','order_column_name'=>'menkuangcz,menshancz'],
            ['rule_name'=>'窗花显示规则','order_column_name'=>'chuanghua'],
            ['rule_name'=>'底框材料显示规则','order_column_name'=>'dkcailiao'],
            ['rule_name'=>'底框厚度显示规则','order_column_name'=>'底框厚度'],
            ['rule_name'=>'后门板厚度显示规则','order_column_name'=>'hmenbhd'],
            ['rule_name'=>'门框铰链显示规则','order_column_name'=>'门框铰链'],
            ['rule_name'=>'门框显示规则','order_column_name'=>'门框'],
            ['rule_name'=>'门框要求显示规则','order_column_name'=>'menkuangyq'],
            ['rule_name'=>'门扇半品门框显示规则','order_column_name'=>'门扇半品门框'],
            ['rule_name'=>'门扇铰链显示规则','order_column_name'=>'门扇铰链'],
            ['rule_name'=>'前门板厚度显示规则','order_column_name'=>'qmenbhd'],
            ['rule_name'=>'锁具显示规则','order_column_name'=>'suoju'],
        ];
        if ($doorType == 'M9') {
            return retmsg(0,$data1);
        } elseif ($doorType == 'M8') {
            return retmsg(0,$data2);
        } else {
            return retmsg(-1,null,'暂未开放，请等候...');
        }
    }

    /**
     * 获取转换规则
     * @param string $DoorType
     * @param string $Rule_Name
     * @return array
     */
    public function getConvertRule($doorType='',$ruleName='')
    {
      $sql="SELECT ORDER_VAL,CONVERTED_VAL from BOM_ORDERVAL_CONVERT where DOOR_TYPE='$doorType' and RULE_NAME='$ruleName'";
      $result=DB::query($sql);
      return retmsg(0,$result);
    }

    /**
     * 添加转换规则
     * @return array
     */
    public function addConvertRule()
    {
        $postData=json_decode(file_get_contents("php://input"),true);
//        $postData=json_decode('{"rule_name":"底框材料","door_type":"M9","order_column_name":"dkcailiao,底框","order_val":"testaaaa","converted_val":"test"}',true);
        $data=$postData;
        $rule_name=$data['rule_name'];
        $door_type=$data['door_type'];
        $order_column_name=$data['order_column_name'];
        $order_val=$data['order_val'];
        $converted_val=$data['converted_val'];
        $exist = "select * from BOM_ORDERVAL_CONVERT where ORDER_COLUMN_NAME='$order_column_name' and ORDER_VAL='$order_val' and CONVERTED_VAL='$converted_val' and DOOR_TYPE='$door_type' and RULE_NAME='$rule_name'";
        $exlist = $retYuanJian=DB::query($exist);
        if(!empty($exlist)) {
            return retmsg(-1,null,"该转换规则已经存在！");
        }
        $insertsql = "insert into BOM_ORDERVAL_CONVERT(ORDER_COLUMN_NAME,ORDER_VAL,CONVERTED_VAL,DOOR_TYPE,RULE_NAME) 
values('$order_column_name','$order_val','$converted_val','$door_type','$rule_name')";
        $retYuanJian=Db::execute($insertsql);
        if(!$retYuanJian){
            return retmsg(-1,null,$insertsql);
        }
        return retmsg(0);
    }

    public function updateConvertRule()
    {
        $postData=json_decode(file_get_contents("php://input"),true);
        $data=$postData;
        $rule_name=$data['rule_name'];
        $door_type=$data['door_type'];
        $order_column_name=$data['order_column_name'];
        $order_val=$data['order_val'];
        $converted_val=$data['converted_val'];
        $sql = "update bom_orderval_convert set converted_val='$converted_val' where rule_name='$rule_name' and door_type='$door_type' and order_val='$order_val'";
        Db::execute($sql);
        return retmsg(0);
    }

    /**
     * 删除转换规则
     * @return array
     */
    public function deleteConvertRule()
    {
        $postData=json_decode(file_get_contents("php://input"),true);
        $data=$postData;
        $rule_name=$data['rule_name'];
        $door_type=$data['door_type'];
        $order_column_name=$data['order_column_name'];
        $order_val=$data['order_val'];
        $converted_val=$data['converted_val'];
        $exist = "select * from BOM_ORDERVAL_CONVERT where ORDER_COLUMN_NAME='$order_column_name' and ORDER_VAL='$order_val' and CONVERTED_VAL='$converted_val' and DOOR_TYPE='$door_type' and RULE_NAME='$rule_name'";
        $exlist = $retYuanJian=DB::query($exist);
        if(empty($exlist)) {
            return retmsg(-1,null,"该转换规则不存在！");
        }
        $deletesql = "delete from  BOM_ORDERVAL_CONVERT where ORDER_COLUMN_NAME='$order_column_name' and ORDER_VAL='$order_val' and CONVERTED_VAL='$converted_val' and DOOR_TYPE='$door_type' and RULE_NAME='$rule_name'";
        $retYuanJian=DB::execute($deletesql);
        if(!$retYuanJian) {
            return retmsg(-1,null,$deletesql);
        }
        return retmsg(0);
    }

    public function ruleList($doorType)
    {
        $array = $this->getConvertRuleName($doorType);
        $rules = $array['data'];
        $arr = array();
        foreach ($rules as $k => $v) {
            $arr[$v['rule_name']] = $v['order_column_name'];
        }
        return $arr;
    }

    public function importConvertRule($doorType='M9',$ruleName='锁具')
    {
        $order_column_name = "";
        $array = $this->ruleList($doorType);
        if(!empty($array[$ruleName])) {
            $order_column_name = $array[$ruleName];
        } else {
            return retmsg(-1,null,$ruleName."分类不存在！");
        }

        vendor("PHPExcel.Classes.PHPExcel");
        $fileName=mt_rand();
        $extension=substr(strrchr($_FILES["file"]["name"], '.'), 1);
        if($extension!='xlsx'&&$extension!='xls') {
            $this->response(retmsg(-1,null,'请上传Excel文件！'),'json');
        }
        $tempPath=str_replace('\\','/',realpath(__DIR__.'/../../../')).'/upload/'.$fileName.".".$extension;
        $flag=move_uploaded_file($_FILES["file"]["tmp_name"],$tempPath);
        if(!$flag) {
            $this->response(retmsg(-1,null,"文件保存失败：$tempPath"),'json');
        } try {
            $PHPExcel=\PHPExcel_IOFactory::load($tempPath);
        } catch (\PHPExcel_Reader_Exception $e) {
            $this->response(retmsg(-1,null,$e->__toString()),'json');
        }
        unlink($tempPath);//删除临时文件
        $sheets=$PHPExcel->getAllSheets();
        if(empty($sheets)) {
            $this->response(retmsg(-1,null,"无法读取此Excel!"),'json');
        }
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数字符串
        //读取表头匹配列
        DB::startTrans();
        $oknum=0;
        # 先全删除数据，在进行存储
        Db::execute("delete from BOM_ORDERVAL_CONVERT where door_type='$doorType' and rule_name='$ruleName'");
        for($j=2;$j<=$highestRow;$j++) {
            $order_val = $PHPExcel->getActiveSheet()->getCell("A" . $j)->getValue();//名称
            $convert_val = $PHPExcel->getActiveSheet()->getCell("B" . $j)->getValue();//工号
//            $qstr = "select * from BOM_ORDERVAL_CONVERT where ORDER_VAL='$order_val' and CONVERTED_VAL='$convert_val' and DOOR_TYPE='$doorType' and RULE_NAME='$ruleName' and ORDER_COLUMN_NAME='$order_column_name'";
//            $list = Db::query($qstr);
//            if(empty($list)) {
//
//            }
            if (!empty($order_val)) {
                $insertstr= "insert into BOM_ORDERVAL_CONVERT(ORDER_COLUMN_NAME,ORDER_VAL,CONVERTED_VAL,DOOR_TYPE,RULE_NAME) values('$order_column_name','$order_val','$convert_val','$doorType','$ruleName')";
                $ret = Db::execute($insertstr);
                $oknum++;
            }
        }
        DB::commit();
        return retmsg(0,null,"成功导入".$oknum."行数据！");
    }

    public function exportConvertRule($doorType='M9',$ruleName='锁具显示规则')
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');

        Vendor('PHPExcel.Classes.PHPExcel');//调用类库,路径是基于vendor文件夹的
        $objExcel = new \PHPExcel();
        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();
        //设置当前活动sheet的名称
        $objActSheet->setTitle('转换规则');
        $objActSheet->setCellValue('A1', '原数据');
        $objActSheet->setCellValue('B1', '转换后数据');
        $sql="SELECT ORDER_VAL,CONVERTED_VAL from BOM_ORDERVAL_CONVERT where DOOR_TYPE='$doorType' and RULE_NAME='$ruleName' order by order_val";
        $result=DB::query($sql);
        $j=2;
        foreach ($result as $akey =>$arow) {
            $objActSheet->setCellValue("A".$j,$arow["ORDER_VAL"]);
            $objActSheet->setCellValue("B".$j,$arow["CONVERTED_VAL"]);
            $j++;
        }
        $outputFileName = $ruleName.".xls";
        //到浏览器
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$outputFileName");
        header('Cache-Control: max-age=0');
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        $objWriter->save('php://output'); //文件通过浏览器下载
        return;
    }


















    

}
