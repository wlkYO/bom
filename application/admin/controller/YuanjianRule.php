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
            ['door_type'=>'M5','name'=>'精品门'],
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
        //添加客户名称
        array_push($data,['CLASS_NAME'=>'客户名称','ORDER_COLUMN_NAME'=>'TESHUYQ']);
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
    public function getAttrValue($className,$menkuangID='',$doorType='M9',$searchString='',$zhizaobm='成都制造一部'){
        //查询出该属性的所有可选值
        if ($className == '门框' && $doorType == 'M8') {
            $className = '门框类型';
        } elseif ($className == '门框' && $doorType == 'M7') {
            $className = '产品种类';
        }
        $sql="select attri_id,cast(attri_name as VARCHAR2(100)) as attri_name from order_config_class t1,order_config_attri t2 where 
              t1.class_name='$className' and t1.door_type='$doorType' and t2.attri_name like '%$searchString%' and t1.class_id=t2.class_id  and attri_state='Y'";
        $result=DB::query($sql);

        //查询非门框属性时根据门框属性值查询依赖关系
        //查询出该属性的依赖
       /* if(!empty($menkuangID)){
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
        }*/

        //如果未查询到依赖（直接找到）则返回全部选择项
        $list=empty($list)?$result:$list;
        //特殊要求
        if ($className == '客户名称') {
            if ($zhizaobm == '成都制造一部' || $zhizaobm == '成都制造二部') {
                $list = array(array("attri_id"=>"1","attri_name"=>"广州立伟"),array("attri_id"=>"2","attri_name"=>"非广州立伟"));
            } elseif ($zhizaobm == '成都制造三部') {
                $list = array(array("attri_id"=>"1","attri_name"=>"万科泊寓"),array("attri_id"=>"2","attri_name"=>"万科非泊寓"),array("attri_id"=>"3","attri_name"=>"非万科"));
            }
        }
        //客户名称
        /*if ($className == '客户名称') {
            if ($zhizaobm == '成都制造一部' || $zhizaobm == '成都制造二部') {
                $list = Db::query("select custom_no as attri_id,cast(custom_ab as VARCHAR2(500)) as attri_name from b_custom_info where custom_ab not like '%广州立伟%' and cus_class_no='C07' order by custom_ab");//非广州立伟
                $guangzhouLiwei = Db::query("select custom_no as attri_id,cast(custom_ab as VARCHAR2(500)) as attri_name from b_custom_info where custom_ab like '%广州立伟%'");//广州立伟
                foreach ($guangzhouLiwei as $kguangzhou => $vguangzhou) {
                    array_unshift($list, $vguangzhou);
                }
            }
            if ($zhizaobm == '成都制造三部') {
                $list  = Db::query("select custom_no as attri_id,cast(custom_ab as VARCHAR2(500)) as attri_name from b_custom_info where custom_ab not like '%万科%' and cus_class_no='C07' order by custom_ab");
                $wankeBoyu = Db::query("select custom_no as attri_id,cast(custom_ab as VARCHAR2(500)) as attri_name from b_custom_info where custom_ab like '%万科%' and custom_ab like '%泊寓%' and cus_class_no='C07'");
                $wankeNotBoyu = Db::query("select custom_no as attri_id,cast(custom_ab as VARCHAR2(500)) as attri_name from b_custom_info where custom_ab like '%万科%' and custom_ab not like '%泊寓%' and cus_class_no='C07'");
                foreach ($wankeBoyu as $kboyu => $vboyu) {
                    array_unshift($list, $vboyu);
                }
                foreach ($wankeNotBoyu as $knotboyu => $vnotboyu) {
                    array_unshift($list, $vnotboyu);
                }
            }
            array_unshift($list, array("attri_id"=>0,"attri_name"=>"常规客户"));//常规客户处理
        }*/
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

        //排序号查询
        $sort=DB::query("select nvl(max(sort),0) as sort from bom_yuanjian where yuanjian_name='$yuanJianName' and zhizaobm='$zhizaobm' and menkuang='$menkuang' and door_type='$doorType'",true);
        $xuhao = empty($sort[0]['sort'])?1:$sort[0]['sort']+1;
        DB::startTrans();
        //插入元件表
        $sqlYuanJian="insert into bom_yuanjian(id,yuanjian_name,yuanjian_level,order_column_name,zhizaobm,menkuang,door_type,sort) 
                      values(bom_yuanjian_seq.nextval,'$yuanJianName','$yuanJianLevel','$orderColumnName','$zhizaobm','$menkuang','$doorType',$xuhao)";
        $retYuanJian=DB::execute($sqlYuanJian);
        if(!$retYuanJian){
            DB::rollback();
            return retmsg(-1,null,$sqlYuanJian);
        }

        //组合元件规则数据，插入元件规则表
        $yuanJianRule=$data['rule'];//待插入bom_yuanjian_rule表数组
        //根据提交的rule规则顺序新增序号字段：sort

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
    public function getYuanJianList($zhizaobm='成都制造四部',$menkuang='',$doorType,$yuanJianName='',$yuanJianLevel=''){
        $where='1=1';
        if(!empty($yuanJianName)){
            $where.="and yuanjian_name like '%$yuanJianName%'";
        }
        if ($yuanJianLevel == 'null') {
            $yuanJianLevel = '';
        }
        if(!empty($yuanJianLevel)){
            $where.="and yuanjian_level='$yuanJianLevel'";
        }
        $sql="select t1.id as yuanjian_id,t1.yuanjian_name,t1.yuanjian_level,t1.order_column_name,t1.sort,t2.liaohao,t2.liaohao_pinming,t2.liaohao_guige,t2.liaohao_danwei 
              from bom_yuanjian t1 LEFT JOIN  bom_yuanjian_rule t2 ON T1.ID=T2.YUANJIAN_ID
              where t1.zhizaobm='$zhizaobm' and t1.menkuang='$menkuang' and door_type='$doorType' and  $where
              group by t1.id,t1.yuanjian_name,t1.yuanjian_level,t1.order_column_name,t1.sort,t2.liaohao,t2.liaohao_pinming,t2.liaohao_guige,t2.liaohao_danwei,t1.id order by t1.sort";
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
                $tempYuanjian['SORT']=$v['SORT'];
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
        $orderColumnName1=getColumnName('bom_yuanjian_rule',$orderColumnName);
        if (empty($orderColumnName)) {
            $sql="select yuanjian_id,id as rule_id,sort,liaohao,liaohao_pinming,liaohao_guige,yongliang,liaohao_danwei from bom_yuanjian_rule where yuanjian_id=$yuanJianID ";
        } else {
            $sql="select yuanjian_id,id as rule_id,sort,$orderColumnName1,liaohao,liaohao_pinming,liaohao_guige,yongliang,liaohao_danwei from bom_yuanjian_rule where yuanjian_id=$yuanJianID ";
        }
        if(!empty($liaoHao)){
            $sql.="and liaohao='$liaoHao'";
        }
        $data=DB::query($sql);
        return retmsg(0,$data);
    }

    public function downloadRuleListToExcel($yuanJianID,$orderColumnName='',$yuanJianName=''){
        $data = $this->getRuleList($yuanJianID,$orderColumnName);
        $door_type = Db::query("select door_type from bom_yuanjian where id='$yuanJianID'",true);
        $doorType = $door_type[0]['door_type'];
        $data = $data['data'];
        $columnMap = $this->getRuleColumnMap(0, $doorType);
		$columnMap['teshuyq'] = '客户名称';

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

    public function importRuleListFromExcel($yuanJianID,$orderColumnName=''){
        $door_type = Db::query("select door_type from bom_yuanjian where id='$yuanJianID'",true);
        $doorType = $door_type[0]['door_type'];
        //查询可选的订单属性值
        $orderColumnName = explode(',',strtoupper($orderColumnName));
        $where = '';
        foreach ($orderColumnName as $k=>$v){
            $where .="'$v',";
        }
        $where = rtrim($where,',');
        $sql = "select cast(attri_name as varchar2(500)) as attri_name from order_config_attri where class_id in 
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
        $columnMap = $this->getRuleColumnMap(1,$doorType);

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
                elseif(!in_array($ruleColumn[$column],['start_height','end_height','start_width','end_width','menshan_gaodu','yongliang','liaohao'])){
                    $val = str_replace('/',',',$val);
                    $val = str_replace('\\',',',$val);
                    //验证输入属性是否合法
                    $valArray = explode(',',$val);
                    foreach ($valArray as $k=>$v){
                        if(empty($v)){
                            continue;
                        }
                        if(!in_array($v,$availableAttri) && !in_array($ruleColumn[$column],['teshuyq'])){
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
    public function getRuleColumnMap($keyType=0, $doorType='M9'){
        //查询订单列对应名称
        $orderColumnName = Db::query("select * from order_config_class t where t.order_column_name is not null and t.door_type='$doorType'",true);
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
        $columnMap['客户名称'] = 'teshuyq';
        $columnMap['teshuyq'] = '客户名称';
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
            # 0412编辑时，批量更新料号所对应的品名和规格数据
            if($isUpdate){
                $updateBatch = "update bom_yuanjian_rule set liaohao_pinming='$liaoHaoPinMing',liaohao_guige='$liaohaoGuige',liaohao_danwei='$liaohaoDanWei' where liaohao='$liaoHao'";
                DB::execute($updateBatch);
            }
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
    public function deleteYuanJian($yuanJianID=''){//$yuanJianID
        # 单个元件的删除
        if (!empty($yuanJianID)) {
            $sqlYanJian="delete from bom_yuanjian where id=$yuanJianID";
            $retYuanJian=DB::execute($sqlYanJian);
            $sqlRule="delete from bom_yuanjian_rule where yuanjian_id=$yuanJianID ";
            $retRule=DB::execute($sqlRule);
            $resultCode=empty($retYuanJian)?-1:0;
        } else {
            # 元件的批量删除
//            $json = '{"yuanjian_id": ["481", "484", "491"]}';
        $postData=json_decode(file_get_contents("php://input"),true);
//            $postData=json_decode($json,true);
            $yuanjians = $postData['yuanjian_id'];
            DB::startTrans();
            foreach ($yuanjians as $key => $val) {
                $yuanJianID = $val;
                $sqlYanJian="delete from bom_yuanjian where id=$yuanJianID";
                $retYuanJian=DB::execute($sqlYanJian);
                $sqlRule="delete from bom_yuanjian_rule where yuanjian_id=$yuanJianID ";
                $retRule=DB::execute($sqlRule);
                $resultCode=empty($retYuanJian)?-1:0;
            }
            DB::commit();
        }
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

    public function copyYuanJian(){
        //$json ='{"is_replace":0,"zhizaobm":"齐河制造一部","menkuang":"70花边框110祥云","door_type":"M9","yuanjian_ids":["21911"]}';
        $postData=json_decode(file_get_contents("php://input"),true);
        //$postData=json_decode($json,true);
        $menkuang=$postData['menkuang'];
        $zhizaobm=$postData['zhizaobm'];
        $door_type=$postData['door_type'];
        $ids=$postData['yuanjian_ids'];
        $isReplace=$postData['is_replace'];
        //复制元件
        DB::startTrans();
        $yuanjianColumnStr='YUANJIAN_NAME,YUANJIAN_LEVEL,ORDER_COLUMN_NAME,DOOR_TYPE,SORT';
        $yujianColumn=getColumnName('bom_yuanjian',$yuanjianColumnStr);
        $ruleColumnStr='MKHOUDU,QMENBHD,HMENBHD,DKCAILIAO,MENSHAN,GUIGE,KAIXIANG,JIAOLIAN,HUASE,BIAOMCL,BIAOMIANTSYQ,
                   CHUANGHUA,MAOYAN,BIAO_PAI,SUOJU,TIANDSCSHUO,SUOBA_INFO,BIAOJIAN,BAOZHPACK,BAOZHUANGFS,LIAOHAO,
                   LIAOHAO_PINMING,LIAOHAO_GUIGE,YONGLIANG,IS_REQUIRE,START_HEIGHT,END_HEIGHT,START_WIDTH,END_WIDTH,
                   SUOXIN,DANG_CI,LIAOHAO_DANWEI,MENSHAN_GAODU,MENKUANGYQ,TESHUYQ,MSKAIKONG,CHA_XIAO,XFBIAOQIAN,
                   FHJIBIE,SHUNXUQI,BIAOQIAN_TIEMEN,FUCHUANG,BIMENQI,MENSHANCZ,MENSHANYQ';
        $ruleColumn=getColumnName('bom_yuanjian_rule',$ruleColumnStr);
        foreach ($ids as $k=>$v){
            $id=$v;
            //查询该元件是否已经在目标门框中存在
            $yuanJianName=Db::query("select yuanjian_name,door_type from bom_yuanjian where id='$id' ",true);
            $yuanjian_name=$yuanJianName[0]['yuanjian_name'];
//            $doorType=$yuanJianName[0]['door_type'];
            $doorType=$door_type;
            $count=DB::query("select id from bom_yuanjian where yuanjian_name='$yuanjian_name' and zhizaobm='$zhizaobm' and menkuang='$menkuang' and door_type='$doorType'",true);
            if($count){
                if(!$isReplace){
                    continue;
                }
                else{
                   $deleteYuanjianID= $count[0]['id'];
                   $deleteRuleIDs=array_reduce(M()->query("select id from bom_yuanjian_rule where yuanjian_id='$deleteYuanjianID'",true),function($carry,$item){
                       return $carry.$item['id'].',';
                   },'');
                   $deleteRuleIDs=rtrim($deleteRuleIDs,',');
                }
            }
            $paixu = Db::query("select nvl(max(sort),1) as sort from bom_yuanjian where zhizaobm='$zhizaobm' and menkuang='$menkuang' and door_type='$doorType'",true);
            $sort = $paixu[0]['sort'];
            $sqlYuanjian="insert into bom_yuanjian(id,menkuang,zhizaobm,$yuanjianColumnStr)
                      select bom_yuanjian_seq.nextval,'$menkuang','$zhizaobm',yuanjian_name,yuanjian_level,order_column_name,
                       '$door_type' as door_type,'$sort' as sort from bom_yuanjian where id='$id' ";//$yujianColumn
            Db::execute($sqlYuanjian);

            $test=M()->query("select bom_yuanjian_seq.currval as id from dual");
//            //复制元件规则
//            $paixu2 = Db::query("select nvl(max(sort),1) as sort from bom_yuanjian_rule where yuanjian_id=".$test[0]['id'],true);
//            $sort2 = $paixu2[0]['sort'];
            $sqlYuanjianRule="insert into bom_yuanjian_rule(id,yuanjian_id,menkuang,$ruleColumnStr)
                      select bom_yuanjian_rule_seq.nextval,bom_yuanjian_seq.currval,'$menkuang',$ruleColumn from bom_yuanjian_rule where yuanjian_id='$id' ";

            Db::execute($sqlYuanjianRule);

            //删除原有元件
            if($count&&$isReplace){
                M()->execute("delete from bom_yuanjian where id='$deleteYuanjianID'");
                if(!empty($deleteRuleIDs)){
                    M()->execute("delete from bom_yuanjian_rule where id in ( $deleteRuleIDs )");
                }
            }
        }
        DB::commit();
        return retmsg(0);
    }

    /**
     * bom元件批量导入导出
     * @param $yuanJianID
     * @param string $orderColumnName
     * @param string $yuanJianName
     */
    public function downloadMultiRule($zhizaobm='齐河制造一部',$doorType='M9',$menkuang='50花边框80,60普框'){
        # post方式传递多门框属性值
//        $json = '{"data": ["50花边框80","50普框","60普框"]}';
//        $json = '{"data": ["60普框"]}';
//        $postData = json_decode(file_get_contents('php://input'), true);
//        $postData = json_decode($json, true);
//        $menkuang = $postData['data'];
        $menkuang = explode(',',$menkuang);
        $printData = array();
        $columnMap = $this->getRuleColumnMap(1,$doorType);
        $columns = array_flip($columnMap);
        $columns['sort'] = '序号';
        $columns['teshuyq'] = '客户名称';
        $leftTitle = array();
        $leftA = array();
        foreach ($menkuang as $kmk => $vmk) {
            $temp = array();
            $tempA = array();
            $tempA['menkuang'] = $vmk;
            $count = 0;
            $yuanjian = Db::query("select id as yuanjian_id, yuanjian_name, yuanjian_level, order_column_name,sort from bom_yuanjian where zhizaobm = '$zhizaobm' and door_type = '$doorType' and menkuang = '$vmk' order by yuanjian_level,sort",true);
            if (!empty($yuanjian)) {
                foreach ($yuanjian as $kyj => $vyj) {
                    $yuanjian_id = $vyj['yuanjian_id'];
                    $order_column_name = $vyj['order_column_name'];
                    $data = $this->getRuleList($yuanjian_id,$order_column_name);
                    $count += count($data['data']);
                    $title = $this->getTitle($columns, $data['data'][0]);
                    array_push($printData, $title);
                    $sortNum = 0;
                    foreach ($data['data'] as $kdata => $vdata) {
                        foreach ($vdata as $key => $val) {
                            if (empty($val)) {
                                if ($key == 'sort') {
                                    $vdata[$key] = ++$sortNum;
                                } else {
                                    $vdata[$key] = '0';
                                }
                            }
                        }
                        array_push($printData, $vdata);
                    }
                    //左边标题列打印
//                    $temp = array();
//                    $temp['menkuang_col'] = count($yuanjian) + count($data['data']);
                    $temp['yuanjian_level'] = $vyj['yuanjian_level'];
                    $temp['yuanjian_name'] = $vyj['yuanjian_name'];
                    $temp['yuanjian_level_col'] = 1 + count($data['data']);
                    $temp['sort'] = empty($vyj['sort'])?($kyj+1):$vyj['sort'];
                    array_push($leftTitle, $temp);
                }
            } else {
                $title = array('yuanjian_level'=>'','yuanjian_name'=>'','yuanjian_level_col'=>2,'sort'=>'');
                array_push($leftTitle, $title);
                $fakeData1 = array('yuanjian_id'=>'','rule_id'=>'','sort'=>'序号','liaohao'=>'料号',
                    'liaohao_pinming'=>'料号品名','liaohao_guige'=>'料号规格','yongliang'=>'用量','liaohao_danwei'=>'料号单位');
                $fakeData2 = array('yuanjian_id'=>'','rule_id'=>'','sort'=>'暂无元件','liaohao'=>'',
                    'liaohao_pinming'=>'','liaohao_guige'=>'','yongliang'=>'','liaohao_danwei'=>'');
                array_push($printData, $fakeData1);
                array_push($printData, $fakeData2);
            }
            $tempA['menkuang_col'] = empty($yuanjian)?2:$count + count($yuanjian);
            array_push($leftA, $tempA);
//            if (!empty($tempA['menkuang_col'])) {
//                array_push($leftA, $tempA);
//            }
        }
        $this->downloadAll($zhizaobm,$leftA,$leftTitle,$printData);
    }

    public function downloadAll($zhizaobm,$leftA,$leftTitle,$printData)
    {
        //数据行导出--D列开始
        foreach ($printData as $kprint => $vprint) {
            unset($printData[$kprint]['yuanjian_id']);
            unset($printData[$kprint]['rule_id']);
            $printData[$kprint] = array_values($printData[$kprint]);
        }
//        $char = array('D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T');
        vendor("PHPExcel.Classes.PHPExcel");
        set_time_limit(600);
        ini_set('memory_limit', "-1");//设置内存无限制
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle($zhizaobm);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setCellValue('A1','门框');
        $sheet->setCellValue('B1','元件序号');
        $sheet->setCellValue('C1','层级');
        $sheet->setCellValue('D1','元件名称');

        //A列标题栏

        $leftA[0]['menkuang_col'] = $leftA[0]['menkuang_col'] - 1;
        $leftTitle[0]['yuanjian_level_col'] = $leftTitle[0]['yuanjian_level_col'] - 1;
        $count = 0;
        foreach ($leftTitle as $k => $v) {
            $cols = $v['yuanjian_level_col'];
            $count += $cols;
            $end = $count+1;
            $start = $end-$cols+1;
            $b_char = "B$start:B$end";
            $c_char = "C$start:C$end";
            $d_char = "D$start:D$end";
            $sheet->mergeCells($b_char);
            $sheet->mergeCells($c_char);
            $sheet->mergeCells($d_char);
            $sheet->setCellValue("B$start",$v['sort']);
            $sheet->setCellValue("C$start",$v['yuanjian_level']);
            $sheet->setCellValue("D$start",$v['yuanjian_name']);
        }
        $count1 = 0;
//        var_dump($leftA);
        foreach ($leftA as $ka => $va) {
            $menkuang_col = $va['menkuang_col'];
            if ($menkuang_col == 0) {
                $menkuang_col++;
            }
            $count1 += $menkuang_col;
            $end = $count1+1;
            $start = $end-$menkuang_col+1;
            $sheet->mergeCells("A$start:A$end");
            $sheet->setCellValue("A$start",$va['menkuang']);
        }

        $startChar = 'E';
        foreach ($printData as $key => $val) {
            foreach ($val as $k => $v) {
                $char =chr(ord($startChar)+$k);
                $cell = $char.($key+1);
                $sheet->setCellValue($cell,$v);
            }
        }
        //所有单元格数据居中显示
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        // 直接输出到浏览器
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        //输出到浏览器
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        $filename=$zhizaobm."工艺配置表.xls";
        header("Content-Disposition:attachment;filename=$filename");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        return;
    }

    public function getTitle($column, $arr,$flag=0)
    {
        if ($flag) {
            $re = array();
            foreach ($arr as $k=>$v) {
                $re[$column[$v]] = $v;
            }
            return $re;
        } else {
            $re = array();
            foreach ($arr as $key => $val) {
                $re[$key] = $column[$key];
            }
            return $re;
        }

    }

    /**
     * 工艺配置元件批量导入
     */
    public function importYuanjianList($zhizaobm,$doorType='M9')
    {
        set_time_limit(0);
        vendor("PHPExcel.Classes.PHPExcel");
        $file = $_FILES['file'] ['name'];
        $filetempname = $_FILES ['file']['tmp_name'];
        $filePath = str_replace('\\','/',realpath(__DIR__.'/../../../')).'/upload/';
        $filename = explode(".", $file);
        $time = date("YmdHis");
        $filename [0] = $time;//取文件名t替换
        $name = implode(".", $filename); //上传后的文件名
        $uploadfile = $filePath . $name;
        $result=move_uploaded_file($filetempname,$uploadfile);
        if($result) {
            $extension = substr(strrchr($file, '.'), 1);
            if ($extension != 'xlsx' && $extension != 'xls' && $extension != 'csv') {
                $this->response(retmsg(-1, null, '请上传Excel或csv文件！'), 'json');
            }
            try {
                $objPHPExcel = \PHPExcel_IOFactory::load($uploadfile);
            } catch (\PHPExcel_Reader_Exception $e) {
                $this->response(retmsg(-1, null, $e->__toString()), 'json');
            }
            $sheet = $objPHPExcel->getActiveSheet();//获取当前的工作表
            $data = $objPHPExcel->getSheetNames(0);
            $bumen = $data[0];
            if ($bumen != $zhizaobm) {
                return $this->response(retmsg(-1, null, "制造部门未对应"), 'json');
            }
            $insertData = $this->getImportExcelList($objPHPExcel, $zhizaobm, $doorType);
            $re = $this->saveYuanjian($insertData,$zhizaobm,$doorType);
            return retmsg(0,null,'导入成功');
        }
    }

    public function getImportExcelList($objPHPExcel, $zhizaobm, $doorType)
    {
        $sheet = $objPHPExcel->getSheet(0); // 读取第一工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数字符串
        $highestColumm = \PHPExcel_Cell::columnIndexFromString($highestColumm);
        $index = array("A", "B", "C", "D", "E", "F", "G", "H", "I",
            "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB");
        for ($i = 1; $i <= $highestRow; $i++) {
            $arr = array();
            for ($j = 1; $j <= $highestColumm; $j++) {
                $arr[] = $objPHPExcel->getActiveSheet(0)->getCell($index[$j - 1] . $i)->getValue();
            }
            $data[] = $arr;
        }
        //分析解析data
        $menkuang = array();
        $yuanjian = array();
//        $reset = $data;

        //先排除门框没有设置元件的数据
        foreach ($data as $key=>$val) {
            if ($val[4] == '暂无元件') {
                unset($data[$key]);//删除门框数据为空的数据部分
                unset($data[$key-1]);//删除门框数据为空的标题
            }
            if (empty($val)) {
                unset($data[$key]);
            }
        }
        $data = array_values($data);
        $reset = $data;
        foreach ($reset as $key=>$val) {
            unset($reset[$key][0]);//门框
            unset($reset[$key][1]);//元件序号
            unset($reset[$key][2]);//层级
            unset($reset[$key][3]);//元件名称
            foreach ($val as $kval=>$vval) {
                if (empty($vval) && $vval !='0') {
                    unset($reset[$key][$kval]);
                }
            }
            $reset[$key] = array_values($reset[$key]);
        }
        foreach ($data as $kdata => $vdata) {
            if (!empty($vdata[0])) {//门框
                array_push($menkuang,['index'=>$kdata,'menkuang'=>$vdata[0]]);
            }
            if (!empty($vdata[2])) {//元件层级
                array_push($yuanjian,['index'=>$kdata,'sort'=>$vdata[1],'yuanjian_level'=>$vdata[2],'yuanjian_name'=>$vdata[3],'menkuang'=>$vdata[0]]);
            }
        }
        $tempJudge = $yuanjian;
        if ($menkuang[0]['menkuang'] == '门框') {
            unset($menkuang[0]);
        }
        if ($yuanjian[0]['yuanjian_level'] == '层级') {
            unset($yuanjian[0]);
        }
        $menkuang = array_values($menkuang);
        $lastIndex = $highestRow-1;
        $yuanjian = array_values($yuanjian);
        $setmk = array();
        foreach ($yuanjian as $k => $v) {
            if (!empty($v['menkuang'])) {
                array_push($setmk,['xiabiao'=>$k,'menkuang'=>$v['menkuang']]);
            }
        }
        for ($m=0;$m<count($setmk);$m++) {
            $first = $setmk[$m]['xiabiao'];
            $end = $setmk[$m+1]['xiabiao'] - 1;
            if ($m == (count($setmk) -1)) {
                $end = count($yuanjian)-1;
            }
            for ($l = $first;$l<=$end;$l++) {
                $yuanjian[$l]['menkuang'] = $setmk[$m]['menkuang'];//每个元件加上门框，方便后面查询元件id
            }
        }

        $total = 0;
        for ($i = 0; $i < count($yuanjian);$i++) {
            $temp = $yuanjian[$i+1]['index']-$yuanjian[$i]['index'];
            $total += $temp;
            if ($i == (count($yuanjian)-1)) {
                if ($tempJudge[0]['yuanjian_level'] == '层级') {
                    $yuanjian[$i]['end'] = count($reset)-1;
                } else {
                    $yuanjian[$i]['end'] = $lastIndex;
                }
            } else {
                if ($tempJudge[0]['yuanjian_level'] == '层级') {
                    $yuanjian[$i]['end'] = $total;
                } else {
                    $yuanjian[$i]['end'] = $total-1;
                }
            }
        }
        $insertData = array();
        foreach ($yuanjian as $kyj => $vyj) {
            $temp = array();
            $start_index = $vyj['index'];
            $end_index = $vyj['end'];
            $yuanjian_level = $vyj['yuanjian_level'];
            $yuanjian_name = $vyj['yuanjian_name'];
            $menkuang = $vyj['menkuang'];
            if ($kyj == 0 && $tempJudge[0]['yuanjian_level'] == '层级') {
                $start_index=$vyj['index']-1;
            }
            //根据所有属性查询元件id
            $yuanjianInfo = Db::query("select id as yuanjian_id from bom_yuanjian where zhizaobm = '$zhizaobm' and door_type = '$doorType' 
and menkuang='$menkuang' and yuanjian_level='$yuanjian_level' and yuanjian_name='$yuanjian_name'",true);

            $temp['yuanjian_id'] = empty($yuanjianInfo)?'':$yuanjianInfo[0]['yuanjian_id'];
            $temp['sort'] = $vyj['sort'];
            $temp['yuanjian_level'] = $yuanjian_level;
            $temp['yuanjian_name'] = $yuanjian_name;
            $temp['menkuang'] = $menkuang;
            $temp['rule'] = array();
            for ($j = $start_index;$j <= $end_index;$j++){
                array_push($temp['rule'],$reset[$j]);
            }
            array_push($insertData,$temp);
        }
        return $insertData;
    }

    /**
     * 工艺配置批量更新or入库
     */
    public function saveYuanjian($insertData,$zhizaobm,$doorType)
    {
        $columnMap = $this->getRuleColumnMap(1,$doorType);
        $columnMap['序号'] = 'sort';
        $columnMap['客户名称'] = 'teshuyq';
        foreach ($insertData as $key => $val) {
            $title = $val['rule'][0];
            $titleConverted = $this->getTitle($columnMap,$title,1);
            $insertData[$key]['rule'][0] = $titleConverted;
            for ($i = 1;$i < count($val['rule']);$i++) {
                if (!empty($insertData[$key]['rule'][$i])) {
                    $insertData[$key]['rule'][$i] = array_combine(array_keys($insertData[$key]['rule'][0]),$insertData[$key]['rule'][$i]);
                } else {
                    unset($insertData[$key]['rule'][$i]);
                }
            }
            unset($insertData[$key]['rule'][0]);
            $insertData[$key]['rule'] = array_values($insertData[$key]['rule']);
        }
        //入库操作。有则更新，无则新增
        foreach ($insertData as $kdata =>$vdata) {
            $yuanjian_id = $vdata['yuanjian_id'];
            $yuanjian_sort = $vdata['sort'];
            $yuanjian_level = $vdata['yuanjian_level'];
            $yuanjian_name = $vdata['yuanjian_name'];
            $menkuang = $vdata['menkuang'];
            $order_column_name = $this->getOrderColumnName($vdata['rule'][0]);
            if (empty($yuanjian_id)) {//新增元件及其元件规则
                $id = Db::query("select bom_yuanjian_rule_seq.nextval as id from dual",true);
                $id_val = $id[0]['id'];
                $sql = "insert into bom_yuanjian (id,yuanjian_name,yuanjian_level,order_column_name,menkuang,door_type,created_at,zhizaobm,sort)
values ($id_val,'$yuanjian_name','$yuanjian_level','$order_column_name','$menkuang','$doorType',sysdate,'$zhizaobm',$yuanjian_sort)";
                Db::execute($sql);
                # 元件执行入库，获取当前的元件id
                $yuanjian_id = $id_val;
                foreach ($vdata['rule'] as $krule => $vrule) {
                    $in_str = 'id,yuanjian_id,';
                    $val_str = "bom_yuanjian_rule_seq.nextval,$yuanjian_id,";
                    foreach ($vrule as $k => $v) {
                        $in_str .= "$k,";
                        $val_str .= "'$v',";
                    }
                    $in_str = substr($in_str,0,-1);
                    $val_str = substr($val_str,0,-1);
                    $insertSql = "insert into bom_yuanjian_rule($in_str) values ($val_str)";
                    Db::execute($insertSql);
                }
            } else {//修改元件 or 新增或修改元件规则
                Db::startTrans();
                Db::execute("update bom_yuanjian set sort=$yuanjian_sort where id=$yuanjian_id");//更新元件序号
                foreach ($vdata['rule'] as $krule => $vrule) {
                    $ziduan_str = " '$yuanjian_id' as yuanjian_id,";
                    $inserta_str = 'a.id,a.yuanjian_id,';
                    $insertb_str = 'bom_yuanjian_rule_seq.nextval,
     b.yuanjian_id,';
                    $update_str = '';
                    $match_str = ' a.yuanjian_id = b.yuanjian_id and ';
                    foreach ($vrule as $k => $v) {
                        if ($v == '0') {
                            continue;
                        } else {
                            $ziduan_str .= "'$v' as $k,";
                            if ($k != 'sort') {
                                $match_str .= "a.$k=b.$k and ";
                            }
//                        $update_str .= "a.$k=b.$k,";
                            $inserta_str .= "a.$k,";
                            $insertb_str .= "b.$k,";
                        }
                    }
                    $ziduan_str = substr($ziduan_str,0,-1);
//                    $update_str = substr($update_str,0,-1);
                    $update_str = "a.sort=b.sort";
                    $inserta_str = substr($inserta_str,0,-1);
                    $insertb_str = substr($insertb_str,0,-1);
                    $match_str = rtrim($match_str,'and ');
                    $insertOrUpdateSql = "merge into bom_yuanjian_rule a using 
                    (select $ziduan_str from dual) b
                    on ($match_str) 
                    when matched then
                    update set $update_str where $match_str
                    when not matched then insert ($inserta_str) values ($insertb_str)";
                    Db::execute($insertOrUpdateSql);
                    Db::commit();
                }
            }
        }
        return true;
    }

    public function getOrderColumnName($data)
    {
        $keys = array_keys($data);
        $sort_index = 0;
        $liaohao_index=0;
        foreach ($keys as $k => $v) {
            if($v == 'liaohao') {
                $liaohao_index = $k;
            }
        }
        $column = '';
        foreach ($keys as $k => $v) {
            if ($k > $sort_index && $k < $liaohao_index) {
                $column .= "$v,";
            }
        }
        $column = substr($column,0,-1);
        return $column;
    }

    /**
     * 批量替换：主要替换料号和用量、单位等(excel模板导出)
     */
    public function replaceMuban()
    {
        vendor("PHPExcel.Classes.PHPExcel");
        set_time_limit(600);
        ini_set('memory_limit', "-1");//设置内存无限制
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setCellValue('A1', '部门');
        # 给A1格子设置批注
//        $sheet->getStyle('A1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF33');
        //2.设置变更单元格换行
        $sheet->getComment('A1')->setAuthor('Attention:');//设置作者
        $sheet->getComment( 'A1')->getText()->createTextRun("注意：\n"."该模板即可作为料号批量替换模板，也可作为属性批量替换模板\n"."1.其中料号替换区分了部门，请在A1格子录入‘部门’二字\n"."2.属性替换不区分部门，由于系统属性繁杂，请在A1处明确注明需要替换的属性：举例：如锁芯【表示批量修改锁芯属性】");
        $sheet->setCellValue('B1', '旧料号');
        $sheet->setCellValue('C1', '新料号');
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $sheet->setCellValue('A2', '成都制造一部/成都制造二部');
        $sheet->setCellValue('B2', 'KG3ZLGDX913555');
        $sheet->setCellValue('C2', 'KG3ZCLGDX913666');

        // 直接输出到浏览器
//        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);//不支持批注
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);//支持批注
        //输出到浏览器
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        $filename="工艺配置批量替换料号属性_导入样表.xls";
        header("Content-Disposition:attachment;filename=$filename");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    /**
     * 批量替换录入好之后的工艺配置里面的部分元件
     */
    public function importLiaohaoReplacement()
    {
        set_time_limit(0);
        vendor("PHPExcel.Classes.PHPExcel");
        $file = $_FILES['file'] ['name'];
        $filetempname = $_FILES ['file']['tmp_name'];
        $filePath = str_replace('\\', '/', realpath(__DIR__.'/../../../')).'/upload/';
        $filename = explode(".", $file);
        $time = date("YmdHis");
        $filename [0] = $time;//取文件名t替换
        $name = implode(".", $filename); //上传后的文件名
        $uploadfile = $filePath . $name;
        $result=move_uploaded_file($filetempname, $uploadfile);
        if ($result) {
            $extension = substr(strrchr($file, '.'), 1);
            if ($extension != 'xlsx' && $extension != 'xls') {
                return retmsg(-1, null, '请上传Excel文件！');
            }
            try {
                $objPHPExcel = \PHPExcel_IOFactory::load($uploadfile);
            } catch (\PHPExcel_Reader_Exception $e) {
                return retmsg(-1, null, $e->__toString());
            }
            $sheet = $objPHPExcel->getActiveSheet();//获取当前的工作表
            $highestRow = $sheet->getHighestRow();
            # 查询A1列的值是否为部门
            $cellA1_value = $sheet->getCell('A1')->getValue();
            if ($cellA1_value == '部门') {//根据部门替换旧料号
                for ($i = 2; $i <= $highestRow; $i ++) {
                    $bumenInfo = $sheet->getCell('A'.$i)->getValue();
                    $bumenList = "'".implode("','",explode('/', $bumenInfo))."'";
                    $oldLiaohao = $sheet->getCell('B'.$i)->getValue();
                    $newLiaohao = $sheet->getCell('C'.$i)->getValue();
//                $newYongliang = $sheet->getCell('C'.$i)->getValue();
//                $newDanwei = $sheet->getCell('D'.$i)->getValue();
                    if (!empty($oldLiaohao)) {
//                    $updateSql = "update bom_yuanjian_rule set liaohao='$newLiaohao',yongliang='$newYongliang',liaohao_danwei='$newDanwei'
//                     where liaohao='$oldLiaohao'";
                        # 2019-03-20 新料号替换旧料号,不替换单位和用量值
                        #2019-04-07根据新料号的品名和规格，自动更新旧料号的品名和规格
                        $yuanjianPMLH = Db::query("select liaohao_pinming,liaohao_guige from bom_yuanjian_rule where liaohao='$newLiaohao' and yuanjian_id in (select id from bom_yuanjian where zhizaobm in ($bumenList)) and rownum=1",1);
                        $newPinming = empty($yuanjianPMLH)?'':$yuanjianPMLH[0]['liaohao_pinming'];
                        $newGuige = empty($yuanjianPMLH)?'':$yuanjianPMLH[0]['liaohao_guige'];
                        $updateSql = "update bom_yuanjian_rule set liaohao='$newLiaohao',liaohao_pinming='$newPinming',liaohao_guige='$newGuige' where liaohao='$oldLiaohao' and yuanjian_id in (select id from bom_yuanjian where zhizaobm in ($bumenList)) ";
                        $ret = Db::execute($updateSql);
                        $resultCode = empty($ret)?-1:0;
                    }
                }
            } else {//A1的值为旧属性
                //属性替换工作开始，奇数列为旧属性，偶数列为新属性
                $getColumn = Db::query("select order_column_name from order_config_class where class_name='$cellA1_value' and rownum=1", true);
                if (empty($getColumn)) {
                    $resultCode = -3;
                } else {
                    $columnName = strtolower($getColumn[0]['order_column_name']);
                    for ($i = 2; $i <= $highestRow; $i ++) {
                        $oldAttri = $sheet->getCell('A'.$i)->getValue();
                        $newAttri = $sheet->getCell('B'.$i)->getValue();
                        $updateStr = "update bom_yuanjian_rule set $columnName = replace($columnName,',$oldAttri,',',$newAttri,') where $columnName like '%,$oldAttri,%'";
                        Db::execute($updateStr);
                    }
                }
            }
        }
        if ($resultCode == -3) {
            $msg = "系统订单暂不存在 $cellA1_value 属性";
        } else {
            $msg = '操作成功!';
        }
        return retmsg($resultCode,null,$msg);
    }
}
