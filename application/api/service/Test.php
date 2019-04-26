<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/14
 * Time: 13:55
 */

namespace app\api\test;

use Think\Db;

class Test
{
    public function getImportExcelDate()
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        header("content-type:text/html; charset=utf-8");
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', '-1');
        $excel_file = request()->file('file');
        if (empty($excel_file)) {
            return retmsg(-1, null, '没有获取到文件,请从新导入');
        }
        $extension = substr(strrchr($_FILES["file"]["name"], '.'), 1);
        if ($extension != 'xls' && $extension != 'xlsx') {
            return retmsg(-1, null, '获取文件错误,请重新选择要导入的EXCEL文件');
        }
        $file_path = $this->moveToUpload($excel_file);
        $data = $this->handleExcelFile($file_path);
        unlink($file_path);//删除临时文件
        return $data;
    }

    public function moveToUpload($request_file)
    {
        if (!empty($request_file)) {
            //处理文件逻辑,移动到uploads目录下
            $move_files = $request_file->move(ROOT_PATH . 'public' . DS . 'upload' . DS);
            if ($move_files) {
                $file_path = ROOT_PATH . 'public' . DS . 'upload' . DS . $move_files->getSaveName();
            } else {
                return retmsg(-1, $request_file->getError(), '移动文件失败');
            }
        }
        return $file_path;
    }

    public function handleExcelFile($file_path)
    {
        $all_data = array();  #存放返回的所有数据
        vendor('PHPExcel.Classes.PHPExcel');
        //获取文件后缀,根据后缀来实例化不同的对象
        $file_suffix = pathinfo($file_path)['extension'];
        switch ($file_suffix) {
            case 'xlsx':
                $obj_reader = \PHPExcel_IOFactory::createReader('Excel2007');
                $php_excel = $obj_reader->load($file_path, $encode = 'utf-8');
                break;
            case 'xls':
                $obj_reader = \PHPExcel_IOFactory::createReader('Excel5');
                $php_excel = $obj_reader->load($file_path, $encode = 'utf-8');
                break;
            default:
                return retmsg(-1, '', '文件类型不符合要求');
        }
        $sheet_count = $php_excel->getSheetCount();
        $type_names = $php_excel->getSheetNames();
        for ($i = 0; $i < $sheet_count; $i++) {
            $currentSheet = $php_excel->getSheet($i);
            $highestRow = $currentSheet->getHighestRow();
            $highestCol = $currentSheet->getHighestColumn();
            for ($currentRow = 2; $currentRow <= $highestRow; $currentRow++) {
                for ($currentCol = 'A'; $currentCol <= $highestCol; $currentCol++) {
                    $address = $currentCol . $currentRow;
                    $cell = $currentSheet->getCell($address)->getValue();
                    //把富文本转换成string格式
                    if ($cell instanceof \PHPExcel_RichText) {
                        $cell = $cell->__toString();
                    }
                    //将单个表格中的数据保存在数组中
                    $all_data[$i][$currentRow - 2][$currentCol] = $cell;
                }
            }
        }
        return [$all_data, $type_names];
    }

    public function deleteTable($table, $type = '')
    {
        # 导入之前先清理数据
        $whereStr = empty($type) ? '' : " where type = $type ";
        Db::execute("delete from $table $whereStr");
        return;
    }

    public function getExportAllData($table, $type = '')
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        $all_data_list = $this->getExportData($table, $type);
        return $all_data_list;
    }

    public function handleDataToExcel($all_data_list, $indexArr, $addHeardArr, $heardArr)
    {
        vendor('PHPExcel.Classes.PHPExcel');
        $objExecl = new \PHPExcel();
        ob_end_clean();
        foreach ($all_data_list as $key => $value) {
            $indexKey = $indexArr[$key];
            $heardValue = $heardArr[$key];
            $heard = $addHeardArr[$key];
            array_unshift($all_data_list[$key], $heard);
            //写入数据到表格中
            $objExecl->createSheet();//创建工作簿新的表空间
            $currentSheet = $objExecl->setActiveSheetIndex($key); //当前工作表
            $objExecl->setActiveSheetIndex($key)->setTitle($all_data_list[$key][1]['SHEET_NAME']);
            $this->addDataToExcel($all_data_list[$key], $currentSheet, $indexKey, $heardValue, $key);
        }
        $objExecl->setActiveSheetIndex(0);  //设置导出 默认打开第一个sheet
    }

    public function getExportData($table, $type)
    {
        $sheet = array();
        $whereStr = empty($type) ? '' : " where type = $type ";
        $sql = "select * from $table  $whereStr order by id asc";
        $all_data = Db::query($sql);
        foreach ($all_data as $key => $value) {
            if ($all_data[$key]['SORT'] == 1) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 2) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 3) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 4) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 5) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 6) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 7) {
                array_push($sheet[$key], $all_data[$key]);
            } elseif ($all_data[$key]['SORT'] == 8) {
                array_push($sheet[$key], $all_data[$key]);
            }
        }
        return $sheet;
    }

    public function addDataToExcel($data, $objActSheet, $indexKey, $heard_arr, $key)
    {
        $endLetter = end($heard_arr);
        $startRow = 1;
        $objActSheet->getStyle("A1:$endLetter")->getFont()->setBold(true);
        $objActSheet->freezePane('A2');
        $objActSheet->getStyle("A1:$endLetter")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        foreach ($data as $key => $value) {
            foreach ($indexKey as $k => $v) {
                $objActSheet->setCellValue($heard_arr[$k] . $startRow, $data[$key][$v]);
//                $objActSheet->getStyle($heard_arr[$k] . $startRow)->getFont()->setSize(9);
            }
            $startRow++;
        }
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
}