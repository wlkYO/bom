<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/29
 * Time: 17:22
 */

namespace app\admin\controller;

use think\Db;

# 窗花成本计算---窗花平方值导入
class ChuanghuaPrice
{
    /**
     * 窗花成本计算
     */
    public function importExcel()
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
            //删除表数据，采用覆盖式导入
            $delSql = "delete from bom_chuanghua_price";
            $res = Db::execute($delSql);
            if ($res) {
                DB::commit();
            } else {
                DB::rollback();
            }
            //获取列值
            $highestRow = $sheet->getHighestRow();
            $total = 0;//预计插入的总记录数
            $nowtime = date('Y-m-d H:i:s');
            $user = $GLOBALS['uname'];
            for ($j = 2; $j <= $highestRow; $j++) {
                $chuanghua_type = $sheet->getCell('A' . $j)->getValue();
                $start_width = $sheet->getCell('B' . $j)->getValue();
                $end_width = $sheet->getCell('C' . $j)->getValue();
                $price = $sheet->getCell('D' . $j)->getValue();
                $zhizaobm = $sheet->getCell('E' . $j)->getValue();
                if (!empty($chuanghua_type)) {
                    $sql = "insert into bom_chuanghua_price(chuanghua_type,start_width,end_width,price,created_time,creater,zhizaobm,id) values ('$chuanghua_type','$start_width','$end_width','$price',to_date('$nowtime','yyyy-mm-dd hh24:mi:ss'),'$user','$zhizaobm',BOM_CHUANGHUA_ID.nextval)";
                    Db::execute($sql);
                }
            }
            unlink($uploadfile);//删除临时文件
            return retmsg(0, null, "导入成功");
        }
    }

    # 窗花成本计算---窗花平方值导出
    public function exportExcel()
    {
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        vendor("PHPExcel.Classes.PHPExcel");
        $objPhpExcel = new \PHPExcel();
        $str = " CHUANGHUA_TYPE,START_WIDTH,END_WIDTH,PRICE,ZHIZAOBM";
        $data = Db::query("select $str from BOM_CHUANGHUA_PRICE order by id");
        $objPhpExcel->setActiveSheetIndex()->setCellValue("A1", "窗花类型");
        $objPhpExcel->setActiveSheetIndex()->setCellValue("B1", "宽度区间");
        $objPhpExcel->setActiveSheetIndex()->setCellValue("C1", "宽度区间");
        $objPhpExcel->setActiveSheetIndex()->setCellValue("D1", "元/平方");
        $objPhpExcel->setActiveSheetIndex()->setCellValue("E1", "部门");
        $objPhpExcel->getActiveSheet()->mergeCells("B1:C1");
        $objPhpExcel->getActiveSheet()->setTitle("窗花平方定价区间维护表");
        $objPhpExcel->getActiveSheet()->getStyle("A1:E1")->getFont()->setBold(true);
        $objPhpExcel->getActiveSheet()->freezePane('A2');
        $objPhpExcel->getActiveSheet()->getStyle("A1:E1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        foreach ($data as $key => $value) {
            $hang = $key + 2;
            $objPhpExcel->setActiveSheetIndex()->setCellValue("A" . $hang, $value['CHUANGHUA_TYPE']);
            $objPhpExcel->setActiveSheetIndex()->setCellValue("B" . $hang, $value['START_WIDTH']);
            $objPhpExcel->setActiveSheetIndex()->setCellValue("C" . $hang, $value['END_WIDTH']);
            $objPhpExcel->setActiveSheetIndex()->setCellValue("D" . $hang, $value['PRICE']);
            $objPhpExcel->setActiveSheetIndex()->setCellValue("E" . $hang, $value['ZHIZAOBM']);
        }
        $fileName = "窗花成本单价";
        $this->downLoad($objPhpExcel, $fileName);
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

    public function getChuangHuaPrice($type = '', $width = '')
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        if (!empty($type) && empty($width)) {
            $str = "where chuanghua_type like '%$type%'";
        } elseif (!empty($type) && !empty($width)) {
            $str = "where chuanghua_type like '%$type%' and $width between start_width and end_width";
        } elseif (empty($type) && !empty($width)) {
            $str = "where $width between start_width and end_width";
        }
        $sql = "select chuanghua_type,start_width,end_width,price from bom_chuanghua_price $str";
        $priceRes = Db::query($sql);
        if (empty($priceRes)) {
            return retmsg(-1, '', '查询失败');
        }
        return retmsg(0, $priceRes, '查询成功');
    }
}