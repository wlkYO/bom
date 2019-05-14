<?php

use think\Db;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

//返回函数
function retmsg($retcode, $retdata = null, $retmessage = null)
{
    $retmsg = "";
    switch ($retcode) {
        case 0    :
            {
                $retmsg = "操作成功";
                break;
            }
        case -1    :
            {
                $retmsg = "操作失败";
                break;
            }
        case -2    :
            {
                $retmsg = "token验证失败";
                break;
            }
        default    :
            {
                $retmsg = "未知错误";
            }
    }
    //处理orale大写转成小写
    if (!empty($retdata)) {
        foreach ($retdata as $k => $v) {
            if (is_array($retdata[$k])) {
                $retdata[$k] = array_change_key_case($retdata[$k]);
            }
        }
    }
    return array("resultcode" => $retcode, "resultmsg" => empty($retmessage) ? $retmsg : $retmessage, "data" => $retdata);
}

//导入excel去空格
function excel_trim($content)
{
    if (is_object($content)) {
        $str = preg_replace("/(\s|\&nbsp\;||\xc2\xa0)/", "", $content->__toString());
    }
    $str = preg_replace("/(\s|\&nbsp\;||\xc2\xa0)/", "", $content);
    return $str;
}

//获取接口数据
function curl_get($url, $timeout = 10)
{
    //初始化
    $ch = curl_init();
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    return $output;
}

function findNum($str = '')
{
    $e = "/\d+/";
    preg_match_all($e, $str, $arr);
    return $arr[0][0];
}

//去除字符串中的中文
function deleteChinese($str)
{
    $e = '/([\x80-\xff]*)/i';
    $result = preg_replace($e, '', $str);
    return $result;
}

/**
 * 修改pdo缓存计算错误
 * 使用cast(class_name as VARCHAR2(60)) as class_name方式组合列名
 * @param $tableName
 * @param $columnStr 指定列名以逗号隔开
 * @param $multiple //缓存设置倍数,默认2
 * @return string
 */
function getColumnName($tableName, $columnStr = '', $multiple = 2)
{
    $tableName = strtoupper($tableName);
    if ($columnStr != '') {
        $columnStr = strtoupper($columnStr);
        $columnStrArray = explode(',', excel_trim($columnStr));
        $columnArray = array();
    }
    $columnName = '';//转化后的列
    $sql = "select * from user_tab_columns where Table_Name='$tableName' ";
    $changeDataType = ['VARCHAR2', 'CHAR'];//待转换的数据类型
    $data = DB::query($sql);
    foreach ($data as $k => $v) {
        $dataType = $v['DATA_TYPE'];
        $tempColumnName = $v['COLUMN_NAME'];
        if (empty($columnStrArray) || in_array($tempColumnName, $columnStrArray)) {
            if (in_array($dataType, $changeDataType)) {
//                $dataLength=$v['DATA_LENGTH']*$multiple;
                if (($tableName == 'oeb_file' || $tableName == 'OEB_FILE') && $tempColumnName == 'REMARK') {
                    $dataLength = $v['DATA_LENGTH'];
                } else {
                    $dataLength = $v['DATA_LENGTH'] * $multiple;
                }
                $temp = "cast($tempColumnName as $dataType ($dataLength)) as $tempColumnName";
                $columnName .= " $temp,";
            } else {
                $columnName .= $tempColumnName . ',';
                $temp = $tempColumnName;
            }
            $columnArray[$tempColumnName] = $temp;
        }
    }
    //列名排序按$columnStr的顺序排序$columnName
    if (!empty($columnStrArray)) {
        $coulumn = array();
        foreach ($columnStrArray as $k => $v) {
            $coulumn[] = $columnArray[$v];
        }
        $columnName = implode(',', $coulumn);
    }
    return empty($columnName) ? $columnStr : rtrim($columnName, ',');
}

//laravel dd函数
function dd(...$args)
{
    foreach ($args as $x) {
        $dumper = new HtmlDumper();
        $cloner = new VarCloner();
        $dumper->dump($cloner->cloneVar($x));
    }

    die(1);
}

//tp3 M()
function M()
{
    return DB::getInstance();
}

function two_array_merge(&$array1, $array2)
{
    foreach ($array2 as $k => $v) {
        array_push($array1, $v);
    }
}

function codeRule($ruleName = '成品', $doorType = 'M9')
{
    if ($ruleName == '成品') {
        if ($doorType == 'M7') {
            $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
                "4" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '产品种类'),
                "5" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇类型'),
                "6" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "7" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
                "9" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "10" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "12" => array("order_column_name" => 'KAIXIANG', "code_name" => '扣线'),
                "13" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "16" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
                "18" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
                "19" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
                "20" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
                "21" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
                "22" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
                "24" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
                "25" => array("order_column_name" => 'MENSHANYQ', "code_name" => '门扇要求'),
                "26" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "27" => array("order_column_name" => 'MENZHU', "code_name" => '门头门柱'),
                "28" => array("order_column_name" => 'REMARK', "code_name" => '特殊要求'),
                "29" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        } elseif ($doorType == 'M8') {
            $arr = array("3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇'),
                "5" => array("order_column_name" => 'BIAOMIANTSYQ', "code_name" => '表面方式'),
                "7" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
                "8" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
                "10" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
                "12" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
                "13" => array("order_column_name" => 'QMENBHD', "code_name" => '前门板厚度'),
                "14" => array("order_column_name" => 'HMENBHD', "code_name" => '后门板厚度'),
                "15" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "18" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
                "20" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "21" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "23" => array("order_column_name" => 'SUOXIN', "code_name" => '锁芯'),
                "24" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "25" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装'),
                "26" => array("order_column_name" => 'MAOYAN', "code_name" => '猫眼'),
                "27" => array("order_column_name" => 'BIAOJIAN', "code_name" => '标件'),
                "28" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "29" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器'),
                "30" => array("order_column_name" => 'SHUNXUQI', "code_name" => '顺序器'),
                "31" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
                "32" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
                "33" => array("order_column_name" => 'FIXED', "code_name" => '无意义')
            );
        } elseif ($doorType == 'M9') {
            $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
                "7" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
                "9" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
                "10" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
                "11" => array("order_column_name" => 'QMENBHD', "code_name" => '前门板厚度'),
                "12" => array("order_column_name" => 'HMENBHD', "code_name" => '后门板厚度'),
                "13" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "16" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
                "18" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "19" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "21" => array("order_column_name" => 'SUOXIN', "code_name" => '锁芯'),
                "22" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "23" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装'),
                "24" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框厚度'),
                "25" => array("order_column_name" => 'MAOYAN', "code_name" => '猫眼'),
                "26" => array("order_column_name" => 'BIAOJIAN', "code_name" => '标件'),
                "27" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "29" => array("order_column_name" => 'TIANDSCSHUO', "code_name" => '副锁'),
                "30" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
                "32" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
                "33" => array("order_column_name" => 'FIXED', "code_name" => '无意义')
            );
        } elseif ($doorType == 'M4') {
            $arr = array(
                "3" => array("order_column_name" => 'DANG_CI', "code_name" => '产品类别'),
                "4" => array("order_column_name" => 'MENKUANG', "code_name" => '样式'),
                "5" => array("order_column_name" => 'CHUANGHUA', "code_name" => '横档数量'),
                "6" => array("order_column_name" => 'MAOYAN', "code_name" => '左横档间距'),
                "8" => array("order_column_name" => 'BIAO_PAI', "code_name" => '中横档间距'),
                "10" => array("order_column_name" => 'MSKAIKONG', "code_name" => '右横档间距'),
                "12" => array("order_column_name" => 'JIAOLIAN', "code_name" => '立柱数量'),
                "13" => array("order_column_name" => 'BIAOJIAN', "code_name" => '上立柱间距'),
                "15" => array("order_column_name" => 'BAOZHPACK', "code_name" => '中立柱间距'),
                "17" => array("order_column_name" => 'BAOZHUANGFS', "code_name" => '下立柱间距'),
                "19" => array("order_column_name" => 'MKHOUDU', "code_name" => '窗框厚度'),
                "20" => array("order_column_name" => 'QMENBHD', "code_name" => '扇框厚度'),
                "21" => array("order_column_name" => 'GUIGE', "code_name" => '尺寸(高x宽)'),
                "24" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "25" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "26" => array("order_column_name" => 'SUOJU', "code_name" => '固定件'),
                "27" => array("order_column_name" => 'JIAOLIAN2', "code_name" => '铰链'),
                "28" => array("order_column_name" => 'TESHU_YQ', "code_name" => '特殊要求'),
                "29" => array("order_column_name" => 'MENSHANCZ', "code_name" => '材质'),
                "30" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        } elseif ($doorType == 'M8_1') {
            $arr = array(
                "3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇分类'),
                "5" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "6" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "7" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "9" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门框要求'),
                "10" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框材料'),
                "11" => array("order_column_name" => 'MKHOUDU', "code_name" => '框厚'),
                "12" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "15" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
                "17" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "18" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "20" => array("order_column_name" => 'SUOXIN', "code_name" => '锁芯'),
                "21" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "22" => array("order_column_name" => 'BAOZHUANGFS', "code_name" => '包装品牌/包装方式'),
                "23" => array("order_column_name" => 'MAOYAN', "code_name" => '猫眼'),
                "24" => array("order_column_name" => 'BIAOJIAN', "code_name" => '标件'),
                "25" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "26" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器&顺序器'),
                "27" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
                "28" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
                "30" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
                "32" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
                "33" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
                "34" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
                "35" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
                "37" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
                "38" => array("order_column_name" => 'REMARK', "code_name" => '特殊要求'),
                "39" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }

    }

    if ($ruleName == '半品窗框') {
        $arr = array(
            "3" => array("order_column_name" => 'DANG_CI', "code_name" => '产品类别'),
            "4" => array("order_column_name" => 'MENKUANG', "code_name" => '样式'),
            "5" => array("order_column_name" => 'CHUANGHUA', "code_name" => '横档数量'),
            "6" => array("order_column_name" => 'MAOYAN', "code_name" => '左横档间距'),
            "8" => array("order_column_name" => 'BIAO_PAI', "code_name" => '中横档间距'),
            "10" => array("order_column_name" => 'MSKAIKONG', "code_name" => '右横档间距'),
            "12" => array("order_column_name" => 'JIAOLIAN', "code_name" => '立柱数量'),
            "13" => array("order_column_name" => 'BIAOJIAN', "code_name" => '上立柱间距'),
            "15" => array("order_column_name" => 'BAOZHPACK', "code_name" => '中立柱间距'),
            "17" => array("order_column_name" => 'BAOZHUANGFS', "code_name" => '下立柱间距'),
            "19" => array("order_column_name" => 'MKHOUDU', "code_name" => '窗框厚度'),
            "20" => array("order_column_name" => 'GUIGE', "code_name" => '尺寸(高x宽)'),
            "23" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
            "24" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
            "25" => array("order_column_name" => 'JIAOLIAN2', "code_name" => '铰链'),
            "26" => array("order_column_name" => 'TESHU_YQ', "code_name" => '特殊要求'),
            "27" => array("order_column_name" => 'MENSHANCZ', "code_name" => '材质'),
            "28" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
        );
    }
    if ($ruleName == '半品扇框') {
        $arr = array(
            "3" => array("order_column_name" => 'DANG_CI', "code_name" => '产品类别'),
            "4" => array("order_column_name" => 'MENKUANG', "code_name" => '样式'),
            "5" => array("order_column_name" => 'CHUANGHUA', "code_name" => '横档数量'),
            "6" => array("order_column_name" => 'MAOYAN', "code_name" => '左横档间距'),
            "8" => array("order_column_name" => 'BIAO_PAI', "code_name" => '中横档间距'),
            "10" => array("order_column_name" => 'MSKAIKONG', "code_name" => '右横档间距'),
            "12" => array("order_column_name" => 'JIAOLIAN', "code_name" => '立柱数量'),
            "13" => array("order_column_name" => 'BIAOJIAN', "code_name" => '上立柱间距'),
            "15" => array("order_column_name" => 'BAOZHPACK', "code_name" => '中立柱间距'),
            "17" => array("order_column_name" => 'BAOZHUANGFS', "code_name" => '下立柱间距'),
            "19" => array("order_column_name" => 'QMENBHD', "code_name" => '扇框厚度'),
            "20" => array("order_column_name" => 'GUIGE', "code_name" => '尺寸(高x宽)'),
            "23" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
            "24" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
            "25" => array("order_column_name" => 'JIAOLIAN2', "code_name" => '铰链'),
            "26" => array("order_column_name" => 'TESHU_YQ', "code_name" => '特殊要求'),
            "27" => array("order_column_name" => 'MENSHANCZ', "code_name" => '材质'),
            "28" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
        );
    }
    if ($ruleName == '耐火窗单框') {
        $arr = array(
            "3" => array("order_column_name" => 'SUOJU', "code_name" => '固定'),
            "4" => array("order_column_name" => 'DANKUANG_TYPE', "code_name" => '单框类型'),
            "6" => array("order_column_name" => 'MENSHANCZ', "code_name" => '材质'),
            "7" => array("order_column_name" => 'FIXED', "code_name" => '无意义'),
            "10" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
            "14" => array("order_column_name" => 'KUANGHOU', "code_name" => '框厚(窗框/扇框)'),
            "18" => array("order_column_name" => 'BEIYONG', "code_name" => '备用')
        );
    }
    if ($ruleName == '防火门成品') {
        $arr = array(
            "3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
            "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇'),
            "5" => array("order_column_name" => 'BIAOMIANTSYQ', "code_name" => '表面方式'),
            "7" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
            "8" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
            "10" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
            "12" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
            "13" => array("order_column_name" => 'QMENBHD', "code_name" => '前门板厚度'),
            "14" => array("order_column_name" => 'HMENBHD', "code_name" => '后门板厚度'),
            "15" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
            "18" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
            "20" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
            "21" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
            "23" => array("order_column_name" => 'SUOXIN', "code_name" => '锁芯'),
            "24" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
            "25" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装'),
            "26" => array("order_column_name" => 'MAOYAN', "code_name" => '猫眼'),
            "27" => array("order_column_name" => 'BIAOJIAN', "code_name" => '标件'),
            "28" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
            "29" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器'),
            "30" => array("order_column_name" => 'SHUNXUQI', "code_name" => '顺序器'),
            "31" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
            "32" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
            "33" => array("order_column_name" => 'FIXED', "code_name" => '无意义')
        );
    }
    if ($ruleName == '半品门扇') {
        if ($doorType == 'M8_1') {
            $arr = array(
                "3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇分类'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "8" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "9" => array("order_column_name" => 'FIXED', "code_name" => '无意义'),
                "12" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框+门框要求'),
                "13" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '底框材质'),
                "14" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "17" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
                "19" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "20" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "22" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
                "23" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "24" => array("order_column_name" => 'REMARK', "code_name" => '特殊要求'),
                "25" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器'),
                "26" => array("order_column_name" => 'SHUNXUQI', "code_name" => '顺序器'),
                "27" => array("order_column_name" => 'BEIYONG', "code_name" => '备用')
            );
        } else {
            $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '半成品类型'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
                "7" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
                "9" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
                "10" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "13" => array("order_column_name" => 'QMENBHD', "code_name" => '前门板厚度'),
                "14" => array("order_column_name" => 'HMENBHD', "code_name" => '后门板厚度'),
                "15" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
                "17" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "18" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "20" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
                "22" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "23" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
                "24" => array("order_column_name" => 'TIANDSCSHUO', "code_name" => '副锁'),
                "25" => array("order_column_name" => 'MS_FIXED', "code_name" => '无意义')
            );
        }
    }
    if ($ruleName == '半品门框') {
        if ($doorType == 'M8_1') {
            $arr = array(
                "3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇分类'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'FIXED', "code_name" => '无意义'),
                "10" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框+门框要求'),
                "11" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '底框材质'),
                "12" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "15" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
                "16" => array("order_column_name" => 'FIXED1', "code_name" => '无意义'),
                "18" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "19" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "21" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "22" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "23" => array("order_column_name" => 'REMARK', "code_name" => '特殊要求'),
                "24" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器'),
                "25" => array("order_column_name" => 'SHUNXUQI', "code_name" => '顺序器'),
                "26" => array("order_column_name" => 'FIXED', "code_name" => '无意义')
            );
        } else {
            $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
                "4" => array("order_column_name" => 'MENSHAN', "code_name" => '半成品类型'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
                "7" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
                "9" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
                "10" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "13" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
                "14" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框厚度'),
                "15" => array("order_column_name" => 'MK_FIXED1', "code_name" => '无意义'),
                "17" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "18" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
                "20" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "21" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
                "22" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
                "23" => array("order_column_name" => 'TIANDSCSHUO', "code_name" => '副锁'),
                "24" => array("order_column_name" => 'MK_FIXED2', "code_name" => '无意义')
            );
        }
    }

    if ($ruleName == '防火门半品门扇') {
        $arr = array("3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
            "4" => array("order_column_name" => 'MENSHAN', "code_name" => '防火门半成品类型'),
            "5" => array("order_column_name" => 'BIAOMIANTSYQ', "code_name" => '表面要求'),
            "7" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
            "8" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
            "10" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
            "11" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
            "14" => array("order_column_name" => 'QMENBHD', "code_name" => '前门板厚度'),
            "15" => array("order_column_name" => 'HMENBHD', "code_name" => '后门板厚度'),
            "16" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
            "18" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
            "19" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
            "21" => array("order_column_name" => 'MSKAIKONG', "code_name" => '门扇开孔'),
            "22" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
            "23" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
            "24" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器'),
            "25" => array("order_column_name" => 'SHUNXUQI', "code_name" => '顺序器'),
            "26" => array("order_column_name" => 'MS_FIXED', "code_name" => '无意义')
        );
    }
    if ($ruleName == '防火门半品门框') {
        $arr = array(
            "3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
            "4" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇'),
            "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
            "6" => array("order_column_name" => 'FIXED', "code_name" => '无意义'),
            "7" => array("order_column_name" => 'MENKUANG', "code_name" => '门扇类型'),
            "8" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框形式'),
            "10" => array("order_column_name" => 'DKCAILIAO', "code_name" => '底框类型'),
            "12" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
            "15" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
            "16" => array("order_column_name" => 'FIXED', "code_name" => '无意义'),
            "18" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
            "19" => array("order_column_name" => 'SUOJU', "code_name" => '主锁'),
            "21" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
            "22" => array("order_column_name" => 'JIAOLIAN', "code_name" => '铰链'),
            "23" => array("order_column_name" => 'TESHUYQ,MENSHANCZ', "code_name" => '门类'),
            "24" => array("order_column_name" => 'BIMENQI', "code_name" => '闭门器'),
            "25" => array("order_column_name" => 'SHUNXUQI', "code_name" => '顺序器'),
            "26" => array("order_column_name" => 'FIXED', "code_name" => '无意义')
        );
    }

    if ($ruleName == '单框') {
        if($doorType == 'M8_1'){
            $arr = array(
                "3" => array("order_column_name" => 'BANCP', "code_name" => '半成品'),
                "4" => array("order_column_name" => 'DKTYPE', "code_name" => '单框类型'),
                "5" => array("order_column_name" => 'FIXED', "code_name" => '无意义'),
                "6" => array("order_column_name" => 'MENKUANG,DKCAILIAO', "code_name" => '门框+底框材料'),
                "7" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框+门框要求'),
                "8" => array("order_column_name" => 'FIXED1', "code_name" => '无意义'),
                "10" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "11" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "15" => array("order_column_name" => 'MKHOUDU', "code_name" => '框厚'),
                "19" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }else {
            $arr = array("3" => array("order_column_name" => 'BANCP', "code_name" => '半成品'),
                "4" => array("order_column_name" => 'DKTYPE', "code_name" => '单框类型'),
                "5" => array("order_column_name" => 'MENKUANG', "code_name" => '门框型号'),
                "6" => array("order_column_name" => 'MENKUANGCZ,DKCAILIAO', "code_name" => '单框材质'),
                "7" => array("order_column_name" => 'MENKUANG', "code_name" => '门框'),
                "9" => array("order_column_name" => 'DK_FIXED1', "code_name" => '无意义'),
                "10" => array("order_column_name" => 'KAIXIANG', "code_name" => '开向'),
                "11" => array("order_column_name" => 'MKLENGTH', "code_name" => '门框长度'),
                "15" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度'),
                "19" => array("order_column_name" => 'DK_FIXED2', "code_name" => '无意义')
            );
        }
    }

    if ($ruleName == '门板') {
        $arr = array("3" => array("order_column_name" => 'BANCP', "code_name" => '半成品'),
            "4" => array("order_column_name" => 'MENSHANCZ', "code_name" => '门板材质'),
            "5" => array("order_column_name" => 'QMENBHD', "code_name" => '前门板'),
            "6" => array("order_column_name" => 'HMENBHD', "code_name" => '后门板'),
            "8" => array("order_column_name" => 'WIDTH', "code_name" => '门板宽度'),
            "12" => array("order_column_name" => 'HEIGHT', "code_name" => '门板长度'),
            "16" => array("order_column_name" => 'MENBAN_DIFF', "code_name" => '门板区分')
        );
    }

    if ($ruleName == '窗花') {
        $arr = array("6" => array("order_column_name" => 'CHUANGHUA', "code_name" => '半成品状态'),
            "7" => array("order_column_name" => 'DKCAILIAO', "code_name" => '窗花材质'),
            "8" => array("order_column_name" => 'CHUANGHUA', "code_name" => '成品外形'),
            "10" => array("order_column_name" => 'HEIGHT', "code_name" => '高度'),
            "13" => array("order_column_name" => 'WIDTH', "code_name" => '宽度')
        );
    }

    if ($ruleName == '别墅门') {
        $arr = array("6" => array("order_column_name" => 'door_type', "code_name" => '门类型'),
            "8" => array("order_column_name" => 'remark', "code_name" => '特殊要求'),
            "12" => array("order_column_name" => 'HEIGHT', "code_name" => '高度'),
            "16" => array("order_column_name" => 'WIDTH', "code_name" => '宽度')
        );
    }

    if ($ruleName == '材质') {
        $arr = array("4" => array("order_column_name" => 'MENSHANCZ,MENKUANGCZ,DKCAILIAO', "code_name" => '材质'),
            "8" => array("order_column_name" => 'CAIZHIYQ', "code_name" => '材质要求'),
            "12" => array("order_column_name" => 'TESHUYQ', "code_name" => '特殊要求'),
            "15" => array("order_column_name" => 'MKHOUDU', "code_name" => '材质厚度'),
            "18" => array("order_column_name" => 'WIDTH', "code_name" => '材质宽度')
        );
    }

    if ($ruleName == '外购门框') {
        $arr = array("6" => array("order_column_name" => 'MENKUANG', "code_name" => '门框型号'),
            "8" => array("order_column_name" => 'MENKUANG', "code_name" => '门框'),
            "9" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '单框材质'),
            "10" => array("order_column_name" => 'DKTYPE', "code_name" => '外购单框类型'),
            "11" => array("order_column_name" => 'KAIXIANG', "code_name" => '外购门框开向'),
            "12" => array("order_column_name" => 'MKLENGTH', "code_name" => '门框长度'),
            "16" => array("order_column_name" => 'MKHOUDU', "code_name" => '门框厚度')
        );
    }
    if ($ruleName == '门套') {
        if($doorType == 'M8_1'){
            $arr = array(
                "3" => array("order_column_name" => 'FHJIBIE', "code_name" => '防火级别'),
                "4" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框+门框要求'),
                "5" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇分类'),
                "6" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "7" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "8" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "10" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "13" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
                "15" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
                "17" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
                "18" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
                "19" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
                "20" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
                "22" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
                "23" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "24" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装品牌&方式'),
                "25" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }else {
            $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
                "4" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '产品种类'),
                "5" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇类型'),
                "6" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "7" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "8" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "10" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "13" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
                "15" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
                "16" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
                "17" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
                "18" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
                "19" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
                "21" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
                "22" => array("order_column_name" => 'CHUANGHUA', "code_name" => '窗花'),
                "23" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装品牌方式'),
                "25" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }
    }

    if ($ruleName == '门扇') {
        $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
            "4" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '产品种类'),
            "5" => array("order_column_name" => 'MENSHAN', "code_name" => '门扇类型'),
            "6" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
            "7" => array("order_column_name" => 'HUASE', "code_name" => '花色'),
            "9" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
            "10" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
            "12" => array("order_column_name" => 'KAIXIANG', "code_name" => '扣线'),
            "13" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
            "16" => array("order_column_name" => 'MENSHANYQ', "code_name" => '门扇要求'),
            "17" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装品牌方式'),
            "19" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
        );
    }

    if ($ruleName == '单套板') {
        if($doorType == 'M8_1'){
            $arr = array(
                "3" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框+门框要求'),
                "4" => array("order_column_name" => 'DANBAN_TYPE', "code_name" => '单板类型'),
                "5" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "6" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "7" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "9" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "13" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
                "15" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
                "17" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
                "18" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
                "19" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }else {
            $arr = array("3" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '产品种类'),
                "4" => array("order_column_name" => 'DANBAN_TYPE', "code_name" => '单板类型'),
                "5" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "6" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "7" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "9" => array("order_column_name" => 'KAIXIANG', "code_name" => '扣线'),
                "10" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "14" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
                "16" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
                "17" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
                "18" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
                "19" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }
    }

    if ($ruleName == '窗套') {
        $arr = array("3" => array("order_column_name" => 'DANG_CI', "code_name" => '档次'),
            "4" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '产品种类'),
            "5" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
            "6" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
            "7" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
            "9" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
            "12" => array("order_column_name" => 'MKHOUDU', "code_name" => '门套墙体'),
            "14" => array("order_column_name" => 'MAOYAN', "code_name" => '门套厚度'),
            "15" => array("order_column_name" => 'MENKUANGYQ', "code_name" => '门套结构'),
            "16" => array("order_column_name" => 'MENKUANG', "code_name" => '门套样式'),
            "17" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
            "18" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
            "20" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
            "21" => array("order_column_name" => 'BAOZHPACK,BAOZHUANGFS', "code_name" => '包装品牌方式'),
            "23" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
        );
    }

    if ($ruleName == '线条') {
        if($doorType == 'M8_1'){
            $arr = array(
                "3" => array("order_column_name" => 'MENKUANG,MENKUANGYQ', "code_name" => '门框+门框要求'),
                "4" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '线条类型'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "8" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "12" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
                "13" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
                "15" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
                "16" => array("order_column_name" => 'MENKUANGCZ', "code_name" => '门类结构'),
                "17" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }else {
            $arr = array(
                "3" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '产品种类'),
                "4" => array("order_column_name" => 'DOOR_STYLE', "code_name" => '线条类型'),
                "5" => array("order_column_name" => 'BIAOMCL', "code_name" => '表面方式'),
                "6" => array("order_column_name" => 'BIAO_PAI', "code_name" => '表面花纹'),
                "8" => array("order_column_name" => 'GUIGE', "code_name" => '规格'),
                "12" => array("order_column_name" => 'JIAOLIAN', "code_name" => '线条种类'),
                "13" => array("order_column_name" => 'DKCAILIAO', "code_name" => '线条样式'),
                "15" => array("order_column_name" => 'BIAOJIAN', "code_name" => '线条结构'),
                "16" => array("order_column_name" => 'BEIYONG', "code_name" => '备用'),
            );
        }
    }

    return $arr;
}

function getDoorDesc($doorType = 'M9')
{
    $arr = array('M8' => '防火门', 'M9' => '钢质门', 'M6' => '装甲门', 'M7' => '木质门', 'W9' => '套装门', 'M4' => '耐火窗', 'M8_1' => '室内防火门');
    return $arr[$doorType];
}

/**
 * bom导出标题栏根据订单类别显示
 */
function getBomExcelTitle($title)
{
    if ($title['order_db'] == 'M7') {
        $titleArr = array(
            '订单信息', '订单号', '项次', '前板厚度', '后板厚度', '订单类别', '产品种类', '档次', '门扇类型',
            '门类结构', '花色', '表面方式', '表面花纹', '表面要求', '扣线', '门扇要求', '规格', '窗花', '门套墙体',
            '门套厚度', '门套结构', '门套样式', '线条种类', '线条样式', '线条结构', '包装品牌', '包装方式', '门头/门柱',
            '其他特殊要求', '体积', '数量'
        );
        //订单数据相关信息
        $titleData = array(
            $title['oeb01'], $title['oeb03'], $title['qmenbhd'], $title['hmenbhd'], $title['order_type'], $title['door_style'],
            $title['dang_ci'], $title['menshan'], $title['menshancz'], $title['huase'], $title['biaomcl'], $title['biao_pai'],
            $title['biaomiantsyq'], $title['kaixiang'], $title['menshanyq'], $title['guige'], $title['chuanghua'],
            $title['mkhoudu'], $title['maoyan'], $title['menkuangyq'], $title['menkuang'], $title['jiaolian'],
            $title['dkcailiao'], $title['biaojian'], $title['baozhpack'], $title['baozhuangfs'],
            $title['suoju'], $title['remark'], $title['unitcube'], $title['oeb12']
        );
    } else {
        $titleArr = array(
            '订单信息', '订单号', '项次', '前板厚', '后板厚', '门框材质', '门扇材质',
            '档次', '门框', '框厚', '底框材料', '门扇', '规格', '开向', '铰链', '花色', '表面方式', '表面要求', '窗花', '猫眼', '标牌',
            '主锁', '锁芯', '副锁', '锁把信息', '标件', '包装品牌', '包装方式', '其他特殊要求', '体积', '数量', '订单类别'
        );
        //订单数据相关信息
        $titleData = array(
            $title['oeb01'], $title['oeb03'], $title['qmenbhd'], $title['hmenbhd'], $title['menkuangcz'], $title['menshancz'],
            $title['dang_ci'], $title['menkuang'], $title['mkhoudu'], $title['dkcailiao'], $title['menshan'], $title['guige'],
            $title['kaixiang'], $title['jiaolian'], $title['huase'], $title['biaomcl'], $title['biaomiantsyq'], $title['chuanghua'],
            $title['maoyan'], $title['biao_pai'], $title['suoju'], $title['suoxin'], $title['tiandscshuo'], $title['suoba_info'], $title['biaojian'],
            $title['baozhpack'], $title['baozhuangfs'], $title['remark'], $title['unitcube'], $title['oeb12'], $title['order_type']
        );
    }
    return array('title' => $titleArr, 'body' => $titleData);
}

?>