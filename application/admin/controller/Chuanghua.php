<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:47
 */

namespace app\admin\controller;

use app\admin\service\ImportExcelService;
use think\Db;
use think\controller\Rest;
use think\Loader;

/**
 * 窗花规则维护---不锈钢窗花
 * Class Chuanghua
 * @package app\admin\controller
 */
class Chuanghua extends Rest
{
    private $table;
    private $table_tag;

    /**
     * 获取不锈钢窗花的类型
     */
    public function getChuanghuaType($type)
    {
        # 不锈钢表头字段体现
        if ($this->table_tag == '不锈钢') {
            $arr = array(
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'gaokuandu', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'gaokuandu', 'cailiao_width', 'num', 'liaohao', 'pinming', 'guige'),//'gaokuandu','yongliao' 对应下料规格和材料长度
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'qpa', 'liaohao', 'pinming', 'guige')
            );
            $classify1 = array('上下边框', '左右边框', '中档', '圆管A', '圆管B', '圆管C', '圆管D', '竖条');
            $classify2 = array('红色装饰管');
            $classify3 = array('圆管卡件', '灯笼福', '灯笼福卡件', '红心装饰件', '装饰管卡件', '福字', '福字件');
            if (in_array($type, $classify1)) {
                return $arr[0];
            } elseif (in_array($type, $classify2)) {
                return $arr[1];
            } elseif (in_array($type, $classify3)) {
                return $arr[2];
            }
        } elseif ($this->table_tag == '常规') {
            $arr = array(
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_width', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_height', 'chuanghua_width', 'a_value', 'jiaodu', 'c_value', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_height', 'chuanghua_width', 'a_value', 'yongliao1', 'n_value', 'jiaodu', 'c_value', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_height', 'chuanghua_width', 'a_value', 'yongliao1', 'n_value', 'jiaodu', 'yongliao2', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_height', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
				array('dept','chuanghua_type','start_height','end_height','start_width','end_width',
                    'num','liaohao','pinming','guige')
            );
            $classify1 = array('上下边框','中档');//窗花宽度
            $classify2 = array('花筋1');
            $classify3 = array('花筋2','花筋3');
            $classify4 = array('斜筋1','斜筋2');
            $classify5 = array('左右边框','竖筋01','竖筋02','竖筋03','横筋01','横筋02');//窗花高度
            $classify6 = array('圆花');//新增的圆花
            if (in_array($type, $classify1)) {
                return $arr[0];
            } elseif (in_array($type, $classify2)) {
                return $arr[1];
            } elseif (in_array($type, $classify3)) {
                return $arr[2];
            } elseif (in_array($type, $classify4)) {
                return $arr[3];
            } elseif (in_array($type, $classify5)) {
                return $arr[4];
            } elseif (in_array($type, $classify6)) {
                return $arr[5];
            }
        } elseif ($this->table_tag == '中式') {
            $arr = array(
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_width', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_height', 'yongliao', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width',
                    'chuanghua_height', 'chuanghua_width', 'yongliao1', 'yongliao2', 'yongliao3', 'cailiao_width', 'cailiao_houdu', 'cailiao_midu', 'num', 'liaohao', 'pinming', 'guige'),
                array('dept', 'chuanghua_type', 'start_height', 'end_height', 'start_width', 'end_width', 'num', 'liaohao', 'pinming', 'guige')
            );
            $classify1 = array('上下边框', '横筋01', '横筋02');//窗花宽度
            $classify2 = array('左右边框', '竖筋01', '竖筋02');
            $classify3 = array('斜筋01', '斜筋02');
            $classify4 = array('方花', '圆花');//窗花高度
            if (in_array($type, $classify1)) {
                return $arr[0];
            } elseif (in_array($type, $classify2)) {
                return $arr[1];
            } elseif (in_array($type, $classify3)) {
                return $arr[2];
            } elseif (in_array($type, $classify4)) {
                return $arr[3];
            }
        }
    }

    public function getChuanghuaTag($chuanghua)
    {
        if (strpos($chuanghua, '不锈钢窗花') !== false) {
            $this->table = 'bom_chuanghua_bxg_rule';
            $this->table_tag = '不锈钢';
        } elseif (strpos($chuanghua, '中式窗花') !== false) {
            $this->table = 'bom_chuanghua_zs_rule';
            $this->table_tag = '中式';
        } else {
            $this->table = 'bom_chuanghua_cg_rule';
            $this->table_tag = '常规';
        }
    }

    /**
     * 不锈钢窗花的批量导入处理
     */
    public function importExcel($chuanghua = '', $uploadfile = '', $titles, $objPHPExcel)
    {
//        echo $chuanghua."   ".$path;
//        return;
        # 上传文件大小超过2M---php.ini upload_max_filesize 配置值
        /*ini_set('upload_max_filesize', "5M");//上传文件的大小为5M
        ini_set('max_execution_time', 0);//运行时间无限制
        ini_set('memory_limit', -1);//设置内存无限制*/
        $this->getChuanghuaTag($chuanghua);
//        set_time_limit(0);
//        vendor("PHPExcel.Classes.PHPExcel");
//        $file = $_FILES['file'] ['name'];
//        $filetempname = $_FILES ['file']['tmp_name'];
//        $filePath = str_replace('\\','/',realpath(__DIR__.'/../../../')).'/upload/';
//        $filename = explode(".", $file);
//        $time = date("YmdHis");
//        $filename [0] = $time;//取文件名t替换
//        $name = implode(".", $filename); //上传后的文件名
//        $uploadfile = $filePath . $name;
//        $flag=move_uploaded_file($filetempname,$uploadfile);
//        if ($flag) {
        /*try {
            // 载入excel文件
            vendor("PHPExcel.Classes.PHPExcel");
            $objPHPExcel = \PHPExcel_IOFactory::load($uploadfile);
        } catch (\PHPExcel_Reader_Exception $e) {
            return $this->response(retmsg(-1, null, $e->__toString()), 'json');
        }*/
//            unlink($uploadfile);//删除临时文件
//            $sheets = $objPHPExcel->getAllSheets();
//            if (empty($sheets)) {
//                return $this->response(retmsg(-1, null, "无法读取此Excel!"), 'json');
//            }
//            $titles = $objPHPExcel->getSheetNames(0);
        Db::startTrans();
        $table = $this->table;
        # 写入数据之前将数据表全部清空处理
        Db::execute("delete from $table");
        $totalCount = 0;
        foreach ($titles as $ktype => $vtype) {
            # 导入不同类型的数据
            $data = $this->getTypeIndexData($objPHPExcel, $ktype, $vtype);
            # 数据逐条写入
            foreach ($data as $key => $val) {
                $insertColumns = 'id,';
                $valueStr = 'bom_chuanghua_id.nextval,';
                if (empty($val['chuanghua_type'])) {//空行数据不导入数据库
                    continue;
                }
                # 值域拼接
                foreach ($val as $k => $v) {
                    $insertColumns .= "$k,";
                    $dataType = $this->getFieldType($k);
                    if ($dataType == 'NUMBER') {
                        $valueStr .= "'$v',";
                    } else {
                        $valueStr .= "'$v',";
                    }
//                        $valueStr .= "'$v',";
                }
                # 新增一个sort排序字段，方便后期导出进行排序
                $paixu = $ktype + 1;
                $valueStr .= "'$vtype',$paixu";
                $insertColumns .= "type,sort";
                $insertSql = "insert into $table($insertColumns) values ($valueStr)";
//                    echo $insertSql." \n";
                Db::execute($insertSql);
                $totalCount++;
            }
        }
        Db::commit();
//            return retmsg(0,null,"导入成功,共计 $totalCount 条");
        return $totalCount;
//        }
    }

    /**
     * @param $type获取某某一张类型表的数据
     */
    public function getTypeIndexData($objPHPExcel, $typeIndex, $type)
    {
        $sheet = $objPHPExcel->getSheet($typeIndex); // 读取第一工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数字符串
        $highestColumm = \PHPExcel_Cell::columnIndexFromString($highestColumm);
        $index = array("A", "B", "C", "D", "E", "F", "G", "H", "I",
            "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB");
        for ($i = 2; $i <= $highestRow; $i++) {
            $arr = array();
            for ($j = 1; $j <= $highestColumm; $j++) {
                $arr[] = $sheet->getCell($index[$j - 1] . $i)->getValue();
            }
            $data[] = $arr;
        }
        $res = $this->dataRebuild($data, $type);
        return $res;
    }

    /**
     * 数据重组，根据不同类型数据---匹配数据库存储的对应字段
     * @param $data
     * @param $type
     */
    public function dataRebuild($data, $type)
    {
        $columnArr = $this->getChuanghuaType($type);
        $res = array();
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                foreach ($columnArr as $k => $v) {
                    $res[$key][$v] = $val[$k];
                }
            }
        }
        return $res;
    }

    /**
     * 获取数据库设置的字段的字段类型，数字类型的不能加引号入库
     */
    public function getFieldType($field)
    {
        $table = strtoupper($this->table);
        $match_field = strtoupper($field);
        $tableInfo = Db::query("select table_name,column_name,data_type from user_tab_columns where Table_Name='$table' and column_name='$match_field'");
        return $tableInfo[0]['DATA_TYPE'];
    }

    /**
     * @param string $sheet
     */
    public function getExportExcelData($sheet)
    {
        $this->getChuanghuaTag($sheet);//获取当前表及表标记
        $table = $this->table;
        # 1.查询当前窗花的各种类型，放置于excel的不同下标sheet
        $types = Db::query("select distinct(type),sort from $table order by sort", true);
        foreach ($types as $ktype => $vtype) {
            # 2.查询每一种类型的数据
            $type = $vtype['type'];
            $columns = $this->getChuanghuaType($type);
            $head = $this->getChuanghuaHead($type);
            $columnsString = implode(',', $columns);
            $list = Db::query("select $columnsString from $table where type='$type' order by id", true);
            $types[$ktype]['head'] = $head;
            $types[$ktype]['list'] = $list;
        }
        return $types;
    }

    public function exportExcel($sheet = '')
    {
        # 导出处理
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        # 第三方PHPexcel引入
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        //Excel列下标顺序
        $currentColumn = 'A';
        for ($i = 1; $i <= 26; $i++) {
            $a[] = $currentColumn++;
        }
        # 要导出的数据
        $printData = $this->getExportExcelData($sheet);
        # 数据渲染在excel，标题栏在第一行，数据行从第二行开始
        foreach ($printData as $key => $val) {
            $objPHPExcel->createSheet();//创建工作簿新的表空间
            $currentSheet = $objPHPExcel->setActiveSheetIndex($key);//当前工作表
            $currentSheet->setTitle($val['type']);
            # 标题栏渲染
            foreach ($val['head'] as $khead => $vhead) {
                $currentSheet->setCellValue($a[$khead] . '1', $vhead);
                # 标题栏字体加粗设置
                //加粗字体
                $objPHPExcel->getActiveSheet($key)->getStyle('A1:Z1')->applyFromArray(
                    array(
                        'font' => array(
                            'bold' => true
                        )
                    )
                );
            }
            # 数据列渲染
            foreach ($val['list'] as $klist => $vlist) {
                $index = -1;
                foreach ($vlist as $kk => $vv) {
                    $index++;//数量下标列 形如：0->A,1->B一一对应关系
                    $currentSheet->setCellValue($a[$index] . ($klist + 2), $vv);//数据列从第二行开始渲染
                }
            }
        }
        # 文件下载
        $this->downLoadExcel($objPHPExcel, $sheet);
    }

    public function downLoadExcel($objPHPExcel, $sheet)
    {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$sheet.xls");
        header('Cache-Control: max-age=0');
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save('php://output'); //文件通过浏览器下载
        return;
    }

    public function getChuanghuaHead($type)
    {
        # 不锈钢表头字段体现
        if ($this->table_tag == '不锈钢') {
            $arr = array(
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花宽度', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '下料规格', '材料长度', '数量', '料号', '品名', '规格'),//'gaokuandu','yongliao' 对应下料规格和材料长度
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    'QPA值', '料号', '品名', '规格')
            );
            $classify1 = array('上下边框', '左右边框', '中档', '圆管A', '圆管B', '圆管C', '圆管D', '竖条');
            $classify2 = array('红色装饰管');
            $classify3 = array('圆管卡件', '灯笼福', '灯笼福卡件', '红心装饰件', '装饰管卡件', '福字', '福字件');
            if (in_array($type, $classify1)) {
                return $arr[0];
            } elseif (in_array($type, $classify2)) {
                return $arr[1];
            } elseif (in_array($type, $classify3)) {
                return $arr[2];
            }
        } elseif ($this->table_tag == '常规') {
            $arr = array(
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花宽度', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花高度', '窗花宽度', 'A值', '角度', 'C值', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花高度', '窗花宽度', 'A值', '用料1', 'n值', '角度', 'C值', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花高度', '窗花宽度', 'A值', '用料1', 'n值', '角度', '用料2', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花高度', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
				array('制造部门','窗花类型','窗花高度左区间','窗花高度右区间','窗花宽度左区间','窗花宽度右区间',
                    '数量','料号','品名','规格')
            );
            $classify1 = array('上下边框','中档');//窗花宽度
            $classify2 = array('花筋1');
            $classify3 = array('花筋2','花筋3');
            $classify4 = array('斜筋1','斜筋2');
            $classify5 = array('左右边框','竖筋01','竖筋02','竖筋03','横筋01','横筋02');//窗花高度
            $classify6 = array('圆花');//新增的圆花
            if (in_array($type, $classify1)) {
                return $arr[0];
            } elseif (in_array($type, $classify2)) {
                return $arr[1];
            } elseif (in_array($type, $classify3)) {
                return $arr[2];
            } elseif (in_array($type, $classify4)) {
                return $arr[3];
            } elseif (in_array($type, $classify5)) {
                return $arr[4];
            } elseif (in_array($type, $classify6)) {
                return $arr[5];
            }
        } elseif ($this->table_tag == '中式') {
            $arr = array(
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花宽度', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花高度', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '窗花高度', '窗花宽度', '用料1', '用料2', '用料3', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '方花数量', '料号', '品名', '规格'),
                array('制造部门', '窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间',
                    '圆花数量', '料号', '品名', '规格')
            );
            $classify1 = array('上下边框', '横筋01', '横筋02');//窗花宽度
            $classify2 = array('左右边框', '竖筋01', '竖筋02');
            $classify3 = array('斜筋01', '斜筋02');
            $classify4 = array('方花');//窗花高度
            $classify5 = array('圆花');//窗花高度
            if (in_array($type, $classify1)) {
                return $arr[0];
            } elseif (in_array($type, $classify2)) {
                return $arr[1];
            } elseif (in_array($type, $classify3)) {
                return $arr[2];
            } elseif (in_array($type, $classify4)) {
                return $arr[3];
            } elseif (in_array($type, $classify5)) {
                return $arr[4];
            }
        }
    }


    public function importFbsChuangHua($type = '')
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        header("content-type:text/html; charset=utf-8");
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        ini_set('upload_max_filesize', "5M");//上传文件的大小为5M
        //获取文件上传信息
        $execl_file = request()->file('file');
        if (empty($execl_file)) {
            return retmsg(-1, '', '没有获取到文件');
        }
        $extension = substr(strrchr($_FILES["file"]["name"], '.'), 1);
        if ($extension != 'xls' && $extension != 'xlsx') {
            return retmsg(-1, null, '请上传Excel文件！');
        }
        $importService = Loader::model('ImportExcelService', 'service');
        $data = $importService->importExcel($execl_file);
        $res = $this->handleImportData($data, $type);
        return $res;
    }

    public function exportFbsChuangHua($type = '')
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        $all_data_list = $this->getExportData($type);
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        //导入插件
        vendor('PHPExcel.Classes.PHPExcel');
        $objExecl = new \PHPExcel();
        ob_end_clean();    //擦除缓冲区
        $heard_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R');
        //构造表头字段,添加在list数组之前,组成完整数组
        foreach ($all_data_list as $key => $value) {
            $indexKey = $this->indexKey($type, $key);
            $all_data_list[$key] = $this->addHeadToData($all_data_list[$key], $type, $key);
            //写入数据到表格中
            $objExecl->createSheet();//创建工作簿新的表空间
            $currentSheet = $objExecl->setActiveSheetIndex($key); //当前工作表
            $objExecl->setActiveSheetIndex($key)->setTitle($all_data_list[$key][1]['SHEET_NAME']);
            $this->addDataToExcel($all_data_list[$key], $currentSheet, $indexKey, $heard_arr, $type, $key);
        }
        $objExecl->setActiveSheetIndex(0);  //设置导出 默认打开第一个sheet
        $fileName = $type == 1 ? '成都封闭式窗花用料规则' : '齐河封闭式窗花用料规则';
        $this->downLoadExcel($objExecl, $fileName);
    }

    public function handleImportData($all_data, $type = '')
    {
        # 导入之前先清理数据
        Db::execute("delete from BOM_CHUANGHUA_FBS WHERE TYPE ='$type'");
        $data = $all_data[0];
        $fileName = $all_data[1];
        if ($type == 1) {
            $res = $this->handleChengduFbs($data, $fileName, $type);
        } elseif ($type == 2) {
            $res = $this->handleQiheFbs($data, $fileName, $type);
        }
        if (!empty($res)) {
            return retmsg(0, '', '导入成功');
        } else {
            return retmsg(-1, '', '导入失败');
        }
    }

    public function handleChengduFbs($data, $fileName, $type)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if (strpos($fileName[$k], '铁卷') !== false) {
                $arr = ['sort' => 1, 'type' => '铁卷'];
                $names[$k] = $arr;
            } elseif (strpos($fileName[$k], '蜂窝纸') !== false) {
                $arr = ['sort' => 2, 'type' => '蜂窝纸'];
                $names[$k] = $arr;
            }
        }
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                    if (empty($data[$j][$key]['A'])) {
                        continue;
                    }
                    $sheet_name = $names[$j]['type'];
                    $sort = $names[$j]['sort'];
                    if ($sort == 1) {
                        $dept_name = $data[$j][$key]['A'];
                        $chuanghua_type = $data[$j][$key]['B'];
                        $width_start = $data[$j][$key]['C'];
                        $width_end = $data[$j][$key]['D'];
                        $qpa = $data[$j][$key]['E'];
                        $liaohao = $data[$j][$key]['F'];
                        $pinming = $data[$j][$key]['G'];
                        $guige = $data[$j][$key]['H'];
                        $sql = "insert into BOM_CHUANGHUA_FBS(id,DEPT_NAME,CHUANGHUA_TYPE,WIDTH_START,WIDTH_END,QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$chuanghua_type','$width_start','$width_end','$qpa','$liaohao','$pinming','$guige','$type','$sort','$sheet_name')";
                    } elseif ($sort == 2) {
                        $dept_name = $data[$j][$key]['A'];
                        $chuanghua_type = $data[$j][$key]['B'];
                        $menshan_type = $data[$j][$key]['C'];
                        $qpa = $data[$j][$key]['D'];
                        $liaohao = $data[$j][$key]['E'];
                        $pinming = $data[$j][$key]['F'];
                        $guige = $data[$j][$key]['G'];
                        $sql = "insert into BOM_CHUANGHUA_FBS(id,DEPT_NAME,CHUANGHUA_TYPE,MENSHAN_TYPE,QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$chuanghua_type','$menshan_type','$qpa','$liaohao','$pinming','$guige','$type',$sort,'$sheet_name')";
                    }
                $res = Db::execute($sql);
            }
        }
        return $res;
    }

    public function handleQiheFbs($data, $fileName, $type)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if (strpos($fileName[$k], '上下边框') !== false) {
                $arr = ['sort' => 1, 'type' => '上下边框'];
                $names[$k] = $arr;
            } elseif (strpos($fileName[$k], '左右边框') !== false) {
                $arr = ['sort' => 2, 'type' => '左右边框'];
                $names[$k] = $arr;
            } elseif (strpos($fileName[$k], '竖筋') !== false) {
                $arr = ['sort' => 3, 'type' => '竖筋'];
                $names[$k] = $arr;
            } elseif (strpos($fileName[$k], '面板') !== false) {
                $arr = ['sort' => 4, 'type' => '面板'];
                $names[$k] = $arr;
            }
        }
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {

                    if (empty($data[$j][$key]['A'])) {
                        continue;
                    }
                    $sheet_name = $names[$j]['type'];
                    $sort = $names[$j]['sort'];
                    if ($sort == 1 || $sort == 2 || $sort == 3) {
                        $dept_name = $data[$j][$key]['A'];
                        $chuanghua_type = $data[$j][$key]['B'];
                        $height_start = $data[$j][$key]['C'];
                        $height_end = $data[$j][$key]['D'];
                        $width_start = $data[$j][$key]['E'];
                        $width_end = $data[$j][$key]['F'];
                        $chuanghua_width = $data[$j][$key]['G'];
                        $yongliao = $data[$j][$key]['H'];
                        $cailiao_width = $data[$j][$key]['I'];
                        $cailiao_hd = $data[$j][$key]['J'];
                        $cailiao_md = $data[$j][$key]['K'];
                        $shuliang = $data[$j][$key]['L'];
                        $qpa = '';
                        $liaohao = $data[$j][$key]['N'];
                        $pinming = $data[$j][$key]['O'];
                        $guige = $data[$j][$key]['P'];
                        if ($sort == 1) {
                            $sql = "insert into BOM_CHUANGHUA_FBS(id,DEPT_NAME,CHUANGHUA_TYPE,HEIGHT_START,HEIGHT_END,WIDTH_START,WIDTH_END,CHUANGHUA_WIDTH,YONGLIAO1,CAILIAO_WIDTH,CAILIAO_HD,CAILIAO_MD,SHULIANG,QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$chuanghua_type','$height_start','$height_end','$width_start','$width_end','$chuanghua_width','$yongliao','$cailiao_width','$cailiao_hd','$cailiao_md','$shuliang',
'$qpa','$liaohao','$pinming','$guige','$type','$sort','$sheet_name')";
                        } else {
                            $sql = "insert into BOM_CHUANGHUA_FBS(id,DEPT_NAME,CHUANGHUA_TYPE,HEIGHT_START,HEIGHT_END,WIDTH_START,WIDTH_END,CHUANGHUA_HEIGHT,YONGLIAO1,CAILIAO_WIDTH,CAILIAO_HD,CAILIAO_MD,SHULIANG,QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$chuanghua_type','$height_start','$height_end','$width_start','$width_end','$chuanghua_width','$yongliao','$cailiao_width','$cailiao_hd','$cailiao_md','$shuliang',
'$qpa','$liaohao','$pinming','$guige','$type','$sort','$sheet_name')";
                        }
                    } elseif ($sort == 4) {
                        $dept_name = $data[$j][$key]['A'];
                        $chuanghua_type = $data[$j][$key]['B'];
                        $height_start = $data[$j][$key]['C'];
                        $height_end = $data[$j][$key]['D'];
                        $width_start = $data[$j][$key]['E'];
                        $width_end = $data[$j][$key]['F'];
                        $chuanghua_height = $data[$j][$key]['G'];
                        $chuanghua_width = $data[$j][$key]['H'];
                        $yongliao1 = $data[$j][$key]['I'];
                        $yongliao2 = $data[$j][$key]['J'];
                        $cailiao_width = $data[$j][$key]['K'];
                        $cailiao_hd = $data[$j][$key]['L'];
                        $cailiao_md = $data[$j][$key]['M'];
                        $shuliang = $data[$j][$key]['N'];
                        $qpa = '';
                        $liaohao = $data[$j][$key]['P'];
                        $pinming = $data[$j][$key]['Q'];
                        $guige = $data[$j][$key]['R'];
                        $sql = "insert into BOM_CHUANGHUA_FBS(id,DEPT_NAME,CHUANGHUA_TYPE,HEIGHT_START,HEIGHT_END,WIDTH_START,WIDTH_END,CHUANGHUA_HEIGHT,CHUANGHUA_WIDTH,YONGLIAO1,YONGLIAO2,CAILIAO_WIDTH,CAILIAO_HD,CAILIAO_MD,SHULIANG,QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$dept_name','$chuanghua_type','$height_start','$height_end','$width_start','$width_end','$chuanghua_height','$chuanghua_width','$yongliao1','$yongliao2','$cailiao_width','$cailiao_hd','$cailiao_md','$shuliang',
'$qpa','$liaohao','$pinming','$guige','$type','$sort','$sheet_name')";
                    }
                $res = Db::execute($sql);
            }
        }
        return $res;
    }

    public function getExportData($type)
    {
        $sheet1 = [];
        $sheet2 = [];
        $sheet3 = [];
        $sheet4 = [];
        $sql1 = "select * from BOM_CHUANGHUA_FBS where type='$type' order by id asc";
        $all_data = Db::query($sql1);
        if ($type == 1) {
            foreach ($all_data as $key => $value) {
                if ($all_data[$key]['SORT'] == 1) {
                    array_push($sheet1, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 2) {
                    array_push($sheet2, $all_data[$key]);
                }
            }
            $res = [$sheet1, $sheet2];
        } elseif ($type == 2) {
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
        }
        return $res;
    }

    public function indexKey($type, $key)
    {
        if ($type == 1) {
            if ($key == 0) {
                $indexKey = array('DEPT_NAME', 'CHUANGHUA_TYPE', 'WIDTH_START', 'WIDTH_END', 'QPA', 'LIAOHAO',
                    'PINMING', 'GUIGE');
            } elseif ($key == 1) {
                $indexKey = array('DEPT_NAME', 'CHUANGHUA_TYPE', 'MENSHAN_TYPE', 'QPA', 'LIAOHAO',
                    'PINMING', 'GUIGE');
            }
        } elseif ($type == 2) {
            if ($key == 0) {
                $indexKey = array('DEPT_NAME', 'CHUANGHUA_TYPE', 'HEIGHT_START', 'HEIGHT_END', 'WIDTH_START', 'WIDTH_END', 'CHUANGHUA_WIDTH', 'YONGLIAO1', 'CAILIAO_WIDTH', 'CAILIAO_HD', 'CAILIAO_MD', 'SHULIANG', 'QPA', 'LIAOHAO',
                    'PINMING', 'GUIGE');
            } elseif ($key == 1 || $key == 2) {
                $indexKey = array('DEPT_NAME', 'CHUANGHUA_TYPE', 'HEIGHT_START', 'HEIGHT_END', 'WIDTH_START', 'WIDTH_END', 'CHUANGHUA_HEIGHT', 'YONGLIAO1', 'CAILIAO_WIDTH', 'CAILIAO_HD', 'CAILIAO_MD', 'SHULIANG', 'QPA', 'LIAOHAO', 'PINMING', 'GUIGE');
            } elseif ($key == 3) {
                $indexKey = array('DEPT_NAME', 'CHUANGHUA_TYPE', 'HEIGHT_START', 'HEIGHT_END', 'WIDTH_START', 'WIDTH_END', 'CHUANGHUA_HEIGHT', 'CHUANGHUA_WIDTH', 'YONGLIAO1', 'YONGLIAO2', 'CAILIAO_WIDTH', 'CAILIAO_HD', 'CAILIAO_MD', 'SHULIANG', 'QPA', 'LIAOHAO', 'PINMING', 'GUIGE');
            }
        }
        return $indexKey;
    }

    public function addHeadToData($data, $type, $key)
    {
        if ($type == 1) {
            if ($key == 0) {
                $heard = array('DEPT_NAME' => '部门', 'CHUANGHUA_TYPE' => '窗花类型', 'WIDTH_START' => '窗花宽度区间', 'WIDTH_END' => '窗花宽度区间',
                    'QPA' => 'QPA用量', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格');
            } elseif ($key == 1) {
                $heard = array('DEPT_NAME' => '部门', 'CHUANGHUA_TYPE' => '窗花类型', 'MENSHAN_TYPE' => '门扇类型',
                    'QPA' => 'QPA用量', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格');
            }
        } elseif ($type == 2) {
            if ($key == 0) {
                $heard = array('DEPT_NAME' => '部门', 'CHUANGHUA_TYPE' => '窗花类型', 'HEIGHT_START' => '窗花高度区间', 'HEIGHT_END' => '窗花高度区间', 'WIDTH_START' => '窗花宽度区间', 'WIDTH_END' => '窗花宽度区间', 'CHUANGHUA_WIDTH' => '窗花宽度', 'YONGLIAO1' => '用料', 'CAILIAO_WIDTH' => '材料宽度', 'CAILIAO_HD' => '材料厚度', 'CAILIAO_MD' => '材料密度', 'SHULIANG' => '数量',
                    'QPA' => 'QPA用量', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格');
            } elseif ($key == 1 || $key == 2) {
                $heard = array('DEPT_NAME' => '部门', 'CHUANGHUA_TYPE' => '窗花类型', 'HEIGHT_START' => '窗花高度区间', 'HEIGHT_END' => '窗花高度区间', 'WIDTH_START' => '窗花宽度区间', 'WIDTH_END' => '窗花宽度区间', 'CHUANGHUA_HEIGHT' => '窗花高度', 'YONGLIAO1' => '用料', 'CAILIAO_WIDTH' => '材料宽度', 'CAILIAO_HD' => '材料厚度', 'CAILIAO_MD' => '材料密度', 'SHULIANG' => '数量',
                    'QPA' => 'QPA用量', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格');
            } elseif ($key == 3) {
                $heard = array('DEPT_NAME' => '部门', 'CHUANGHUA_TYPE' => '窗花类型', 'HEIGHT_START' => '窗花高度区间', 'HEIGHT_END' => '窗花高度区间', 'WIDTH_START' => '窗花宽度区间', 'WIDTH_END' => '窗花宽度区间', 'CHUANGHUA_HEIGHT' => '窗花高度', 'CHUANGHUA_WIDTH' => '窗花宽度', 'YONGLIAO1' => '用料1', 'YONGLIAO2' => '用料2', 'CAILIAO_WIDTH' => '材料宽度', 'CAILIAO_HD' => '材料厚度', 'CAILIAO_MD' => '材料密度', 'SHULIANG' => '数量',
                    'QPA' => 'QPA用量', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格');
            }
        }
        array_unshift($data, $heard);
        return $data;
    }

    public function addDataToExcel($data, $objActSheet, $indexKey, $heard_arr, $type, $key)
    {
        $startRow = 1;
        $objActSheet->getStyle('A1:R1')->getFont()->setBold(true);
        $objActSheet->freezePane('A2');
        if ($type == 1) {
            if ($key == 0) {
                $objActSheet->mergeCells("C1:D1");
            }
            $objActSheet->getStyle("A1:H1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        if ($type == 2) {
            $objActSheet->mergeCells("C1:D1");
            $objActSheet->mergeCells("E1:F1");
            $objActSheet->getStyle("A1:R1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        foreach ($data as $key => $value) {
            foreach ($indexKey as $k => $v) {
                $objActSheet->setCellValue($heard_arr[$k] . $startRow, $data[$key][$v]);
//                $objActSheet->getStyle($heard_arr[$k] . $startRow)->getFont()->setSize(9);
            }
            $startRow++;
        }
    }
}