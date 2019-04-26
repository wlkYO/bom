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

class NaihcCodeService
{
    const CHENGPIN = "成品";//耐火窗成品
    const BANPINCK = "半品窗框";//耐火窗半品窗框
    const BANPINSK = "半品扇框";//耐火窗半品扇框
    const DANKUANG = "耐火窗单框";//耐火窗单框
    const CHUANGKUANG_HK = "窗框横框";
    const CHUANGKUANG_SK = "窗框竖框";
    const CHUANGKUANG_HD = "窗框横档";
    const CHUANGKUANG_LZ = "窗框立柱";
    const SHANKUANG_HK = "扇框横框";
    const SHANKUANG_SK = "扇框竖框";
    const ZHUANKUANG_HK = "转换框横框";
    const ZHUANKUANG_SK = "转换框竖框";
    const CHENGPIN_FIXED = "FC";//耐火窗成品前2位固定码
    const BANPINCK_FIXED = "FK";//耐火窗半品窗框前2位固定码
    const BANPINSK_FIXED = "FS";//耐火窗半品扇框前2位固定码
    const DANKUANG_FIXED = "FG";//耐火窗单框前2位固定码

    const CHUANGKUANG_HK_FIXED = "窗框横框";
    const CHUANGKUANG_SK_FIXED = "窗框竖框";
    const CHUANGKUANG_HD_FIXED = "窗框横档";
    const CHUANGKUANG_LZ_FIXED = "窗框立柱";
    const SHANKUANG_HK_FIXED = "扇框横框";
    const SHANKUANG_SK_FIXED = "扇框竖框";
    const ZHUANKUANG_HK_FIXED = "转换框横框";
    const ZHUANKUANG_SK_FIXED = "转换框竖框";

    const JIAOLIAN = '2个耐火窗铰链';  //固定铰链的值
    const TESHUYQ = 'X'; //特殊要求


    public function getNaihcMaterialCode($ruleName, $order, $chuangkLeixing = '', $shanklx = '')
    {
        $doorType = 'M4';//防火窗
        $findAllData = "select * from bom_code_rule where rule_name = '$ruleName' and door_type = '$doorType'  order by code_sort asc";
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
        foreach ($sorts as $k => $v) {
            array_push($handleSortArray, $v['CODE_SORT']);
        }
        //编码数组
        $code = array();
        foreach ($allData as $key => $val) {
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
                //耐火窗成品编码规则
                case self::CHENGPIN:
                    $fixedCode = self::CHENGPIN_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if (  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配 ---包装品牌方式
                            ($orderMultiName != "" && $attriName == $orderMultiName)
                        ) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        } elseif ($isDefault) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                        }
                    }
                    break;
                //耐火窗半品窗框编码规则
                case self::BANPINCK:
                    $fixedCode = self::BANPINCK_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if (  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配
                            ($orderMultiName != "" && $attriName == $orderMultiName)
                        ) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        } elseif ($isDefault) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                        }
                    }
                    break;
                //耐火窗半品扇框编码规则
                case self::BANPINSK:
                    $fixedCode = self::BANPINSK_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if (  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配 ---包装品牌方式
                            ($orderMultiName != "" && $attriName == $orderMultiName)
                        ) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        } elseif ($isDefault) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                        }
                    }
                    break;
                //耐火窗单框编码规则
                case self::DANKUANG:
                    $fixedCode = self::DANKUANG_FIXED;
                    if (in_array($sortID, $handleSortArray)) {
                        if (  #属性值完全匹配
                            $attriName == $orderAttrName ||
                            #/多属性值匹配 ---包装品牌方式
                            ($orderMultiName != "" && $attriName == $orderMultiName)) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        } elseif ($sortID == 4) {  #单框第4码处理
                            if ($attriName == $shanklx) {
                                $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                                $code[$sortID] = $temp;
                                unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                            } elseif ($attriName == $chuangkLeixing) {
                                $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                                $code[$sortID] = $temp;
                                unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                            }
                        } elseif ($sortID == 10) {# 单框第10码取值于规格高度
                            $guige = $order['guige'];
                            $guige_arr = explode('×', $guige);
                            if (empty($guige_arr[1])) {
                                $guige_arr = explode('*', $guige);
                            }
                            $codeStr = sprintf("%04d", $guige_arr[0]);
                            $temp = array("sort_number" => $sortID, "code" => $codeStr);
                            $code[$sortID] = $temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        } elseif ($sortID == 14) {# 单框第10码取值于规格高度
                            $kuanghou = !empty($chuangkLeixing) ? $order['mkhoudu'] : $order['qmenbhd'];
                            $kuanghou = $kuanghou * 100;
                            $codeStr = sprintf("%04d", $kuanghou);
                            $temp = array("sort_number" => $sortID, "code" => $codeStr);
                            $code[$sortID] = $temp;
                            unset($handleSortArray[array_search($sortID, $handleSortArray)]);
                        } elseif ($isDefault) {
                            $temp = array("sort_number" => $sortID, "code" => $val['CODE']);
                            $code[$sortID] = $temp;
                        }
                    }
                    break;
            }
        }
        $liaohao = $fixedCode;
        ksort($code);//根据键顺序重新排列
        foreach ($code as $kk => $vv) {
            $liaohao .= $vv['code'];
        }

        $yuanjianName = '耐火窗' . $ruleName;
        $yuanjianLevel = '成品级';
        $yongliao = 1;
        if ($ruleName == '耐火窗单框') {
            $yuanjianName = empty($chuangkLeixing) ? $shanklx : $chuangkLeixing;
            $jibie = empty($chuangkLeixing) ? '半品扇框' : "半品窗框";
            $yuanjianLevel = $jibie . '级';
            $ruleName = $yuanjianName;
        }
        $liaohaoPinming = $this->getPingmingOrGuige($ruleName . '品名规则', $order, $chuangkLeixing, $shanklx);
        $liaohaoGuige = $this->getPingmingOrGuige($ruleName . '规格规则', $order, $chuangkLeixing, $shanklx);
        return [
            'liaohao' => $liaohao,
            'liaohao_guige' => $liaohaoGuige,
            'liaohao_pinming' => $liaohaoPinming,
            'yongliang' => $yongliao,
            'liaohao_danwei' => '',
            'yuanjian_name' => $yuanjianName,
            'yuanjian_level' => $yuanjianLevel,
        ];
    }

    public function match($codeName, $attriName, $orderAttrName, $mentaoLeixing, $menshanlx)
    {
        switch ($codeName) {
            case '单板类型':
                $str = $mentaoLeixing;
                return $attriName == $str;
            case '门扇类型':
                if (strpos($orderAttrName, '单扇') !== false) {
                    $str = '单扇门';
                } elseif (strpos($orderAttrName, '对开') !== false) {
                    $str = '对开门' . $menshanlx . '门';
                } elseif (strpos($orderAttrName, '子母') !== false) {
                    $str = '子母门' . $menshanlx . '门';
                }
                return $attriName == $str;
            case '线条类型':
                $str = $mentaoLeixing;
                return $attriName == $str;
            case '门扇要求':
                $str = empty($orderAttrName) ? '无' : $orderAttrName;
                return $attriName == $str;
        }
    }

    public function getPingmingOrGuige($ruleName, $orderInfo, $mentaoLeixing, $menshanlx)
    {
        $jiaolian = $this::JIAOLIAN;
        $teshuyq = $this::TESHUYQ;
        $orderInfo = array_change_key_case($orderInfo, CASE_UPPER);
        switch ($ruleName) {
            case '成品品名规则':
                {
                    //产品类别/开向/表面方式/固定件/铰链/特殊要求/材质
                    $retstr = $orderInfo['DANG_CI'] . '/' . $orderInfo['KAIXIANG'] . '/' . $orderInfo['BIAOMCL'] . '/' . $orderInfo['SUOJU'] . '/' . $jiaolian . '/' . $teshuyq . '/' . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '成品规格规则':
                {
                    //样式/横档数量/左横档间距/中横档间距/右横档间距/立柱数量/上立柱间距/中立柱间距/下立柱间距/尺寸/窗框厚度/扇框厚度
                    $retstr = $orderInfo['MENKUANG'] . '/' . $orderInfo['CHUANGHUA'] . '/' . $orderInfo['MAOYAN'] . '/' . $orderInfo['BIAO_PAI'] . '/' . $orderInfo['MSKAIKONG'] . '/' . $orderInfo['JIAOLIAN'] . '/' . $orderInfo['BIAOJIAN'] . '/' . $orderInfo['BAOZHPACK'] . '/' . $orderInfo['BAOZHUANGFS'] . '/' . $orderInfo['GUIGE'] . '/' . $orderInfo['MKHOUDU'] . '/' . $orderInfo['QMENBHD'];
                    break;
                }
            case '半品窗框品名规则':
                {
                    //产品类别/开向（需转换，无=窗框，左开=窗框左开，右开=窗框右开，双开=窗框双开）/表面方式/铰链/特殊要求/材质
                    $kaixiang = $orderInfo['KAIXIANG'];
                    if ($kaixiang == '无') {
                        $kaixiang = '窗框';
                    } elseif ($kaixiang == '左开') {
                        $kaixiang = '窗框左开';
                    } elseif ($kaixiang == '右开') {
                        $kaixiang == '窗框右开';
                    } elseif ($kaixiang == '双开') {
                        $kaixiang = '窗框双开';
                    }
                    $retstr = $orderInfo['DANG_CI'] . '/' . $kaixiang . '/' . $orderInfo['BIAOMCL'] . '/' . $jiaolian . '/' . $teshuyq . '/' . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '半品窗框规格规则':
                {
                    //样式/横档数量/左横档间距/中横档间距/右横档间距/立柱数量/上立柱间距/中立柱间距/下立柱间距/尺寸/窗框厚度
                    $retstr = $orderInfo['MENKUANG'] . '/' . $orderInfo['CHUANGHUA'] . '/' . $orderInfo['MAOYAN'] . '/' . $orderInfo['BIAO_PAI'] . '/' . $orderInfo['MSKAIKONG'] . '/' . $orderInfo['JIAOLIAN'] . '/' . $orderInfo['BIAOJIAN'] . '/' . $orderInfo['BAOZHPACK'] . '/' . $orderInfo['BAOZHUANGFS'] . '/' . $orderInfo['GUIGE'] . '/' . $orderInfo['MKHOUDU'];
                    break;
                }
            case '半品扇框品名规则':
                {
                    //产品类别/开向（需转换，无=扇框，左开=左开扇框，右开=右开扇框，双开=双开母扇框、双开子扇框）/表面方式/铰链/特殊要求/材质
                    $kaixiang = $orderInfo['KAIXIANG'];
                    if ($kaixiang == '无') {
                        $kaixiang = '扇框';
                    } elseif ($kaixiang == '左开') {
                        $kaixiang = '扇框左开';
                    } elseif ($kaixiang == '右开') {
                        $kaixiang == '扇框右开';
                    } elseif ($kaixiang == '双开') {
                        $kaixiang = '扇框双开';
                    }
                    $retstr = $orderInfo['DANG_CI'] . '/' . $kaixiang . '/' . $orderInfo['BIAOMCL'] . '/' . $jiaolian . '/' . $teshuyq . '/' . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '半品扇框规格规则':
                {
                    //样式/横档数量/左横档间距/中横档间距/右横档间距/立柱数量/上立柱间距/中立柱间距/下立柱间距/尺寸/扇框厚度
                    $retstr = $orderInfo['MENKUANG'] . '/' . $orderInfo['CHUANGHUA'] . '/' . $orderInfo['MAOYAN'] . '/' . $orderInfo['BIAO_PAI'] . '/' . $orderInfo['MSKAIKONG'] . '/' . $orderInfo['JIAOLIAN'] . '/' . $orderInfo['BIAOJIAN'] . '/' . $orderInfo['BAOZHPACK'] . '/' . $orderInfo['BAOZHUANGFS'] . '/' . $orderInfo['GUIGE'] . '/' . $orderInfo['QMENBHD'];
                    break;
                }

            case '窗框横框品名规则':
                {
                    //窗框/横框/材质
                    $retstr = "窗框/横框/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '窗框横框规格规则':
                {
                    //尺寸宽度/窗框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[1] . '/' . $orderInfo['MKHOUDU'];
                    break;
                }
            case '窗框竖框品名规则':
                {
                    //窗框/竖框/材质
                    $retstr = "窗框/竖框/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '窗框竖框规格规则':
                {
                    //尺寸高度/窗框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[0] . '/' . $orderInfo['MKHOUDU'];
                    break;
                }
            case '窗框横档品名规则':
                {
                    //窗框/横档/材质
                    $retstr = "窗框/横档/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '窗框横档规格规则':
                {
                    //尺寸宽度/窗框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[1] . '/' . $orderInfo['MKHOUDU'];
                    break;
                }
            case '窗框立柱品名规则':
                {
                    //窗框/立柱/材质
                    $retstr = "窗框/立柱/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '窗框立柱规格规则':
                {
                    //尺寸高度/窗框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[0] . '/' . $orderInfo['MKHOUDU'];
                    break;
                }
            case '扇框横框品名规则':
                {
                    //扇框/横框/材质
                    $retstr = "扇框/横框/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '扇框横框规格规则':
                {
                    //尺寸宽度/扇框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[1] . '/' . $orderInfo['QMENBHD'];
                    break;
                }
            case '扇框竖框品名规则':
                {
                    //扇框/竖框/材质
                    $retstr = "扇框/竖框/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '扇框竖框规格规则':
                {
                    //尺寸高度/扇框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[0] . '/' . $orderInfo['QMENBHD'];
                    break;
                }
            case '转换框横框品名规则':
                {
                    //转换框/横框/材质
                    $retstr = "转换框/横框/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '转换框横框规格规则':
                {
                    //尺寸宽度/窗框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[1] . '/' . $orderInfo['MKHOUDU'];
                    break;
                }
            case '转换框竖框品名规则':
                {
                    //转换框/竖框/材质
                    $retstr = "转换框/竖框/" . $orderInfo['MENSHANCZ'];
                    break;
                }
            case '转换框竖框规格规则':
                {
                    //尺寸高度/窗框厚度
                    $guige = $orderInfo['GUIGE'];
                    $guige_arr = explode('×', $guige);
                    if (empty($guige[1])) {
                        $guige_arr = explode('*', $guige);
                    }
                    $retstr = $guige_arr[0] . '/' . $orderInfo['MKHOUDU'];
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
    public function getNaihcYuanJianList($order)
    {
        $orderID = $order['oeb01'];
        $doorType = 'M4';
        $menKuang = $order['door_style'];
        $zhizaobm = $order['zhizaobm'];
        $order['teshuyq'] = empty($order['customer_name']) ? '' : $order['customer_name'];
        $order['menkuangyq'] = empty($order['menkuangyq']) ? '无' : $order['menkuangyq'];
        $sql = "select * from bom_yuanjian a where a.menkuang = '$menKuang' and a.door_type = '$doorType' and a.zhizaobm='$zhizaobm' order by id";
        $yuanjian_list = Db::query($sql, true);
        $data = array();
        foreach ($yuanjian_list as $key => $val) {
            $id = $val['id'];
            $columns = $val['order_column_name'];//该元件关联的订单属性

            //关联订单列不为空则动态拼接sql
            $where = "1=1";
            if (!empty($columns)) {
                //如果字段中包含逗号，则有多个属性
                $cols = explode(',', $columns);
                //根据关联的订单属性动态拼接sql
                foreach ($cols as $k => $v) {
                    $columnName = $v;
                    $orderValue = $order[strtolower($v)];
                    $height = $order['height'];
                    $width = $order['width'];
                    //高宽
                    if ($columnName == 'start_height') {
                        $where .= " and  $height>=start_height";
                    } elseif ($columnName == 'end_height') {
                        $where .= " and  $height<=end_height";
                    } elseif ($columnName == 'start_width') {
                        $where .= " and   $width>=start_width";
                    } elseif ($columnName == 'end_width') {
                        $where .= " and  $width<=end_width";
                    } //前后板厚除以100
                    elseif ($columnName == 'qmenbhd' || $columnName == 'hmenbhd') {
                        $orderValue = $orderValue / 100;
                        $where .= " and $columnName like '%,$orderValue,%'";
                    } elseif ($columnName == 'teshuyq') {//特殊要求的单独处理---客户名称
                        $convertedValue = $this->convertValue($orderValue, $zhizaobm);
                        $where .= " and $columnName like '%,$convertedValue,%'";
                    } else {
                        $where .= " and $columnName like '%,$orderValue,%'";
                    }
                }
            }

            $where = rtrim($where, 'and');
            /*$detail = "select  yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei
                       from bom_yuanjian_rule t1,bom_yuanjian t2
                       where yuanjian_id=$id and t1.yuanjian_id=t2.id and ($where or is_require=1)
                       group by yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei ";*/
            $detail = "select  yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei  
                       from bom_yuanjian_rule t1,bom_yuanjian t2  
                       where yuanjian_id=$id and t1.yuanjian_id=t2.id and ($where or is_require=1)";
            $liaohaoInfo = DB::query($detail, true);
            foreach ($liaohaoInfo as $k => $v) {
                //胶条用量
                if (strpos(excel_trim($v['yuanjian_name']), '胶条') !== false) {
                    if (strpos(excel_trim($v['yongliang']), '门扇高度') !== false) {
                        $v['yongliang'] = ($order['menshan_gaodu'] - deleteChinese($v['yongliang'])) / 1000;
                    } elseif (strpos(excel_trim($v['yongliang']), '门框宽度') !== false) {
                        $v['yongliang'] = ($order['width'] - deleteChinese($v['yongliang'])) / 1000;
                    } elseif (strpos(excel_trim($v['yongliang']), '门框高度') !== false) {
                        $v['yongliang'] = ($order['height'] - deleteChinese($v['yongliang'])) / 1000;
                    } elseif (strpos(excel_trim($v['yongliang']), '固定值') !== false) {
                        $v['yongliang'] = deleteChinese($v['yongliang']) / 1000;
                    }
                }
                //胶纱网用量
                if (strpos(excel_trim($v['yuanjian_name']), '胶纱网') !== false) {
                    $yongliang = explode('*', $v['yongliang']);
                    //$v['yongliang'] = $
                    $chuanghuaSquare = 0;//窗花面积
                    if (!empty($order['chuanghuayl']['chuanghua_height'])) {
                        $chuanghuaSquare = $order['chuanghuayl']['chuanghua_height'] * $order['chuanghuayl']['chuanghua_width'];
                    } elseif (!empty($order['chuanghuayl']['chuanghua_height_x'])) {
                        $chuanghuaSquare = $order['chuanghuayl']['chuanghua_height_x'] * $order['chuanghuayl']['chuanghua_width_x'] * $order['chuanghuayl']['chuanghua_yongliang_x']
                            + $order['chuanghuayl']['chuanghua_height_d'] * $order['chuanghuayl']['chuanghua_width_d'] * $order['chuanghuayl']['chuanghua_yongliang_d'];
                    }
                    $v['yongliang'] = ($yongliang[0] * $yongliang[1]) == 0 ? 0 : $chuanghuaSquare / ($yongliang[0] * $yongliang[1]) / 1000000;
                }
                array_push($data, $v);
            }
        }
        return $data;
    }
}