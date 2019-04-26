<?php
/**
 * Created by PhpStorm.
 * User: 000
 * Date: 2019/1/15
 * Time: 14:23
 */

namespace app\admin\service;


use function PHPSTORM_META\type;


class ImportExcelService
{

    /**
     * 文件导入处理 ,返回导入的所有数据
     * @param $execl_file
     * @return array
     */
    public function importExcel($execl_file)
    {
        $file_path = $this->moveToUpload($execl_file);
        if (!empty($file_path)) {
            $data = $this->handleExcelFile($file_path);
            unlink($file_path);//删除临时文件
            return $data;
        } else {
            return '';
        }
    }

    /**
     * 处理获取到的文件移动到uploads下，返回路径
     * @param $request_file 获取到的文件
     * @return array|string 返回移动后的路径
     */
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

    /**
     * 处理表格中的数据返回所有数据组成的数组
     * @param $file_path 文件路径
     * @return array  文件中的数据,三维数组,如果只有一个sheet,遍历的时候选择 array[0]
     */
    public function handleExcelFile($file_path)
    {
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
        //获取sheet的个数,遍历每个sheet的数据
        $sheet_count = $php_excel->getSheetCount();
        $all_data = [];     //用于保存表中的数据
        $type_names  =$php_excel->getSheetNames();   //存放sheet的名字
        for ($i = 0; $i < $sheet_count; $i++) {
            $currentSheet = $php_excel->getSheet($i);   //获取表中的第一个工作表

//                $now_sheet = $php_excel->setActiveSheetIndex($i);//获得当前sheet
            $highestRow = $currentSheet->getHighestRow(); // 获取最大行
            $highestCol = $currentSheet->getHighestColumn();  //获取最大列
            //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从1开始，从第二行开始,去掉表头字段
            for ($currentRow = 2; $currentRow <= $highestRow; $currentRow++) {
                //从哪列开始，A表示第一列
                for ($currentCol = 'A'; $currentCol <= $highestCol; $currentCol++) {
                    //选中当前坐标
                    $address = $currentCol . $currentRow;
                    //读取数据
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
        return [$all_data,$type_names];
    }
}