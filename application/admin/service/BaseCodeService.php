<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5
 * Time: 11:11
 */

namespace app\admin\service;

use think\Db;


/**
 * 编码料号公共服务
 * Class BaseCode
 * @package app\admin\service
 */
class BaseCodeService
{
    const CHENGPIN = "成品";//成品编码规则名称
    const FH_CHENGPIN = "防火门成品";//防火门成品编码规则名称
    const CHENGPIN_FIXED_CODE = "KH";//成品规则前2位固定码
    const BANPINMK = "半品门框";//半品门框编码规则名称
    const FH_BANPINMK = "防火门半品门框";//防火门半品门框编码规则名称
    const BANPINMS = "半品门扇";//半品门扇编码规则名称
    const FH_BANPINMS = "防火门半品门扇";//防火门半品门扇编码规则名称
    const BANPINMK_FIXED_CODE = "KB";//半品门框前2位固定码
    const BANPINMS_FIXED_CODE = "KB";//半品门扇前2位固定码
    const CHUANGHUA = "窗花";//窗花编码规则名称
    const CHUANGHUA_FIXED_CODE = "KGMAP";//窗花规则前5位固定码
    const DANKUANG = "单框";//单框编码规则名称
    const MENBAN = "门板";//门板编码规则名称
    const MENBAN_FIXED_CODE = "KG";//门板前2位固定码
    const DANKUANG_FIXED_CODE = "KG";//单框前2位固定码
    const CAIZHI = "材质";//材质编码规则名称
    const CAIZHI_FIXED_CODE = "KM4";//材质前3位固定码
    const CAIZHI_TESHU_FIXED_CODE = "KM3PTGXXXXX";//矩管底框材质前11位固定码
    const CAIZHI_DENSITY = 7.85;//材质密度
    const QPA_MENBAN_WIDTH_ADD = 2;//门板计算加此数
    const WAIGOU_MENKUANG = "外购门框";//外购门框
    const WAIGOU_MENKUANG_FIXED = 'KG1XX';//外购门框固定码
    const BIESHU_DOOR = "别墅门";//别墅大门---针对门框为别墅大门21墙和别墅大门27墙
    const BIESHU_DOOR_FIXED = "KPPBQ";//别墅门固定码
    const FH_CP_DOOR_FIXED = "FH";//防火门成品前2位固定码
    const FH_BPMK_DOOR_FIXED = "FK";//防火门半品门框前2位固定码
    const FH_BPMS_DOOR_FIXED = "FS";//防火门半品门扇前2位固定码


    /**
     * 根据订单信息获取料号
     * @param $ruleName 规则名称
     * @param $order 订单信息 包含用料信息
     * @param $dankuanglx 单框类型：上框、低框、边框、中框，
     * @param $menshanlx 门扇类型 母，子，母子，子子 默认母
     * @param $menbanlx 门板类型 前门板、后门板
     * $param $chuanghualx 窗花类型 (只有四开窗花时才有)大、小
     * $param $waigoulx 外购门框类型
     */
    public function getMaterialCode($ruleName, $order, $dankuanglx='', $menshanlx='', $menbanlx='', $chuanghualx='', $waigoulx='',$bieshulx='')
    {
//        $doorType = empty($order['order_db'])?'M9':$order['order_db'];//默认门类型为钢质门
        $doorType = ($order['order_db'] == 'M8')?'M8':'M9';//默认门类型为钢质门
        $findAllData = "select t.* from bom_code_rule t where t.rule_name='$ruleName' and t.door_type='$doorType' and t.order_column_name!='GUIGE' order by t.code_sort asc";
        $sortNum = "select distinct(code_sort),order_column_name from bom_code_rule where rule_name='$ruleName' and door_type='$doorType' order by code_sort asc";
        $allData = DB::query($findAllData);
        //去空格
        foreach ($allData as $k => $v) {
            foreach ($v as $key => $val) {
                $allData[$k][$key] = excel_trim($val);
            }
        }
        $sorts = DB::query($sortNum);
        $handleSortAraay = array();//待处理的序号数组
        foreach ($sorts as $k=>$v) {
            array_push($handleSortAraay, $v['CODE_SORT']);
        }

        //编码数组
        $code = array();
        //对规格进行单独处理
        $guige = $order['guige'];
        if ($ruleName=='半品门扇' || $ruleName=='防火门半品门扇') {
            $guige = $this->gaoduChange($order['menkuang'], $order['height'], $order['chuanghua'], $order['zhizaobm']).'×'.$order['width'];
        }
        $guigeChuli = "select code,code_sort from bom_code_rule where rule_name='$ruleName' and attri_name='$guige' and door_type='$doorType'";
        $guigeCode = DB::query($guigeChuli);
        if (empty($guigeCode)) {
            $guigeCode = DB::query("select code,code_sort from bom_code_rule where rule_name='$ruleName' and order_column_name='GUIGE' and is_default=1 and door_type='$doorType'");
        }
        if (!empty($guigeCode)) {
            $guigeMaterialCode = $guigeCode[0]['CODE'];
            $guigeCodeSort = $guigeCode[0]['CODE_SORT'];
            $guigeArr = array("sort_number"=>$guigeCodeSort,"code"=>$guigeMaterialCode);
            $code[$guigeCodeSort]=$guigeArr;
            unset($handleSortAraay[array_search($guigeCodeSort, $handleSortAraay)]);
        }
        //规格处理结束
        $fixedCode = "";
        foreach ($allData as $key=>$val) {
            $attriName = $val['ATTRI_NAME'];//属性值
            $orderColumnName = $val['ORDER_COLUMN_NAME'];//关联的订单列名
            $orderAttrName=$order[strtolower($orderColumnName)];//关联的订单属性值
            $orderMultiName = "";//关联多订单属性，拼接字符串值
            if (strpos($orderColumnName, ',')) {
                $orderColumnNameArray=explode(',', $orderColumnName);
                foreach ($orderColumnNameArray as $k=>$v) {
                    $orderMultiName .= $order[strtolower($v)];
                }
            }
            $codeName=$val['CODE_NAME'];//编码含义
            $sortID = $val['CODE_SORT'];//排序码
            $isDefault=$val['IS_DEFAULT'];//默认占位符
            switch ($ruleName) {
                //成品编码规则
                case self::CHENGPIN:
                    $fixedCode = self::CHENGPIN_FIXED_CODE;
                    if ($sortID==23) {
                        $test=1;
                    }
                    if ($attriName == '') {

                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        //规则属性值与订单属性值匹配
                        if (
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //门框 门扇类型
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //主锁 门锁体类型及品牌
//                            ($codeName=='主锁'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //锁芯
//                            ($codeName=='锁芯'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //底框材料 底框厚度
                            ($codeName=='底框厚度'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName, $attriName, $order,'','','','','','','成品'))||
                            //多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)||
                            //门扇开孔
                            ($codeName=='门扇开孔'&&$this->match($codeName, $attriName, $orderAttrName))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                case self::FH_CHENGPIN:
                    $fixedCode = self::FH_CP_DOOR_FIXED;
                    if ($sortID==23) {
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        //规则属性值与订单属性值匹配
                        if (
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //门框 门扇类型
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //主锁 门锁体类型及品牌
//                            ($codeName=='主锁'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //锁芯
//                            ($codeName=='锁芯'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //底框材料 底框类型
//                            ($codeName=='底框类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //底框材料 底框厚度
//                            ($codeName=='底框厚度'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName, $attriName, $order))||
                            //多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                            //门扇开孔
//                            ($codeName=='门扇开孔'&&$this->match($codeName, $attriName, $orderAttrName))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //半品门框编码规则
                case self::BANPINMK:
                    $fixedCode = self::BANPINMK_FIXED_CODE;
                    if ($codeName=='门扇类型') {
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #门框 门扇类型 门框中提取
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #主锁 门锁体类型及品牌
//                            ($codeName=='主锁'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #底框材料 底框厚度
                            ($codeName=='底框厚度'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName, $attriName, $order,'','','','','','','半品门框'))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //防火门半品门框编码规则
                case self::FH_BANPINMK:
                    $fixedCode = self::FH_BPMK_DOOR_FIXED;
                    if ($codeName=='门扇类型') {
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #门框 门扇类型 门框中提取
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #主锁 门锁体类型及品牌
//                            ($codeName=='主锁'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #底框材料 底框类型
//                            ($codeName=='底框类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #底框材料 底框厚度
                            ($codeName=='底框厚度'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName, $attriName, $order))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //半品门扇编码规则
                case self::BANPINMS:
                    $fixedCode = self::BANPINMS_FIXED_CODE;
                    if ($codeName=='半成品类型') {
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #半成品类型
                            ($codeName=='半成品类型'&&$this->match($codeName, $attriName, $orderAttrName, '', $menshanlx))||
                            #门框 门扇类型=门框+门框要求
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #主锁 门锁体类型及品牌
//                            ($codeName=='主锁'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName, $attriName, $order,'','','','','','','半品门扇'))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)||
                            //门扇开孔
                            ($codeName=='门扇开孔'&&$this->match($codeName, $attriName, $orderAttrName))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                case self::FH_BANPINMS:
                    $fixedCode = self::FH_BPMS_DOOR_FIXED;
                    if ($codeName=='半成品类型') {
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #半成品类型
                            ($codeName=='防火门半成品类型'&&$this->match($codeName, $attriName, $orderAttrName, '', $menshanlx))||
                            #门框 门扇类型=门框+门框要求
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #主锁 门锁体类型及品牌
//                            ($codeName=='主锁'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName, $attriName, $orderAttrName))||
                            #特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName, $attriName, $order))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)||
                            //门扇开孔
                            ($codeName=='门扇开孔'&&$this->match($codeName, $attriName, $orderAttrName))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //窗花编码规则
                case self::CHUANGHUA:
                    $fixedCode = self::CHUANGHUA_FIXED_CODE;
                    if ($codeName=='窗花材质') {
                        $test=1;
                    }
                    //获取窗花用料规则
                    if (!isset($chuanghuayl)) {
                        $chuanghuayl=$order['chuanghuayl'];
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        //规则属性值与订单属性值匹配
                        if (
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //半成品状态
                            ($codeName=='半成品状态'&&$this->match($codeName, $attriName, $orderAttrName,'','','','','',$order['operate_plan']))||
                            //材质
                            ($codeName=='窗花材质'&&$this->match($codeName, $attriName, $order))||
                            //成品外形
                            ($codeName=='成品外形'&&$attriName==$this->orderValueConvert(strtolower($orderColumnName), $orderAttrName,$doorType))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //高度宽度特殊处理
                        elseif ($codeName=='高度') {
                            $codeStr=$chuanghuayl['chuanghua_height'];
                            if ($chuanghualx=='大') {
                                $codeStr=$chuanghuayl['chuanghua_height_d'];
                            } elseif ($chuanghualx=='小') {
                                $codeStr=$chuanghuayl['chuanghua_height_x'];
                            }
							$codeStr=sprintf("%03d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //宽度特殊处理
                        elseif ($codeName=='宽度') {
                            $codeStr=$chuanghuayl['chuanghua_width'];
                            if ($chuanghualx=='大') {
                                $codeStr=$chuanghuayl['chuanghua_width_d'];
                            } elseif ($chuanghualx=='小') {
                                $codeStr=$chuanghuayl['chuanghua_width_x'];
                            }
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //单框编码规则
                case self::DANKUANG:
                    $fixedCode = self::DANKUANG_FIXED_CODE;
                    if ($codeName=='单框材质') {
                        $test=1;
                    }
                    //获取门框用料规则
                    if (!isset($menkuangyl)) {
                        $menkuangyl=$order['menkuangyl'];
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        //规则属性值与订单属性值匹配
                        if (
                            //属性值完全匹配
                            ($attriName == $orderAttrName && $codeName!='门框厚度')||
                            //单框类型
                            ($codeName=='单框类型'&&$this->match($codeName, $attriName, $orderAttrName, $dankuanglx, $menshanlx))||
                            //门框型号
                            ($codeName=='门框型号'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //单框材质
                            ($codeName=='单框材质'&&$this->match($codeName, $attriName, $order, $dankuanglx))||
                            //底框材料 底框类型
                            ($codeName=='开向'&&$this->match($codeName, $attriName, $order, $dankuanglx, $menshanlx))||
                            //多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //门框长度特殊处理；边框用规格高度、其余用规格宽度，代码规则为4位用量尺寸，只有3位时第一位用0表示
                        elseif ($codeName=='门框长度') {
                            //上框，下门框，中门框查询宽度,铰框和锁框查询长度
                            if (in_array($dankuanglx, ['铰框','锁框'])) {
                                $codeStr=$order['height'];
                            } else {
                                $codeStr=$order['width'];
                            }
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //门框厚度特殊处理;中框和底框按照实际用料厚度,其余用门框厚度
                        elseif ($codeName=='门框厚度') {
                            $codeStr = $orderAttrName;
                            if ($dankuanglx=='中框') {
                                $zhongmk = $menkuangyl['shang_zhong_yongliao']['zhongmk'];
                                $codeStr = substr($zhongmk, 0, strpos($zhongmk, '*'));
                            } elseif ($dankuanglx=='底框') {
                                $xiamk = $menkuangyl['xia_yongliao']['xiamk'];
                                if (strpos($order['dkcailiao'],'矩管') !== false){
                                    $codeStr = $menkuangyl['xia_yongliao']['xiamk_hd'];
                                } else {
                                    $codeStr = substr($xiamk, 0, strpos($xiamk, '*'));
                                }
                            }
                            $codeStr=sprintf("%04d", $codeStr*100);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //外购门框编码规则
                case self::WAIGOU_MENKUANG:
                    $fixedCode = self::WAIGOU_MENKUANG_FIXED;
                    $order['menkuangcz'] = '冷轧';
                    if ($codeName=='外购门框开向') {
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            //属性值完全匹配
                            ($attriName == $orderAttrName && $codeName!='门框厚度'&& $codeName!='外购门框开向')||
                            //单框类型
                            ($codeName=='外购单框类型'&&$this->match($codeName, $attriName, $orderAttrName, $dankuanglx, $menshanlx,$menbanlx,$waigoulx))||
                            //门框型号
                            ($codeName=='门框型号'&&$this->match($codeName, $attriName, $orderAttrName))||
                            //开向
                            ($codeName=='外购门框开向'&&$this->match($codeName, $attriName, $orderAttrName, $dankuanglx,$menshanlx,$menbanlx,$waigoulx))||
                            //多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //门框长度特殊处理；绞门框、锁门框用规格高度、其余用规格宽度，代码规则为4位用量尺寸，只有3位时第一位用0表示
                        elseif ($codeName=='门框长度') {
                            //上门框，底框，中门框查询宽度,铰框和锁框查询长度
                            if (in_array($waigoulx, ['外购铰门框','外购锁门框'])) {
                                $codeStr=$order['height'];
                            } else {
                                $codeStr=$order['width'];
                            }
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //门框厚度特殊处理;中框按照实际用料厚度,其余用门框厚度
                        elseif ($codeName=='门框厚度') {
                            $codeStr = $orderAttrName;
                            if ($waigoulx=='外购中门框') {
                                $zhongmk = $menkuangyl['shang_zhong_yongliao']['zhongmk'];
                                $codeStr = substr($zhongmk, 0, strpos($zhongmk, '*'));
                            }
                            $codeStr=sprintf("%03d", $codeStr*100);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //门板编码规则
                case self::MENBAN:
                    $fixedCode = self::MENBAN_FIXED_CODE;
                    if ($codeName=='门板材质') {
                        $test=1;
                    }
                    if (($codeName=='后门板'&&$menbanlx!='后门板')||($codeName=='前门板'&&$menbanlx!='前门板')) {
                        continue;
                    }
                    //获取门板用料规则
                    if (!isset($menbanyl)) {
                        $menbanyl=$order['menbanyl'];
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        //规则属性值与订单属性值匹配
                        if (
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //前后门板
                            ($attriName == $menbanlx)||
                            //单框类型
                            ($codeName=='门板材质'&&$this->match($codeName, $attriName, $orderAttrName))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //门板长度,门板宽度特殊处理
                        elseif ($codeName=='门板宽度'||$codeName=='门板长度') {
                            $column=$codeName=='门板宽度'?'menban_width':'menban_length';
                            //母板单扇默认只有母板
                            $codeStr=$this->getMenbanYongliao($menshanlx, $menbanlx, $menbanyl, $column);
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //材质编码规则
                case self::CAIZHI:
                    $isJuguandk = strpos($order['dkcailiao'], '矩管') !== false?1:0;//是否为矩管底框
                    $fixedCode = self::CAIZHI_FIXED_CODE;
                    if ($codeName=='特殊要求') {
                        $test=1;
                    }
                    //获取门板用料规则
                    if (!isset($menbanyl)) {
                        $menbanyl=$order['menbanyl'];
                    }
                    //获取门框用料规则
                    if (!isset($menkuangyl)) {
                        $menkuangyl=$order['menkuangyl'];
                    }
                    if ($isJuguandk && $dankuanglx == '底框') {
                        $fixedCode = self::CAIZHI_TESHU_FIXED_CODE;
                        $codeArray = $this->getJuguanCode($code, $menkuangyl);
                        $code = $codeArray['code'];
                        $caizhiyl = $codeArray['caizhi'];
                        break;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        $special = $this->specialMenkuang($order['menkuang'],$order['menkuangcz']);
                        $order['menkuangcz'] = ($dankuanglx=='中框'&&$attriName!='材质'&& $special)?'冷轧':$order['menkuangcz'];
                        if (
                            //单框 （不包括底框，取门框材质）
                            $dankuanglx!=''&&$dankuanglx!='底框'&&strpos($order['menkuangcz'], $attriName)!==false||
                            //底框根据低框材料获取
                            $dankuanglx=='底框'&&$attriName!='材质厚度'&&strpos($order['dkcailiao'], $attriName)!==false||
                            //门板(取门板材质)
                            $menbanlx!=''&&strpos($order['menshancz'], $attriName)!==false||
                            //特殊要求
                            ($codeName=='特殊要求'&&$this->match($codeName, $attriName, $order, $dankuanglx, $menshanlx, $menbanlx))||
                            ($dankuanglx !='底框'&&$codeName=='材质'&&$this->match($codeName, $attriName, $order, $dankuanglx, $menshanlx, $menbanlx))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        //材质厚度、宽度
                        elseif ($codeName=='材质厚度'||$codeName=='材质宽度') {
                            //门板
                            //门板材质厚度为门板厚，宽为用料宽
                            if ($menbanlx!='') {
                                //宽度读取用料
                                if ($codeName=='材质宽度') {
                                    $codeStr=$this->getMenbanYongliao($menshanlx, $menbanlx, $menbanyl, 'menban_width');
                                    $caizhiyl['width']=$codeStr;
                                    $caizhiyl['height']=$this->getMenbanYongliao($menshanlx, $menbanlx, $menbanyl, 'menban_length');
                                }
                                //厚度读取门板厚度
                                else {
                                    $qianbanhReal=$this->orderValueConvert('qmenbhd', $order['qmenbhd'],$doorType);
                                    $houbanhReal=$this->orderValueConvert('hmenbhd', $order['hmenbhd'],$doorType);
                                    $caizhiyl['thickness_real']=$menbanlx=='前门板'?$qianbanhReal:$houbanhReal;
                                    $codeStr=$menbanlx=='前门板'?$order['qmenbhd']/100:$order['hmenbhd']/100;
                                    //门板厚为0.3和0.25的料号品名转化
                                    if (floatval($codeStr)==0.3) {
                                        $codeStr = 0.27;
                                    } elseif (floatval($codeStr)==0.25) {
                                        $codeStr = 0.23;
                                    }
                                    $caizhiyl['thickness']=$codeStr;
                                }
                            }
                            //门框
                            if ($dankuanglx!='') {
                                switch ($dankuanglx) {
                                    case'铰框':
                                        $yongliao=$menkuangyl['jiao_suo_yongliao'];
                                        $menkuang='jiaomk';
                                        break;
                                    case'锁框':
                                        $yongliao=$menkuangyl['jiao_suo_yongliao'];
                                        $menkuang='suomk';
                                        break;
                                    case'上框':
                                        $yongliao=$menkuangyl['shang_zhong_yongliao'];
                                        $menkuang='shangmk';
                                        break;
                                    case'中框':
                                        $yongliao=$menkuangyl['shang_zhong_yongliao'];
                                        $menkuang='zhongmk';
                                        break;
                                    case'底框':
                                        $yongliao=$menkuangyl['xia_yongliao'];
                                        $menkuang='xiamk';
                                        $caizhiDensity=$yongliao['xiamk_density'];
                                        break;
                                }
                                $codeStr=$yongliao[$menkuang];
                                $caizhiyl['height']=$yongliao[$menkuang.'_length'];
                                $caizhiyl['thickness']=substr($codeStr, 0, strpos($codeStr, '*'));
                                $caizhiyl['thickness_real']=$yongliao[$menkuang.'_hd'];
                                $caizhiyl['width']=substr($codeStr, strpos($codeStr, '*')+1);
                                $codeStr=explode('*', $codeStr);
                                $codeStr=$codeName=='材质厚度'?$codeStr[0]:$codeStr[1];
                            }
                            //格式化厚度宽度
                            $codeStr=$codeName=='材质厚度'?sprintf("%03d", $codeStr*100):sprintf("%05d", str_replace('.', 'X', $codeStr));
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //别墅大门编码规则
                case self::BIESHU_DOOR:
                    $fixedCode = self::BIESHU_DOOR_FIXED;
                    if ($codeName=='门类型') {
                        $test=1;
                    }
                    $guigeData = explode('*', $order['guige']);
                    if (empty($guigeData[1])) {
                        $guigeData = explode('×', $order['guige']);
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #半成品类型
                            ($codeName=='门类型'&&$this->match($codeName, $attriName, $orderAttrName, '', '','','',$bieshulx))||
                            #特殊要求 别墅门
                            ($codeName=='特殊要求'&&$this->match($codeName, $attriName, $order,'','','','',$bieshulx))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        } elseif ($codeName=='高度') {
                            $codeStr=sprintf("%04d", $guigeData[0]);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        } elseif ($codeName=='宽度') {
                            $codeStr=sprintf("%04d", $guigeData[1]);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID, $handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                default:
                    $fixedCode = "";
            }
        }

        $liaohao=$fixedCode;
        ksort($code);//根据键顺序重新排列
        foreach ($code as $kk=>$vv) {
            $liaohao .= $vv['code'];
        }
//        $yuanjianName=$ruleName;
        //钢框木扇名称区分其他防火门：2018-07-17
        $yuanjianName=($doorType == 'M8' && strpos($order['menkuang'],'钢框木扇') !== false)?str_replace("防火门","钢框木扇",$ruleName):$ruleName;
        $yuanjianLevel='成品级';
        //封闭式窗花原件名称为封闭式窗花，其余则为窗花---20190424不区分，窗花原件名称统一为窗花
        if ($ruleName == "窗花" && (strpos($order['chuanghua'],'封闭式气窗') !== false || strpos($order['chuanghua'],'封闭式窗花') !== false)) {
            $yuanjianName = '封闭式窗花';
        }
        if ($ruleName=='门板'||$ruleName=='单框') {
            $yuanjianName=$ruleName=='门板'?$menbanlx:$dankuanglx;
            if ($ruleName=='单框') {
                $yuanjianLevel='门框级';
            } else {
                $yuanjianLevel="门扇级(".$menshanlx."门)";
            }
        }
        if ($ruleName=='外购门框') {
            $yuanjianName = $waigoulx;
            $yuanjianLevel='门框级';
        }
        if ($ruleName == '别墅门') {
            $yuanjianName = $ruleName.$bieshulx;
        }

        $yongliang=1;//默认用量为1
        if ($chuanghualx=='大') {
            $yongliang=$chuanghuayl['chuanghua_yongliang_d'];
        } elseif ($chuanghualx=='小') {
            $yongliang=$chuanghuayl['chuanghua_yongliang_x'];
        }

        //外购别墅门
        if ($bieshulx == '门柱') {
            $yongliang = 2;
        }


        //除开单扇门铰框默认都是2单门扇默认有一边铰方一边锁方，对开、子母、四开默认两边都是铰方
        if ($dankuanglx=='铰框'&&strpos($order['menshan'], '单扇')===false&&!in_array($order['menkuangyq'],['左开左无边','右开右无边','左开右无边','右开左无边'])) {
            $yongliang = 2;
        }
        if ($waigoulx == '外购铰门框' && strpos($order['menshan'], '单扇') ===false) {
            $yongliang = 2;
        }
        //特殊门框底框用量为2
        $teshuMenkuangArr = array('70三角框115钢套门20墙','70三角框115钢套门18墙','70三角框115钢套门27墙');
        if ($dankuanglx == '底框' && trim($order['dang_ci']) == '钢套门' && in_array($order['menkuang'],$teshuMenkuangArr) && strpos($order['dkcailiao'],'矩管') !== false) {
            $yongliang=2;
        }

        //获取品名规格
        $name=$ruleName;
        if ($ruleName=='单框') {
            $name=$dankuanglx;
        } elseif ($ruleName=='门板') {
            $name=$menbanlx;
        } elseif ($ruleName == '外购门框') {
            $name = $waigoulx;
        }

        //材质品名规格用量
        $caizhiDensity=empty($caizhiDensity)?self::CAIZHI_DENSITY:$caizhiDensity;
        if ($ruleName=='材质') {
            $liaohaoGuige=$caizhiyl['thickness'].'*'.$caizhiyl['width'];
            if ($dankuanglx == '底框' && strpos($order['dkcailiao'], '矩管') !== false) {
                $liaohaoGuige = $caizhiyl['width'];
            }
            $liaohaoPinming='铁卷';
            //门板材质
            if ($menshanlx!=''&&!empty($order['menshancz'])) {
                $liaohaoPinming=$this->orderValueConvert('menkuangcz,menshancz', $order['menshancz'],$doorType);
            }
            //门框材质
            if ($dankuanglx!=''&&$dankuanglx!='底框'&&!empty($order['menkuangcz'])) {
                $liaohaoPinming=$this->orderValueConvert('menkuangcz,menshancz', $order['menkuangcz'],$doorType);
            }
            if ($dankuanglx=='底框') {
                $liaohaoPinming=$this->orderValueConvert('dkcailiao', $order['dkcailiao'],$doorType);
            }
            //门板QPA计算时用料宽度未+2
            if ($ruleName=='材质'&&$menshanlx!='') {
                $caizhiyl['width']+=self::QPA_MENBAN_WIDTH_ADD;
            }
            if ($dankuanglx == '底框' && strpos($order['dkcailiao'], '矩管') !== false) {
                $yongliang=round(($caizhiyl['thickness']+$caizhiyl['length'])*2*$caizhiyl['thickness_real']*$caizhiDensity*$caizhiyl['height']/1000000, 4);
            } else {
                $yongliang=round($caizhiyl['thickness_real']*$caizhiyl['width']*$caizhiyl['height']*$caizhiDensity/1000000, 4);
            }
            //除开单扇门 其余铰框默认都是2,对应的材质QPA*2
//            if ($dankuanglx=='铰框'&&strpos($order['menshan'], '单扇')===false) {
//                $yongliang=$yongliang*2;
//            }
            //特殊门框主材用量对应*2
//            if ($dankuanglx == '底框' && trim($order['dang_ci']) == '钢套门' && in_array($order['menkuang'],$teshuMenkuangArr) && strpos($order['dkcailiao'],'矩管') !== false) {
//                $yongliang = $yongliang*2;
//            }
        } else {
            $liaohaoPinming=$this->getPingmingOrGuige($name.'品名规则', $order, $dankuanglx, $menshanlx, $menbanlx, $chuanghualx,$waigoulx,$bieshulx);
            $liaohaoGuige=$this->getPingmingOrGuige($name.'规格规则', $order, $dankuanglx, $menshanlx, $menbanlx, $chuanghualx,$waigoulx,$bieshulx);
        }
        return [
            'liaohao'=>$liaohao,
            'liaohao_guige'=>$liaohaoGuige,
            'liaohao_pinming'=>$liaohaoPinming,
            'yongliang'=>$yongliang,
            'liaohao_danwei'=>'',
            'yuanjian_name'=>$yuanjianName,
            'yuanjian_level'=>$yuanjianLevel,
        ];
    }

    /**
     * @param $codeName 待匹配的规则名称
     * @param $ruleAtrName
     * @param $orderAttrName
     * @param string $dankuanglx 单框类型
     * @param string $menshanlx 门扇类型
     * @param $string $menbanlx 门板类型
     * @return bool|int
     */
    public function match($codeName, $ruleAtrName, $orderAttrName='', $dankuanglx='', $menshanlx='', $menbanlx='', $waigoulx='',$bieshulx='',$zhizaobm='',$ruleName='')
    {
        switch ($codeName) {
            //半成品类型
            case '半成品类型':
                if (strpos($orderAttrName, '单扇')!==false) {
                    $str='单扇门';
                } elseif (strpos($orderAttrName, '对开')!==false) {
                    $str='对开'.$menshanlx.'扇';
                } elseif (strpos($orderAttrName, '子母')!==false) {
                    $str='子母'.$menshanlx.'扇';
                }
                if (strpos($orderAttrName, '子母四开')!==false) {
                    $str='子母四开边门';
                    if ($menshanlx=='母'||$menshanlx=='子') {
                        $str='子母四开'.$menshanlx.'扇';
                    }
                } elseif (strpos($orderAttrName, '均等四开')!==false) {
                    $str='均等四开边门';
                    if ($menshanlx=='母'||$menshanlx=='子') {
                        $str='均等四开'.$menshanlx.'扇';
                    }
                }

                return $ruleAtrName==$str;
            //半成品类型
            case '防火门半成品类型':
                if (strpos($orderAttrName, '单扇')!==false) {
                    return $ruleAtrName==$orderAttrName;
                } elseif (strpos($orderAttrName, '对开')!==false || strpos($orderAttrName, '子母')!==false) {
                    $str=$orderAttrName.$menshanlx.'扇';
                }
                return $ruleAtrName==$str;
            //门扇类型
            case '门扇类型':
                $str=findNum($orderAttrName).'门';
                if(strpos($orderAttrName, '别墅') !== false) {
                    $str = '别墅门';
                }
                return $ruleAtrName==$str;
            //主锁（门锁体类型及品牌）
            case '主锁':
                return strpos($orderAttrName, $ruleAtrName)!==false?1:0;
            //锁芯
            case '锁芯':
                return strpos($orderAttrName, $ruleAtrName)!==false?1:0;
            //底框类型
            case '底框类型':
                return strpos($orderAttrName, $ruleAtrName)!==false?1:0;
            //底框厚度
            case '底框厚度':
                $str=mb_substr($orderAttrName, -3);
                return $str==$ruleAtrName?1:0;
            //特殊要求 门类
            case '门类':
                $str = '';
                if (strpos($orderAttrName['teshuyq'], '楼宇门')!==false) {
                    $str='楼宇门';
                } elseif (strpos($orderAttrName['menshancz'], '覆膜')!==false) {
                    $str='覆塑门';
                }
//                elseif (strpos($orderAttrName['dang_ci'], '保温门')!==false) {
//                    $str='保温门';//20181226取消保温门编码规则，改为门扇要求匹配
//                }
                elseif (strpos($orderAttrName['customer_name'], '广州立伟')!==false) {
                    $str='立伟门';
                } elseif (strpos($orderAttrName['customer_name'], '万科')!==false && strpos($orderAttrName['menshancz'], '镀锌')!==false) {
                    $str='万科镀锌门';
                }
//                elseif (strpos($orderAttrName['customer_name'], '万科')!==false && strpos($orderAttrName['menkuangcz'], '冷轧')!==false) {
//                    $str='万科门';//20181226万科门拆分为万科非泊寓和万科泊寓两种类别
//                }
                elseif (strpos($orderAttrName['order_type'], '经销商招商订单') !== false) {
                    $str='招商门';
                } elseif (strpos($orderAttrName['menshancz'], '冷轧')!==false && strpos($orderAttrName['menkuangcz'], '镀锌')!==false) {
                    $str='门框镀锌';
                } elseif (strpos($orderAttrName['menshancz'], '镀锌')!==false && strpos($orderAttrName['menkuangcz'], '镀锌')!==false) {
                    $str='门框门扇镀锌';
                } elseif (strpos($orderAttrName['menshancz'], '镀锌')!==false && strpos($orderAttrName['menkuangcz'], '冷轧')!==false) {
                    $str='门扇镀锌';
                } elseif (strpos($orderAttrName['menkuangcz'], '锌合金')!==false) {
                    $str='锌合金';
                } elseif (strpos($orderAttrName['huase'], '单开门中门')!==false) {
                    $str = '单开门中门';
                } elseif (strpos($orderAttrName['huase'], '通开门中门')!==false) {
                    $str = '通开门中门';
                } elseif (strpos($orderAttrName['customer_name'], '万科')!==false && strpos($orderAttrName['customer_name'], '泊寓')===false && strpos($orderAttrName['menkuangcz'], '冷轧')!==false) {
                    $str = '万科非泊寓';
                } elseif (strpos($orderAttrName['customer_name'], '泊寓')!==false && strpos($orderAttrName['menkuangcz'], '冷轧')!==false) {
                    $str = '万科泊寓';
                }

                //关于树纹，成品，半品门框，半品门扇取值不同
                if ($ruleName == '成品') {
                    if (strpos($orderAttrName['menshancz'], '深雕树纹')!==false && strpos($orderAttrName['menkuangcz'], '深雕树纹')!==false) {
                        $str = '深雕树纹';
                    } elseif (strpos($orderAttrName['menshancz'], '方格树纹')!==false && strpos($orderAttrName['menkuangcz'], '方格纹')!==false) {
                        $str = '门扇方格树纹门框方格纹';
                    } elseif (strpos($orderAttrName['menshancz'], '方格纹')!==false && strpos($orderAttrName['menkuangcz'], '方格纹')!==false) {
                        $str = '方格纹';
                    } elseif (strpos($orderAttrName['menshancz'], '白板树纹')!==false && strpos($orderAttrName['menkuangcz'], '冷轧')!==false) {
                        $str = '门扇白板树纹门框冷轧';
                    } elseif (strpos($orderAttrName['menshancz'], '常规树纹')!==false && strpos($orderAttrName['menkuangcz'], '常规树纹')!==false) {
                        $str = '常规树纹';
                    } elseif (strpos($orderAttrName['menshancz'], '白板树纹')!==false && strpos($orderAttrName['menkuangcz'], '常规树纹')!==false) {
                        $str = '门扇白板树纹门框常规树纹';
                    }
                } elseif ($ruleName == '半品门框') {
                    if (strpos($orderAttrName['menkuangcz'], '深雕树纹')!==false) {
                        $str = '深雕树纹';
                    } elseif (strpos($orderAttrName['menkuangcz'], '方格纹')!==false) {
                        $str = '方格纹';
                    } elseif (strpos($orderAttrName['menkuangcz'], '常规树纹')!==false) {
                        $str = '常规树纹';
                    }
                } elseif ($ruleName == '半品门扇') {
                    if (strpos($orderAttrName['menshancz'], '深雕树纹')!==false) {
                        $str = '深雕树纹';
                    } elseif (strpos($orderAttrName['menshancz'], '方格树纹')!==false) {
                        $str = '方格树纹';
                    } elseif (strpos($orderAttrName['menshancz'], '方格树纹')!==false) {
                        $str = '方格纹';
                    } elseif (strpos($orderAttrName['menshancz'], '白板树纹')!==false) {
                        $str = '白板树纹';
                    } elseif (strpos($orderAttrName['menshancz'], '常规树纹')!==false) {
                        $str = '常规树纹';
                    }
                }

                //20181226成品和半品门扇新增门扇要求匹配
                if ($ruleName == '成品'||$ruleName == '半品门扇') {
                    if (strpos($orderAttrName['menshanyq'], '聚氨酯全填')!==false) {
                        $str = '聚氨酯全填';
                    } elseif (strpos($orderAttrName['menshanyq'], '岩棉局部填')!==false) {
                        $str = '岩棉局部填';
                    } elseif (strpos($orderAttrName['menshanyq'], '岩棉半填')!==false) {
                        $str = '岩棉半填';
                    } elseif (strpos($orderAttrName['menshanyq'], '岩棉全填')!==false) {
                        $str = '岩棉全填';
                    }
                }
                return $str==$ruleAtrName;
            //半成品状态
            case '半成品状态':
                $str='自产';
                if (in_array($zhizaobm,['DS9','DS10'])) {//齐河基地铁艺、不锈钢窗花均为外购，其余为自产
                    if (strpos($orderAttrName, '不锈钢窗花')!==false || strpos($orderAttrName, '铁艺窗花')!==false) {
                        $str='外购';
                    }
                } else {//成都基地只有铁艺窗花外购其它均自产
                    if (strpos($orderAttrName, '铁艺窗花')!==false) {
                        $str='外购';
                    }
                }
                return $ruleAtrName==$str;
            //窗花 材质:常规窗花，铁艺窗花，材质为常规铁。不锈钢窗花材质为不锈钢。不锈铁窗花材质为不锈铁。中式窗花材质为中式。其余为其它。
            case '窗花材质':
                if (strpos($orderAttrName['chuanghua'], '常规窗花')!==false
                   ) {
                    $str='常规铁';
                } elseif (strpos($orderAttrName['chuanghua'], '不锈钢')!==false) {
                    $str='不锈钢';
                } elseif (strpos($orderAttrName['chuanghua'], '不锈铁')!==false) {
                    $str='不锈铁';
                } elseif (strpos($orderAttrName['chuanghua'], '中式窗花')!==false) {
                    $str='中式';
                } elseif (strpos($orderAttrName['chuanghua'], '铝')!==false) {
                    $str='铝';
                }elseif(strpos($orderAttrName['chuanghua'], '封闭式')!==false){
                    $str='其它';
                }
                return $ruleAtrName==$str;

            //单框
            //单框类型
            case '单框类型':
                $str=$dankuanglx;
                //边框分为铰框锁框
                if (in_array($dankuanglx, ['铰框','锁框'])) {
                    $str='边框';
                }
                return $ruleAtrName==$str;
            case '外购单框类型':
                $str=$waigoulx;
                return $ruleAtrName==$str;
            //门框型号
            case '门框型号':
                $str=findNum($orderAttrName).'门';
                if(strpos($orderAttrName, '别墅') !== false) {
                    $str = '别墅门';
                }
                return $ruleAtrName==$str;
            //材质 (单框)。不锈铁平底和不锈铁无台阶平底均表示为不锈铁平底
            //门框、门扇材质为冷轧统称普铁
            //底框读取底框材料，其余读取订单门框材质字段
            case '单框材质':
                if ($dankuanglx=='底框') {
                    if ($ruleAtrName == '普铁') {
                        break;
                    }
                    return strpos($orderAttrName['dkcailiao'], $ruleAtrName)!==false?1:0;
                }
                if ($dankuanglx == '中框' && strpos($orderAttrName['menkuang'], '70三角框115钢套门') !== false) {
                    $spMenkuang = $this->specialMenkuang($orderAttrName['menkuang'], $orderAttrName['menkuangcz']);
                    $orderAttrName['menkuangcz'] = $spMenkuang?'冷轧':$orderAttrName['menkuangcz'];
                }
                if ($orderAttrName['menkuangcz'] == '冷轧') {
                    $orderAttrName['menkuangcz'] = '普铁';
                }
                if (strpos($orderAttrName['menkuangcz'], '锌合金') !== false) {
                    $orderAttrName['menkuangcz'] = '锌合金';
                }
                if (strpos($orderAttrName['menkuangcz'], '镀锌') !== false) {
                    $orderAttrName['menkuangcz'] = '镀锌';
                }
                if (strpos($orderAttrName['menkuangcz'], '深雕树纹') !== false) {
                    $orderAttrName['menkuangcz'] = '深雕树纹';
                }
                if (strpos($orderAttrName['menkuangcz'], '方格纹') !== false) {
                    $orderAttrName['menkuangcz'] = '方格纹';
                }
                if (strpos($orderAttrName['menkuangcz'], '常规树纹') !== false) {
                    $orderAttrName['menkuangcz'] = '常规树纹';
                }
                return strpos($orderAttrName['menkuangcz'], excel_trim($ruleAtrName))!==false?1:0;
            //开向 (单框) 开向为内左内右时为内开，开向为外左外右时为外开，边框要分铰方锁方，上框，中框、底框只分内外
            case '开向':
                $str='';
                $kaixiang='';
                if (in_array($orderAttrName['kaixiang'], ['内右','内左'])) {
                    $kaixiang='内开';
                } elseif (in_array($orderAttrName['kaixiang'], ['外左','外右'])) {
                    $kaixiang='外开';
                }
                $str=$kaixiang;
                //边框类型
                if (in_array($dankuanglx, ['铰框','锁框'])) {
                    if ($menshanlx == '半花边框' && in_array($orderAttrName['menkuangyq'],['左开左无边','右开右无边','左开右无边','右开左无边'])) {
                        $str="半花边".$kaixiang.$dankuanglx;
                    } else {
                        $str=$kaixiang.$dankuanglx;
                    }
                }
                return $ruleAtrName==$str;

            case '外购门框开向':
                $kaixiang='';
                if (in_array($waigoulx,['外购上门框'])) {
                    if (in_array($orderAttrName, ['内右','内左'])) {
                        $kaixiang='内开';
                    } elseif (in_array($orderAttrName, ['外左','外右'])) {
                        $kaixiang='外开';
                    }
                } elseif (in_array($waigoulx, ['外购铰门框','外购锁门框'])) {
                    $kaixiang = $orderAttrName;
                } else { //中门框
                    $kaixiang = '不分开向';
                }
                return $ruleAtrName==$kaixiang;

            //门板材质
            case '门板材质':
                return strpos($orderAttrName, $ruleAtrName)!==false?1:0;

            //材质规则
            //特殊要求
            case '特殊要求':
                $str='';
                //门框材质为镀锌51D 锌合金51D 时用 51D  (单框不包含底框)
                if ($dankuanglx!=''&&$dankuanglx!='底框') {
                    if (strpos($orderAttrName['menkuangcz'], '镀锌')!==false||strpos($orderAttrName['menkuangcz'], '锌合金')!==false) {
                        $str='51D';
                    }
                }
                //底框材质为201不锈钢时用201,底框材质为304不锈钢时用304，底框
                elseif ($dankuanglx=='底框') {
                    if (strpos($orderAttrName['dkcailiao'], '201不锈钢') !== false || strpos($orderAttrName['dkcailiao'], '不锈钢201') !== false) {
                        $str='201';
                    } elseif (strpos($orderAttrName['dkcailiao'], '304不锈钢') !== false || strpos($orderAttrName['dkcailiao'], '不锈钢304') !== false) {
                        $str='304';
                    }
                }
                //门板
                elseif ($menbanlx!='') {
                    //门板材质为锌合金56D 用56D
                    if (strpos($orderAttrName['menshancz'], '锌合金')!==false) {
                        $str='56D';
                    }
                    //门板材质为镀锌53D 用53D
                    elseif (strpos($orderAttrName['menshancz'], '镀锌53D')!==false) {
                        $str='53D';
                    }
                }
                //别墅门
                elseif ($bieshulx!='') {
                    return strpos($orderAttrName['remark'], $ruleAtrName) !== false;
                }
                return $ruleAtrName==$str;

            //材质规则
            //材质
            case '材质':
                if ($dankuanglx != '') {
                    if (in_array($orderAttrName['menkuangcz'], ['锌合金','深雕树纹','方格树纹','方格纹','白板树纹'])) {
                        $orderAttrName['menkuangcz'] = '锌合金';
                    }
                    if (strpos($orderAttrName['menkuangcz'],'冷轧')!==false || strpos($orderAttrName['menkuangcz'],'常规树纹') !== false) {
                        $orderAttrName['menkuangcz'] = '冷轧';
                    }
                    $str = (strpos($orderAttrName['menkuangcz'], $ruleAtrName) !== false)?1:0;
                }
                if ($menbanlx !='') {
                    if (in_array($orderAttrName['menshancz'], ['锌合金','深雕树纹','方格树纹','方格纹','白板树纹'])) {
                        $orderAttrName['menshancz'] = '锌合金';
                    }
                    if (strpos($orderAttrName['menshancz'],'冷轧')!==false || strpos($orderAttrName['menshancz'],'常规树纹') !== false) {
                        $orderAttrName['menshancz'] = '冷轧';
                    }
                    $str = (strpos($orderAttrName['menshancz'], $ruleAtrName) !== false)?1:0;
                }
                return $str;
            //成品、半品门扇 门扇开孔 只区分空白和非空白
            case '门扇开孔':
                if (!empty(excel_trim($orderAttrName)) && excel_trim($orderAttrName)!='无') {
                    $orderAttrName = '有';
                } else {
                    $orderAttrName = '无';
                }
                return $ruleAtrName==$orderAttrName;

            case '门类型':
                $str = $bieshulx;
                return $str == $ruleAtrName;
        }
    }

    /**
     * 根据订单获取门板 用料规则
     * @param null $data
     * @return array
     */
    public function getMenbanYongLiaoRule($data=null)
    {
        $data = array_change_key_case($data);
        $zhizaobm = $data['zhizaobm'];
        $dangci = $data['dang_ci'];
        $menkuang = $data['menkuang'];
        $menshan = $data['menshan'];
        $menshancz = $data['menshancz'];
        $dikuangcl = $data['dkcailiao'];
        $guige = $data['guige'];
        $chuanghua = $data['chuanghua'];
        $kaixiang='';
        if (in_array($data['kaixiang'], ['内右','内左'])) {
            $kaixiang='内开';
        } elseif (in_array($data['kaixiang'], ['外左','外右'])) {
            $kaixiang='外开';
        }
        $mkhoudu = $data['mkhoudu'];
        $qianmb = $data['qmenbhd']/100; //前门板厚度
        $houmb = $data['hmenbhd']/100; //后门板厚度
        $guigecArr = explode('*', $guige);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $guige);
        }
        $guigeLength = $this->gaoduChange($menkuang, $guigecArr[0], $chuanghua, $zhizaobm);
        $guigeWide = $guigecArr[1];
        if (strpos($dikuangcl, '矩管')!==false) {
            $dkcl = " DIKUANGCL  = '/矩管/'";
        } else {
            $dkcl = " DIKUANGCL <> '/矩管/'";
        }
        $menshanczLike = '';
        if (empty($menshancz) || $menshancz == '冷轧') {
            $menshanczLike = " and MENSHANCZ IS NULL";
        } else {
            $menshanczLike = " and MENSHANCZ like '%$menshancz%'";
        }
        //获取门板长度用料规则
        $sql_menban_length = "select MUQIANMB,MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB from 
                                BOM_MENBAN_LENGTH_RULE where ZHIZAOBM LIKE '%/$zhizaobm/%' and DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang/%' and
                                MENSHAN like '%/$menshan/%' and KAIXIANG like '%/$kaixiang/%' and MKHOUDU like '%$mkhoudu%' and $dkcl and GUIGECD='$guigeLength'";

        //获取门板宽度用料规则
        $sql_menban_wide = "select MUQIANMB,MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB,CAIZHI from 
                                BOM_MENBAN_WIDE_RULE where ZHIZAOBM LIKE '%/$zhizaobm/%' and DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang/%' and
                                MENSHAN like '%/$menshan/%' and KAIXIANG like '%/$kaixiang/%' and MKHOUDU like '%$mkhoudu%' $menshanczLike and GUIGECD='$guigeWide' and qianmb='$qianmb' and houmb='$houmb'";

        $result1 = Db::query($sql_menban_length);
        $result2 = Db::query($sql_menban_wide);
        //返回数据
        $data = array(
            'menban_length'=>empty($result1)?$result1:array_change_key_case($result1[0]),
            'menban_width'=>empty($result2)?$result2:array_change_key_case($result2[0])
        );
        return $data;
    }



    /**
     * 获取某一门板用料高宽
     * @param $menshanlx 门扇类型 母、子、母子、子子
     * @param $menbanlx 门板类型 前门板、后门板
     * @param $menbanylRule 门板用料规则
     * @param $column 高宽：menban_width、menban_height
     */
    public function getMenbanYongliao($menshanlx, $menbanlx, $menbanylRule, $column)
    {
        if ($menshanlx=='母') {
            return $menbanlx=='前门板'?$menbanylRule[$column]['muqianmb']:$menbanylRule[$column]['muhoumb'];
        }
        //子板
        if ($menshanlx=='子') {
            return $menbanlx=='前门板'?$menbanylRule[$column]['ziqianmb']:$menbanylRule[$column]['zihoumb'];
        }
        //母子板
        if ($menshanlx=='母子') {
            return $menbanlx=='前门板'?$menbanylRule[$column]['muziqmb']:$menbanylRule[$column]['muzihmb'];
        }
        //子子板
        if ($menshanlx=='子子') {
            return $menbanlx=='前门板'?$menbanylRule[$column]['ziziqmb']:$menbanylRule[$column]['zizihmb'];
        }
    }

    /**
     * 根据副窗订单-门扇高度 转换成规格高度
     * @param $menkuang
     * @param $length
     * @return int
     */
    /**
     * 根据副窗订单-门扇高度 转换成规格高度
     * @param $menkuang
     * @param $length
     * @return int
     */
    public function gaoduChange($menkuang, $length ,$chuanghua, $zhizaobm)
    {
        //判断90门
        if (findNum($menkuang) != '90') {
            $yuewu_key = "M9:!90";
        } else {
            $yuewu_key = "M9:90";
        }
        $hasFuchuang = 1;
		if(strpos($chuanghua,'无副窗')!==false ||$chuanghua == '无' || empty($chuanghua)){
            $hasFuchuang = 0;
        }
        $sql = "select VAL from BOM_INTERVAL_RULE where YEWU_KEY='$yuewu_key' and zhizaobm like '%/$zhizaobm/%' and $length BETWEEN START_VAL and END_VAL";
        $result = Db::query($sql);
        return $hasFuchuang?$result[0]['VAL']:$length;
    }

    /**
     * 根据订单获取门框 用料规则
     * @param null $data
     * @return array
     */
    public function getMenkuangYongLiaoRule($data=null)
    {
        $data = array_change_key_case($data);
        $zhizaobm = $data['zhizaobm'];
        $menkuanghd = $data['mkhoudu'];
        $dangci = $data['dang_ci'];
        $menkuang = $data['menkuang'];
        $menkuangcz = $data['menkuangcz'];
        $menkuang_jiao_suo = $menkuang;
        $menkuang_shang_zhong = $menkuang;
        if (!empty(excel_trim($data['menkuangyq']))&&excel_trim($data['menkuangyq'])!='无') {
            $menkuang_jiao_suo .= $data['menkuangyq'];
        }
        # 0403只考虑TG开头的门框要求
        if (!empty(excel_trim($data['menkuangyq']))&&excel_trim($data['menkuangyq'])!='无'&&strpos($data['menkuangyq'],'TG') !== false) {
            $menkuang_shang_zhong .= $data['menkuangyq'];
        }
        $menshan = $data['menshan'];
//        if (strpos($menshan, '四开门')!==false) {
//            $menshan = '四开门';
//        }
        $dikuangcl = $data['dkcailiao'];
        $guige = $data['guige'];
        $kaixiang = $data['kaixiang'];
        if (strpos($kaixiang, '内') === false) {
            $kaixiang = '外开';
        } else {
            $kaixiang = '内开';
        }
        $guigecArr = explode('*', $guige);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $guige);
        }
        $guigeLength = $guigecArr[0];
        $guigeWide = $guigecArr[1];
        //特殊门框材质数据
//        $menkuangczLike = " 1=1 ";
//        $caizhi = array('镀锌51D','锌合金51D','冷轧');
//        $caizhi = implode('',$caizhi);
        //0403门框材质为空或等于无则默认为冷轧
        $menkuangcz = (empty($menkuangcz) || $menkuangcz == '无')?'冷轧':$menkuangcz;
//        if (!empty($menkuangcz) && strpos($caizhi, $menkuangcz) !== false) {
//            $menkuangczLike .= " OR MENKUANGCZ LIKE '%$menkuangcz%' ";
//        }
        $menkuangczLike = "  MENKUANGCZ LIKE '%$menkuangcz%' ";
        //获取门框长度用料规则
        $sql_menkuang_jiao_suo = "select (JIAOMK_LENGTH+$guigeLength)JIAOMK_LENGTH ,JIAOMK,JIAOMK_HD,JIAOMK_DENSITY,(SUOMK_LENGTH+$guigeLength)SUOMK_LENGTH,SUOMK,SUOMK_HD,SUOMK_DENSITY from BOM_MENKUANG_JIAOSUO_RULE where 
                                 ZHIZAOBM LIKE '%/$zhizaobm/%' and DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang_jiao_suo/%' and MENSHAN like '%/$menshan/%' and KAIXIANG like '%/$kaixiang/%' and to_char(MENKUANGHD,'990.99') =to_char('$menkuanghd','990.99') and $menkuangczLike";
        //获取门框宽度用料规则
        $sql_menkuang_shang_zhong = "select (SHANGMK_LENGTH+$guigeWide)SHANGMK_LENGTH,SHANGMK,SHANGMK_HD,SHANGMK_DENSITY,(ZHONGMK_LENGTH+$guigeWide)ZHONGMK_LENGTH,ZHONGMK,ZHONGMK_HD,ZHONGMK_DENSITY from 
                                BOM_MENKUANG_SHANGZHONG_RULE where ZHIZAOBM LIKE '%/$zhizaobm/%' and DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang_shang_zhong/%'
                                and KAIXIANG like '%/$kaixiang/%' and MENSHAN like '%/$menshan/%' and to_char(MENKUANGHD,'990.99') =to_char('$menkuanghd','990.99') and $menkuangczLike";
        $sql_menkuang_xia = "select (XIAMK_LENGTH+$guigeWide)XIAMK_LENGTH,XIAMK,XIAMK_HD,XIAMK_DENSITY from BOM_MENKUANG_XIA_RULE 
                                where ZHIZAOBM LIKE '%/$zhizaobm/%' and  DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang/%'
                                and KAIXIANG like '%/$kaixiang/%' and MENSHAN like '%/$menshan/%' and to_char(MENKUANGHD,'990.99') =to_char('$menkuanghd','990.99') and DIKUANGCL like '%/$dikuangcl/%' ";

        $result3 = Db::query($sql_menkuang_jiao_suo);
        $result4 = Db::query($sql_menkuang_shang_zhong);
        $result5 = Db::query($sql_menkuang_xia);
        //返回数据
        $data = array(
            'jiao_suo_yongliao'=>empty($result3)?$result3:array_change_key_case($result3[0]),
            'shang_zhong_yongliao'=>empty($result4)?$result4:array_change_key_case($result4[0]),
            'xia_yongliao'=>empty($result5)?$result5:array_change_key_case($result5[0])
        );
        return $data;
    }


    /**
     * 获取窗花用料规则
     * @param null $data
     * @return array|null
     */
    public function getChuanghuaYongliaoRule($data =null)
    {
        $data = array_change_key_case($data);
        $zhizaobm = $data['zhizaobm'];
        $dangci = $data['dang_ci'];
        $menkuang = $data['menkuang'];
        $menshan = $data['menshan'];
        $guige = $data['guige'];
        $kaixiang = $data['kaixiang'];
        $mkhoudu = $data['mkhoudu'];
        if (strpos($kaixiang, '内') === false) {
            $kaixiang = '外开';
        } else {
            $kaixiang = '内开';
        }
        $chuanghua = $data['chuanghua'];
        //$chuanghua = mb_substr($chuanghua,0,3,'utf-8');
        $guigecArr = explode('*', $guige);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $guige);
        }
        $guigeLength = $guigecArr[0];
        $guigeWide = $guigecArr[1];
        //窗花非四开门用料规则
        if (strpos($menshan, '四开门') === false) {
            $sql_chuanghua = "select (BOM_CHUANGHUA_RULE.MKHEIGHT_RULE || '+$guigeLength')as gaodu,
                                (BOM_CHUANGHUA_RULE.MKWIDE_RULE ||'+$guigeWide') as wide from BOM_CHUANGHUA_RULE 
                                where  ZHIZAOBM LIKE '%/$zhizaobm/%' and DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang/%' and 
                                MENSHAN like '%/$menshan/%' and CHUANGHUA like '%/$chuanghua/%' and KAIXIANG like '%/$kaixiang/%' and MKHOUDU like '%/$mkhoudu/%'
                                and $guigeLength BETWEEN START_HEIGHT and END_HEIGHT ";
            $result = Db::query($sql_chuanghua);
            //返回数据
            $data = array(
                'chuanghua_height'=>empty($result)?0:eval("return ".$result[0]['GAODU'].";"),
                'chuanghua_width'=>empty($result)?0:eval("return ".$result[0]['WIDE'].";")
            );
        } else {
            //窗花非四开门用料规则
            $columns = getColumnName('BOM_CHUANGHUA_SIKAI_RULE');
            $sql_chuanghua = "select $columns from BOM_CHUANGHUA_SIKAI_RULE where  ZHIZAOBM LIKE '%/$zhizaobm/%' and MENKUANG like '%/$menkuang/%' and MENSHAN like '%/$menshan/%'
                              and CHUANGHUA  like '%/$chuanghua/%' and XINGHAO ='$guige'";
            $result = Db::query($sql_chuanghua);
            $guigecX = explode('*', $result[0]['CHUANGHUAX']);
            if (empty($guigecX[1])) {
                $guigecX = explode('×', $result[0]['CHUANGHUAX']);
            }
            $guigecD = explode('*', $result[0]['CHUANGHUAD']);
            if (empty($guigecD[1])) {
                $guigecD = explode('×', $result[0]['CHUANGHUAD']);
            }
            //返回数据
            $data = array(
                'chuanghua_height_x' =>empty($guigecX[0])?0:$guigecX[0],
                'chuanghua_width_x' =>empty($guigecX[1])?0:$guigecX[1],
                'chuanghua_yongliang_x' =>empty($result[0]['YONGLIANGX'])?0:$result[0]['YONGLIANGX'],
                'chuanghua_height_d' =>empty($guigecD[0])?0:$guigecD[0],
                'chuanghua_width_d' =>empty($guigecD[1])?0:$guigecD[1],
                'chuanghua_yongliang_d' =>empty($result[0]['YONGLIANGD'])?0:$result[0]['YONGLIANGD'],
            );
        }
        return $data;
    }

    /**
     *获取元件列表
     * @param $order  订单信息
     * @return json
     */
    public function getYuanJianList($order)
    {
        $orderID = $order['oeb01'];
        $doorType = DB::query("select order_db from oea_file where oea01='$orderID'", true);
        $doorType = empty($doorType) ? 'M9' : $doorType[0]['order_db'];
        $menKuang = $order['menkuang'];
        $zhizaobm= $order['zhizaobm'];
        $order['teshuyq'] = empty($order['customer_name'])?'':$order['customer_name'];
        $order['menkuangyq'] = empty($order['menkuangyq'])?'无':$order['menkuangyq'];
        $sql = "select * from bom_yuanjian a where a.menkuang = '$menKuang' and a.door_type = '$doorType' and a.zhizaobm='$zhizaobm' order by id";
        $yuanjian_list = Db::query($sql, true);
        $data = array();
        foreach ($yuanjian_list as $key=>$val) {
            if ($val['yuanjian_name']=='70合页铰链') {
                $test=1;
            }
            $id = $val['id'];
            $columns = $val['order_column_name'];//该元件关联的订单属性

            //关联订单列不为空则动态拼接sql
            $where = "1=1";
            if (!empty($columns)) {
                //如果字段中包含逗号，则有多个属性
                $cols = explode(',', $columns);
                //根据关联的订单属性动态拼接sql
                foreach ($cols as $k=>$v) {
                    $columnName = $v;
                    $orderValue = $order[strtolower($v)];
                    $height = $order['height'];
                    $width = $order['width'];
                    //高宽
                    if ($columnName=='start_height') {
                        $where.= " and  $height>=start_height";
                    } elseif ($columnName=='end_height') {
                        $where.= " and  $height<=end_height";
                    } elseif ($columnName=='start_width') {
                        $where.= " and   $width>=start_width";
                    } elseif ($columnName=='end_width') {
                        $where.= " and  $width<=end_width";
                    }
                    //前后板厚除以100
                    elseif ($columnName=='qmenbhd'||$columnName=='hmenbhd') {
                        $orderValue = $orderValue/100;
                        if ($orderValue == 1) {//前后板计算出来等于1，用1.0去匹配
                            $orderValue = '1.0';
                        }                        $where.=" and $columnName like '%,$orderValue,%'";
                    } elseif($columnName == 'teshuyq') {//特殊要求的单独处理---客户名称
                        $convertedValue = $this->convertValue($orderValue, $zhizaobm);
                        $where.=" and $columnName like '%,$convertedValue,%'";
                    } else {
                        $where.=" and $columnName like '%,$orderValue,%'";
                    }
                }
            }

            $where = rtrim($where, 'and');
            /*$detail = "select  yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei  
                       from bom_yuanjian_rule t1,bom_yuanjian t2  
                       where yuanjian_id=$id and t1.yuanjian_id=t2.id and ($where or is_require=1)  
                       group by yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei ";*/
			$detail	 = "select  yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei  
                       from bom_yuanjian_rule t1,bom_yuanjian t2  
                       where yuanjian_id=$id and t1.yuanjian_id=t2.id and ($where or is_require=1)";  
            $liaohaoInfo = DB::query($detail, true);
            foreach ($liaohaoInfo as $k=>$v) {
                //胶条用量
                if (strpos(excel_trim($v['yuanjian_name']), '胶条')!==false) {
                    if (strpos(excel_trim($v['yongliang']), '门扇高度')!==false) {
                        $v['yongliang'] = ($order['menshan_gaodu']-deleteChinese($v['yongliang']))/1000;
                    } elseif (strpos(excel_trim($v['yongliang']), '门框宽度')!==false) {
                        $v['yongliang'] = ($order['width']-deleteChinese($v['yongliang']))/1000;
                    } elseif (strpos(excel_trim($v['yongliang']), '门框高度')!==false) {
                        $v['yongliang'] = ($order['height']-deleteChinese($v['yongliang']))/1000;
                    } elseif (strpos(excel_trim($v['yongliang']), '固定值')!==false) {
                        $v['yongliang'] = deleteChinese($v['yongliang'])/1000;
                    }
                }
                //胶纱网用量
                if (strpos(excel_trim($v['yuanjian_name']), '胶纱网')!==false) {
                    $yongliang = explode('*', $v['yongliang']);
                    //$v['yongliang'] = $
                   $chuanghuaSquare = 0;//窗花面积
                   if (!empty($order['chuanghuayl']['chuanghua_height'])) {
                       $chuanghuaSquare = $order['chuanghuayl']['chuanghua_height']*$order['chuanghuayl']['chuanghua_width'];
                   } elseif (!empty($order['chuanghuayl']['chuanghua_height_x'])) {
                       $chuanghuaSquare = $order['chuanghuayl']['chuanghua_height_x']*$order['chuanghuayl']['chuanghua_width_x']*$order['chuanghuayl']['chuanghua_yongliang_x']
                       +$order['chuanghuayl']['chuanghua_height_d']*$order['chuanghuayl']['chuanghua_width_d']*$order['chuanghuayl']['chuanghua_yongliang_d'];
                   }
                    $v['yongliang'] =($yongliang[0]*$yongliang[1])==0?0:$chuanghuaSquare/($yongliang[0]*$yongliang[1])/1000000;
                }
                array_push($data, $v);
            }
        }
        return $data;
    }

    /**
     * 根据订单信息获取品名规格
     * @param $ruleName 规则名称
     * @param $order 订单信息 包含用料信息
     * @param $dankuanglx 单框类型：上框、低框、边框、中框，
     * @param $menshanlx 门扇类型 母，子，母子，子子 默认母
     * @param $menbanlx 门板类型 前门板、后门板
     * $param $chuanghualx 窗花类型 (只有四开窗花时才有)大、小
     */
    public function getPingmingOrGuige($ruleName, $orderInfo, $dankuanglx, $menshanlx, $menbanlx, $chuanghualx,$waigoulx,$bieshulx)
    {
        //防火门钢质门规则转换分开转换
//        $doorType = empty(excel_trim($orderInfo['order_db']))?'M9':$orderInfo['order_db'];
        $doorType = (excel_trim($orderInfo['order_db']) == 'M8')?'M8':'M9';//精品门取钢质门那一套规则
        $retstr = '';
        $guigelist = DB::query("select order_column_name from bom_code_rule where rule_name='$ruleName'", true);
        $guigearr = explode(",", $guigelist[0]["order_column_name"]);
        switch ($ruleName) {
            case '成品品名规则':
            {
               /* if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }*/
               # 20190318 开向不转换，直接取开向值
                $kaixiang = $orderInfo[strtolower($guigearr[1])];
                $kaikong = $orderInfo[strtolower($guigearr[15])];//门扇开孔
                $mskaikong = (empty($kaikong) || strpos($kaikong,'无') !== false || strpos($kaikong,'否') !== false) ? "":"门扇开观察孔";
                $dang_ci = (strpos($orderInfo[strtolower($guigearr[16])], '保温') !== false)?"保温门":"";
                $retstr = $orderInfo[strtolower($guigearr[0])].          //花色
                    $kaixiang.  //开向
                    $orderInfo[strtolower($guigearr[2])].  //主锁
                    $orderInfo[strtolower($guigearr[3])].  //锁芯
                    $orderInfo[strtolower($guigearr[4])].  //铰链
                    $orderInfo[strtolower($guigearr[5])].  //门扇
                    $orderInfo[strtolower($guigearr[6])].  //表面方式
                    $orderInfo[strtolower($guigearr[7])].  //标件
                    $orderInfo[strtolower($guigearr[8])].  //副窗
                    $orderInfo[strtolower($guigearr[9])].  //副锁
                    $this->orderTeshuyqConvert("成品",$orderInfo[strtolower($guigearr[10])],$orderInfo[strtolower($guigearr[11])],$orderInfo[strtolower($guigearr[12])],$orderInfo[strtolower($guigearr[13])],$orderInfo[strtolower($guigearr[14])],'','',$orderInfo['menshanyq']). //特殊要求.门扇要求15
                    $mskaikong.//门扇开孔
                    $dang_ci;//档次包含保温显示保温门
                break;
            }
            case '成品规格规则':
            {
                $BzppBzfs = $this->orderValueConvert("baozhpack,baozhuangfs", $orderInfo[strtolower($guigearr[8])].$orderInfo[strtolower($guigearr[9])], $doorType);
                $menkuang = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[5])],$doorType);
//                $menkuangyq = empty($orderInfo[strtolower($guigearr[6])])?"":"半花边框";
                $menkuangyq = empty($orderInfo[strtolower($guigearr[6])])?"":$this->orderValueConvert("menkuangyq",$orderInfo[strtolower($guigearr[6])],$doorType);
                $retstr = $orderInfo[strtolower($guigearr[0])].             //档次
                    ($orderInfo[strtolower($guigearr[1])]/100)."*".         //前板厚
                    ($orderInfo[strtolower($guigearr[2])]/100)."*".         //后板厚
                    str_replace("×", "*", $orderInfo[strtolower($guigearr[3])]).//规格
                    "(".$orderInfo[strtolower($guigearr[4])].")".           //框厚
                    $menkuang.                   //门框
                    $menkuangyq.                   //门框要求
                    $orderInfo[strtolower($guigearr[7])].                   //底框材料
                    $BzppBzfs.                                              //包装品牌&包装方式
                    $orderInfo[strtolower($guigearr[10])];                   //猫眼
                break;
            }
            case '防火门成品品名规则':{
                /*if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }*/
                # 20190318 开向不转换，直接取开向值
                $kaixiang = $orderInfo[strtolower($guigearr[1])];
                $kaikong = $orderInfo[strtolower($guigearr[10])];//门扇开孔
                $mskaikong = (empty($kaikong) || strpos($kaikong,'无') !== false || strpos($kaikong,'否') !== false) ? "":$kaikong;
                $bimenq = $orderInfo[strtolower($guigearr[11])];
                $shunxuq = $orderInfo[strtolower($guigearr[12])];
                $bimenqi = strpos($bimenq,'无') !== false || empty($bimenq)?'':'带闭门器';
                $shunxuqi = strpos($shunxuq,'无') !== false || empty($shunxuq)?'':'带顺序器';
                $retstr = $orderInfo[strtolower($guigearr[0])].          //花色
                    $kaixiang.  //开向
                    $orderInfo[strtolower($guigearr[2])].  //主锁
                    $orderInfo[strtolower($guigearr[3])].  //锁芯
                    $orderInfo[strtolower($guigearr[4])].  //铰链
                    $orderInfo[strtolower($guigearr[5])].  //门扇
                    $this->orderValueConvert('biaomiantsyq',$orderInfo[strtolower($guigearr[6])],$doorType).  //表面方式
                    $orderInfo[strtolower($guigearr[7])].  //标件
                    $orderInfo[strtolower($guigearr[8])].  //副窗
                    $this->orderTeshuyqConvert("成品",$orderInfo[strtolower($guigearr[9])],$orderInfo[strtolower($guigearr[13])],'','',$orderInfo[strtolower($guigearr[14])],$orderInfo[strtolower($guigearr[15])],'',$orderInfo['menshanyq']). //特殊要求
                    $mskaikong.//门扇开孔
                    $bimenqi.$shunxuqi;//闭门器和顺序器
                break;
            }
            case '防火门成品规格规则':{
                $BzppBzfs = $this->orderValueConvert("baozhpack,baozhuangfs", $orderInfo[strtolower($guigearr[8])].$orderInfo[strtolower($guigearr[9])],$doorType);
                $menkuang = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[5])],$doorType);
//                $menkuangyq = empty($orderInfo[strtolower($guigearr[6])])?"":"半花边框";
                $menkuangyq = empty($orderInfo[strtolower($guigearr[6])])?"":$this->orderValueConvert("menkuangyq",$orderInfo[strtolower($guigearr[6])],$doorType);
                $retstr = $orderInfo[strtolower($guigearr[0])].             //档次
                    ($orderInfo[strtolower($guigearr[1])]/100)."*".         //前板厚
                    ($orderInfo[strtolower($guigearr[2])]/100)."*".         //后板厚
                    str_replace("×", "*", $orderInfo[strtolower($guigearr[3])]).//规格
                    "(".$orderInfo[strtolower($guigearr[4])].")".           //框厚
                    $menkuang.                   //门框
                    $menkuangyq.                   //门框要求
                    $orderInfo[strtolower($guigearr[7])].                   //底框材料
                    $BzppBzfs.                                              //包装品牌&包装方式
                    $orderInfo[strtolower($guigearr[10])];                   //猫眼
                break;
            }
            case '半品门框品名规则':
            {
                /*if (strpos($orderInfo[strtolower($guigearr[2])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[2])], "外") !== false) {
                    $kaixiang = "外开";
                }*/
                # 20190318 开向不转换，直接取开向值
                $kaixiang = $orderInfo[strtolower($guigearr[2])];
                $menshan = $orderInfo[strtolower($guigearr[0])]=='单扇门'?'门框单扇':
                    ($orderInfo[strtolower($guigearr[0])]=='对开门' || $orderInfo[strtolower($guigearr[0])]=='子母门'?'门框双扇':
                        ($orderInfo[strtolower($guigearr[0])]=='子母四开门' || $orderInfo[strtolower($guigearr[0])]=='均等四开门'?'门框四扇':"未知门扇"));
                $retstr = $menshan.//门扇(门扇需转换，单扇门=门框单扇，对开门、子母门=门框双扇，子母四开门、均等四开门=门框四扇)
                    $orderInfo[strtolower($guigearr[1])].   //表面方式
                    $kaixiang;                           //开向
                $suoju = $this->orderValueConvert("suoju",$orderInfo[strtolower($guigearr[4])],$doorType);
                $retstr .= $orderInfo[strtolower($guigearr[3])]=='无副窗'?'无副窗':'有副窗'; //窗花
                $retstr .= $suoju.$orderInfo[strtolower($guigearr[5])];//主锁副锁
                $retstr .= $this->orderTeshuyqConvert("门框半品",$orderInfo[strtolower($guigearr[6])],$orderInfo[strtolower($guigearr[7])],$orderInfo[strtolower($guigearr[8])],$orderInfo[strtolower($guigearr[9])],$orderInfo[strtolower($guigearr[10])],'','',$orderInfo['menshanyq']);//特殊要求
                break;
            }
            case '半品门框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[1])]);
                $menkuang = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[4])],$doorType);
                $menkuangyqConvert = $this->orderValueConvert('menkuangyq',$orderInfo[strtolower($guigearr[5])],$doorType);
                $menkuangyq = empty($menkuangyqConvert)?"":$menkuangyqConvert;
                $retstr = $orderInfo[strtolower($guigearr[0])]. //档次
                    $guige_replace."*".                         //规格
                    $orderInfo[strtolower($guigearr[2])]."/".       //框厚
                    $this->orderValueConvert('门框铰链', $orderInfo[strtolower($guigearr[3])],$doorType).       //铰链
                    $menkuang.                                  //门框
                    $menkuangyq.                                //门框要求
                    $orderInfo[strtolower($guigearr[6])];       //底框
                break;
            }
            case '防火门半品门框品名规则':
            {
                /*if (strpos($orderInfo[strtolower($guigearr[2])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[2])], "外") !== false) {
                    $kaixiang = "外开";
                }*/
                # 20190318 开向不转换，直接取开向值
                $kaixiang = $orderInfo[strtolower($guigearr[2])];
                $menshan = $orderInfo[strtolower($guigearr[0])];
                if (strpos($menshan, '单扇门') !== false) {
                    $mens = '门框单扇';
                } elseif (strpos($menshan, '子母门') !== false || strpos($menshan, '对开门') !== false) {
                    $mens = '门框双扇';
                } elseif (strpos($menshan, '四开门') !== false) {
                    $mens = '门框四扇';
                } else {
                    $mens = $menshan;
                }
                $retstr = $mens.//含单扇门---门框单扇，子母或对开---门框双扇，四开--门框四扇
                    $orderInfo[strtolower($guigearr[1])].   //表面方式
                    $kaixiang;                           //开向
                $suoju = $this->orderValueConvert("suoju",$orderInfo[strtolower($guigearr[4])],$doorType);
                $retstr .= $orderInfo[strtolower($guigearr[3])]=='无副窗'?'无副窗':'有副窗'; //窗花
                $retstr .= $suoju;//主锁副锁
                $retstr .= $this->orderTeshuyqConvert("门框半品",$orderInfo[strtolower($guigearr[5])],$orderInfo[strtolower($guigearr[6])],'','',$orderInfo[strtolower($guigearr[7])],$orderInfo[strtolower($guigearr[8])],'',$orderInfo['menshanyq']);//特殊要求
                $bimenq = $orderInfo[strtolower($guigearr[9])];
                $shunxuq = $orderInfo[strtolower($guigearr[10])];
                $bimenqi = strpos($bimenq,'无') !== false || empty($bimenq)?'':'带闭门器';
                $shunxuqi = strpos($shunxuq,'无') !== false || empty($shunxuq)?'':'带顺序器';
                $retstr .= $bimenqi.$shunxuqi;//闭门器顺序器
                break;
            }
            case '防火门半品门框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[1])]);
                $menkuang = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[4])],$doorType);
                $menkuangyqConvert = $this->orderValueConvert('menkuangyq',$orderInfo[strtolower($guigearr[5])],$doorType);
                $menkuangyq = empty($menkuangyqConvert)?"":$menkuangyqConvert;
                $retstr = $orderInfo[strtolower($guigearr[0])]. //档次
                    $guige_replace."*".                         //规格
                    $orderInfo[strtolower($guigearr[2])]."/".       //框厚
                    $this->orderValueConvert('门框铰链', $orderInfo[strtolower($guigearr[3])],$doorType).       //铰链
                    $menkuang.                                  //门框
                    $menkuangyq.                                //门框要求
                    $orderInfo[strtolower($guigearr[6])];       //底框
                break;
            }
            case '半品门扇品名规则':
            {
                //门扇(需转换，单扇门=门扇，子母门=子母母扇、子母子扇，对开门=对开母扇、对开子扇，子母四开门=子母四开边门*2、子母四开母扇、子母四开子扇，均等四开门=均等四开边门*2、均等四开母扇、均等四开子扇)
                $zhusuo = $this->orderValueConvert("suoju",$orderInfo[strtolower($guigearr[2])],$doorType).$orderInfo[strtolower($guigearr[3])];
                $teshuyq = $this->orderTeshuyqConvert("门扇半品",$orderInfo[strtolower($guigearr[4])],$orderInfo[strtolower($guigearr[5])],$orderInfo[strtolower($guigearr[6])],$orderInfo[strtolower($guigearr[7])],$orderInfo[strtolower($guigearr[8])],'','',$orderInfo['menshanyq']);
                $kaikong = $orderInfo[strtolower($guigearr[9])];//门扇开孔
                $mskaikong = (empty($kaikong) || strpos($kaikong,'无') !== false || strpos($kaikong,'否') !== false) ? "":"门扇开观察孔";
                $dang_ci = (strpos($orderInfo[strtolower($guigearr[10])],'保温') !== false)?"保温门":"";
                $kaixiang = $orderInfo['kaixiang'];
                if (in_array($kaixiang,['内左','外右'])) {
                    $kx = '右';
                } else {
                    $kx = '左';
                }
                if ($orderInfo[strtolower($guigearr[0])]=='单扇门') {
                    if ($menshanlx == "母") {
                        $retstr = '门扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    }
                } elseif ($orderInfo[strtolower($guigearr[0])]=='子母门') {
                    if ($menshanlx == "母") {
                        $retstr = '子母母扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    } elseif ($menshanlx == "子") {
                        $retstr = '子母子扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    }
                } elseif ($orderInfo[strtolower($guigearr[0])]=='对开门') {
                    if ($menshanlx == "母") {
                        $retstr = '对开母扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    } elseif ($menshanlx == "子") {
                        $retstr = '对开子扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    }
                } elseif ($orderInfo[strtolower($guigearr[0])]=='子母四开门') {
                    if ($menshanlx == "母子" || $menshanlx == "子子") {
                        $retstr = '子母四开边门'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    } elseif ($menshanlx == '母') {
                        $retstr = '子母四开母扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    } elseif ($menshanlx == "子") {
                        $retstr = '子母四开子扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    }
                } elseif ($orderInfo[strtolower($guigearr[0])]=='均等四开门') {
                    if ($menshanlx == "母子" || $menshanlx == "子子") {
                        $retstr = '均等四开边门'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    } elseif ($menshanlx == '母') {
                        $retstr = '均等四开母扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    } elseif ($menshanlx == "子") {
                        $retstr = '均等四开子扇'.$orderInfo[strtolower($guigearr[1])].$zhusuo.$teshuyq.$mskaikong.$dang_ci.$kx;
                    }
                } else {
                    $retstr = "未知门扇";
                }
                break;
            }
            case '半品门扇规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[1])]);
                $guige_replace_arr = explode("*", $guige_replace);
                $guige_change_high = $this->gaoduChange($orderInfo[strtolower($guigearr[5])], $guige_replace_arr[0], $orderInfo['chuanghua'], $orderInfo['zhizaobm']);
                $retstr = $orderInfo[strtolower($guigearr[0])].       //档次
                    $guige_change_high."*".$guige_replace_arr[1]."*". //规格
                    ($orderInfo[strtolower($guigearr[2])]/100)."*".   //前板厚
                    ($orderInfo[strtolower($guigearr[3])]/100)."/".       //后板厚
                    $this->orderValueConvert('门扇铰链', $orderInfo[strtolower($guigearr[4])],$doorType).       //铰链.       //铰链
                    $this->orderValueConvert("门扇半品门框",$orderInfo[strtolower($guigearr[5])],$doorType).       //门框
                    $orderInfo[strtolower($guigearr[6])];       //花色
                break;
            }
            case '防火门半品门扇品名规则':
            {
                //门扇(需转换，单扇门=门扇，子母门=子母母扇、子母子扇，对开门=对开母扇、对开子扇，子母四开门=子母四开边门*2、子母四开母扇、子母四开子扇，均等四开门=均等四开边门*2、均等四开母扇、均等四开子扇)
                $zhusuo = $this->orderValueConvert("suoju",$orderInfo[strtolower($guigearr[2])],$doorType);
                $teshuyq = $this->orderTeshuyqConvert("门扇半品",$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[7])],'','',$orderInfo[strtolower($guigearr[8])],$orderInfo[strtolower($guigearr[9])],'',$orderInfo['menshanyq']);
                $kaikong = $orderInfo[strtolower($guigearr[4])];//门扇开孔
                $mskaikong = (empty($kaikong) || strpos($kaikong,'无') !== false || strpos($kaikong,'否') !== false) ? "":$kaikong;
                $bimenq = $orderInfo[strtolower($guigearr[5])];
                $shunxuq = $orderInfo[strtolower($guigearr[6])];
                $bimenqi = strpos($bimenq,'无') !== false || empty($bimenq)?'':'带闭门器';
                $shunxuqi = strpos($shunxuq,'无') !== false || empty($shunxuq)?'':'带顺序器';
                $menshan = "";
                if (strpos($orderInfo[strtolower($guigearr[0])], '单扇门') !== false) {
                    $menshan = "门扇";
                } elseif (strpos($orderInfo[strtolower($guigearr[0])], '子母门') !== false) {
                    if ($menshanlx == "母") {
                        $menshan = "子母母扇";
                    } elseif ($menshanlx == "子") {
                        $menshan = "子母子扇";
                    }
                } elseif (strpos($orderInfo[strtolower($guigearr[0])], '对开门') !== false) {
                    if ($menshanlx == "母") {
                        $menshan = '对开母扇';
                    } elseif ($menshanlx == "子") {
                        $menshan = '对开子扇';
                    }
                } elseif (strpos($orderInfo[strtolower($guigearr[0])], '子母四开门') !== false) {
                    if ($menshanlx == "母子" || $menshanlx == "子子") {
                        $menshan = '子母四开边门';
                    } elseif ($menshanlx == '母') {
                        $menshan = '子母四开母扇';
                    } elseif ($menshanlx == "子") {
                        $menshan = '子母四开子扇';
                    }
                } elseif (strpos($orderInfo[strtolower($guigearr[0])], '均等四开门') !== false) {
                    if ($menshanlx == "母子" || $menshanlx == "子子") {
                        $menshan = '均等四开边门';
                    } elseif ($menshanlx == '母') {
                        $menshan = '均等四开母扇';
                    } elseif ($menshanlx == "子") {
                        $menshan = '均等四开子扇';
                    }
                } else {
                    $menshan = "未知门扇";
                }
                $kaixiang = $orderInfo['kaixiang'];
                if (in_array($kaixiang,['内左','外右'])) {
                    $kx = '右';
                } else {
                    $kx = '左';
                }
                $retstr = $menshan.
                $this->orderValueConvert('biaomiantsyq', $orderInfo[strtolower($guigearr[1])],$doorType).
                    $zhusuo.$teshuyq.$mskaikong.$bimenqi.$shunxuqi.$kx;
                break;
            }
            case '防火门半品门扇规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[1])]);
                $guige_replace_arr = explode("*", $guige_replace);
                $guige_change_high = $this->gaoduChange($orderInfo[strtolower($guigearr[5])], $guige_replace_arr[0], $orderInfo['chuanghua'], $orderInfo['zhizaobm']);
                $retstr = $orderInfo[strtolower($guigearr[0])].       //档次
                    $guige_change_high."*".$guige_replace_arr[1]."*". //规格
                    ($orderInfo[strtolower($guigearr[2])]/100)."*".   //前板厚
                    ($orderInfo[strtolower($guigearr[3])]/100)."/".       //后板厚
                    $this->orderValueConvert('门扇铰链', $orderInfo[strtolower($guigearr[4])],$doorType).       //铰链.       //铰链
                    $this->orderValueConvert("门扇半品门框",$orderInfo[strtolower($guigearr[5])],$doorType).       //门框
                    $orderInfo[strtolower($guigearr[6])];       //花色
                break;
            }
            case '上门框品名规则':
            {
                $kaixiang = "";
                if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }
                $menkuangyq = '';
//                if (empty($orderInfo['menkuangyq']) || $orderInfo['menkuangyq'] == '无') {
//
//                }
                if (in_array($orderInfo['menkuangyq'],['左开左无边','左开右无边','右开左无边','右开右无边'])) {
                    $menkuangyq = '半花边框';
                } elseif (in_array($orderInfo['menkuangyq'],['TG860','TG880','TG890','TG900'])){
                    $menkuangyq = $orderInfo['menkuangyq'];
                } else {
                    $menkuangyq = '';
                }
                $retstr = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[0])],$doorType).                //门框
                    "上门框".                                                //上框
                    $kaixiang.                                              //开向
                    $this->orderTeshuyqConvert("单框",$orderInfo[strtolower($guigearr[2])],$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[4])],$orderInfo[strtolower($guigearr[5])],$orderInfo[strtolower($guigearr[6])]).
                    $menkuangyq; //特殊要求---门框材质
                break;
            }
            case '上门框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*", $guige_replace);
                $retstr = $guige_width[1]."*".                  //规格宽度
                    $orderInfo[strtolower($guigearr[1])];      //门框厚度
                break;
            }
            case '铰框品名规则':
            {
                $kaixiang = "";
                //边框
                $biankuang = $this->getBiankuang($orderInfo,'铰框');
                if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }
                $retstr = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[0])],$doorType).           //门框
                    $biankuang.                                              //边框
                    $kaixiang.                                          //开向
                    "铰框".                                              //铰框
                    $this->orderTeshuyqConvert("单框",$orderInfo[strtolower($guigearr[2])],$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[4])],$orderInfo[strtolower($guigearr[5])],$orderInfo[strtolower($guigearr[6])]);     //特殊要求
                break;
            }
            case '铰框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*", $guige_replace);
                $retstr = $guige_width[0]."*".                      //规格高度
                    $orderInfo[strtolower($guigearr[1])];           //门框厚度
                break;
            }
            case '锁框品名规则':
            {
                $kaixiang = "";
                $biankuang = $this->getBiankuang($orderInfo,'锁框');
                if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }
                $retstr = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[0])],$doorType).                //门框
                    $biankuang.                                                  //边框
                    $kaixiang.                                              //开向
                    "锁框".                                                  //锁框
                    $this->orderTeshuyqConvert("单框",$orderInfo[strtolower($guigearr[2])],$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[4])],$orderInfo[strtolower($guigearr[5])],$orderInfo[strtolower($guigearr[6])]);       //特殊要求
                break;
            }
            case '锁框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*", $guige_replace);
                $retstr = $guige_width[0]."*".                  //规格高度
                    $orderInfo[strtolower($guigearr[1])];       //门框厚度
                break;
            }
            case '底框品名规则':
            {
                $kaixiang = "";
                if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }
                $dkcl = "";
                if ($doorType == 'M8') {
                    $dkcl = $this->orderValueConvert("dkcailiao",$orderInfo[strtolower($guigearr[2])],$doorType);
                }
                $retstr = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[0])],$doorType).                //门框
                    "底框".                                               //底框
                    $kaixiang.                                              //开向
//                    str_replace("底框", "", $orderInfo[strtolower($guigearr[2])]);//底框材料
                    $this->orderValueConvert("底框", $orderInfo[strtolower($guigearr[2])],$doorType).//底框材料
                    $dkcl;
                break;
            }
            case '底框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*", $guige_replace);
                $dkhd_arr = explode('*', $orderInfo[strtolower($guigearr[1])]["xia_yongliao"]["xiamk"]);
                $isJuguandk = strpos($orderInfo['dkcailiao'], '矩管') !== false?1:0;//是否为矩管底框
                if ($doorType == 'M8') {
                    $retstr = $guige_width[1]."*".              //规格宽度
                        $this->orderValueConvert('底框厚度',$orderInfo[strtolower($guigearr[3])],$doorType);
                } else {
                    $retstr = $guige_width[1]."*".              //规格宽度
                        $dkhd_arr[0];                           //底框厚度（底框厚度见用料规则)
                    if ($isJuguandk) {
                        $retstr = $guige_width[1]."*".              //规格宽度
                            $dkhd_arr[2];                           //底框厚度（底框厚度见用料规则)
                    }
                }
                break;
            }
            case '中框品名规则':
            {
                $kaixiang = "";
                if (strpos($orderInfo[strtolower($guigearr[2])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[2])], "外") !== false) {
                    $kaixiang = "外开";
                }
                //判断窗花有无
                $retstr = $this->orderValueConvert("门框",$orderInfo[strtolower($guigearr[0])],$doorType).                //门框
                    (empty($orderInfo[strtolower($guigearr[1])])?"无中框":"中框").   //中框(无副窗就没有中框)
                    $kaixiang.                                                      //开向
                    $this->orderTeshuyqConvert("单框",$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[4])],$orderInfo[strtolower($guigearr[5])],$orderInfo[strtolower($guigearr[6])],$orderInfo[strtolower($guigearr[7])],$orderInfo[strtolower($guigearr[0])],'中框');//特殊要求
                break;
            }
            case '中框规格规则':
            {
                $guige_replace = str_replace("×", "*", $orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*", $guige_replace);
                $dkhd = $this->findNum($orderInfo[strtolower($guigearr[1])]["shang_zhong_yongliao"]["zhongmk"]);
                $retstr = $guige_width[1]."*".  //规格宽度
                    $dkhd;                      //中框厚度（中框厚度见用料规则)
                break;
            }
            case '前门板品名规则':
            {
                //特殊要求处理
                $teshuyq = $this->orderTeshuyqConvert("门板",$orderInfo[strtolower($guigearr[0])],$orderInfo[strtolower($guigearr[1])],$orderInfo[strtolower($guigearr[2])],$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[4])]);
                $retstr = "前门板".$teshuyq;  //前门板（原件料号尾字母为Q默认前门板）
                break;
            }
            case '前门板规格规则':
            {
                //根据门扇类型获取不同的用料规格
                $menbanLength = $this->getMenbanYongliao($menshanlx, "前门板", $orderInfo[strtolower($guigearr[1])], "menban_length");
                $menbanWidth = $this->getMenbanYongliao($menshanlx, "前门板", $orderInfo[strtolower($guigearr[1])], "menban_width");
                $retstr = ($orderInfo[strtolower($guigearr[0])]/100)."*".$menbanWidth."*".$menbanLength;  //（前门板板厚&用料宽度&用料高度）
                break;
            }
            case '后门板品名规则':
            {
                //特殊要求处理
                $teshuyq = $this->orderTeshuyqConvert("门板",$orderInfo[strtolower($guigearr[0])],$orderInfo[strtolower($guigearr[1])],$orderInfo[strtolower($guigearr[2])],$orderInfo[strtolower($guigearr[3])],$orderInfo[strtolower($guigearr[4])]);
                $retstr = "后门板".$teshuyq;  //后门板（原件料号尾字母为H默认后门板）
                break;
            }
            case '后门板规格规则':
            {
                //根据门扇类型获取不同的用料规格
                $menbanLength = $this->getMenbanYongliao($menshanlx, "后门板", $orderInfo[strtolower($guigearr[1])], "menban_length");
                $menbanWidth = $this->getMenbanYongliao($menshanlx, "后门板", $orderInfo[strtolower($guigearr[1])], "menban_width");
                $retstr = ($orderInfo[strtolower($guigearr[0])]/100)."*".$menbanWidth."*".$menbanLength;  //（后门板板厚&用料宽度&用料高度）
                break;
            }
            case '窗花品名规则':
            {
                $retstr = $this->orderValueConvert(strtolower($guigearr[0]), $orderInfo[strtolower($guigearr[0])],$doorType);//窗花
                break;
            }
            case '窗花规格规则':
            {
                if ($chuanghualx == '大') {
                    $retstr = $orderInfo[strtolower($guigearr[0])]["chuanghua_height_d"]."*".   //大窗花用料 高
                        $orderInfo[strtolower($guigearr[0])]["chuanghua_width_d"];
                }              //大窗花用料 宽
                elseif ($chuanghualx == '小') {
                    $retstr = $orderInfo[strtolower($guigearr[0])]["chuanghua_height_x"]."*".   //小窗花用料 高
                        $orderInfo[strtolower($guigearr[0])]["chuanghua_width_x"];
                }              //小窗花用料 宽
                else {
                    $retstr = $orderInfo[strtolower($guigearr[0])]["chuanghua_height"]."*".     //单窗花用料 高
                        $orderInfo[strtolower($guigearr[0])]["chuanghua_width"];
                }                //单窗花用料 宽
                break;
            }
            case '外购上门框品名规则':
            {
                $kaixiang = "";
                if (strpos($orderInfo[strtolower($guigearr[1])], "内") !== false) {
                    $kaixiang = "内开";
                }
                if (strpos($orderInfo[strtolower($guigearr[1])], "外") !== false) {
                    $kaixiang = "外开";
                }
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).'上门框'.$kaixiang;
                break;
            }
            case '外购上门框规格规则':
            {
                $guige = $orderInfo[strtolower($guigearr[0])];
                $guigecArr = explode('*', $guige);
                if (empty($guigecArr[1])) {
                    $guigecArr = explode('×', $guige);
                }
                $retstr = $guigecArr[1]."*".$orderInfo[strtolower($guigearr[1])];
                break;
            }
            case '外购中门框品名规则':
            {
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).'中门框';
                break;
            }
            case '外购中门框规格规则':
            {
                $guige = $orderInfo[strtolower($guigearr[0])];
                $guigecArr = explode('*', $guige);
                if (empty($guigecArr[1])) {
                    $guigecArr = explode('×', $guige);
                }
                $retstr = $guigecArr[1]."*".$orderInfo[strtolower($guigearr[1])];
                break;
            }
            case '外购铰门框品名规则':
            {
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).'铰门框'.$orderInfo[strtolower($guigearr[1])];
                break;
            }
            case '外购铰门框规格规则':
            {
                $guige = $orderInfo[strtolower($guigearr[0])];
                $guigecArr = explode('*', $guige);
                if (empty($guigecArr[1])) {
                    $guigecArr = explode('×', $guige);
                }
                $retstr = $guigecArr[0]."*".$orderInfo[strtolower($guigearr[1])];
                break;
            }
            case '外购锁门框品名规则':
            {
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).'锁门框'.$orderInfo[strtolower($guigearr[1])];
                break;
            }
            case '外购锁门框规格规则':
            {
                $guige = $orderInfo[strtolower($guigearr[0])];
                $guigecArr = explode('*', $guige);
                if (empty($guigecArr[1])) {
                    $guigecArr = explode('×', $guige);
                }
                $retstr = $guigecArr[0]."*".$orderInfo[strtolower($guigearr[1])];
                break;
            }

            case '别墅门品名规则':
            {
                $remark = $orderInfo[strtolower($guigearr[0])];
                $str = '';
                #别墅门门头门柱第8码---特殊要求
                $code = Db::query("select attri_name from bom_code_rule where rule_name='别墅门' and code_sort=8 and is_default=0",true);
                foreach($code as $kcode => $vcode) {
                    if (strpos($remark,$vcode['attri_name']) !== false) {
                        $str = $vcode['attri_name'].'/';
                    }
                }
                /*if (strpos($remark,'HL-01') !== false) {
                    $str = 'HL-01/';
                } elseif(strpos($remark,'HL-02') !== false) {
                    $str = 'HL-02/';
                } elseif(strpos($remark,'HL-03') !== false) {
                    $str = 'HL-03/';
                } elseif(strpos($remark,'HL-04') !== false) {
                    $str = 'HL-04/';
                } elseif(strpos($remark,'HL-05') !== false) {
                    $str = 'HL-05/';
                }*/
                $code = '';
                if ($bieshulx == '门头') {
                    $code = 'MT';
                } elseif($bieshulx == '门柱') {
                    $code = 'MZ';
                }
                $retstr = $bieshulx."(".$str.$code.")";
                break;
            }

            case '别墅门规格规则':
            {
                $guige = $orderInfo[strtolower($guigearr[0])];
                $retstr = str_replace('×','*',$guige);
                break;
            }
        }
        return $retstr;
    }
    /**
     * @param $str 含数字的字符串
     * @return 第一个数字值
     */
    public function findNum($str='')
    {
        $retstr = '';
        $str=trim($str);
        if (empty($str)) {
            return '';
        }
        $temp=array('1','2','3','4','5','6','7','8','9','0','.','-');
        for ($i=0;$i<strlen($str);$i++) {
            if (in_array($str[$i], $temp)) {
                $retstr .= $str[$i];
            } else {
                if ($retstr !='') {
                    break;
                }
            }
        }
        return $retstr;
    }

    /**
     * @param $orderColumn 订单列名
     * @param $value 订单值
     * @return 转换过后的值
     */
    public function orderValueConvert($orderColumn, $value, $doorType)
    {
        if ($orderColumn == 'chuanghua') {
            if (strpos($value, '单玻钢化') !== false) {
                $str = str_replace('单玻钢化','',$value);
            } elseif (strpos($value, '双玻钢化') !== false) {
                $str = str_replace('双玻钢化','',$value);
            } elseif (strpos($value, '无玻') !== false) {
                $str = str_replace('无玻','',$value);
            } else {
                $str = $value;
            }
            return  $str;
        } else {
            $convertValues = DB::query("select converted_val from bom_orderval_convert where order_column_name='$orderColumn' and order_val='$value' and door_type='$doorType'", true);
            return $convertValues[0]["converted_val"];
        }
    }

    /**
     * @param $orderColumn 订单列名
     * @param $value 订单值
     * @return mixed 包含后的值
     */
    public function orderTeshuyqConvert($ruleName, $teshuyq='', $menshancz='', $customer_name='', $order_type='', $menkuangcz='', $menkuang='', $dankuanglx='',$menshanyq='')
    {
        $resultString = "";
        switch ($ruleName) {
            case "成品":
                if(strpos($teshuyq, '楼宇门') !== false) {
                    $resultString = "楼宇门";
                } elseif(strpos($menshancz, '覆塑') !== false) {
                    $resultString = "覆塑门";
                }
//                elseif(strpos($teshuyq, '保温门') !== false) {
//                    $resultString = "保温门";
//                }
                elseif($customer_name == "广州立纬") {
                    $resultString = "立纬门";
                } elseif(strpos($customer_name,'万科') !== false && strpos($menkuangcz,'镀锌') !== false) {
                    $resultString = "万科镀锌门";
                }
//                elseif(strpos($customer_name,'万科') !== false && $menkuangcz == '冷轧') {
//                    $resultString = "万科门";
//                }
                elseif(strpos($order_type, '经销商招商订单') !== false) {
                    $resultString = "招商门";
                } elseif(strpos($menkuangcz,'镀锌') !== false && $menshancz == '冷轧') {
                    $resultString = "门框镀锌";
                } elseif(strpos($menkuangcz,'镀锌') !== false && strpos($menshancz,'镀锌') !== false) {
                    $resultString = "门框门扇镀锌";
                } elseif($menkuangcz == '冷轧' && strpos($menshancz,'镀锌') !== false) {
                    $resultString = "门扇镀锌";
                } elseif(strpos($menkuangcz,'锌合金') !== false) {
                    $resultString = "锌合金";
                } elseif(strpos($menshancz,'深雕树纹') !== false && strpos($menkuangcz,'深雕树纹') !== false) {
                    $resultString = "深雕树纹";
                } elseif(strpos($menshancz,'方格树纹') !== false && strpos($menkuangcz,'方格纹') !== false) {
                    $resultString = "门扇方格树纹门框方格纹";
                } elseif(strpos($menshancz,'方格纹') !== false && strpos($menkuangcz,'方格纹') !== false) {
                    $resultString = "方格纹";
                } elseif(strpos($menshancz,'白板树纹') !== false && strpos($menkuangcz,'冷轧') !== false) {
                    $resultString = "门扇白板树纹门框冷轧";
                } elseif(strpos($menshancz,'白板树纹') !== false && strpos($menkuangcz,'常规树纹') !== false) {
                    $resultString = "门扇白板树纹门框常规树纹";
                } elseif(strpos($menshancz,'常规树纹') !== false && strpos($menkuangcz,'常规树纹') !== false) {
                    $resultString = "常规树纹";
                } elseif(strpos($customer_name,'万科') !== false && strpos($customer_name,'泊寓') === false && $menkuangcz == '冷轧') {
                    $resultString = "万科非泊寓";
                } elseif(strpos($customer_name,'泊寓') !== false && $menkuangcz == '冷轧') {
                    $resultString = "万科泊寓";
                } elseif (strpos($menshanyq, '聚氨酯全填')!==false) {
                    $resultString = '聚氨酯全填';
                } elseif (strpos($menshanyq, '岩棉局部填')!==false) {
                    $resultString = '岩棉局部填';
                } elseif (strpos($menshanyq, '岩棉半填')!==false) {
                    $resultString = '岩棉半填';
                } elseif (strpos($menshanyq, '岩棉全填')!==false) {
                    $resultString = '岩棉全填';
                }
                break;
            case "门框半品":
                if(strpos($teshuyq, '楼宇门') !== false) {
                    $resultString = "楼宇门";
                } elseif($customer_name == "广州立纬") {
                    $resultString = "立纬门";
                }  elseif(strpos($customer_name,'万科') !== false && strpos($menkuangcz,'镀锌') !== false) {
                    $resultString = "万科镀锌门";
                }
//                elseif(strpos($customer_name,'万科') !== false && $menkuangcz == '冷轧') {
//                    $resultString = "万科门";
//                }
                elseif(strpos($order_type, '经销商招商订单') !== false) {
                    $resultString = "招商门";
                } elseif(strpos($menkuangcz,'镀锌') !== false && $menshancz == '冷轧') {
                    $resultString = "门框镀锌";
                } elseif(strpos($menkuangcz,'镀锌') !== false && strpos($menshancz,'镀锌') !== false) {
                    $resultString = "门框门扇镀锌";
                } elseif(strpos($menkuangcz,'锌合金') !== false) {
                    $resultString = "锌合金";
                } elseif(strpos($menkuangcz,'深雕树纹') !== false) {
                    $resultString = "深雕树纹";
                } elseif(strpos($menkuangcz,'方格纹') !== false) {
                    $resultString = "方格纹";
                } elseif(strpos($menkuangcz,'常规树纹') !== false) {
                    $resultString = "常规树纹";
                } elseif(strpos($customer_name,'万科') !== false && strpos($customer_name,'泊寓') === false && $menkuangcz == '冷轧') {
                    $resultString = "万科非泊寓";
                } elseif(strpos($customer_name,'泊寓') !== false && $menkuangcz == '冷轧') {
                    $resultString = "万科泊寓";
                }
                break;
            case "门扇半品":
                if(strpos($teshuyq, '楼宇门') !== false) {
                    $resultString = "楼宇门";
                } elseif(strpos($menshancz, '覆塑') !== false) {
                    $resultString = "覆塑门";
                }
//                elseif(strpos($teshuyq, '保温门') !== false) {
//                    $resultString = "保温门";
//                }
                elseif($customer_name == "广州立纬") {
                    $resultString = "立纬门";
                }  elseif(strpos($customer_name,'万科') !== false && strpos($menkuangcz,'镀锌') !== false) {
                    $resultString = "万科镀锌门";
                }
//                elseif(strpos($customer_name,'万科') !== false && $menkuangcz == '冷轧') {
//                    $resultString = "万科门";
//                }
                elseif(strpos($order_type, '经销商招商订单') !== false) {
                    $resultString = "招商门";
                } elseif($menkuangcz == '冷轧' && strpos($menshancz,'镀锌') !== false) {
                    $resultString = "门扇镀锌";
                } elseif(strpos($menkuangcz,'锌合金') !== false) {
                    $resultString = "锌合金";
                } elseif(strpos($menshancz,'锌合金') !== false) {
                    $resultString = "锌合金";
                } elseif(strpos($menshancz,'深雕树纹') !== false) {
                    $resultString = "深雕树纹";
                } elseif(strpos($menshancz,'方格树纹') !== false) {
                    $resultString = "方格树纹";
                } elseif(strpos($menshancz,'方格纹') !== false) {
                    $resultString = "方格纹";
                } elseif(strpos($menshancz,'白板树纹') !== false) {
                    $resultString = "白板树纹";
                } elseif(strpos($menshancz,'常规树纹') !== false) {
                    $resultString = "常规树纹";
                } elseif(strpos($customer_name,'万科') !== false && strpos($customer_name,'泊寓') === false && $menkuangcz == '冷轧') {
                    $resultString = "万科非泊寓";
                } elseif(strpos($customer_name,'泊寓') !== false && $menkuangcz == '冷轧') {
                    $resultString = "万科泊寓";
                } elseif (strpos($menshanyq, '聚氨酯全填')!==false) {
                    $resultString = '聚氨酯全填';
                } elseif (strpos($menshanyq, '岩棉局部填')!==false) {
                    $resultString = '岩棉局部填';
                } elseif (strpos($menshanyq, '岩棉半填')!==false) {
                    $resultString = '岩棉半填';
                } elseif (strpos($menshanyq, '岩棉全填')!==false) {
                    $resultString = '岩棉全填';
                }
                break;
            case "单框":
                if (strpos($menkuang, '70三角框115钢套门') !== false && $dankuanglx == '中框') {
                    $resultString = '';
                } else {
                    if(strpos($menkuangcz,'镀锌') !== false) {
                        $resultString = "门框镀锌";
                    } elseif(strpos($menkuangcz,'锌合金') !== false) {
                        $resultString = "锌合金";
                    } elseif(strpos($menkuangcz,'深雕树纹') !== false) {
                        $resultString = "深雕树纹";
                    } elseif(strpos($menkuangcz,'方格纹') !== false) {
                        $resultString = "方格纹";
                    } elseif(strpos($menkuangcz,'常规树纹') !== false) {
                        $resultString = "常规树纹";
                    }
                }
                break;
            case "门板":
                if(strpos($menshancz,'镀锌') !== false) {
                    $resultString = "门扇镀锌";
                } elseif(strpos($menshancz,'锌合金') !== false) {
                    $resultString = "锌合金";
                } elseif(strpos($menshancz,'深雕树纹') !== false) {
                    $resultString = "深雕树纹";
                } elseif(strpos($menshancz,'方格树纹') !== false) {
                    $resultString = "方格树纹";
                } elseif(strpos($menshancz,'方格纹') !== false) {
                    $resultString = "方格纹";
                } elseif(strpos($menshancz,'白板树纹') !== false) {
                    $resultString = "白板树纹";
                } elseif(strpos($menshancz,'常规树纹') !== false) {
                    $resultString = "常规树纹";
                }
                break;
            default:
                $resultString = "";
        }
        return $resultString;
    }

    /**获取材质底框矩管编码规则
     * @param $codeArray
     * @param $menkuangyl
     * @return array
     */
    public function getJuguanCode(&$codeArray, $menkuangyl)
    {
        $yongliao=$menkuangyl['xia_yongliao'];
        $menkuang='xiamk';
        $caizhiDensity=$yongliao['xiamk_density'];
        $codeStr=$yongliao[$menkuang];
        $caizhiyl['height']=$yongliao[$menkuang.'_length'];
        $caizhiyl['thickness_real']=$yongliao[$menkuang.'_hd'];
        $caizhiyl['width']=$codeStr;
        $codeStr=explode('*', $codeStr);
        $caizhiyl['thickness']=$codeStr[1];
        $codeStr[0] = sprintf("%04d", $codeStr[0]*1);
        $codeStr[1] = sprintf("%04d", $codeStr[1]*1);
        $codeStr[2] = sprintf("%04d", $codeStr[2]*1000);
        $xiamkInfo = explode('*', $caizhiyl['width']);
        $caizhiyl['length'] = $xiamkInfo[0];
        $codeStr = implode('', $codeStr);
        $temp = array("sort_number"=>'1',"code"=>$codeStr);
        $codes['1'] = $temp;
        $codeArray = array('code'=>$codes,'caizhi'=>$caizhiyl);
        return $codeArray;
    }

    /**
     * 特殊门框处理
     */
    public function specialMenkuang($menkuang = '', $menkuangcz = '')
    {
        $menkuangCaizhi = array('冷轧','镀锌');
        $data = array();
        if (strpos($menkuang, '70三角框115钢套门') !== false && (in_array($menkuangcz,$menkuangCaizhi) || strpos($menkuangcz, '镀锌') !== false)) {
            $data['menkuangcz'] = '冷轧';
        }
        return empty($data)?0:1;
    }

    /**
     * 客户名称转换
     * @param $value
     * @return string
     */
    public function convertValue($value, $zhizaobm)
    {
        if (in_array($zhizaobm, ['成都制造一部','成都制造二部'])) {
            if (strpos($value, '广州立伟') !== false) {
                return '广州立伟';
            } else {
                return '非广州立伟';
            }
        } elseif ($zhizaobm == '成都制造三部') {
            if (strpos($value, '泊寓') !== false) {
                return '万科泊寓';
            } elseif (strpos($value, '万科') !== false && strpos($value, '泊寓') === false) {
                return '万科非泊寓';
            } elseif(strpos($value, '万科') === false && strpos($value, '泊寓') === false) {
                return '非万科';
            }
        }
    }

    public function getBiankuang($order, $type)
    {
        $menshan = $order['menshan'];
        $menkuangyq = $order['menkuangyq'];
        if (strpos($menshan, '单扇门') !== false) {
            if (in_array($menkuangyq,['左开左无边','右开右无边'])) {
                if ($type == '铰框') {
                    return '普框边框';
                } elseif ($type == '锁框') {
                    return '边框';
                }
            } elseif (in_array($menkuangyq, ['左开右无边','右开左无边'])) {
                if ($type == '铰框') {
                    return '边框';
                } elseif ($type == '锁框') {
                    return '普框边框';
                }
            } elseif (!in_array($menkuangyq,['左开左无边','右开右无边','左开右无边','右开左无边'])) {
                return '边框';
            }
        } else {
            if (in_array($menkuangyq,['左开左无边','右开右无边'])) {
                if ($type == '铰框') {
                    return '普框边框';
                } elseif ($type == '锁框') {
                    return '边框';
                }
            } elseif (in_array($menkuangyq, ['左开右无边','右开左无边'])) {
                if ($type == '铰框') {
                    return '边框';
                } elseif ($type == '锁框') {
                    return '普框边框';
                }
            } elseif (!in_array($menkuangyq,['左开左无边','右开右无边','左开右无边','右开左无边'])) {
                return '边框';
            }
        }
    }
}
