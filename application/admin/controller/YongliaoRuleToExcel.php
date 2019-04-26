<?php
/**
 * Created by PhpStorm.
 * User: 000
 * Date: 2019/2/21
 * Time: 14:40
 */

namespace app\admin\controller;

use think\Db;
use think\Loader;


class YongliaoRuleToExcel
{
    /**
     * 处理防火门封边条用料规则 Execl 多sheet表格导入
     * @param $token
     * @return array
     */
    public function importExcel()
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

        $err_data = $this->handleImportData($data);
        return $err_data;
    }

    public function handleImportData($all_data)
    {
		# 导入之前先清理数据
		Db::execute("delete from BOM_FENGBIANT_RULE");
        $data = $all_data[0];
        $names = [];
        foreach ($all_data[1] as $k => $v) {
            if (strpos($all_data[1][$k], '母门锁方') !== false) {
                $arr = ['sort' => 1, 'type' => '母门锁方'];
                $names[$k] = $arr;
            } elseif (strpos($all_data[1][$k], '子门铰方') !== false) {
                $arr = ['sort' => 2, 'type' => '子门铰方'];
                $names[$k] = $arr;
            } elseif (strpos($all_data[1][$k], '子门锁方') !== false) {
                $arr = ['sort' => 3, 'type' => '子门锁方'];
                $names[$k] = $arr;
            } elseif (strpos($all_data[1][$k], '母门铰方') !== false) {
                $arr = ['sort' => 4, 'type' => '母门铰方'];
                $names[$k] = $arr;
            }
        }
        $err_data = [];  //存放错误信息
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                foreach ($data[$j][$key] as $k => $v) {
                    if (empty($data[$j][$key]['A'])) {
                        continue;
                    }

                    $type = $names[$j]['type'];
                    $sort = $names[$j]['sort'];

                    $ZHIZAOBM = $data[$j][$key]['A'];
                    $MENKUANG = $data[$j][$key]['B'];
                    $MENSHAN = $data[$j][$key]['C'];
                    $BIAOMCL = $data[$j][$key]['D'];
                    $BIAOMIANTSYQ = $data[$j][$key]['E'];
                    $DKCAILIAO = $data[$j][$key]['F'];
                    $GUIGE_GD = $data[$j][$key]['G'];
                    $YONGLIANG = $data[$j][$key]['H'];
                    $LIAOHAO = $data[$j][$key]['I'];
                    $PINMING = $data[$j][$key]['J'];
                    $GUIGE = $data[$j][$key]['K'];
                    $QPA = $data[$j][$key]['L'];
                    $MUMEN = '';
                    $ZIMEN = '';
                    $save_data = [
                        'ZHIZAOBM' => $data[$j][$key]['A'],
                        'MENKUANG' => $data[$j][$key]['B'],
                        'MENSHAN' => $data[$j][$key]['C'],
                        'BIAOMCL' => $data[$j][$key]['D'],

                        'BIAOMIANTSYQ' => $data[$j][$key]['E'],
                        'DKCAILIAO' => $data[$j][$key]['F'],
                        'GUIGE_GD' => $data[$j][$key]['G'],
                        'YONGLIANG' => $data[$j][$key]['H'],
                        'LIAOHAO' => $data[$j][$key]['I'],
                        'PINMING' => $data[$j][$key]['J'],

                        'GUIGE' => $data[$j][$key]['K'],
                        'QPA' => $data[$j][$key]['L'],
                        'MUMEN' => '',
                        'ZIMEN' => '',
                        'TYPE' => $type,
                        'SORT' => $sort
                    ];
                }
                $sql = "insert into BOM_FENGBIANT_RULE(id,ZHIZAOBM,MENKUANG,MENSHAN,BIAOMCL,BIAOMIANTSYQ,DKCAILIAO,GUIGE_GD,YONGLIANG,LIAOHAO,PINMING,GUIGE,MUMEN,ZIMEN,TYPE,SORT,QPA) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$ZHIZAOBM','/$MENKUANG/','/$MENSHAN/','$BIAOMCL','$BIAOMIANTSYQ','/$DKCAILIAO/','$GUIGE_GD','$YONGLIANG','$LIAOHAO','$PINMING','$GUIGE','$MUMEN','$ZIMEN','$type','$sort',$QPA)";
				$res = Db::execute($sql);
                if (empty($res)) {
                    array_push($err_data, $save_data);
                }
            }
        }
        $count = count($err_data);
        return retmsg(0, $err_data, '导入成功,导入失败' . $count . '条信息');
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
        $file_name = '防火门钢框木扇免漆板封边条用料规则' . '.xls';
        $indexKey = $this->indexKey();
        $heard_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        //构造表头字段,添加在list数组之前,组成完整数组
        foreach ($all_data_list as $key => $value) {
            $all_data_list[$key] = $this->addHeadToData($all_data_list[$key]);
            //写入数据到表格中
            $objExecl->createSheet();//创建工作簿新的表空间
            $currentSheet = $objExecl->setActiveSheetIndex($key);//当前工作表
            $objExecl->setActiveSheetIndex($key)->setTitle($all_data_list[$key][1]['TYPE']);
            $this->addDataToExcel($all_data_list[$key], $currentSheet, $indexKey, $heard_arr);
        }
        $objExecl->setActiveSheetIndex(0);  //设置导出 默认打开第一个sheet
        $this->downLoad($objExecl, '防火门钢框木扇封边条用料规则');
    }

    /**
     * 得到需要导出的数据  返回所有数据
     * @param string $year
     * @param string $month
     * @param string $keyword
     * @param $type
     * @return mixed
     */
    public function getExportData($keyword = '')
    {
//        $sql1 = 'select * from bom_fengbiant_rule order by id asc';
        $sql1 = 'select id,
                    trim(both \'/\' from menkuang) as menkuang,
                    trim(both \'/\' from menshan) as menshan,
                    biaomcl,
                    biaomiantsyq,
                    guige_gd,
                    mumen,
                    zimen,
                    liaohao,
                    pinming,
                    guige,
                    zhizaobm,
                    trim(both \'/\' from dkcailiao) as dkcailiao,
                    type,
                    sort,
                    yongliang,
                    qpa
               from bom_fengbiant_rule
               order by id asc';
        $all_data = Db::query($sql1);
        $sheet1 = [];
        $sheet2 = [];
        $sheet3 = [];
        $sheet4 = [];
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
        return [$sheet1, $sheet2, $sheet3, $sheet4];
    }

    /**
     * 返回 对应表中 的字段名
     * @param $type
     * @return array
     */
    public function indexKey()
    {
        $indexKey = array('ZHIZAOBM', 'MENKUANG', 'MENSHAN', 'BIAOMCL', 'BIAOMIANTSYQ', 'DKCAILIAO', 'GUIGE_GD', 'YONGLIANG', 'LIAOHAO', 'PINMING', 'GUIGE','QPA');    //$indexKey $list数组中与Excel表格表头$header中每个项目对应的字段的名字(key值)
        return $indexKey;
    }

    /**\
     * 添加表头信息
     * @param $data
     * @param $type
     * @return int
     */
    public function addHeadToData($data)
    {
        $heard = array('ZHIZAOBM' => '制造部门', 'MENKUANG' => '门框', 'MENSHAN' => '门扇', 'BIAOMCL' => '表面方式', 'BIAOMIANTSYQ' => '表面要求', 'DKCAILIAO' => '底框材料', 'GUIGE_GD' => '规格高度', 'YONGLIANG' => '用量', 'LIAOHAO' => '料号', 'PINMING' => '品名', 'GUIGE' => '规格' ,'QPA'=>'QPA计算');
        array_unshift($data, $heard);
        return $data;
    }

    public function addDataToExcel($data, $objActSheet, $indexKey, $heard_arr)
    {
        $startRow = 1;
        $objActSheet->getStyle('A1:L1')->getFont()->setBold(true);
        $objActSheet->freezePane('A2');
        foreach ($data as $key => $value) {
            foreach ($indexKey as $k => $v) {
                $objActSheet->setCellValue($heard_arr[$k] . $startRow, $data[$key][$v]);
//                $objActSheet->getStyle($heard_arr[$k] . $startRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
//                $objActSheet->getStyle($heard_arr[$k] . $startRow)->getFont()->setSize(10)->setName('宋体');
            }
            $startRow++;
        }
        $objActSheet->getColumnDimension('A')->setWidth(20);
        $objActSheet->getColumnDimension('B')->setWidth(50);
        $objActSheet->getColumnDimension('C')->setWidth(15);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(20);
        $objActSheet->getColumnDimension('F')->setWidth(15);
        $objActSheet->getColumnDimension('G')->setWidth(15);
        $objActSheet->getColumnDimension('H')->setWidth(15);
        $objActSheet->getColumnDimension('I')->setWidth(25);
        $objActSheet->getColumnDimension('J')->setWidth(20);
        $objActSheet->getColumnDimension('K')->setWidth(20);
        $objActSheet->getColumnDimension('L')->setWidth(15);
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