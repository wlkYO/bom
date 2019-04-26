<?php
/**
 * Created by PhpStorm.
 * User: 000
 * Date: 2019/4/22
 * Time: 10:40
 */

namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Validate;


class GlassRule
{
    public function importExcel()
    {
        $excel_file = $this->getImportFile();
        if (empty($excel_file)) {
            return retmsg(-1, null, '获取文件错误,请重新选择要导入的EXCEL文件');
        }
        $importService = Loader::model('ImportExcelService', 'service');
        $all_data = $importService->importExcel($excel_file);
        $this->handleImportData($all_data);
        return retmsg(0, '', '导入成功');
    }

    public function handleImportData($all_data)
    {
        # 导入之前先清理数据
        Db::execute("delete from BOM_BOLI_RULE");
        $data = $all_data[0];
        $fileName = $all_data[1];
        $res = $this->handleGlassRule($data, $fileName);
        return retmsg(0, $res[0], '导入成功,导入失败' . $res[1] . '条信息');
    }

    public function exportExcel()
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        $all_data_list = $this->getExportData();
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        //导入插件
        vendor('PHPExcel.Classes.PHPExcel');
        $objExecl = new \PHPExcel();
        ob_end_clean();    //擦除缓冲区
        $heard_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T');
        //构造表头字段,添加在list数组之前,组成完整数组
        foreach ($all_data_list as $key => $value) {
            $indexKey = $this->indexKey($key);
            $all_data_list[$key] = $this->addHeadToData($all_data_list[$key], $key);
            //写入数据到表格中
            $objExecl->createSheet();//创建工作簿新的表空间
            $currentSheet = $objExecl->setActiveSheetIndex($key); //当前工作表
            $objExecl->setActiveSheetIndex($key)->setTitle($all_data_list[$key][1]['SHEET_NAME']);
            $this->addDataToExcel($all_data_list[$key], $currentSheet, $indexKey, $heard_arr, $key);
        }
        $objExecl->setActiveSheetIndex(0);  //设置导出 默认打开第一个sheet
        $fileName = '窗花玻璃用料规则';
        $this->downLoad($objExecl, $fileName);
    }


    public function getExportData()
    {
        $sheet1 = [];
        $sheet2 = [];
        $sheet3 = [];
        $sheet4 = [];
        $sql1 = "select * from BOM_BOLI_RULE  order by id asc";
        $all_data = Db::query($sql1);
        foreach ($all_data as $key => $value) {
            if ($all_data[$key]['SORT'] == 1) {
                array_push($sheet1, $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 2) {
                array_push($sheet2, $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 3) {
                array_push($sheet3, $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 4) {
                array_push($sheet4, $all_data[$key]);
            }
        }
        $res = [$sheet1, $sheet2, $sheet3, $sheet4];
        return $res;
    }


    public function indexKey($key)
    {
        if ($key == 0) {
            $indexKey = array('DEPT_NAME', 'MENKUANG', 'DANGCI', 'CHUANGHUA', 'KAIXIANG', 'MENSHAN', 'KUANGHOU', 'HEIGHT_START', 'HEIGHT_END', 'HEIGHT_RULE', 'WIDTH_RULE', 'BOLI_HOUDU', 'QPA1');
        }
        if ($key == 1) {
            $indexKey = array('DEPT_NAME', 'MENKUANG', 'MENSHAN', 'CHUANGHUA', 'XINGHAO', 'CH_XIAO', 'QPA1', 'CH_DA', 'QPA2', 'BOLI_HOUDU');
        }
        if ($key == 2) {
            $indexKey = array('DEPT_NAME', 'HUASE', 'MENSHAN', 'KAIXIANG', 'CHUANGHUA', 'HEIGHT_START', 'HEIGHT_END', 'WIDTH_START', 'WIDTH_END', 'WIDTH_GG', 'BOLI_HEIGHT', 'BOLI_WIDTH_M', 'BOLI_WIDTH_Z', 'BOLI_WIDTH_MZ', 'BOLI_WIDTH_ZZ', 'BOLI_HOUDU', 'QPA1', 'BOLI_TYPE', 'XISHU1', 'XISHU2');
        }
        if ($key == 3) {
            $indexKey = array('DEPT_NAME', 'DANGCI', 'MENSHAN', 'KAIXIANG', 'QPA1', 'QPA2', 'LIAOHAO', 'PINMING', 'GUIGE');
        }
        return $indexKey;
    }

    public function addHeadToData($data, $key)
    {
        if ($key == 0) {
            $heard = array('DEPT_NAME' => '制造部门', 'MENKUANG' => '门框', 'DANGCI' => '档次', 'CHUANGHUA' => '窗花', 'KAIXIANG' => '开向', 'MENSHAN' => '门扇', 'KUANGHOU' => '框厚', 'HEIGHT_START' => '开始区间', 'HEIGHT_END' => '结束区间', 'HEIGHT_RULE' => '高度规则', 'WIDTH_RULE' => '宽度规则', 'BOLI_HOUDU' => '玻璃厚度', 'QPA1' => '用量');
        }
        if ($key == 1) {
            $heard = array('DEPT_NAME' => '制造部门', 'MENKUANG' => '门框', 'MENSHAN' => '门扇', 'CHUANGHUA' => '窗花', 'XINGHAO' => '型号', 'CH_XIAO' => '窗花(小)', 'QPA1' => '用量', 'CH_DA' => '窗花(大)', 'QPA2' => '用量', 'BOLI_HOUDU' => '玻璃厚度');
        }
        if ($key == 2) {
            $heard = array('DEPT_NAME' => '制造部门', 'HUASE' => '花色', 'MENSHAN' => '门扇', 'KAIXIANG' => '开向', 'CHUANGHUA' => '窗花', 'HEIGHT_START' => '高度区间', 'HEIGHT_END' => '高度区间', 'WIDTH_START' => '宽度区间', 'WIDTH_END' => '宽度区间', 'WIDTH_GG' => '宽度规格', 'BOLI_HEIGHT' => '玻璃高度', 'BOLI_WIDTH_M' => '玻璃宽度(母门)', 'BOLI_WIDTH_Z' => '玻璃宽度(子门)', 'BOLI_WIDTH_MZ' => '玻璃宽度(母子门)', 'BOLI_WIDTH_ZZ' => '玻璃宽度(子子门)', 'BOLI_HOUDU' => '玻璃厚度', 'QPA1' => '用量', 'BOLI_TYPE' => '玻璃分类', 'XISHU1' => '计算系数1', 'XISHU2' => '计算系数2');
        }
        if ($key == 3) {
            $heard = array('DEPT_NAME' => '制造部门', 'DANGCI' => '档次', 'MENSHAN' => '门扇', 'KAIXIANG' => '门扇开孔', 'QPA1' => '母门用料', 'QPA2' => '子门用料', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格');
        }
        array_unshift($data, $heard);
        return $data;
    }

    public function addDataToExcel($data, $objActSheet, $indexKey, $heard_arr, $key)
    {
        $startRow = 1;
        $objActSheet->getStyle("A1:Z1")->getFont()->setBold(true);
        $objActSheet->freezePane('A2');
        $objActSheet->getStyle("A1:Z1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        foreach ($data as $key => $value) {
            foreach ($indexKey as $k => $v) {
                $objActSheet->setCellValue($heard_arr[$k] . $startRow, $data[$key][$v]);
//                $objActSheet->getStyle($heard_arr[$k] . $startRow)->getFont()->setSize(9);
            }
            $startRow++;
        }
    }

    public function downLoad($objExecl = '', $file_name = '')
    {
        $objWriter = \PHPExcel_IOFactory::createWriter($objExecl, 'Excel5');
        // 下载这个表格，在浏览器输出
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename=' . $file_name . '.xls');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    public function getImportFile()
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        header("content-type:text/html; charset=utf-8");
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', '-1');
        ini_set('upload_max_filesize', "6M");//上传文件的大小为5M
        //获取文件上传信息
        $execl_file = request()->file('file');
        if (empty($execl_file)) {
            return;
        }
        $extension = substr(strrchr($_FILES["file"]["name"], '.'), 1);
        if ($extension != 'xls' && $extension != 'xlsx') {
            return;
        }
        return $execl_file;
    }

    public function handleGlassRule($data, $fileName)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if ($v == '玻璃计算规则') {
                $arr = ['sort' => 1, 'sheet_name' => '玻璃计算规则'];
                $names[$k] = $arr;
            } elseif (strpos($v, '四开玻璃') !== false) {
                $arr = ['sort' => 2, 'sheet_name' => '四开玻璃计算规则'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门花玻璃') !== false) {
                $arr = ['sort' => 3, 'sheet_name' => '门花玻璃计算规则'];
                $names[$k] = $arr;
            } elseif (strpos($v, '观察孔') !== false) {
                $arr = ['sort' => 4, 'sheet_name' => '观察孔用料规则'];
                $names[$k] = $arr;
            }
        }
        for ($j = 0; $j < count($data, 0); $j++) {
            $sheet_name = $names[$j]['sheet_name'];
            $sort = $names[$j]['sort'];
            foreach ($data[$j] as $key => $value) {
                if (empty($value['A'])) {
                    continue;
                }
                if ($sort == 1) {
                    $dept_name = $value['A'];
                    $menkuang = $value['B'];
                    $dangci = $value['C'];
                    $chuanghua = $value['D'];
                    $kaixiang = $value['E'];
                    $menshan = $value['F'];
                    $kuanghou = $value['G'];
                    $height_start = $value['H'];
                    $height_end = $value['I'];
                    $height_rule = $value['J'];
                    $width_rule = $value['K'];
                    $boli_houdu = $value['L'];
                    $qpa1 = $value['M'];
                    $sql = "insert into BOM_BOLI_RULE(ID,DEPT_NAME,MENKUANG,DANGCI,CHUANGHUA,KAIXIANG,MENSHAN,KUANGHOU,HEIGHT_START,HEIGHT_END,HEIGHT_RULE,WIDTH_RULE,BOLI_HOUDU,QPA1,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$menkuang','$dangci','$chuanghua','$kaixiang','$menshan','$kuanghou','$height_start','$height_end','$height_rule','$width_rule','$boli_houdu','$qpa1','$sort','$sheet_name')";
                }
                if ($sort == 2) {
                    $dept_name = $value['A'];
                    $menkuang = $value['B'];
                    $menshan = $value['C'];
                    $chuanghua = $value['D'];
                    $xinghao = $value['E'];
                    $ch_xiao = $value['F'];
                    $qpa1 = $value['G'];
                    $ch_da = $value['H'];
                    $qpa2 = $value['I'];
                    $boli_houdu = $value['J'];
                    $sql = "insert into BOM_BOLI_RULE(ID,DEPT_NAME,MENKUANG,MENSHAN,CHUANGHUA,XINGHAO,CH_XIAO,QPA1,CH_DA,QPA2,BOLI_HOUDU,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$menkuang','$menshan','$chuanghua','$xinghao','$ch_xiao','$qpa1','$ch_da','$qpa2','$boli_houdu','$sort','$sheet_name')";
                }
                if ($sort == 3) {
                    $dept_name = $value['A'];
                    $huase = $value['B'];
                    $menshan = $value['C'];
                    $kaixiang = $value['D'];
                    $chuanghua = $value['E'];
                    $height_start = $value['F'];
                    $height_end = $value['G'];
                    $width_start = $value['H'];
                    $width_end = $value['I'];
                    $width_gg = $value['J'];
                    $boli_height = $value['K'];
                    $boli_width_m = $value['L'];
                    $boli_width_z = $value['M'];
                    $boli_width_mz = $value['N'];
                    $boli_width_zz = $value['O'];
                    $boli_houdu = $value['P'];
                    $qpa1 = $value['Q'];
                    $boli_type = $value['R'];
                    $xishu1 = $value['S'];
                    $xishu2 = $value['T'];
                    $sql = "insert into BOM_BOLI_RULE(ID,DEPT_NAME,HUASE,MENSHAN,KAIXIANG,CHUANGHUA,HEIGHT_START,HEIGHT_END,WIDTH_START,WIDTH_END,WIDTH_GG,BOLI_HEIGHT,BOLI_WIDTH_M,BOLI_WIDTH_Z,BOLI_WIDTH_MZ,BOLI_WIDTH_ZZ,BOLI_HOUDU,QPA1,BOLI_TYPE,XISHU1,XISHU2,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$huase','$menshan','$kaixiang','$chuanghua','$height_start','$height_end','$width_start','$width_end','$width_gg','$boli_height','$boli_width_m','$boli_width_z','$boli_width_mz','$boli_width_zz','$boli_houdu','$qpa1','$boli_type','$xishu1','$xishu2','$sort','$sheet_name')";
                }
                if ($sort == 4) {
                    $dept_name = $value['A'];
                    $dangci = $value['B'];
                    $menshan = $value['C'];
                    $kaixiang = $value['D'];
                    $mumen_qpa = $value['E'];
                    $zimen_qpa = $value['F'];
                    $liaohao = $value['G'];
                    $pinming = $value['H'];
                    $guige = $value['I'];
                    $sql = "insert into BOM_BOLI_RULE(ID,DEPT_NAME,DANGCI,MENSHAN,KAIXIANG,QPA1,QPA2,LIAOHAO,PINMING,GUIGE,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$dangci','$menshan','$kaixiang','$mumen_qpa','$zimen_qpa','$liaohao','$pinming','$guige','$sort','$sheet_name')";
                }
                Db::execute($sql);
            }
        }
        return;
    }
}