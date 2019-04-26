<?php
namespace app\admin\controller;
use app\admin\service\BaseCodeService;
use app\api\service\Test;
use Firebase\JWT\JWT;
use think\Db;
class YuanjianRule extends AdminBase
{
    public function index()
    {
       $jwt=new JWT();
       /*$user=['uid'=>'123','name'=>'chensong'];
        $token=$jwt->encode($user,config('jwt_secret'));
        echo $token.'<br>';*/
        //print_r($this->user);
        //echo $this->findNum('cs123c3');
      
    }

    /**
     * 获取门类型
     * @return array
     */
    public function getDoorType(){
        $data=[
            ['door_type'=>'M9','name'=>'钢质门'],
            ['door_type'=>'M8','name'=>'防火门'],
            ['door_type'=>'M7','name'=>'木质门'],
            ['door_type'=>'M6','name'=>'装甲门'],
            ['door_type'=>'W9','name'=>'套装门'],
        ];
        return retmsg(0,$data);
    }

    /**
     * 根据门类型获取订单属性名称
     * @param string $doorType
     * @return array
     */
    public function getAttrClassName($doorType='M9'){
        $sql="select cast(class_name as VARCHAR2(60)) as class_name ,order_column_name from order_config_class where door_type='$doorType' and order_column_name is not null";
        $data=DB::query($sql);
        //添加门扇高度
        array_push($data,['CLASS_NAME'=>'门扇高度','ORDER_COLUMN_NAME'=>'MENSHAN_GAODU']);
        foreach ($data as $k=>$v){
            $data[$k]['ORDER_COLUMN_NAME']=strtolower($v['ORDER_COLUMN_NAME']);
        }
        return retmsg(0,$data);
    }

    /**
     * 根据门框门类型获取订单属性值
     * @param $className
     * @param string $menkuangID
     * @param string $doorType
     * @return array
     */
    public function getAttrValue($className,$menkuangID='',$doorType='M9',$searchString=''){
        //查询出该属性的所有可选值
        $sql="select attri_id,cast(attri_name as VARCHAR2(100)) as attri_name from order_config_class t1,order_config_attri t2 where 
              t1.class_name='$className' and t1.door_type='$doorType' and t2.attri_name like '%$searchString%' and t1.class_id=t2.class_id and attri_state='Y' ";
        $result=DB::query($sql);

        //查询非门框属性时根据门框属性值查询依赖关系
        //查询出该属性的依赖
        if(!empty($menkuangID)){
           $sql="select t1.attri_id from order_config_attri_rel t1,(
                select attri_id from order_config_class t1,order_config_attri t2 where 
                t1.class_name='$className' and t1.door_type='$doorType' and t1.class_id=t2.class_id
              ) t2 where t1.attri_id=t2.attri_id 
              and (attri_pid_a=$menkuangID or  attri_pid_b=$menkuangID or  attri_pid_c=$menkuangID or  attri_pid_d=$menkuangID or  attri_pid_e=$menkuangID or  attri_pid_f=$menkuangID) ";
           $ret=DB::query($sql);
           $rely=array();
           foreach ($ret as $k=>$v){
               array_push($rely,$v['ATTRI_ID']);
           }
           $list=array();
           foreach ($result as $k=>$v){
               if(in_array($v['ATTRI_ID'],$rely)){
                   array_push($list,$v);
               }
           }
        }

        //如果未查询到依赖（直接找到）则返回全部选择项
        $list=empty($list)?$result:$list;
        //查询门扇高度
        if($className=='门扇高度'&&!empty($menkuangID)){
            $sql="select attri_name from order_config_attri where attri_id=$menkuangID";
            $menkuang=DB::query($sql);
            $menkuang=$menkuang[0]['ATTRI_NAME'];
            if(strpos($menkuang,'90')){
                $menkuang='90';
            }
            else{
                $menkuang='!90';
            }
            $key=$doorType.':'.$menkuang;
            $sql="select id as attri_id,val as attri_name from bom_interval_rule where yewu_key='$key'";
            $interval=DB::query($sql);
            $list=$interval;
        }
        return retmsg(0,$list);
    }

    /**
     * 设置副窗订单门扇高度规则
     * @return array
     */
    public function getYuanJianLevel(){
        $data=['成品级','门框级','门扇级(母门)','门扇级(子门)','门扇级(母子门)','门扇级(子子门)'];
        return retmsg(0,$data);
    }

    /**
     * 添加元件和规则
     * 元件单条添加料号多条
     * @return array
     */
    public function addYuanJian(){
        $postData=json_decode(file_get_contents("php://input"),true);
        $data=$postData;
        $yuanJianName=$data['yuanjian_name'];
        $yuanJianLevel=$data['yuanjian_level'];
        $orderColumnName=implode(',',$data['order_column_name']);
        $zhizaobm=$data['zhizaobm'];
        $menkuang=$data['menkuang'];
        $doorType=$data['door_type'];

        //判断该元件是否已经存在
        $count=DB::query("select * from bom_yuanjian where yuanjian_name='$yuanJianName' and zhizaobm='$zhizaobm' and menkuang='$menkuang' and door_type='$doorType'");
        if(!empty($count)){
            return retmsg(-1,null,"该元件【 $yuanJianName 】已经存在！");
        }

        DB::startTrans();
        //插入元件表
        $sqlYuanJian="insert into bom_yuanjian(id,yuanjian_name,yuanjian_level,order_column_name,zhizaobm,menkuang,door_type) 
                      values(bom_yuanjian_seq.nextval,'$yuanJianName','$yuanJianLevel','$orderColumnName','$zhizaobm','$menkuang','$doorType')";
        $retYuanJian=DB::execute($sqlYuanJian);
        if(!$retYuanJian){
            DB::rollback();
            return retmsg(-1,null,$sqlYuanJian);
        }

        //组合元件规则数据，插入元件规则表
        $yuanJianRule=$data['rule'];//待插入bom_yuanjian_rule表数组
        //组合列名
        $column='(id,yuanjian_id,';
        if(!empty($yuanJianRule[0])){
            foreach ($yuanJianRule[0] as $k=>$v){
                $column.=$k.',';
            }
            $column=rtrim($column,',');
            $column.=')';
            foreach ($yuanJianRule as $k=>$v){
                $values= '(bom_yuanjian_rule_seq.nextval,bom_yuanjian_seq.currval,';
                foreach ($v as $key=>$val){
                    $val=excel_trim($val);
                    if(is_array($val)){
                        $temp=implode(',',$val);
                        $values.="',$temp,',";
                    }
                    else{
                        $values.="'$val',";
                    }
                }
                $values=rtrim($values,',');
                $values="$values)";
                $sqlYuanJianRule="insert into bom_yuanjian_rule $column values $values";
                $retYuanJianRule=DB::execute($sqlYuanJianRule);
                if(!$retYuanJianRule){
                    DB::rollback();
                    return retmsg(-1,null,$sqlYuanJianRule);
                }
            }
        }
        DB::commit();
        $yuanjianID = M()->query("select bom_yuanjian_seq.currval as id from dual",true);
        return retmsg(0,['yuanjian_id'=>$yuanjianID[0]['id']]);
    }

    /**
     * 修改元件，目前只允许修改元件名称
     */
    public function updateYuanJian(){
        $postData=json_decode(file_get_contents("php://input"),true);
        $yuanJianID=$postData['yuanjian_id'];
        $menkuang=$postData['memkuang'];
        $doorType=$postData['door_type'];
        $yuanJianName=$postData['yuanjian_name'];
        $yuanJianLevel=$postData['yuanjian_level'];
        //判断该元件是否已经存在
        $count=DB::query("select * from bom_yuanjian where yuanjian_name='$yuanJianName' and menkuang='$menkuang' and door_type='$doorType' and id!=$yuanJianID");
        if(!empty($count)){
            return retmsg(-1,null,"该元件【 $yuanJianName 】已经存在！");
        }

        $sql="update bom_yuanjian set yuanjian_name='$yuanJianName',yuanjian_level='$yuanJianLevel'  where id=$yuanJianID ";
        $ret=DB::execute($sql);
        $resultCode=empty($ret)?-1:0;
        return retmsg($resultCode);
    }

    /**
     * 获取元件及料号列表
     * @param $zhizaobm
     * @param $menkuang
     * @param $doorType
     * @return array
     */
    public function getYuanJianList($zhizaobm='成都制造四部',$menkuang,$doorType,$yuanJianName='',$yuanJianLevel=''){
        $where='1=1';
        if(!empty($yuanJianName)){
            $where.="and yuanjian_name like '%$yuanJianName%'";
        }
        if(!empty($yuanJianLevel)){
            $where.="and yuanjian_level='$yuanJianLevel'";
        }
        $sql="select t1.id as yuanjian_id,t1.yuanjian_name,t1.yuanjian_level,t1.order_column_name,t2.liaohao,t2.liaohao_pinming,t2.liaohao_guige,t2.liaohao_danwei 
              from bom_yuanjian t1 LEFT JOIN  bom_yuanjian_rule t2 ON T1.ID=T2.YUANJIAN_ID
              where t1.zhizaobm='$zhizaobm' and t1.menkuang='$menkuang' and door_type='$doorType' and  $where
              group by t1.id,t1.yuanjian_name,t1.yuanjian_level,t1.order_column_name,t2.liaohao,t2.liaohao_pinming,t2.liaohao_guige,t2.liaohao_danwei,t1.id order by t1.id";
        $data=DB::query($sql);
        $result=array();
        foreach ($data as $k=>$v){
            $flag=0;
            $tempRule=array();
            if(!empty($v['LIAOHAO'])){
                $tempRule['liaohao']=$v['LIAOHAO'];
                $tempRule['liaohao_pinming']=$v['LIAOHAO_PINMING'];
                $tempRule['liaohao_guige']=$v['LIAOHAO_GUIGE'];
            }
            foreach ($result as $key=>$value){
                if($v['YUANJIAN_ID']==$value['YUANJIAN_ID']){
                    array_push($result[$key]['RULE'],$tempRule);
                    $flag=1;
                }
            }
            if(!$flag){
                $tempYuanjian=array();
                $tempYuanjian['YUANJIAN_ID']=$v['YUANJIAN_ID'];
                $tempYuanjian['YUANJIAN_NAME']=$v['YUANJIAN_NAME'];
                $tempYuanjian['YUANJIAN_LEVEL']=$v['YUANJIAN_LEVEL'];
                $tempYuanjian['ORDER_COLUMN_NAME']=$v['ORDER_COLUMN_NAME'];
                $tempYuanjian['RULE']=array();
                if(!empty($tempRule)){
                    array_push($tempYuanjian['RULE'],$tempRule);
                }
                array_push($result,$tempYuanjian);
            }
        }
        return retmsg(0,$result);
    }

    /**
     * 获取由订单列料号信息列组成的元件或该元件下的某一料号的订单规则
     * @param $yuanJianID 元件id
     * @param $orderColumnName 订单列
     * @param string $liaoHao 料号
     * @return array
     */
    public function getRuleList($yuanJianID,$orderColumnName,$liaoHao=''){
        $orderColumnName=getColumnName('bom_yuanjian_rule',$orderColumnName);
        $sql="select yuanjian_id,id as rule_id,$orderColumnName,liaohao,liaohao_pinming,liaohao_guige,yongliang,liaohao_danwei from bom_yuanjian_rule where yuanjian_id=$yuanJianID ";
        if(!empty($liaoHao)){
            $sql.="and liaohao='$liaoHao'";
        }
        $data=DB::query($sql);
        return retmsg(0,$data);
    }

    public function downloadRuleListToExcel($yuanJianID,$orderColumnName,$yuanJianName=''){
        $data = $this->getRuleList($yuanJianID,$orderColumnName);
        $data = $data['data'];
        $columnMap = $this->getRuleColumnMap();

        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        //循环打印表头部分
        $column = 0;
        if(!empty($data[0])){
            $head = $data[0];
        }
        else{
            $head = array_reduce(explode(',',strtolower($orderColumnName)),function($carry,$item){
                $array =$carry;
                $array[$item] = '';
                return $array;
            },[]);
            $head['liaohao'] = '';
            $head['liaohao_pinming'] = '';
            $head['liaohao_guige'] = '';
            $head['yongliang'] = '';
            $head['liaohao_danwei'] = '';
        }
        foreach ($head as $k=>$v){
            if(empty($columnMap[$k])){
                continue;
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(\PHPExcel_Cell::stringFromColumnIndex($column++).'1',$columnMap[$k]);
        }
        //循环打印excel数据部分
        $offset=2;//数据部分偏移量
        foreach ($data as $k=>$v){
            $column = 0;//列索引
            foreach ($v as $key=>$val){
                if(empty($columnMap[$key])){
                    continue;
                }
                $row=$k+$offset;
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(\PHPExcel_Cell::stringFromColumnIndex($column++).$row,$val);
            }
        }
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$yuanJianName 用料规则.xls");
        header('Cache-Control: max-age=0');
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save('php://output'); //文件通过浏览器下载
        return;
    }

    public function importRuleListFromExcel($yuanJianID,$orderColumnName){
        //查询可选的订单属性值
        $orderColumnName = explode(',',strtoupper($orderColumnName));
        $where = '';
        foreach ($orderColumnName as $k=>$v){
            $where .="'$v',";
        }
        $where = rtrim($where,',');
        $sql = "select attri_name from order_config_attri where class_id in 
                (select class_id from order_config_class where order_column_name in ($where))";
        $availableAttri = array_reduce(M()->query($sql,true),function($carry,$item){
            array_push($carry,$item['attri_name']);
            return $carry;
        },[]);

        vendor("PHPExcel.Classes.PHPExcel");
        $fileName=mt_rand();
        $extension=substr(strrchr($_FILES["file"]["name"], '.'), 1);
        if($extension!='xlsx'&&$extension!='xls'){
            $this->response(retmsg(-1,null,'请上传Excel文件！'),'json');
        }
        $tempPath=str_replace('\\','/',realpath(__DIR__.'/../../../')).'/upload/'.$fileName.".".$extension;
        $flag=move_uploaded_file($_FILES["file"]["tmp_name"],$tempPath);
        if(!$flag){
            $this->response(retmsg(-1,null,"文件保存失败：$tempPath"),'json');
        }
        try {
            $PHPExcel=\PHPExcel_IOFactory::load($tempPath);
        } catch (\PHPExcel_Reader_Exception $e) {
            $this->response(retmsg(-1,null,$e->__toString()),'json');
        }
        unlink($tempPath);//删除临时文件
        $sheets=$PHPExcel->getAllSheets();
        if(empty($sheets)){
            $this->response(retmsg(-1,null,"无法读取此Excel!"),'json');
        }
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数字符串
        $highestColumm = \PHPExcel_Cell::columnIndexFromString($highestColumm);

        //读取表头匹配列
        $ruleColumn = array();//yuanjian_rule 表列
        $searchColumn = array();//需要查询的料号信息（品名，规格，单位）
        $unknownColumn = array();
        $columnMap = $this->getRuleColumnMap(1);
        for ($column = 0;$column < $highestColumm; $column++) {
            $val = excel_trim($sheet->getCell(\PHPExcel_Cell::stringFromColumnIndex($column).'1')->getValue());
            if(empty($columnMap[$val])){
                array_push($unknownColumn,$column);
            }
            array_push($ruleColumn,$columnMap[$val]);
            if(in_array($columnMap[$val],['liaohao_pinming','liaohao_guige','liaohao_danwei'])){
                array_push($searchColumn,$column);
            }
            if($columnMap[$val]=='liaohao'){
                $liaohaoColumn = $column;//料号所在列
            }
        }

        //读取数据插入到bom_yuanjian_rule
        DB::startTrans();
        M()->execute("delete from bom_yuanjian_rule where yuanjian_id=$yuanJianID");//清除该元件的所有规则
        //执行插入
        $columnStr = 'id,yuanjian_id,'.implode(',',$ruleColumn);
        $rowOffset = 2;
        $successAmount = 0;
        $msg = "共计%d行,成功%d行，失败%d行<br>";
        $errorMsg = '';
        for ($row = $rowOffset; $row <= $highestRow; $row++){
            $sql = "insert into bom_yuanjian_rule ($columnStr) values (bom_yuanjian_rule_seq.nextval,$yuanJianID,";
            $isLegal =1;//订单属性是否合法
            $liaohao = excel_trim($sheet->getCell(\PHPExcel_Cell::stringFromColumnIndex($liaohaoColumn).$row)->getValue());
            if(empty($liaohao)){
                $isLegal = 0;
                $errorMsg .= "第 $row 行，料号不能为空！<br>";
            }
            for ($column = 0;$column < $highestColumm; $column++) {
                //未识别的列
                if(in_array($column,$unknownColumn)){
                    continue;
                }

                $val = excel_trim($sheet->getCell(\PHPExcel_Cell::stringFromColumnIndex($column).$row)->getValue());
                //料号信息字段
                if(in_array($column,$searchColumn)){
                    if(empty($val)){
                        $ret = $this->getLiaohaoInfo($liaohao);
                        $val = $ret['data'][$ruleColumn[$column]];
                    }
                }
                //订单属性字段
                elseif(!in_array($ruleColumn[$column],['yongliang','liaohao'])){
                    $val = str_replace('/',',',$val);
                    $val = str_replace('\\',',',$val);
                    //验证输入属性是否合法
                    $valArray = explode(',',$val);
                    foreach ($valArray as $k=>$v){
                        if(empty($v)){
                            continue;
                        }
                        if(!in_array($v,$availableAttri)){
                            $isLegal = 0;
                            $errorMsg .= '第'.$row.'行'.\PHPExcel_Cell::stringFromColumnIndex($column).'列:['.$v.']不符合订单属性值<br>';
                            break;
                        }
                    }
                    if(!$isLegal){
                        break;
                    }
                    //首位加','标示一个属性值开始结束
                    if(mb_substr($val,0,1)!==','){
                        $val = ','.$val;
                    }
                    if(mb_substr($val,-1,1)!==','){
                        $val = $val.',';
                    }
                }
                $sql .= "'$val',";
            }
            $sql = rtrim($sql,',');
            $sql .= ')';
            if($isLegal){
                M()->execute($sql);
                $successAmount++;
            }
        }
        DB::commit();
        $msg = sprintf($msg,$highestRow-1,$successAmount,$highestRow-1-$successAmount);
        return retmsg(0,null,$msg.$errorMsg);
    }

    /**
     * 获取用料规则列名与含义map eg:['SUOXIN'=>'锁芯']
     * @param $keyType:0以列名为键，1以含义为键
     */
    public function getRuleColumnMap($keyType=0){
        //查询订单列对应名称
        $orderColumnName = M()->query('select * from order_config_class t where t.order_column_name is not null',true);
        //eg:['SUOXIN'=>'锁芯']
        $orderColumnMap = array_reduce($orderColumnName,function($carry,$item){
            return array_merge($carry,array(strtolower($item['order_column_name'])=>$item['class_name']));
        },[]);
        //料号信息列名映射
        $liaohaoMap = [
            'liaohao' => '料号',
            'liaohao_pinming' => '料号品名',
            'liaohao_guige' => '料号规格',
            'yongliang' => '用量',
            'liaohao_danwei' => '料号单位',
        ];
        //特殊订单属性列映射
        $teshuMap = [
            'start_height'=>'起高',
            'end_height'=>'止高',
            'start_width'=>'起宽',
            'end_width'=>'止宽',
            'menshan_gaodu'=>'门扇高度',
        ];
        $columnMap = array_merge($orderColumnMap,$liaohaoMap);
        $columnMap = array_merge($columnMap,$teshuMap);
        if($keyType==1){
           $array = array();
           foreach ($columnMap as $k=>$v){
               $array[$v] = $k;
           }
            $columnMap = $array;
        }
        return $columnMap;
    }

    /**
     * 添加或修改料号及用料规则
     * 料号单条添加规则多条
     * @return array
     */
    public function saveOrUpdateLiaoHao(){
        $postData=json_decode(file_get_contents("php://input"),true);
        $yuanJianID=$postData['yuanjian_id'];
        $liaoHao=$postData['liaohao'];
        $liaoHaoPinMing=$postData['liaohao_pinming'];
        $liaohaoGuige=$postData['liaohao_guige'];
        $liaohaoDanWei=$postData['liaohao_danwei'];

        //动态拼接sql
        DB::startTrans();
        $insertSql="insert into bom_yuanjian_rule ";
        $updateSql="update bom_yuanjian_rule set ";
        foreach ($postData['rule'] as $k=>$v){
            $insertColumn='(id,yuanjian_id,liaohao,liaohao_pinming,liaohao_guige,liaohao_danwei,';
            $insertValues=" values(bom_yuanjian_rule_seq.nextval,$yuanJianID,'$liaoHao','$liaoHaoPinMing','$liaohaoGuige','$liaohaoDanWei',";
            $updateColumnValues="liaohao='$liaoHao',liaohao_pinming='$liaoHaoPinMing',liaohao_guige='$liaohaoGuige',liaohao_danwei='$liaohaoDanWei',";
            $ruleID=$v['rule_id'];
            $isUpdate=empty($ruleID)?0:1;
            foreach ($v as $key=>$val){
                $val=excel_trim($val);
                if(is_array($val)){
                    $val=','.implode(',',$val).',';
                }
               if($key!=='rule_id'){
                   if($isUpdate){
                       $updateColumnValues.="$key='$val',";
                   }
                   else{
                       $insertColumn.="$key,";
                       $insertValues.="'$val',";
                   }
               }
            }
            $sql='';
            if($isUpdate){
                $updateColumnValues=rtrim($updateColumnValues,',');
                $sql=$updateSql.$updateColumnValues." where id=$ruleID";
            }
            else{
                $insertColumn=rtrim($insertColumn,',').')';
                $insertValues=rtrim($insertValues,',').')';
                $sql=$insertSql.$insertColumn.$insertValues;
            }
           DB::execute($sql);
            /*echo "$sql".'<br>' ;
            exit();*/
        }
        //exit();
        DB::commit();
        return retmsg(0);

    }

    /**
     * 删除某一元件的某一料号
     * 级联删除规则
     * @param $yuanJianID
     * @param $liaohao
     */
    public function deleteLiaoHao($yuanJianID,$liaohao){
        $sql="delete from bom_yuanjian_rule where yuanjian_id=$yuanJianID and liaohao='$liaohao' ";
        $ret=DB::execute($sql);
        $resultCode=empty($ret)?-1:0;
        return retmsg($resultCode);
    }

    /**
     * 删除某一料号的某一条规则
     * @param $ruleID
     */
    public function deleteLiaoHaoRule($ruleID){
        $sql="delete from bom_yuanjian_rule where id=$ruleID ";
        $ret=DB::execute($sql);
        $resultCode=empty($ret)?-1:0;
        return retmsg($resultCode);
    }

    /**
     * 删除某一元件
     * 级联删除规则
     * @param $yuanJianID
     */
    public function deleteYuanJian($yuanJianID){
        DB::startTrans();
        $sqlYanJian="delete from bom_yuanjian where id=$yuanJianID";
        $retYuanJian=DB::execute($sqlYanJian);
        $sqlRule="delete from bom_yuanjian_rule where yuanjian_id=$yuanJianID ";
        $retRule=DB::execute($sqlRule);
        $resultCode=empty($retYuanJian)?-1:0;
        DB::commit();
        return retmsg($resultCode);
    }


    public function setMenshanGaodu(){
        $postData=json_decode(file_get_contents("php://input"),true);
        $mengkuang=$postData['menkuang'];
        $doorType=$postData['door_type'];
        $key=$doorType.':'.$mengkuang;
        foreach ($postData['interval'] as $k=>$v){
            $start=$v['start_val'];
            $end=$v['end_val'];
            $val=$v['val'];
            $sql="insert into bom_interval_rule(id,start_val,end_val,val,yewu_key) values(bom_interval_seq.nextval,$start,$end,'$val','$key') ";
            DB::execute($sql);
        }
        return retmsg(0);
    }

    /**
     * @return mixed
     */
    public function test()
    {
        $test = new Test();
        var_dump($test);
    }

    public function getLiaohaoInfo($liaohao){
        $url = config('sale_system_url')."/app/material/get?code=$liaohao";
        $result = curl_get($url);
        $data = json_decode($result,true);
        $data['data']['liaohao_pinming'] =  empty($data['data']['materialName'])?'':$data['data']['materialName'];
        $data['data']['liaohao_guige'] =  empty($data['data']['materialGuige'])?'':$data['data']['materialGuige'];
        $data['data']['liaohao_danwei'] =  empty($data['data']['materialUnit'])?'':$data['data']['materialUnit'];
        return $data;
    }

    public function getZhizaobm(){
        $data = array_reduce(M()->query("select gys_name from sell_gys where gys_state='启用'",true),function ($carry,$item){
            array_push($carry,$item['gys_name']);
            return $carry;
        },[]);
        return $data;
    }



    

}
