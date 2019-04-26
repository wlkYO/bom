<?php
/**
 * 木质门编码规则根据订单属性解析
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/25
 * Time: 14:34
 */
namespace app\admin\service;

use think\Db;

class MuzhiCodeService
{
    const CHENGPIN = "成品";//木质门成品
    const MENTAO= "门套";//木质门门套
    const MENSHAN = "门扇";//木质门门扇
    const DANTAOBAN = "单套板";//木质门单套板
    const CHUANGTAO = "窗套";//木质门窗套
    const XAINTIAO = "线条";//木质门线条
    const CHENGPIN_FIXED = "MH";//木质门成品前2位固定码
    const MENTAO_FIXED = "MT";//木质门门套前2位固定码
    const MENSHAN_FIXED = "MS";//木质门门扇前2位固定码
    const DANTAOBAN_FIXED = "MG";//木质门单套板前2位固定码
    const CHUANGTAO_FIXED = "MC";//木质门窗套前2位固定码
    const XAINTIAO_FIXED = "MX";//木质门线条前2位固定码

    /**
     * @param $ruleName  规则名称
     * @param $order 订单信息
     * @param $mentaoLeixing 门套类型
     */
    public function getMaterialCode($ruleName, $order, $mentaoLeixing='',$menshanlx='')
    {
        $doorType = 'M7';//木质门
        if (in_array($ruleName,['单套板','线条'])) {
            $findAllData = "select t.* from bom_code_rule t where t.rule_name='$ruleName' and t.door_type='$doorType' order by t.code_sort asc";
        } else {
            $findAllData = "select t.* from bom_code_rule t where t.rule_name='$ruleName' and t.door_type='$doorType' and t.order_column_name!='GUIGE' order by t.code_sort asc";
        }

        $sortNum = "select distinct(code_sort),order_column_name from bom_code_rule where rule_name='$ruleName' and door_type='$doorType' order by code_sort asc";
        $allData = DB::query($findAllData);
        //去空格
        foreach ($allData as $k => $v) {
            foreach ($v as $key => $val) {
                $allData[$k][$key] = excel_trim($val);
            }
        }
        $sorts = DB::query($sortNum);
        $handleSortArray = array();//待处理的序号数组
        foreach ($sorts as $k=>$v) {
            array_push($handleSortArray, $v['CODE_SORT']);
        }

        //编码数组
        $code = array();
        //对规格进行单独处理
        $guige = $order['guige'];
        $guigeChuli = "select code,code_sort from bom_code_rule where rule_name='$ruleName' and attri_name='$guige' and door_type='$doorType'";
        $guigeCode = DB::query($guigeChuli);
        if (empty($guigeCode)) {
            $guigeCode = DB::query("select code,code_sort from bom_code_rule where rule_name='$ruleName' and order_column_name='GUIGE' and is_default=1 and door_type='$doorType'");
        }
        if (!empty($guigeCode) && !in_array($ruleName,['单套板','线条'])) {
            $guigeMaterialCode = $guigeCode[0]['CODE'];
            $guigeCodeSort = $guigeCode[0]['CODE_SORT'];
            $guigeArr = array("sort_number"=>$guigeCodeSort,"code"=>$guigeMaterialCode);
            $code[$guigeCodeSort]=$guigeArr;
            unset($handleSortArray[array_search($guigeCodeSort, $handleSortArray)]);
        }//规格处理结束
//        var_dump($handleSortArray);
//        return;
        $fixedCode = "";
        //规格拆分为数组
        $guigecArr = explode('*',$guige);
        if (empty($guigecArr[1])){
            $guigecArr = explode('×',$guige);
        }

       if ($mentaoLeixing ==  '侧方套板' || $mentaoLeixing ==  '侧方线条') {
            $guigeValue = $guigecArr[0];////侧方套板线条取高度
        } else {
            $guigeValue = $guigecArr[1];//上中下套板取宽度
        }
        foreach ($allData as $key=>$val) {
            $attriName = $val['ATTRI_NAME'];//属性值
            $orderColumnName = $val['ORDER_COLUMN_NAME'];//关联的订单列名
            $orderAttrName = $order[strtolower($orderColumnName)];//关联的订单属性值
            $orderMultiName = "";//关联多订单属性，拼接字符串值
            if (strpos($orderColumnName, ',')) {
                $orderColumnNameArray = explode(',', $orderColumnName);
                foreach ($orderColumnNameArray as $k => $v) {
                    $orderMultiName .= $order[strtolower($v)];
                }
            }
            $codeName = $val['CODE_NAME'];//编码含义
            $sortID = $val['CODE_SORT'];//排序码
            $isDefault = $val['IS_DEFAULT'];//默认占位符

            switch ($ruleName) {
                //木门成品编码规则
                case self::CHENGPIN:
                    $fixedCode = self::CHENGPIN_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if(  #属性值完全匹配
                        $attriName == $orderAttrName ||
                        #/多属性值匹配 ---包装品牌方式
                        ($orderMultiName!= "" && $attriName==$orderMultiName) ||
                        ($codeName=='门扇要求'&&$this->match($codeName, $attriName, $orderAttrName, '', $menshanlx))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                break;
                //木门门套编码规则
                case self::MENTAO:
                    $fixedCode = self::MENTAO_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if(  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;
                //木门门扇编码规则
                case self::MENSHAN:
                    $fixedCode = self::MENSHAN_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if(  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配 ---包装品牌方式
                            ($orderMultiName!= "" && $attriName==$orderMultiName) ||
                            ($codeName=='门扇类型'&&$this->match($codeName, $attriName, $orderAttrName, '', $menshanlx)) ||
                            ($codeName=='门扇要求'&&$this->match($codeName, $attriName, $orderAttrName, '', $menshanlx))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;
                //木门单套板编码规则
                case self::DANTAOBAN:
                    $fixedCode = self::DANTAOBAN_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if(  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配 ---包装品牌方式
                            ($orderMultiName!= "" && $attriName==$orderMultiName) ||
                            ($codeName=='单板类型'&&$this->match($codeName, $attriName, $orderAttrName, $mentaoLeixing,''))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($sortID == 10) {# 单套板第10码取值于规格宽度
                            $codeStr=sprintf("%04d", $guigeValue);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;
                //木门窗套编码规则
                case self::CHUANGTAO:
                    $fixedCode = self::CHUANGTAO_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if(  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配 ---包装品牌方式
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;
                //木门线条编码规则
                case self::XAINTIAO:
                    $fixedCode = self::XAINTIAO_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if(  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            ($codeName=='线条类型'&&$this->match($codeName, $attriName, $orderAttrName, $mentaoLeixing, ''))
                        ) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($sortID == 8) {# 线条第8码取值于规格宽度
                            $codeStr=sprintf("%04d", $guigeValue);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        }
                        elseif ($isDefault) {
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;
            }
        }
        $liaohao=$fixedCode;
        ksort($code);//根据键顺序重新排列
        foreach ($code as $kk=>$vv) {
            $liaohao .= $vv['code'];
        }
        $yuanjianName='木门'.$ruleName;
        $yuanjianLevel='成品级';
        if (in_array($ruleName,['单套板','线条'])) {
            $yuanjianLevel = '门套级';
        }
        if ($ruleName == '单套板' || $ruleName == '线条') {
            $yuanjianName = '木门'.$mentaoLeixing;
        }
        if (!empty($menshanlx)) {
            $yuanjianName='木门'.$menshanlx.'门';
//            $yuanjianLevel = '门扇级('.$menshanlx.'门)';
        }
        if (!empty($mentaoLeixing)) {
            $name='木门'.$mentaoLeixing;
        } else {
            $name='木门'.$ruleName;
        }
        $yongliao = 1;
        if ($mentaoLeixing == '侧方线条' || $mentaoLeixing == '侧方套板') {
            $yongliao = 2;
        }

        $liaohaoPinming=$this->getPingmingOrGuige($name.'品名规则', $order, $mentaoLeixing, $menshanlx);
        $liaohaoGuige=$this->getPingmingOrGuige($name.'规格规则', $order, $mentaoLeixing, $menshanlx);
        return [
            'liaohao'=>$liaohao,
            'liaohao_guige'=>$liaohaoGuige,
            'liaohao_pinming'=>$liaohaoPinming,
            'yongliang'=>$yongliao,
            'liaohao_danwei'=>'',
            'yuanjian_name'=>$yuanjianName,
            'yuanjian_level'=>$yuanjianLevel,
        ];
    }

    public function match($codeName, $attriName, $orderAttrName,$mentaoLeixing,$menshanlx)
    {
        switch ($codeName) {
            case '单板类型':
                $str=$mentaoLeixing;
                return $attriName==$str;
            case '门扇类型':
                if (strpos($orderAttrName, '单扇')!==false) {
                    $str='单扇门';
                } elseif (strpos($orderAttrName, '对开')!==false) {
                    $str='对开门'.$menshanlx.'门';
                } elseif (strpos($orderAttrName, '子母')!==false) {
                    $str='子母门'.$menshanlx.'门';
                }
                return $attriName==$str;
            case '线条类型':
                $str=$mentaoLeixing;
                return $attriName==$str;
            case '门扇要求':
                $str=empty($orderAttrName)?'无':$orderAttrName;
                return $attriName==$str;
        }
    }

    public function getPingmingOrGuige($ruleName, $orderInfo, $mentaoLeixing, $menshanlx)
    {
        $doorType = (excel_trim($orderInfo['order_db']) == 'M8')?'M8':'M9';//精品门取钢质门那一套规则
        $retstr = '';
        $guigelist = DB::query("select order_column_name from bom_code_rule where rule_name='$ruleName'", true);
        $guigearr = explode(",", $guigelist[0]["order_column_name"]);
        switch ($ruleName) {
            case '木门成品品名规则':{
                //DANG_CI,DOOR_STYLE,MENSHAN,HUASE,MENKUANGYQ,JIAOLIAN,MENSHANYQ,CHUANGHUA,REMARK
                $remark = $orderInfo[strtolower($guigearr[8])];
                $menshanyq = empty($orderInfo[strtolower($guigearr[6])])?'无':$orderInfo[strtolower($guigearr[6])];
                $mentouzhu = '无';
                if (strpos($remark,'门头') !== false && strpos($remark,'门柱') !== false) {
                    $mentouzhu = '门头门柱';
                } elseif (strpos($remark,'门头') !== false) {
                    $mentouzhu = '门头';
                } elseif (strpos($remark,'门柱') !== false) {
                    $mentouzhu ='门柱';
                }
                $retstr = $orderInfo[strtolower($guigearr[0])].          //档次
                    $orderInfo[strtolower($guigearr[1])].  //产品种类
                    $orderInfo[strtolower($guigearr[2])].  //门扇类型
                    $orderInfo[strtolower($guigearr[3])].  //花色
                    $orderInfo[strtolower($guigearr[4])].  //门套结构
                    $orderInfo[strtolower($guigearr[5])].  //线条种类
                    $menshanyq.  //门扇要求
                    $orderInfo[strtolower($guigearr[7])].  //窗花
                    $mentouzhu;//门头门柱
                break;
            }
            case '木门成品规格规则':{
                //GUIGE,MKHOUDU,MAOYAN,MENKUANG,MENKUANGCZ,BIAOMCL,BIAO_PAI,DKCAILIAO,BIAOJIAN
                $retstr = $orderInfo[strtolower($guigearr[0])].      //规格
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门套结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])].  //表面花纹
                    $orderInfo[strtolower($guigearr[7])].  //线条样式
                    $orderInfo[strtolower($guigearr[8])];  //线条结构
                break;
            }
            case '木门门套品名规则':{
//                MENSHAN,DOOR_STYLE,MENKUANGYQ,JIAOLIAN,CHUANGHUA,BAOZHPACK,BAOZHUANGFS
//                DOOR_STYLE,MENKUANGYQ,JIAOLIAN,CHUANGHUA,BAOZHPACK,BAOZHUANGFS--0222取消门扇数据
                $retstr =
                    $orderInfo[strtolower($guigearr[0])].  //产品种类
                    $orderInfo[strtolower($guigearr[1])].  //门套结构
                    $orderInfo[strtolower($guigearr[2])].  //线条种类
                    $orderInfo[strtolower($guigearr[3])].  //窗花
                    $orderInfo[strtolower($guigearr[4])].  //包装品牌
                    $orderInfo[strtolower($guigearr[5])];  //包装方式
                break;
            }
            case '木门门套规格规则':{
//                GUIGE,MKHOUDU,MAOYAN,MENKUANG,MENKUANGCZ,BIAOMCL,BIAO_PAI,DKCAILIAO,BIAOJIAN
                $retstr = $orderInfo[strtolower($guigearr[0])].      //规格
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门类结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])].  //表面花纹
                    $orderInfo[strtolower($guigearr[7])].  //线条样式
                    $orderInfo[strtolower($guigearr[8])];  //线条结构
                break;
            }
            case '木门门扇品名规则':{
//                MENSHAN,DANG_CI,DOOR_STYLE,HUASE,KAIXIANG,MENSHANYQ,BAOZHPACK,BAOZHUANGFS
                $menshan = $orderInfo[strtolower($guigearr[0])];
                if (strpos($menshan, '单扇') !== false) {
                    $ms = "门扇";
                } elseif (strpos($menshan, '子母') !== false) {
                    if ($menshanlx == "母") {
                        $ms = "子母母扇";
                    } elseif ($menshanlx == "子") {
                        $ms = "子母子扇";
                    }
                } elseif (strpos($menshan, '对开') !== false) {
                    if ($menshanlx == "母") {
                        $ms = '对开母扇';
                    } elseif ($menshanlx == "子") {
                        $ms = '对开子扇';
                    }
                } elseif (strpos($menshan, '单推') !== false) {
                    $ms = '单推';
                } elseif (strpos($menshan, '双推') !== false) {
                    if ($menshanlx == "母") {
                        $ms = '双推母扇';
                    } elseif ($menshanlx == "子") {
                        $ms = '双推子扇';
                    }
                } else {
                    $menshan = "未知门扇";
                }
                $menshanyq = empty($orderInfo[strtolower($guigearr[5])])?'无':$orderInfo[strtolower($guigearr[5])];
                $retstr = $ms.      //门扇
                    $orderInfo[strtolower($guigearr[1])].  //档次
                    $orderInfo[strtolower($guigearr[2])].  //产品种类
                    $orderInfo[strtolower($guigearr[3])].  //花色
                    $orderInfo[strtolower($guigearr[4])].  //扣线
                    $menshanyq.  //门扇要求
                    $orderInfo[strtolower($guigearr[6])].  //包装品牌
                    $orderInfo[strtolower($guigearr[7])];  //包装方式
                break;
            }
            case '木门门扇规格规则':{
//                GUIGE,MENKUANGCZ,BIAOMCL,BIAO_PAI
                $retstr = $orderInfo[strtolower($guigearr[0])].      //规格
                    $orderInfo[strtolower($guigearr[1])].  //门类结构
                    $orderInfo[strtolower($guigearr[2])].  //表面方式
                    $orderInfo[strtolower($guigearr[3])];  //表面花纹
                break;
            }
            case '木门窗套品名规则':{
//                DOOR_STYLE,MENKUANGYQ,JIAOLIAN,BAOZHPACK,BAOZHUANGFS
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    '窗套'.  //窗套
                    $orderInfo[strtolower($guigearr[1])].  //门套结构
                    $orderInfo[strtolower($guigearr[2])].  //线条种类
                    $orderInfo[strtolower($guigearr[3])].  //包装品牌
                    $orderInfo[strtolower($guigearr[4])];  //包装方式
                break;
            }
            case '木门窗套规格规则':{
//                GUIGE,MKHOUDU,MAOYAN,MENKUANG,MENKUANGCZ,BIAOMCL,BIAO_PAI,DKCAILIAO,BIAOJIAN
                $retstr = $orderInfo[strtolower($guigearr[0])].      //规格
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门类结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])].  //表面花纹
                    $orderInfo[strtolower($guigearr[7])].  //线条样式
                    $orderInfo[strtolower($guigearr[8])];  //线条结构
                break;
            }
            case '木门上方线条品名规则':{
//                DOOR_STYLE,BIAOMCL,BIAO_PAI
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    $mentaoLeixing.
                    $orderInfo[strtolower($guigearr[1])].  //表面方式
                    $orderInfo[strtolower($guigearr[2])];  //表面花纹
                break;
            }
            case '木门上方线条规格规则':{
//                GUIGE,JIAOLIAN,DKCAILIAO,BIAOJIAN
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[1].      //规格
                    $orderInfo[strtolower($guigearr[1])].  //线条种类
                    $orderInfo[strtolower($guigearr[2])].  //线条样式
                    $orderInfo[strtolower($guigearr[3])];  //线条结构
                break;
            }
            case '木门下方线条品名规则':{
//                DOOR_STYLE,BIAOMCL,BIAO_PAI
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    $mentaoLeixing.
                    $orderInfo[strtolower($guigearr[1])].  //表面方式
                    $orderInfo[strtolower($guigearr[2])];  //表面花纹
                break;
            }
            case '木门下方线条规格规则':{
//                GUIGE,JIAOLIAN,DKCAILIAO,BIAOJIAN
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[1].      //规格
                    $orderInfo[strtolower($guigearr[1])].  //线条种类
                    $orderInfo[strtolower($guigearr[2])].  //线条样式
                    $orderInfo[strtolower($guigearr[3])];  //线条结构
                break;
            }
            case '木门侧方线条品名规则':{
//                DOOR_STYLE,BIAOMCL,BIAO_PAI
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    $mentaoLeixing.
                    $orderInfo[strtolower($guigearr[1])].  //表面方式
                    $orderInfo[strtolower($guigearr[2])];  //表面花纹
                break;
            }
            case '木门侧方线条规格规则':{
//                GUIGE,JIAOLIAN,DKCAILIAO,BIAOJIAN
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[0].      //规格
                    $orderInfo[strtolower($guigearr[1])].  //线条种类
                    $orderInfo[strtolower($guigearr[2])].  //线条样式
                    $orderInfo[strtolower($guigearr[3])];  //线条结构
                break;
            }
            case '木门上方套板品名规则':{
//                DOOR_STYLE,MENKUANGYQ
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    '上方套板'.
                    $orderInfo[strtolower($guigearr[1])];  //线条种类
                break;
            }
            case '木门上方套板规格规则':{
//                GUIGE,MKHOUDU,MAOYAN,MENKUANG,MENKUANGCZ,BIAOMCL,BIAO_PAI
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[1].      //规格---规格宽度
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门类结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])];  //表面花纹
                break;
            }
            case '木门侧方套板品名规则':{
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    '侧方套板'.
                    $orderInfo[strtolower($guigearr[1])];  //线条种类
                break;
            }
            case '木门侧方套板规格规则':{
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[0].      //规格
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门类结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])];  //表面花纹
                break;
            }
            case '木门中方套板品名规则':{
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    '中方套板'.
                    $orderInfo[strtolower($guigearr[1])];  //线条种类
                break;
            }
            case '木门中方套板规格规则':{
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[1].      //规格
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门类结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])];  //表面花纹
                break;
            }
            case '木门下方套板品名规则':{
                $retstr = $orderInfo[strtolower($guigearr[0])].      //产品种类
                    '下方套板'.
                    $orderInfo[strtolower($guigearr[1])];  //线条种类
                break;
            }
            case '木门下方套板规格规则':{
                $guigeArr = explode('*',$orderInfo[strtolower($guigearr[0])]);
                if (empty($guigeArr[1])){
                    $guigeArr = explode('×',$orderInfo[strtolower($guigearr[0])]);
                }
                $retstr = $guigeArr[1].      //规格
                    '/'.
                    $orderInfo[strtolower($guigearr[1])].  //门套墙体
                    $orderInfo[strtolower($guigearr[2])].  //门套厚度
                    $orderInfo[strtolower($guigearr[3])].  //门套样式
                    $orderInfo[strtolower($guigearr[4])].  //门类结构
                    $orderInfo[strtolower($guigearr[5])].  //表面方式
                    $orderInfo[strtolower($guigearr[6])];  //表面花纹
                break;
            }
        }
        return $retstr;
    }

    /**
     *获取元件列表
     * @param $order  订单信息
     * @return json
     */
    public function getYuanJianList($order)
    {
        $orderID = $order['oeb01'];
        $doorType = 'M7';
        $menKuang = $order['door_style'];
        $zhizaobm= $order['zhizaobm'];
        $order['teshuyq'] = empty($order['customer_name'])?'':$order['customer_name'];
        $order['menkuangyq'] = empty($order['menkuangyq'])?'无':$order['menkuangyq'];
        $sql = "select * from bom_yuanjian a where a.menkuang = '$menKuang' and a.door_type = '$doorType' and a.zhizaobm='$zhizaobm' order by id";
        $yuanjian_list = Db::query($sql, true);
        $data = array();
        foreach ($yuanjian_list as $key=>$val) {
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
                        $where.=" and $columnName like '%,$orderValue,%'";
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
}