<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/27
 * Time: 13:43
 */

namespace app\admin\controller;

use think\Db;

//include_once VENDOR_PATH.'PHPExcel\\Classes\\PHPExcel.php';

class CodeRule extends AdminBase
{
    /**
     * 获取门类型，不同门类型编码规则不尽相同
     * @return array
     */
    public function getDoorType()
    {
        $data = [
            ['door_type' => 'M9', 'name' => '钢质门'],
            ['door_type' => 'M8', 'name' => '防火门'],
            ['door_type' => 'M7', 'name' => '木质门'],
            ['door_type' => 'M6', 'name' => '装甲门'],
            ['door_type' => 'W9', 'name' => '套装门'],
            ['door_type' => 'M4', 'name' => '耐火窗'],
            ['door_type' => 'M8_1', 'name' => '室内防火门'],
        ];
        return retmsg(0, $data);
    }

    /**
     * 获取编码规则类型
     */
    public function getRuleTypes($doorType = 'M9')
    {
        $rules = "select distinct(rule_name) from bom_code_rule where rule_name not like '%规则%' and door_type='$doorType' order by rule_name";
        $ruleNames = Db::query($rules, true);
        if (empty($ruleNames)) {
            return retmsg(-1, array(array('rule_name' => '暂未开放')), getDoorDesc($doorType) . "编码规则功能暂未开放");
        } else {
            return retmsg(0, $ruleNames);
        }
    }

    /**
     * 根据编码规则获取其属性参数列表
     */
    public function getAttriByRule($ruleName, $doorType = 'M9')
    {
        $codeName = "select distinct(code_sort),order_column_name,code_name from bom_code_rule where rule_name='$ruleName' and door_type='$doorType'  order by code_sort asc";
        $attributes = Db::query($codeName, true);
        return retmsg(0, $attributes);
    }

    public function getAttributeValues($ruleName, $codeSort, $page = 1, $pagesize = 30, $searchString = '', $doorType = 'M9')
    {
        //属性值过多，表格展示时分页处理
        $start = $pagesize * ($page - 1) + 1;
        $end = $page * $pagesize;
        $where = "";
        if (!empty($searchString)) {
            $where .= "and t.attri_name like '%$searchString%'";
        }
        $where .= " and t.door_type='$doorType'";
        $detail = "select t1.* from (select t.*, row_number() over(order by t.code asc) rn from bom_code_rule t where t.rule_name ='$ruleName' and t.code_sort='$codeSort' $where) t1
                   where t1.rn between $start and $end";
        $total = "select * from bom_code_rule t where t.rule_name ='$ruleName' and t.code_sort='$codeSort' $where";
        $amount = count(Db::query($total));
        $attriValues = Db::query($detail, true);
        $data = array();
        $data['count'] = $amount;
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['list'] = $attriValues;
        return retmsg(0, $data);
    }


    /**
     * 添加编码,可以批量添加
     * 选择需要添加的属性，输入添加的属性值以及code码，进行入库操作
     */
    public function addCodeInfo($ruleName = '成品', $codeSort = 3, $doorType = 'M9')
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $data = $data['data'];
        $existAttri = array();
        foreach ($data as $key => $val) {
            $attriName = $val['attri_name'];
            $code = $val['code'];
            $sql = "select distinct(code_sort),order_column_name,code_name from bom_code_rule where rule_name='$ruleName' and code_sort='$codeSort' and door_type='$doorType'";
            $info = Db::query($sql);
            $codeName = $info[0]["CODE_NAME"];
            $codeSort = $info[0]['CODE_SORT'];
            $orderColumnName = $info[0]['ORDER_COLUMN_NAME'];
            $isDefault = empty($val['is_default']) ? 0 : 1;//是否设为默认属性
            $existSql = "select attri_name from bom_code_rule where rule_name='$ruleName' and attri_name='$attriName' and code_sort='$codeSort' and door_type='$doorType'";
            $existData = Db::query($existSql);
            if (empty($existData)) {//数据存在则不添加
                DB::startTrans();
                $insertSql = "insert into bom_code_rule(attri_name,code,code_sort,rule_name,order_column_name,is_default,code_name,door_type) values ('$attriName','$code','$codeSort','$ruleName','$orderColumnName','$isDefault','$codeName','$doorType')";
                $retAttri = Db::execute($insertSql);
                if (!$retAttri) {
                    DB::rollback();
                    return retmsg(-1, null, $insertSql);
                }
                DB::commit();
            } else {
                array_push($existAttri, $attriName);
            }
        }
        if (empty($existAttri)) {
            return retmsg(0, null, '添加成功！');
        } else {
            $str = "";
            foreach ($existAttri as $k) {
                $str .= $k . ",";
            }
            $str = substr($str, 0, -1);
            return retmsg(-1, null, "属性: $str 已存在！");
        }
    }

    /**
     * 修改编码信息---暂时仅能修改code值
     */
    public function updateCodeInfo($ruleName = '成品', $codeSort = '3', $attriName = '', $doorType = 'M9')
    {
        $data = json_decode(file_get_contents("php://input"), true);//修改后的code值
        $code = $data['code'];
        $updateSql = "update bom_code_rule set code='$code' where code_sort='$codeSort' and attri_name='$attriName' and rule_name='$ruleName' and door_type='$doorType'";
        $re = Db::execute($updateSql);//返回影响行数
        if ($re > 0) {
            return retmsg(0, null, '修改成功!');
        } else {
            return retmsg(-1, null, '没有数据被修改!');
        }
    }

    /**
     * 根据属性名称模糊查询
     */
    public function searchCodeInfo($ruleName = '成品', $codeSort = '3', $searchString = '', $page = 1, $pagesize = 20, $doorType = 'M9')
    {
        //属性值过多，表格展示时分页处理
        $start = $pagesize * ($page - 1) + 1;
        $end = $page * $pagesize;
        if (strpos($searchString, '*') !== false) {
            $searchString = str_replace('*', '×', $searchString);
        }
        $searchSql = "select t1.* from (select t.*, row_number() over(order by t.code asc) rn from bom_code_rule t where 
t.rule_name ='$ruleName' and t.code_sort='$codeSort' and t.door_type='$doorType' and t.attri_name like '%$searchString%' and t.is_default !=1) t1
                   where t1.rn between $start and $end";
        $totalSql = "select * from bom_code_rule where rule_name='$ruleName' and code_sort='$codeSort' and t.door_type='$doorType' and attri_name like '%$searchString%' ";
        $list = Db::query($searchSql, true);
        $total = count(Db::query($totalSql, true));
        $data = array();
        $data['count'] = $total;
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['list'] = $list;
        return retmsg(0, $data);
    }

    /**
     * 删除某属性编码信息
     */
    public function deleteCodeInfo($ruleName = '成品', $codeSort = '3', $attriName = '门中窗', $doorType = 'M9')
    {
        $deleteSql = "delete from bom_code_rule where rule_name = '$ruleName' and code_sort = '$codeSort' and attri_name='$attriName' and door_type='$doorType'";
        Db::startTrans();
        Db::execute($deleteSql);
        Db::commit();
        return retmsg(0, null, '删除成功！');
    }

    /**
     * 显示全部code数据，默认为非规格的数据
     */
    public function getAllAttributes($ruleName, $column = '', $page = 1, $pagesize = 30, $searchString = '', $print = false, $doorType = 'M9')
    {
        $start = $pagesize * ($page - 1) + 1;
        $end = $page * $pagesize;
        if (strpos($searchString, '*') !== false) {
            $searchString = str_replace('*', '×', $searchString);
        }
        $where = "";
        $column = strtoupper($column);
        if (!empty($column) && $column == "GUIGE") {
            $where .= " and t.order_column_name='$column'";
        } else {
            $where .= " and t.order_column_name!='GUIGE'";
        }
        if (!empty($searchString)) {
            $where .= " and t.attri_name like '%$searchString%'";
        }
        $where .= " and t.door_type='$doorType'";
        $findSql = "select t1.*  from (select t.*, row_number() over(order by t.code_sort asc) rn from bom_code_rule t
  where t.rule_name = '$ruleName'  $where ) t1 where t1.rn between $start and $end";
        $total = "select * from bom_code_rule t where t.rule_name ='$ruleName'  $where";
        $amount = count(Db::query($total));
        $list = Db::query($findSql, true);
        $data = array();
        $data['count'] = $amount;
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['list'] = $list;
        if ($print) {
            return $data;
        } else {
            if (empty($data['list'])) {
                return retmsg(-1, $data, getDoorDesc($doorType) . "编码规则功能暂未开放");
            } else {
                return retmsg(0, $data);
            }
        }
    }

    /**
     * 导出简易显示---横向显示
     */
//    public function exportExcelConvient($ruleName='成品',$column=''){
//        set_time_limit(600);
//        ini_set('memory_limit', "-1");//设置内存无限制
//        $objPHPExcel = new \PHPExcel();
//        $objPHPExcel->setActiveSheetIndex(0);
//        $objPHPExcel->getActiveSheet()->freezePane('A3');
//        $objPHPExcel->getActiveSheet()->setTitle('codeRules');
//        //标题栏垂直居中
//        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//        //设置B列的宽度
//        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
//        //待打印数据
//        $printData  = $this->getAllAttributes($ruleName,$column,1,100000,'',true);
//        //设置表头
//        $objPHPExcel->getActiveSheet()->setCellValue('A1',$ruleName.'编码规则表');
//        $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
//        $objPHPExcel->getActiveSheet()->setCellValue('A3',$ruleName);
//        $length = $printData['count']+2;
//        $objPHPExcel->getActiveSheet()->mergeCells("A3:A$length");
//        $objPHPExcel->getActiveSheet()->getStyle('A3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//        $objPHPExcel->getActiveSheet()->setCellValue('A2','编码类型');
//        $objPHPExcel->getActiveSheet()->setCellValue('B2','编码位数');
//        $objPHPExcel->getActiveSheet()->setCellValue('C2','订单属性');
//        $objPHPExcel->getActiveSheet()->setCellValue('D2','编码');
//        $objPHPExcel->getActiveSheet()->setCellValue('E2','属性值');
//        //宽度设置
//        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
//        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
//        //编码位数和订单属性的特殊处理
//        if(empty($column)){
//            $sql = "select distinct(t.code_sort),t.order_column_name,t.code_name from bom_code_rule t where t.rule_name='$ruleName' and t.order_column_name!='GUIGE' order by t.code_sort asc";
//        }else{
//            $sql = "select distinct(t.code_sort),t.order_column_name,t.code_name from bom_code_rule t where t.rule_name='$ruleName' and t.order_column_name='GUIGE' order by t.code_sort asc";
//        }
//        $result = Db::query($sql,true);
//        $start = 3;//打印起始行为第三行
//        foreach ($result as $key=>$val){
//            $code_sort = $val['code_sort'];
//            $findLength = Db::query("select count(t.code_sort) as amount from bom_code_rule t  where t.rule_name='$ruleName' and t.code_sort='$code_sort'",true);
//            $separateLength = $findLength[0]['amount'];
//            //B列,C列
//            $end = $separateLength+$start-1;
//            $objPHPExcel->getActiveSheet()->setCellValue("B$start",'第'.$val['code_sort'].'码');
//            $objPHPExcel->getActiveSheet()->mergeCells("B$start:B$end");
//            $objPHPExcel->getActiveSheet()->setCellValue("C$start",$val['code_name']);
//            $objPHPExcel->getActiveSheet()->mergeCells("C$start:C$end");
//            $start = $start+$separateLength;
//        }
//        foreach ($printData['list'] as $rowKey=>$rowValue){
//            $row=$rowKey+3;//数值行
//            $objPHPExcel->getActiveSheet()->setCellValue("D".$row,$rowValue['code']);
//            $objPHPExcel->getActiveSheet()->setCellValue("E".$row,$rowValue['attri_name']);
//        }
//        // 直接输出到浏览器
//        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
//        //输出到浏览器
//        header("Pragma: public");
//        header("Expires: 0");
//        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
//        header("Content-Type:application/force-download");
//        header("Content-Type:application/vnd.ms-execl");
//        header("Content-Type:application/octet-stream");
//        header("Content-Type:application/download");
//        $filename=$ruleName."编码规则表.xls";
//        header("Content-Disposition:attachment;filename=$filename");
//        header("Content-Transfer-Encoding:binary");
//        $objWriter->save('php://output');
//    }


//    public function importCsv($path){
//        vendor("PHPExcel.Classes.PHPExcel");
//        $data = array ();//csv 数据数组
//        $n = 0;
//        $k = 0;
//        $m = 0;
//        $handle=fopen($path,'r');
//        //循环读取每一行数据解析成二维数组
//        while ($row = fgetcsv($handle, 10000)) {
//            $num = count($row);
//            for ($i = 0; $i < $num; $i++) {
//                $data[$n][$i] =iconv("gbk","utf-8",$row[$i]);
//            }
//            $n++;
//        }
//        fclose($handle);
//        $len = count($data);
//        for($j = 1;$j < $len;$j++){//第2行（下标为1）开始循环数据行
//            $m++;
//            $ruleName = $data[$j][0];//规则类型
//            $attriName = $data[$j][1];//属性值
//            $code = $data[$j][2];//编码值
//            $codeName = $data[$j][3];//编码名称
//            //根据规则类型和编码名称查询涉及到的属性值及其code码位
//            $sqlInfo = "select distinct(t.code_sort),t.order_column_name from bom_code_rule t where t.rule_name='$ruleName' and t.code_name like '%$codeName%'";
//            $sqlResult = Db::query($sqlInfo,true);
//            $codeSort = $sqlResult[0]['code_sort'];
//            $orderColumnName = $sqlResult[0]['order_column_name'];
//            //1.先查询该条属性值及其对应code是否存在
//            $searchSql = "select * from bom_code_rule t where t.rule_name='$ruleName' and t.code_name like '%$codeName%' and t.attri_name like '%$attriName%'";
//            $searchResult = Db::query($searchSql);
//            $insertSql = "insert into bom_code_rule(attri_name,code,code_sort,rule_name,order_column_name,code_name) values
//                                  ('$attriName','$code','$codeSort','$ruleName','$orderColumnName','$codeName')";
//            if(empty($searchResult)){//数据入库操作
//                DB::startTrans();
//                $re = Db::execute($insertSql);
//                if(!$re){
//                    DB::rollback();
//                    return retmsg(-1,null,$insertSql);
//                }
//                DB::commit();
//                $n++;
//            }else{
//                //暂不做处理
//            }
//        }
////        return retmsg(0,null,"编码规则总录入:$m,成功:$n;");
//        echo json_encode(array('resultcode'=>0,'data'=>null,'resultmsg'=>"上传编码规则:$m,成功:$k;"));
//    }

    /**
     * 编码规则导入,简单表格导入
     */
//    public function importExcelConvient(){
//        $file = $_FILES['file'] ['name'];
//        $filetempname = $_FILES ['file']['tmp_name'];
//        $filePath = str_replace('\\','/',realpath(__DIR__.'/../../../')).'/excel/';
//        $filename = explode(".", $file);
//        $time = date("YmdHis");
//        $filename [0] = $time;//取文件名t替换
//        $name = implode(".", $filename); //上传后的文件名
//        $uploadfile = $filePath . $name;
//        $result=move_uploaded_file($filetempname,$uploadfile);
//        if($result){
//            $extension = substr(strrchr($file, '.'), 1);
//            if ($extension != 'xls' && $extension != 'xlsx'){
//                $this->response(array('resultcode'=>-1,'resultmsg'=>'文件格式错误!'),'json');
//            }
//            $n = 0; //成功插入记录条数
//            $m = 0;
//            $objPHPExcel = \PHPExcel_IOFactory::load($uploadfile);
//            $sheet = $objPHPExcel->getSheet(0);//获取当前的工作表
//            $mergeCells = $sheet->getMergeCells();
//            unset($mergeCells["A1:E1"]);
//            foreach ($mergeCells as $key=>$val){
//                $arr = explode(':',$val);
//                $rowStart = $arr[0];
//                $rowEnd = $arr[1];
//                $start = substr($rowStart,1,6);
//                $end = substr($rowEnd,1,6);
//                $startChar = substr($rowStart,0,1);
//                $values = $objPHPExcel->getActiveSheet()->getCell($rowStart)->getValue();//取消合并之后第一个单元格有值
//                for($i = $start;$i <= $end;$i++){
//                    $objPHPExcel->getActiveSheet()->setCellValue("$startChar".$i,$values);//拆分单元格之后使每个格子的值等于之前合并的值
//                }
//            }
//            $highestRow = $sheet->getHighestRow(); // 总行数
//            $error = array();
//            for ($j = 3; $j <= $highestRow-1; $j++) {//从第2行开始取数据,到倒数第二行数据结束
//                $m++;
//                $ruleName = excel_trim($objPHPExcel->getActiveSheet()->getCell("A$j")->getValue());//编码类型
//                $codeSort = findNum(excel_trim($objPHPExcel->getActiveSheet()->getCell("B$j")->getValue()));//编码位数
//                $codeName = excel_trim($objPHPExcel->getActiveSheet()->getCell("C$j")->getValue());//属性
//                $code = excel_trim($objPHPExcel->getActiveSheet()->getCell("D$j")->getValue());//编码值
//                $attriName = excel_trim($objPHPExcel->getActiveSheet()->getCell("E$j")->getValue());//属性值
//                //根据规则类型和编码名称查询涉及到的属性值及其code码位
//                $sqlInfo = "select distinct(t.order_column_name) from bom_code_rule t where t.rule_name='$ruleName' and t.code_name like '%$codeName%'";
//                $sqlResult = Db::query($sqlInfo,true);
//                $orderColumnName = empty($sqlResult[0]['order_column_name'])?"NEWATTRI":$sqlResult[0]['order_column_name'];
//                //1.先查询该条属性值及其对应code是否存在,存在则不重复入库
//                $searchSql = "select * from bom_code_rule t where t.rule_name='$ruleName' and t.code_name='$codeName' and t.attri_name='$attriName' and t.code_sort='$codeSort'";
//                $searchResult = Db::query($searchSql,true);
//                $insertSql = "insert into bom_code_rule(attri_name,code,code_sort,rule_name,order_column_name,code_name) values
//                                  ('$attriName','$code','$codeSort','$ruleName','$orderColumnName','$codeName')";
//                if(empty($searchResult)){//数据入库操作
//                    DB::startTrans();
//                    $re = Db::execute($insertSql);
//                    if(!$re){
//                        DB::rollback();
//                        return retmsg(-1,null,$insertSql);
//                    }
//                    DB::commit();
//                    $n++;
//                }else{
//                    //暂不做处理
//                    array_push($error,$searchResult);
//                }
//            }
//            var_dump($error);
//            unlink($uploadfile);//删除临时文件
//            return retmsg(0,null,"编码规则录入:$m,成功:$n;");
//        }
//    }

    /**
     * 通过导出的表格导入Excel(导出后编辑数据之后在进行导入)
     */
    public function exportExcel($ruleName = '成品', $column = '', $doorType = 'M9')
    {
        vendor("PHPExcel.Classes.PHPExcel");
        set_time_limit(600);
        ini_set('memory_limit', "-1");//设置内存无限制
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->freezePane('A5');
        $sheet->setTitle($ruleName . "编码规则");
        //标题栏垂直居中
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //获取打印的码位
        $codes = $this->getAttriByRule($ruleName, $doorType);
        $codes = $codes['data'];
        $count = count($codes);
        $endColumn = \PHPExcel_Cell::stringFromColumnIndex(2 * $count - 1);//根据打印数据自动获取列
        //设置表头
        $sheet->setCellValue('A1', $ruleName . '编码规则表');
        $sheet->mergeCells('A1:' . $endColumn . "1");
        //设置标题栏
        $startChar = 'A';
        foreach ($codes as $key => $val) {
            $codeSort = "第" . $val['code_sort'] . "码";
            $codeName = $val['code_name'];
            $charStart = chr(ord($startChar) + 2 * $key);
            $charEnd = chr(ord($charStart) + 1);
            if ($key >= 13) {
                $charStart = 'A' . chr(ord($startChar) + 2 * ($key - 13));
                $charEnd = 'A' . chr(ord($startChar) + 2 * ($key - 13) + 1);
            }
            if ($key >= 26) {
                $charStart = 'B' . chr(ord($startChar) + 2 * ($key - 26));
                $charEnd = 'B' . chr(ord($startChar) + 2 * ($key - 26) + 1);
            }
            //设置标题栏值并将相邻单元格合并
            $sheet->setCellValue($charStart . "2", $codeSort);
            $sheet->setCellValue($charStart . "3", $codeName);
            $sheet->mergeCells($charStart . "2:" . $charEnd . "2");
            $sheet->mergeCells($charStart . "3:" . $charEnd . "3");
            //详细标题栏---值域和代码
            $sheet->setCellValue($charEnd . "4", "代码");
            //打印的数据栏
            //1.查询当前码位下的数据
            $sort = $val['code_sort'];
            $printData = "select t.attri_name,t.code,t.code_name from bom_code_rule t where t.rule_name='$ruleName' and t.code_sort='$sort' and t.door_type='$doorType' order by t.is_default desc,t.code asc";
            $data = Db::query($printData, true);
            foreach ($data as $k => $v) {
                $sheet->setCellValue($charStart . "4", strval($v["code_name"]));
                $sheet->setCellValue($charStart . ($k + 5), strval($v["attri_name"]));
                $sheet->setCellValueExplicit($charEnd . ($k + 5), strval($v['code']), \PHPExcel_Cell_DataType::TYPE_STRING);//转字符串
            }
        }
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
        $intro = getDoorDesc($doorType);
        $filename = $intro . "_" . $ruleName . "编码规则表.xls";
        header("Content-Disposition:attachment;filename=$filename");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }


    public function importExcel($doorType = 'M9')
    {
        set_time_limit(0);
        vendor("PHPExcel.Classes.PHPExcel");
        $file = $_FILES['file'] ['name'];
        $filetempname = $_FILES ['file']['tmp_name'];
        $filePath = str_replace('\\', '/', realpath(__DIR__ . '/../../../')) . '/upload/';
        $filename = explode(".", $file);
        $time = date("YmdHis");
        $filename [0] = $time;//取文件名t替换
        $name = implode(".", $filename); //上传后的文件名
        $uploadfile = $filePath . $name;
        $result = move_uploaded_file($filetempname, $uploadfile);
        if ($result) {
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
            //获取A1列的值
            $ruleName = trim(str_replace('编码规则表', '', $sheet->getCell('A1')->getValue()));
            //删除ruleName对应的值
            $delSql = "delete from bom_code_rule t where t.rule_name='$ruleName' and t.door_type='$doorType'";
            $res = Db::execute($delSql);
            if ($res) {
                DB::commit();
            } else {
                DB::rollback();
            }
            //获取列值
            $highestColumn = $sheet->getHighestColumn();
            $highestNum = \PHPExcel_Cell::columnIndexFromString($highestColumn);
            $error = array();
            $total = 0;//预计插入的总记录数
            $done = 0;//成功
            $notDone = 0;//失败
            $is_default = 0;//是否设置属性默认值
            $definedArr = codeRule($ruleName, $doorType);//属性定义数组
            for ($j = 0; $j < intval($highestNum / 2); $j++) {//1---22
                $m = 2 * $j;//奇数列
                $n = 2 * $j + 1;//偶数列
                $oddRow = \PHPExcel_Cell::stringFromColumnIndex($m);//奇数列
                $evenRow = \PHPExcel_Cell::stringFromColumnIndex($n);//偶数列
                $codeSort = findNum($sheet->getCell($oddRow . "2")->getValue());
                //查询OrderColumnName
                $columnName = $definedArr[$codeSort]['order_column_name'];
                $codeName = $definedArr[$codeSort]['code_name'];
                //循环获取每一列的行数
                $oddRowNum = $sheet->getHighestRow($oddRow);
                $evenRowNum = $sheet->getHighestRow($evenRow);
                //逐列取值
                for ($k = 5; $k <= $oddRowNum; $k++) {
                    if ($k == 5) { //默认值设置
                        $is_default = 1;
                    } else {
                        $is_default = 0;
                    }
                    $eachRowLeftValAttri = excel_trim($sheet->getCell($oddRow . $k)->getValue());
                    $eachRowRightValCode = excel_trim($sheet->getCell($evenRow . $k)->getValue());
                    //方法一：merge into 处理如果该属性值已经存在则更新code的值，否则将新增数据入库
                    //属性值和code值不为空则录入
//                    if(!empty($eachRowLeftValAttri) && !empty($eachRowRightValCode)){
//                        $total++;
//                        $insertOrUpdateSql = "merge into bom_code_rule a using (select '$eachRowLeftValAttri' as attri_name,'$eachRowRightValCode' as code,'$codeSort' as code_sort,'$ruleName' as rule_name,
//                            '$columnName' as order_column_name,'$codeName' as code_name,'0' as is_default  from dual) b on (a.rule_name=b.rule_name
//                            and a.code_sort=b.code_sort and a.attri_name = b.attri_name)
//                            when matched then
//                              update set a.code = b.code where a.attri_name = b.attri_name and a.rule_name=b.rule_name and a.code_sort=b.code_sort
//                            when not matched then
//                              insert values (b.attri_name, b.code, b.code_sort, b.rule_name, b.order_column_name,b.is_default,b.code_name)";
//                        DB::startTrans();
//                        $affectedRow = Db::execute($insertOrUpdateSql);
//                        if(!$affectedRow){
//                            DB::rollback();
//                            return retmsg(-1,null,$insertOrUpdateSql);
//                        }
//                        DB::commit();
//                    }
                    //方法二：1.删除对应的code_sort的值,在读取表格数据逐一插入数据库
                    if ((!empty($eachRowLeftValAttri) || $eachRowLeftValAttri == 0) && $eachRowRightValCode != '') {
                        $total++;
                        $insertSql = "insert into bom_code_rule(attri_name,code,code_sort,rule_name,order_column_name,is_default,code_name,door_type) values
                                  ('$eachRowLeftValAttri','$eachRowRightValCode','$codeSort','$ruleName','$columnName','$is_default','$codeName','$doorType')";
                        DB::startTrans();
                        $re = Db::execute($insertSql);
                        if (!$re) {
                            DB::rollback();
                            return retmsg(-1, null, $insertSql);
                        } else {
                            DB::commit();
                            $done++;
                        }
                    }
                }
            }
            unlink($uploadfile);//删除临时文件
            return retmsg(0, null, "编码规则录入成功");
        }
    }
}
