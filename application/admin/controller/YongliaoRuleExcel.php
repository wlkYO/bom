<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/28
 * Time: 9:10
 */

namespace app\admin\controller;

use app\admin\logic\MaterialRuleLogic;
use think\Db;
use think\controller\Rest;
use Think\Model;

class YongliaoRuleExcel extends Rest
{
    //导入用料规则表
    public function importExcel($sheet = '门板宽度用料规则')
    {
        if ($sheet == '防火门钢框木扇封边条用料规则') {
            $model = new YongliaoRuleToExcel();
            $res = $model->importExcel();
            return $res;
        }
        if ($sheet == '木门封边条用料规则' || $sheet == '木门门扇面板用料规则' || $sheet == '木门门扇木方用料规则' || $sheet == '木门门扇芯料用料规则' || $sheet == '木门线条用料规则') {
            if ($sheet == '木门封边条用料规则') {
                $type = 1;
            } elseif ($sheet == '木门门扇面板用料规则') {
                $type = 2;
            } elseif ($sheet == '木门门扇木方用料规则') {
                $type = 3;
            } elseif ($sheet == '木门门扇芯料用料规则') {
                $type = 4;
            } elseif ($sheet == '木门线条用料规则') {
                $type = 5;
            }
            $model = new MenShanRuleToExcel();
            $res = $model->importExcel($type);
            return $res;
        }
        if ($sheet == '齐河封闭式窗花用料规则' || $sheet == '成都封闭式窗花用料规则') {
            $type = $sheet == '成都封闭式窗花用料规则' ? 1 : 2;
            $chuanghua = new Chuanghua();
            $res = $chuanghua->importFbsChuangHua($type);
            return $res;
        }
        if($sheet == '窗花玻璃用料规则'){
            $glass_rule = new GlassRule();
            $res = $glass_rule->importExcel();
            return $res;
        }
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        ini_set('upload_max_filesize', "5M");// 上传文件的大小为5M
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        $fileName = mt_rand();
        $extension = substr(strrchr($_FILES["file"]["name"], '.'), 1);
        if ($extension != 'xlsx' && $extension != 'xls') {
            return $this->response(retmsg(-1, null, '请上传Excel文件！'), 'json');
        }
        $tempPath = str_replace('\\', '/', realpath(__DIR__ . '/../../../')) . '/upload/' . $fileName . "." . $extension;
        $flag = move_uploaded_file($_FILES["file"]["tmp_name"], $tempPath);
        if ($flag) {
            if (in_array($sheet, ['木门门套用料规则(复合实木)', '木门门套用料规则(集成实木)', '木门门套用料规则(强化木门)', '木门门套用料规则(转印木门)'])) {
                $materialRuleLogic = new MaterialRuleLogic($sheet);
                $retNum = $materialRuleLogic->importExcel($extension, $tempPath, $sheet, $tempPath);
                return $this->response(array('resultcode' => 0, 'resultmsg' => "导入成功, 共计 $retNum 行!"), 'json');
            }
            try {
                // 载入excel文件
                vendor("PHPExcel.Classes.PHPExcel");
                $objPHPExcel = \PHPExcel_IOFactory::load($tempPath);
            } catch (\PHPExcel_Reader_Exception $e) {
                return $this->response(retmsg(-1, null, $e->__toString()), 'json');
            }
//            unlink($tempPath);//删除临时文件
            $sheets = $objPHPExcel->getAllSheets();
            if (empty($sheets)) {
                return $this->response(retmsg(-1, null, "无法读取此Excel!"), 'json');
            }
            $title = $objPHPExcel->getSheetNames(0);
            if (in_array($sheet, ['常规窗花用料规则', '不锈钢窗花用料规则', '中式窗花用料规则'])) {
                //读取$tempPath文件的数据并导入数据
                $chuanghua = new Chuanghua();
                $chuanghuaValue = substr($sheet, 0, -12);
                $hangs = $chuanghua->importExcel($chuanghuaValue, $tempPath, $title, $objPHPExcel);
                return $this->response(array('resultcode' => 0, 'resultmsg' => "导入成功, 共计 $hangs 行!"), 'json');
            } else {
                if ($title[0] != $sheet) {
                    return $this->response(retmsg(-1, null, "用料导入表对应不正确!"), 'json');
                }
                $this->getImportExcelList($objPHPExcel, $title[0]);
            }
            unlink($tempPath);//删除临时文件
            return $this->response(array('resultcode' => 0, 'resultmsg' => '导入成功!'), 'json');
        }
    }

    public function getImportExcelList($objPHPExcel, $title)
    {
        $sheet = $objPHPExcel->getSheet(0); // 读取第一工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数字符串
        $highestColumm = \PHPExcel_Cell::columnIndexFromString($highestColumm);
        $index = array("A", "B", "C", "D", "E", "F", "G", "H", "I",
            "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB");
        for ($i = 2; $i <= $highestRow; $i++) {
            $arr = array();
            for ($j = 1; $j <= $highestColumm; $j++) {
                $arr[] = $objPHPExcel->getActiveSheet(0)->getCell($index[$j - 1] . $i)->getValue();
            }
            $data[] = $arr;
        }
        switch ($title) {
            case '门板长度用料规则':
                {
                    $this->getMenbanLengthSql($data);
                    break;
                }
            case '门板宽度用料规则':
                {
                    $this->getMenbanWideSql($data);
                    break;
                }
            case '门框铰方、锁方用料规则':
                {
                    $this->getMenkuangJiaoSuoSql($data);
                    break;
                }
            case '门框上门框、中门框用料规则':
                {
                    $this->getMenkuangShangZhongSql($data);
                    break;
                }
            case '下门框用料规则':
                {
                    $this->getMenkuangXiaSql($data);
                    break;
                }
            case '窗花计算规则':
                {
                    $this->getChuanghuaSql($data);
                    break;
                }
            case '四开窗花计算规则':
                {
                    $this->getSikaiChuanghuaSql($data);
                    break;
                }
            case '钢框木扇用料规则':
                {
                    $this->getgangkuangMushanSql($data);
                    break;
                }
            case '齐河基地钢框木扇用料规则':
                {
                    $this->getgangkuangMushanQHSql($data);
                    break;
                }
            case '防火门填芯用料规则':
                {
                    $this->gettianxinMenshanSql($data);
                    break;
                }
            case '齐河基地防火门填芯用料规则':
                {
                    $this->gettianxinMenshanQHSql($data);
                    break;
                }
            case '防火门钢框木扇免漆板用料规则':
                {
                    $this->getMianqiSql($data);
                    break;
                }
            case '防火门钢框木扇封边条用料规则':
                {
                    $this->getFengbianSql($data);
                    break;
                }
            case '中式窗花带玻胶条用料规则':
                {
                    $this->getChuanghuaZhongshiSql($data);
                    break;
                }
            case 'pe包装用料规则':
                {
                    $this->getPeBaozhuangSql($data);
                    break;
                }
            case '成都基地防火门钢框木扇长边骨架用料规则':
                {
                    $this->getGangkuangChangbgujiaYongliaoSql($data);
                    break;
                }
            case '成都基地防火门钢框木扇长边骨架品名规则':
                {
                    $this->getGangkuangChangbgujiaPinmingSql($data);
                    break;
                }
        }
    }

    public function doSql($str, $biao)
    {
        if (!empty($str)) {
            Db::execute("delete from $biao ");
            $str = "insert all $str SELECT * FROM dual";
            Db::execute($str);
        }
        return true;
    }

    public function getMenbanLengthSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENBAN_LENGTH_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $dangci = $val[1];
                $menkuang = $val[2];
                $menshan = $val[3];
                $dikuangcl = $val[4];
                $guigecd = $val[5];
                $muqianmb = $val[6];
                $muhoumb = $val[7];
                $ziqianmb = $val[8];
                $zihoumb = $val[9];
                $muziqmb = $val[10];
                $muzihoumb = $val[11];
                $ziziqmb = $val[12];
                $zizihoumb = $val[13];
                $mkhoudu = $val[14];
                $kaixiang = $val[15];
                $id = $key + 1;
                $str = " insert into BOM_MENBAN_LENGTH_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,DIKUANGCL,GUIGECD,MUQIANMB,MUHOUMB,ZIQIANMB,
                            ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB,MKHOUDU,KAIXIANG)VALUES(
                    $id,'/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','/$dikuangcl/','$guigecd','$muqianmb','$muhoumb','$ziqianmb',
                    '$zihoumb','$muziqmb','$muzihoumb','$ziziqmb','$zizihoumb','/$mkhoudu/','/$kaixiang/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getMenbanWideSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENBAN_WIDE_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $dangci = $val[1];
                $menkuang = $val[2];
                $menshan = $val[3];

                $qianmb = $val[4];
                $houmb = $val[5];

                $guigecd = $val[6];
                $muqianmb = $val[7];
                $muhoumb = $val[8];
                $ziqianmb = $val[9];
                $zihoumb = $val[10];
                $muziqmb = $val[11];
                $muzihoumb = $val[12];
                $ziziqmb = $val[13];
                $zizihoumb = $val[14];
                $menshancz = $val[15];
                $mkhoudu = $val[16];
                $kaixiang = $val[17];
                $id = $key + 1;
                $str = " insert into BOM_MENBAN_WIDE_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,QIANMB,HOUMB,GUIGECD,MUQIANMB,
                        MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB,MENSHANCZ,MKHOUDU,KAIXIANG)VALUES(
                $id,'/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','$qianmb','$houmb','$guigecd','$muqianmb',
                '$muhoumb','$ziqianmb','$zihoumb','$muziqmb','$muzihoumb','$ziziqmb','$zizihoumb','$menshancz','/$mkhoudu/','/$kaixiang/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getMenkuangJiaoSuoSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENKUANG_JIAOSUO_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $dangci = $val[1];
                $menkuang = $val[2];
                $kaixiang = $val[3];
                $menkuanghd = $val[4];
                $guigecd = $val[5];
                $jiaomk_length = trim($val[6], '规格长度');
                $jiaomk = str_replace('铁卷', '', $val[7]);
                $jiaomk = str_replace('锌铁合金51D', '', $jiaomk);
                $jiaomk = str_replace('镀锌卷51D', '', $jiaomk);
                $jiaomk_hd = $val[8];
                $jiaomk_density = trim($val[9], '用料长*宽*用量厚度*');
                $suomk_length = trim($val[10], '规格长度');
                $suomk = str_replace('铁卷', '', $val[11]);
                $suomk = str_replace('锌铁合金51D', '', $suomk);
                $suomk = str_replace('镀锌卷51D', '', $suomk);
                $suomk_hd = $val[12];
                $suomk_density = trim($val[13], '用料长*宽*用量厚度*');
                $menkuangcz = $val[14];
                $menshan = $val[15];
                $id = $key + 1;
                $str = " insert into BOM_MENKUANG_JIAOSUO_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,KAIXIANG,MENKUANGHD,GUIGECD,JIAOMK_LENGTH,
                        JIAOMK,JIAOMK_HD,JIAOMK_DENSITY,SUOMK_LENGTH,SUOMK,SUOMK_HD,SUOMK_DENSITY,MENKUANGCZ,MENSHAN)VALUES(
                $id,'/$zhizaobm/','/$dangci/','/$menkuang/','/$kaixiang/','$menkuanghd','$guigecd','$jiaomk_length','$jiaomk','$jiaomk_hd',
                '$jiaomk_density','$suomk_length','$suomk','$suomk_hd','$suomk_density','/$menkuangcz/','/$menshan/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getMenkuangShangZhongSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENKUANG_SHANGZHONG_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $dangci = $val[1];
                $menkuang = $val[2];
                $menshan = $val[3];
                $kaixiang = $val[4];
                $menkuanghd = $val[5];
                $caizhi = '';
                $guigecd = $val[6];
                $jiaomk_length = trim($val[7], '规格宽度');
                $jiaomk = str_replace('铁卷', '', $val[8]);
                $jiaomk = str_replace('锌铁合金51D', '', $jiaomk);
                $jiaomk = str_replace('镀锌卷51D', '', $jiaomk);
                $jiaomk_hd = $val[9];
                $jiaomk_density = trim($val[10], '用料长*宽*用量厚度*');
                $suomk_length = trim($val[11], '规格宽度');
                $suomk = str_replace('铁卷', '', $val[12]);
                $suomk = str_replace('锌铁合金51D', '', $suomk);
                $suomk = str_replace('镀锌卷51D', '', $suomk);
                $suomk_hd = $val[13];
                $suomk_density = trim($val[14], '用料长*宽*用量厚度*');
                $menkuangcz = $val[15];
                $id = $key + 1;
                $str = " insert into BOM_MENKUANG_SHANGZHONG_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,KAIXIANG,MENKUANGHD,CAIZHI,GUIGECD,
                        SHANGMK_LENGTH,SHANGMK,SHANGMK_HD,SHANGMK_DENSITY,ZHONGMK_LENGTH,ZHONGMK,ZHONGMK_HD,ZHONGMK_DENSITY,MENKUANGCZ)VALUES(
                $id,'/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','/$kaixiang/','$menkuanghd','$caizhi','$guigecd','$jiaomk_length','$jiaomk','$jiaomk_hd',
                '$jiaomk_density','$suomk_length','$suomk','$suomk_hd','$suomk_density','/$menkuangcz/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getMenkuangXiaSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENKUANG_XIA_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $dangci = $val[1];
                $menkuang = $val[2];
                $menshan = $val[3];
                $kaixiang = $val[4];
                $menkuanghd = $val[5];
                $dikuangcl = $val[6];
                $guigecd = $val[7];
                $xiamk_length = trim($val[8], '规格宽度');
                $xiamk = str_replace('铁卷', '', $val[9]);
                $xiamk = str_replace('锌铁合金51D', '', $xiamk);
                $xiamk = str_replace('镀锌卷51D', '', $xiamk);
                $xiamk_hd = $val[10];
                $xiamk_density = trim($val[11], '用料长*宽*用量厚度*');
                $menkuangcz = $val[12];
                $id = $key + 1;
                $str = " insert into BOM_MENKUANG_XIA_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,KAIXIANG,MENKUANGHD,DIKUANGCL,GUIGECD,
                        XIAMK_LENGTH,XIAMK,XIAMK_HD,XIAMK_DENSITY,MENKUANGCZ)VALUES(
                $id,'/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','/$kaixiang/','$menkuanghd','/$dikuangcl/','$guigecd','$xiamk_length','$xiamk','$xiamk_hd',
                '$xiamk_density','/$menkuangcz/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getChuanghuaSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_CHUANGHUA_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $menkuang = $val[1];
                $dangci = $val[2];
                $chuanghua = $val[3];
                $kaixiang = $val[4];
                $menshan = $val[5];
                $mkhoudu = $val[6];
                $start_height = $val[7];
                $end_height = $val[8];
                $mkheight_rule = trim($val[9], '门框高度');
                $mkwide_rule = trim($val[10], '门框宽度');
                $usage = $val[11];
                $id = $key + 1;
                $str = "insert into BOM_CHUANGHUA_RULE(ID,ZHIZAOBM,MENKUANG,DANGCI,CHUANGHUA,KAIXIANG,MENSHAN,MKHOUDU,START_HEIGHT,
                        END_HEIGHT,MKHEIGHT_RULE,MKWIDE_RULE,USAGE)VALUES ($id,'/$zhizaobm/','/$menkuang/','/$dangci/','/$chuanghua/',
                        '/$kaixiang/','/$menshan/','/$mkhoudu/','$start_height','$end_height','$mkheight_rule','$mkwide_rule',
                        '$usage')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getChuanghuaZhongshiSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_CHUANGHUA_ZHONGSHI_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $menkuang = $val[1];
                $dangci = $val[2];
                $chuanghua = $val[3];
                $kaixiang = $val[4];
                $menshan = $val[5];
                $mkhoudu = $val[6];
                $start_height = $val[7];
                $end_height = $val[8];
                $mkheight_rule = trim($val[9], '门框高度');
                $mkwide_rule = trim($val[10], '门框宽度');
                $liaohao = $val[11];
                $pinming = $val[12];
                $guige = $val[13];
                $id = $key + 1;
                $str = "insert into BOM_CHUANGHUA_ZHONGSHI_RULE(ID,ZHIZAOBM,MENKUANG,DANGCI,CHUANGHUA,KAIXIANG,MENSHAN,MKHOUDU,START_HEIGHT,
                        END_HEIGHT,MKHEIGHT_RULE,MKWIDE_RULE,LIAOHAO,PINMING,GUIGE)VALUES ($id,'/$zhizaobm/','/$menkuang/','/$dangci/','/$chuanghua/',
                        '/$kaixiang/','/$menshan/','/$mkhoudu/','$start_height','$end_height','$mkheight_rule','$mkwide_rule',
                        '$liaohao','$pinming','$guige')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getPeBaozhuangSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_PEBAOZHUANG_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $dangci = $val[1];
                $menkuang = $val[2];
                $menshan = $val[3];
                $baozhuangfs = $val[4];
                $baozhpack = $val[5];
                $start_height = $val[6];
                $end_height = $val[7];
                $start_width = $val[8];
                $end_width = $val[9];
                $yongliao_length = $val[10];
                $yongliao_width = $val[11];
                $cailiao_length = $val[12];
                $shuliang = $val[13];
                $liaohao = $val[14];
                $pinming = $val[15];
                $guige = $val[16];
                $id = $key + 1;
                $str = "insert into BOM_PEBAOZHUANG_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,BAOZHUANGFS,BAOZHPACK,START_HEIGHT,END_HEIGHT,START_WIDTH,END_WIDTH,
YONGLIAO_LENGTH,YONGLIAO_WIDTH,CAILIAO_LENGTH,SHULIANG,LIAOHAO,PINMING,GUIGE)VALUES ($id,'/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','/$baozhuangfs/',
'/$baozhpack/','$start_height','$end_height','$start_width','$end_width','$yongliao_length','$yongliao_width','$cailiao_length','$shuliang','$liaohao','$pinming','$guige')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    /**
     * 长边骨架用料规则--计算QPA值规则
     * @param $data
     */
    public function getGangkuangChangbgujiaYongliaoSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_GK_CBGUJIA_YLIAO_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $menkuang = $val[0];
                $gaodu_start = $val[1];
                $gaodu_end = $val[2];
                $menshan = $val[3];
                $dkcailiao = $val[4];
                $guige_cd = $val[5];
                $mumen_jiao_yongliao = $val[6];
                $mumen_suo_yongliao = $val[7];
                $zimen_jiao_yongliao = $val[8];
                $zimen_suo_yongliao = $val[9];
                $cailiao_cd = $val[10];
                $id = $key + 1;
                $str = "insert into BOM_GK_CBGUJIA_YLIAO_RULE(ID,MENKUANG,GAODU_START,GAODU_END,MENSHAN,DKCAILIAO,GUIGE_CD,MUMEN_JIAO_YONGLIAO,MUMEN_SUO_YONGLIAO,ZIMEN_JIAO_YONGLIAO,ZIMEN_SUO_YONGLIAO,CAILIAO_CD) VALUES 
                  ($id,'/$menkuang/','$gaodu_start','$gaodu_end','/$menshan/','/$dkcailiao/','$guige_cd','$mumen_jiao_yongliao','$mumen_suo_yongliao','$zimen_jiao_yongliao','$zimen_suo_yongliao','$cailiao_cd')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    /**
     * 长边骨架品名规格规则
     * @param $data
     */
    public function getGangkuangChangbgujiaPinmingSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_GK_CBGUJIA_PINMING_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $menkuang = $val[0];
                $width_start = $val[1];
                $width_end = $val[2];
                $menshan = $val[3];
                $mumen_jiao_liaohao = $val[4];
                $mumen_jiao_pinming = $val[5];
                $mumen_jiao_guige = $val[6];
                $mumen_suo_liaohao = $val[7];
                $mumen_suo_pinming = $val[8];
                $mumen_suo_guige = $val[9];

                $zimen_jiao_liaohao = $val[10];
                $zimen_jiao_pinming = $val[11];
                $zimen_jiao_guige = $val[12];
                $zimen_suo_liaohao = $val[13];
                $zimen_suo_pinming = $val[14];
                $zimen_suo_guige = $val[15];
                $id = $key + 1;
                $str = "insert into BOM_GK_CBGUJIA_PINMING_RULE(ID,MENKUANG,WIDTH_START,WIDTH_END,MENSHAN,MUMEN_JIAO_LIAOHAO,MUMEN_JIAO_PINMING,MUMEN_JIAO_GUIGE,MUMEN_SUO_LIAOHAO,MUMEN_SUO_PINMING,MUMEN_SUO_GUIGE,ZIMEN_JIAO_LIAOHAO,ZIMEN_JIAO_PINMING,ZIMEN_JIAO_GUIGE,ZIMEN_SUO_LIAOHAO,ZIMEN_SUO_PINMING,ZIMEN_SUO_GUIGE) VALUES 
                  ($id,'/$menkuang/','$width_start','$width_end','/$menshan/','$mumen_jiao_liaohao','$mumen_jiao_pinming','$mumen_jiao_guige','$mumen_suo_liaohao','$mumen_suo_pinming','$mumen_suo_guige','$zimen_jiao_liaohao','$zimen_jiao_pinming','$zimen_jiao_guige','$zimen_suo_liaohao','$zimen_suo_pinming','$zimen_suo_guige')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getsikaiChuanghuaSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_CHUANGHUA_SIKAI_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[1])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $menkuang = $val[1];
                $menshan = $val[2];
                $chuanghua = $val[3];
                $xinghao = $val[4];
                $chuanghuax = $val[5];
                $chuanghuax_usage = $val[6];
                $chuanghuad = $val[7];
                $chuanghuad_usage = $val[8];

                $id = $key + 1;
                $str = "insert into BOM_CHUANGHUA_SIKAI_RULE(ID,ZHIZAOBM,MENKUANG,MENSHAN,CHUANGHUA,XINGHAO,CHUANGHUAX,YONGLIANGX,
                        CHUANGHUAD,YONGLIANGD)VALUES ($id,'/$zhizaobm/','/$menkuang/','/$menshan/','/$chuanghua/',
                        '$xinghao','$chuanghuax','$chuanghuax_usage','$chuanghuad','$chuanghuad_usage')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getgangkuangMushanSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENBAN_GANGKUANG_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $menkuang = $val[0];
                $menshan = $val[1];
                $mskaikong = $val[2];
                $dkcailiao = $val[3];
                $mumen_guigecd = $val[4];
                $mumen_guigekd = $val[5];
                $mumen_yongliaocd = $val[6];
                $mumen_yongliaokd = $val[7];
                $mumen_cailiaocd = $val[8];
                $mumen_cailiaokd = $val[9];
                $mumen_genshu = $val[10];
                $zimen_guigecd = $val[11];
                $zimen_guigekd = $val[12];
                $zimen_yongliaocd = $val[13];
                $zimen_yongliaokd = $val[14];
                $zimen_cailiaocd = $val[15];
                $zimen_cailiaokd = $val[16];
                $zimen_genshu = $val[17];
                $liaohao = $val[18];
                $classify = $val[19];
                $pinming = $val[20];
                $guige = $val[21];
                $id = $key + 1;
                $str = "insert into BOM_MENBAN_GANGKUANG_RULE(ID,MENKUANG,MENSHAN,MSKAIKONG,DKCAILIAO,MUMEN_GUIGECD,MUMEN_GUIGEKD,MUMEN_YONGLIAOCD,MUMEN_YONGLIAOKD,
    MUMEN_CAILIAOCD,MUMEN_CAILIAOKD,MUMEN_GENSHU,ZIMEN_GUIGECD,ZIMEN_GUIGEKD,ZIMEN_YONGLIAOCD,ZIMEN_YONGLIAOKD,ZIMEN_CAILIAOCD,ZIMEN_CAILIAOKD,ZIMEN_GENSHU,
    LIAOHAO,CLASSIFY,PINMING,GUIGE)VALUES ($id,'/$menkuang/','/$menshan/','/$mskaikong/','/$dkcailiao/',
    '$mumen_guigecd','$mumen_guigekd','$mumen_yongliaocd','$mumen_yongliaokd','$mumen_cailiaocd','$mumen_cailiaokd','$mumen_genshu',
    '$zimen_guigecd','$zimen_guigekd','$zimen_yongliaocd','$zimen_yongliaokd','$zimen_cailiaocd','$zimen_cailiaokd','$zimen_genshu',
    '$liaohao','$classify','$pinming','$guige')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getgangkuangMushanQHSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MENBAN_GANGKUANG_QH_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $menkuang = $val[0];
                $interval_start = $val[1];
                $interval_end = $val[2];
                $menshan = $val[3];
                $mskaikong = $val[4];
                $dkcailiao = $val[5];
                $mumen_guigecd = $val[6];
                $mumen_guigekd = $val[7];
                $mumen_yongliaocd = $val[8];
                $mumen_yongliaokd = $val[9];
                $mumen_cailiaocd = $val[10];
                $mumen_cailiaokd = $val[11];
                $mumen_genshu = $val[12];
                $zimen_guigecd = $val[13];
                $zimen_guigekd = $val[14];
                $zimen_yongliaocd = $val[15];
                $zimen_yongliaokd = $val[16];
                $zimen_cailiaocd = $val[17];
                $zimen_cailiaokd = $val[18];
                $zimen_genshu = $val[19];
                $liaohao = $val[20];
                $classify = $val[21];
                $pinming = $val[22];
                $guige = $val[23];
                $id = $key + 1;
                $str = "insert into BOM_MENBAN_GANGKUANG_QH_RULE(ID,MENKUANG,INTERVAL_START,INTERVAL_END,MENSHAN,MSKAIKONG,DKCAILIAO,MUMEN_GUIGECD,MUMEN_GUIGEKD,MUMEN_YONGLIAOCD,MUMEN_YONGLIAOKD,
    MUMEN_CAILIAOCD,MUMEN_CAILIAOKD,MUMEN_GENSHU,ZIMEN_GUIGECD,ZIMEN_GUIGEKD,ZIMEN_YONGLIAOCD,ZIMEN_YONGLIAOKD,ZIMEN_CAILIAOCD,ZIMEN_CAILIAOKD,ZIMEN_GENSHU,
    LIAOHAO,CLASSIFY,PINMING,GUIGE)VALUES ($id,'/$menkuang/',$interval_start,$interval_end,'/$menshan/','/$mskaikong/','/$dkcailiao/',
    '$mumen_guigecd','$mumen_guigekd','$mumen_yongliaocd','$mumen_yongliaokd','$mumen_cailiaocd','$mumen_cailiaokd','$mumen_genshu',
    '$zimen_guigecd','$zimen_guigekd','$zimen_yongliaocd','$zimen_yongliaokd','$zimen_cailiaocd','$zimen_cailiaokd','$zimen_genshu',
    '$liaohao','$classify','$pinming','$guige')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function gettianxinMenshanSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_TIANXIN_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $menkuang = $val[0];
                $menshan = $val[1];
                $dkcailiao = $val[2];
                $huase = $val[3];
                $yongliao_mumen = $val[4];
                $yongliao_zimen = $val[5];
                $liaohao = $val[6];
                $pinming = $val[7];
                $guige = $val[8];
                $classify = $val[9];
                $id = $key + 1;
                $str = "insert into BOM_TIANXIN_RULE(ID,MENKUANG,MENSHAN,DKCAILIAO,HUASE,YONGLIAO_MUMEN,YONGLIAO_ZIMEN,LIAOHAO,PINMING,GUIGE,CLASSIFY)VALUES 
($id,'/$menkuang/','/$menshan/','/$dkcailiao/','/$huase/','$yongliao_mumen','$yongliao_zimen','$liaohao','$pinming','$guige','$classify')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function gettianxinMenshanQHSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_TIANXIN_QH_RULE';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $menkuang = $val[0];
                $interval_start = $val[1];
                $interval_end = $val[2];
                $menshan = $val[3];
                $dkcailiao = $val[4];
                $huase = $val[5];
                $yongliao_mumen = $val[6];
                $yongliao_zimen = $val[7];
                $liaohao = $val[8];
                $pinming = $val[9];
                $guige = $val[10];
                $classify = $val[11];
                $id = $key + 1;
                $str = "insert into BOM_TIANXIN_QH_RULE(ID,MENKUANG,INTERVAL_START,INTERVAL_END,MENSHAN,DKCAILIAO,HUASE,YONGLIAO_MUMEN,YONGLIAO_ZIMEN,LIAOHAO,PINMING,GUIGE,CLASSIFY)VALUES 
($id,'/$menkuang/','$interval_start','$interval_end','/$menshan/','/$dkcailiao/','/$huase/','$yongliao_mumen','$yongliao_zimen','$liaohao','$pinming','$guige','$classify')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getMianqiSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_MIANQIB';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $menkuang = $val[1];
                $menshan = $val[2];
                $start_len = $val[3];
                $end_len = $val[4];
                $biaomcl = $val[5];
                $biaomiantsyq = $val[6];
                $mumen = $val[7];
                $zimen = $val[8];
                $liaohao = $val[9];
                $pinming = $val[10];
                $guige = $val[11];
                $id = $key + 1;
                $str = "insert into BOM_MIANQIB(ID,MENKUANG,MENSHAN,START_LEN,END_LEN,BIAOMCL,BIAOMIANTSYQ,MUMEN,ZIMEN,LIAOHAO,PINMING,GUIGE,ZHIZAOBM)VALUES 
($id,'/$menkuang/','/$menshan/','$start_len','$end_len','$biaomcl','$biaomiantsyq','$mumen','$zimen','$liaohao','$pinming','$guige','/$zhizaobm/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }

    public function getFengbianSql($data)
    {
        Db::startTrans();
        if (count($data)) {
            $biao = 'BOM_FENGBIANT';
            Db::execute("delete from $biao ");
            foreach ($data as $key => $val) {
                if (empty($val[0])) {
                    continue;
                }
                $zhizaobm = $val[0];
                $menkuang = $val[1];
                $menshan = $val[2];
                $biaomcl = $val[3];
                $biaomiantsyq = $val[4];
                $dkcailiao = $val[5];
                $guige_cd = $val[6];
                $mumen = $val[7];
                $zimen = $val[8];
                $liaohao = $val[9];
                $pinming = $val[10];
                $guige = $val[11];
                $id = $key + 1;
                $str = "insert into BOM_FENGBIANT(ID,MENKUANG,MENSHAN,BIAOMCL,BIAOMIANTSYQ,GUIGE_CD,MUMEN,ZIMEN,LIAOHAO,PINMING,GUIGE,ZHIZAOBM,DKCAILIAO)VALUES 
($id,'/$menkuang/','/$menshan/','$biaomcl','$biaomiantsyq','$guige_cd','$mumen','$zimen','$liaohao','$pinming','$guige','/$zhizaobm/','/$dkcailiao/')";
                Db::execute($str);
            }
            $mark = 1;
        }
        if ($mark == 1) {
            Db::commit();
        } else {
            Db::rollback();
        }
    }


    //导出用料规则表
    public function exportExcel($sheet = '')
    {
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        if (empty($sheet)) {
            $sheet = '门板长度用料规则';
        }
        //Excel列下标顺序
        $currentColumn = 'A';
        for ($i = 1; $i <= 50; $i++) {
            $a[] = $currentColumn++;
        }
        switch ($sheet) {
            case '门板长度用料规则':
                {
                    $this->getMenbanLengthExcel($sheet);
                    break;
                }
            case '门板宽度用料规则':
                {
                    $this->getMenbanWideExcel($sheet);
                    break;
                }
            case '门框铰方、锁方用料规则':
                {
                    $this->getMenkuangJiaoSuoExcel($sheet);
                    break;
                }
            case '门框上门框、中门框用料规则':
                {
                    $this->getMenkuangShangZhongExcel($sheet);
                    break;
                }
            case '下门框用料规则':
                {
                    $this->getMenkuangXiaExcel($sheet);
                    break;
                }
            case '窗花计算规则':
                {
                    $this->getChuanghuaExcel($sheet);
                    break;
                }
            case '四开窗花计算规则':
                {
                    $this->getSikaiChuanghuaExcel($sheet);
                    break;
                }
            case '钢框木扇用料规则':
                {
                    $this->getgangkunagMushanExcel($sheet);
                    break;
                }
            case '齐河基地钢框木扇用料规则':
                {
                    $this->getgangkunagMushanQHExcel($sheet);
                    break;
                }
            case '防火门填芯用料规则':
                {
                    $this->gettianxinMenshanExcel($sheet);
                    break;
                }
            case '齐河基地防火门填芯用料规则':
                {
                    $this->gettianxinMenshanQHExcel($sheet);
                    break;
                }
            case '防火门钢框木扇免漆板用料规则':
                {
                    $this->getMianqiExcel($sheet);
                    break;
                }
            case '防火门钢框木扇封边条用料规则':
                {
//                $this->getFengbianExcel($sheet);
                    $model = new YongliaoRuleToExcel();
                    $model->exportExcel();
                    break;
                }
            case '中式窗花带玻胶条用料规则':
                {
                    $this->getChuanghuaZhongshiExcel($sheet);
                    break;
                }
            case 'pe包装用料规则':
                {
                    $this->getPeBaozhuangExcel($sheet);
                    break;
                }
            case '成都基地防火门钢框木扇长边骨架用料规则':
                {
                    $this->getGangkuangChangbgujiaYongliaoExcel($sheet);
                    break;
                }
            case '成都基地防火门钢框木扇长边骨架品名规则':
                {
                    $this->getGangkuangChangbgujiaPinmingExcel($sheet);
                    break;
                }
            case '常规窗花用料规则':
                {
                    $chuanghua = new Chuanghua();
                    $chuanghua->exportExcel($sheet);
                    break;
                }
            case '不锈钢窗花用料规则':
                {
                    $chuanghua = new Chuanghua();
                    $chuanghua->exportExcel($sheet);
                    break;
                }
            case '中式窗花用料规则':
                {
                    $chuanghua = new Chuanghua();
                    $chuanghua->exportExcel($sheet);
                    break;
                }
            case "木门门套用料规则(复合实木)":
            case "木门门套用料规则(集成实木)":
            case "木门门套用料规则(强化木门)":
            case "木门门套用料规则(转印木门)":
                {
                    $matericalRuleLogic = new MaterialRuleLogic($sheet);
                    $ret = $matericalRuleLogic->exportExcel();
                    return $ret;
                    break;
                }
            case "木门封边条用料规则":
            case "木门门扇面板用料规则":
            case "木门门扇木方用料规则":
            case "木门门扇芯料用料规则":
            case "木门线条用料规则":
                {
                    if ($sheet == '木门封边条用料规则') {
                        $type = 1;
                    } elseif ($sheet == '木门门扇面板用料规则') {
                        $type = 2;
                    } elseif ($sheet == '木门门扇木方用料规则') {
                        $type = 3;
                    } elseif ($sheet == '木门门扇芯料用料规则') {
                        $type = 4;
                    } elseif ($sheet == '木门线条用料规则') {
                        $type = 5;
                    }
                    $model = new MenShanRuleToExcel();
                    $res = $model->exportExcel($type);
                    break;
                }
            case "齐河封闭式窗花用料规则":
            case "成都封闭式窗花用料规则":
                {
                    $type = $sheet == '成都封闭式窗花用料规则' ? 1 : 2;
                    $chuanghua = new Chuanghua();
                    $chuanghua->exportFbsChuangHua($type);
                    break;
                }
            case "窗花玻璃用料规则":
                {
                    $glassRule = new GlassRule();
                    $glassRule->exportExcel();
                    break;
                }

        }
    }

    public function getMenbanLengthExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENBAN_LENGTH_RULE');
        $sql = "select $str from BOM_MENBAN_LENGTH_RULE ORDER BY DANGCI,MENKUANG,MENSHAN,GUIGECD";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '用料长度(母前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '用料长度(母后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用料长度(子前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '用料长度(子后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '用料长度(母子前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '用料长度(母子后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '用料长度(子子前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '用料长度(子子后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O1', '门框厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P1', '开向');
        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['DIKUANGCL'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['MUQIANMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUHOUMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['ZIQIANMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['ZIHOUMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['MUZIQMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['MUZIHMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['ZIZIQMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['ZIZIHMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, trim($val['MKHOUDU'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, trim($val['KAIXIANG'], '/'));
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getMenbanWideExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENBAN_WIDE_RULE');
        $sql = "select $str from BOM_MENBAN_WIDE_RULE ORDER BY DANGCI,MENKUANG,MENSHAN,GUIGECD";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '前门板');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '后门板');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '用料宽度(母前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用料宽度(母后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '用料宽度(子前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '用料宽度(子后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '用料宽度(母子前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '用料宽度(母子后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '用料宽度(子子前门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O1', '用料宽度(子子后门板)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P1', '门扇材质');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('Q1', '门框厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('R1', '开向');
        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['QIANMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['HOUMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUQIANMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['MUHOUMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['ZIQIANMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['ZIHOUMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['MUZIQMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['MUZIHMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['ZIZIQMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, $val['ZIZIHMB']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, $val['MENSHANCZ']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('Q' . $hang, trim($val['MKHOUDU'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('R' . $hang, trim($val['KAIXIANG'], '/'));
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getMenkuangJiaoSuoExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENKUANG_JIAOSUO_RULE');
        $sql = "select $str from BOM_MENKUANG_JIAOSUO_RULE ORDER BY DANGCI,MENKUANG,KAIXIANG";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '开向');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '门框厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '用料长度(铰门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '用料(铰门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用量厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '密度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '用料长度(锁门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '用料(锁门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '用量厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '密度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O1', '门框材质');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P1', '门扇');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['KAIXIANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['MENKUANGHD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['JIAOMK_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['JIAOMK']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['JIAOMK_HD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['JIAOMK_DENSITY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['SUOMK_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['SUOMK']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['SUOMK_HD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['SUOMK_DENSITY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, trim($val['MENKUANGCZ'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, trim($val['MENSHAN'], '/'));
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getMenkuangShangZhongExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENKUANG_SHANGZHONG_RULE');
        $sql = "select $str from BOM_MENKUANG_SHANGZHONG_RULE ORDER BY DANGCI,MENKUANG,MENSHAN,KAIXIANG";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '开向');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '门框厚度');
//        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1','材质');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '用料长度(上门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用料(上门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '用量厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '密度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '用料长度(中门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '用料(中门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '用量厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O1', '密度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P1', '门框材质');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['KAIXIANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['MENKUANGHD']);
//            $objPHPExcel->setActiveSheetIndex()->setCellValue('G'.$hang,$val['CAIZHI']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['SHANGMK_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['SHANGMK']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['SHANGMK_HD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['SHANGMK_DENSITY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['ZHONGMK_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['ZHONGMK']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['ZHONGMK_HD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, $val['ZHONGMK_DENSITY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, trim($val['MENKUANGCZ'], '/'));
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getMenkuangXiaExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENKUANG_XIA_RULE');
        $sql = "select $str from BOM_MENKUANG_XIA_RULE ORDER BY DANGCI,MENKUANG,MENSHAN,KAIXIANG";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '开向');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '门框厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用料长度(下门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '用料(下门框)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '用量厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '密度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '门框材质');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['KAIXIANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['MENKUANGHD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, trim($val['DIKUANGCL'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['XIAMK_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['XIAMK']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['XIAMK_HD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['XIAMK_DENSITY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, trim($val['MENKUANGCZ'], '/'));
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getChuanghuaExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_CHUANGHUA_RULE');
        $sql = "select $str from BOM_CHUANGHUA_RULE ORDER BY DANGCI,MENKUANG,CHUANGHUA,KAIXIANG,
                        MENSHAN,START_HEIGHT";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '窗花');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '开向');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '门框厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '开始区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '结束区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '高度规则');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '宽度规则');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '用量');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['CHUANGHUA'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['KAIXIANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['MKHOUDU']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['START_HEIGHT']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['END_HEIGHT']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['MKHEIGHT_RULE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['MKWIDE_RULE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['USAGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getChuanghuaZhongshiExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_CHUANGHUA_ZHONGSHI_RULE');
        $sql = "select $str from BOM_CHUANGHUA_ZHONGSHI_RULE ORDER BY DANGCI,MENKUANG,CHUANGHUA,KAIXIANG,
                        MENSHAN,START_HEIGHT";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '窗花');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '开向');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '门框厚度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '开始区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '结束区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '高度规则');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '宽度规则');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '规格');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['CHUANGHUA'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['KAIXIANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, trim($val['MKHOUDU'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['START_HEIGHT']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['END_HEIGHT']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['MKHEIGHT_RULE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['MKWIDE_RULE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getPeBaozhuangExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_PEBAOZHUANG_RULE');
        $sql = "select $str from BOM_PEBAOZHUANG_RULE ORDER BY DANGCI,MENKUANG,MENSHAN,BAOZHUANGFS,BAOZHPACK,START_HEIGHT,END_HEIGHT,START_WIDTH,END_WIDTH,YONGLIAO_LENGTH";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '档次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '包装方式');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '包装品牌');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '起始高度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '结束高度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '起始宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '结束宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '用料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '用料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M1', '材料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '数量');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('Q1', '规格');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['DANGCI'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['BAOZHUANGFS'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, trim($val['BAOZHPACK'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['START_HEIGHT']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['END_HEIGHT']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['START_WIDTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['END_WIDTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['YONGLIAO_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['YONGLIAO_WIDTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['CAILIAO_LENGTH']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['SHULIANG']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('Q' . $hang, $val['GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getGangkuangChangbgujiaYongliaoExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_GK_CBGUJIA_YLIAO_RULE');
        $sql = "select $str from BOM_GK_CBGUJIA_YLIAO_RULE ORDER BY MENKUANG,GAODU_START,GAODU_END,MENSHAN";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '高度起始值');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '高度结束值');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇分类');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '用料长度(母门铰方)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '用料长度(母门锁方)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用料长度(子门铰方)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '用料长度(子门锁方)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '材料长度');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, $val['GAODU_START']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, $val['GAODU_END']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['DKCAILIAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['GUIGE_CD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['MUMEN_JIAO_YONGLIAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUMEN_SUO_YONGLIAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['ZIMEN_JIAO_YONGLIAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['ZIMEN_SUO_YONGLIAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['CAILIAO_CD']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getGangkuangChangbgujiaPinmingExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_GK_CBGUJIA_PINMING_RULE');
        $sql = "select $str from BOM_GK_CBGUJIA_PINMING_RULE ORDER BY MENKUANG,WIDTH_START,WIDTH_END,MENSHAN";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '门框');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:A2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '规格宽度区间');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('B1:C2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇分类');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('D1:D2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '母门(铰方)');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('E1:G1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E2', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F2', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G2', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '母门(锁方)');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('H1:J1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H2', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I2', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J2', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '子门(铰方)');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('K1:M1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K2', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L2', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M2', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '子门(锁方)');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('N1:P1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N2', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O2', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P2', '规格');

        foreach ($result as $key => $val) {
            $hang = $key + 3;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, $val['WIDTH_START']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, $val['WIDTH_END']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['MUMEN_JIAO_LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['MUMEN_JIAO_PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['MUMEN_JIAO_GUIGE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUMEN_SUO_LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['MUMEN_SUO_PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['MUMEN_SUO_GUIGE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['ZIMEN_JIAO_LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['ZIMEN_JIAO_PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['ZIMEN_JIAO_GUIGE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['ZIMEN_SUO_LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, $val['ZIMEN_SUO_PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, $val['ZIMEN_SUO_GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getsikaiChuanghuaExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_CHUANGHUA_SIKAI_RULE');
        $sql = "select $str from BOM_CHUANGHUA_SIKAI_RULE ORDER BY MENKUANG,MENSHAN,CHUANGHUA,XINGHAO ";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '窗花');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '型号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '窗花(小)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '用量');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '窗花(大)');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '用量');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['CHUANGHUA'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['XINGHAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['CHUANGHUAX']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['YONGLIANGX']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['CHUANGHUAD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['YONGLIANGD']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getgangkunagMushanExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENBAN_GANGKUANG_RULE');
        $sql = "select $str from BOM_MENBAN_GANGKUANG_RULE ORDER BY ID";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '门框');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:A2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门扇分类');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('B1:B2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门扇开孔');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('C1:C2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('D1:D2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '母门');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('E1:K1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '子门');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('L1:R1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('S1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('T1', '类别');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('U1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('V1', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E2', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F2', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G2', '用料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H2', '用料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I2', '材料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J2', '材料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K2', '用料根数');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L2', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M2', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N2', '用料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O2', '用料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P2', '材料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('Q2', '材料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('R2', '用料根数');

        foreach ($result as $key => $val) {
            $hang = $key + 3;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MSKAIKONG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['DKCAILIAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['MUMEN_GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['MUMEN_GUIGEKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['MUMEN_YONGLIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUMEN_YONGLIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['MUMEN_CAILIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['MUMEN_CAILIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['MUMEN_GENSHU']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['ZIMEN_GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['ZIMEN_GUIGEKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['ZIMEN_YONGLIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, $val['ZIMEN_YONGLIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, $val['ZIMEN_CAILIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('Q' . $hang, $val['ZIMEN_CAILIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('R' . $hang, $val['ZIMEN_GENSHU']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('S' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('T' . $hang, $val['CLASSIFY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('U' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('V' . $hang, $val['GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getgangkunagMushanQHExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MENBAN_GANGKUANG_QH_RULE');
        $sql = "select $str from BOM_MENBAN_GANGKUANG_QH_RULE ORDER BY ID";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '门框');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:A2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '规格(高度/宽度)起始区间');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('B1:B2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '规格(高度/宽度)结束区间');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('C1:C2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇分类');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('D1:D2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '门扇开孔');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('E1:E2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('F1:F2');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '母门');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('G1:M1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N1', '子门');
        $objPHPExcel->setActiveSheetIndex()->mergeCells('N1:T1');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('U1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('V1', '类别');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('W1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('X1', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G2', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H2', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I2', '用料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J2', '用料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K2', '材料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L2', '材料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('M2', '用料根数');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('N2', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('O2', '规格宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('P2', '用料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('Q2', '用料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('R2', '材料长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('S2', '材料宽度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('T2', '用料根数');

        foreach ($result as $key => $val) {
            $hang = $key + 3;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, $val['INTERVAL_START']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, $val['INTERVAL_END']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['MSKAIKONG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, trim($val['DKCAILIAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['MUMEN_GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUMEN_GUIGEKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['MUMEN_YONGLIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['MUMEN_YONGLIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['MUMEN_CAILIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['MUMEN_CAILIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('M' . $hang, $val['MUMEN_GENSHU']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('N' . $hang, $val['ZIMEN_GUIGECD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('O' . $hang, $val['ZIMEN_GUIGEKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('P' . $hang, $val['ZIMEN_YONGLIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('Q' . $hang, $val['ZIMEN_YONGLIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('R' . $hang, $val['ZIMEN_CAILIAOCD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('S' . $hang, $val['ZIMEN_CAILIAOKD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('T' . $hang, $val['ZIMEN_GENSHU']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('U' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('V' . $hang, $val['CLASSIFY']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('W' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('X' . $hang, $val['GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function gettianxinMenshanExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_TIANXIN_RULE');
        $sql = "select $str from BOM_TIANXIN_RULE ORDER BY ID";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门扇分类');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '花色');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '母门用料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '子门用料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '规则类别');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['DKCAILIAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['HUASE'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['YONGLIAO_MUMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['YONGLIAO_ZIMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['GUIGE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['CLASSIFY']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function gettianxinMenshanQHExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_TIANXIN_QH_RULE');
        $sql = "select $str from BOM_TIANXIN_QH_RULE ORDER BY ID";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '规格(高度/宽度)起始区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '规格(高度/宽度)结束区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '门扇分类');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '花色');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '母门用料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '子门用料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '规则类别');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, $val['INTERVAL_START']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, $val['INTERVAL_END']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, trim($val['DKCAILIAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, trim($val['HUASE'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['YONGLIAO_MUMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['YONGLIAO_ZIMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['GUIGE']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['CLASSIFY']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getMianqiExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_MIANQIB');
        $sql = "select $str from BOM_MIANQIB ORDER BY ID";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '宽度起始区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '宽度结束区间');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '表面方式');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '表面要求');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '母门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '子门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '规格');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, $val['START_LEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['END_LEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, $val['BIAOMCL']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['BIAOMIANTSYQ']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['ZIMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function getFengbianExcel($sheet)
    {
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $str = getColumnName('BOM_FENGBIANT');
        $sql = "select $str from BOM_FENGBIANT ORDER BY ID";
        $result = Db::query($sql);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1', '制造部门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B1', '门框');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C1', '门扇');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D1', '表面方式');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E1', '表面要求');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F1', '底框材料');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G1', '规格长度');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H1', '母门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I1', '子门');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J1', '料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('K1', '品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('L1', '规格');

        foreach ($result as $key => $val) {
            $hang = $key + 2;
            $objPHPExcel->setActiveSheetIndex()->setCellValue('A' . $hang, trim($val['ZHIZAOBM'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('B' . $hang, trim($val['MENKUANG'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('C' . $hang, trim($val['MENSHAN'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('D' . $hang, $val['BIAOMCL']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('E' . $hang, $val['BIAOMIANTSYQ']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('F' . $hang, trim($val['DKCAILIAO'], '/'));
            $objPHPExcel->setActiveSheetIndex()->setCellValue('G' . $hang, $val['GUIGE_CD']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('H' . $hang, $val['MUMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('I' . $hang, $val['ZIMEN']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('J' . $hang, $val['LIAOHAO']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('K' . $hang, $val['PINMING']);
            $objPHPExcel->setActiveSheetIndex()->setCellValue('L' . $hang, $val['GUIGE']);
        }
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function downLoadExcel($objPHPExcel, $sheet)
    {
        //加粗字体
        $objPHPExcel->getActiveSheet()->getStyle('A1:Z1')->applyFromArray(
            array(
                'font' => array(
                    'bold' => true
                )
            )
        );
        $objPHPExcel->getActiveSheet()->freezePane('A2');       //冻结单元格
        $objPHPExcel->getActiveSheet()->setTitle($sheet);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$sheet.xls");
        header('Cache-Control: max-age=0');
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save('php://output'); //文件通过浏览器下载
        return;
    }
}