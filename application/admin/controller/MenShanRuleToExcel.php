<?php
/**
 * Created by PhpStorm.
 * User: 000
 * Date: 2019/3/19
 * Time: 14:40
 */

namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Validate;


class MenShanRuleToExcel
{
    public function importExcel($type = '')
    {
        $excel_file = $this->getImportFile();
        if (empty($excel_file)) {
            return retmsg(-1, null, '获取文件错误,请重新选择要导入的EXCEL文件');
        }
        $importService = Loader::model('ImportExcelService', 'service');
        $all_data = $importService->importExcel($excel_file);
        $err_data = $this->handleImportData($all_data, $type);
        return $err_data;
    }

    public function handleImportData($all_data, $type)
    {
        # 导入之前先清理数据
        Db::execute("delete from BOM_MUMEN_MENSHAN_RULE WHERE type = '$type'");
        $data = $all_data[0];
        $fileName = $all_data[1];
        if ($type == 1) {
            $res = $this->handleMuMenFbt($data, $fileName, $type);
        }
        if ($type == 2) {
            $res = $this->handleMenShanMb($data, $fileName, $type);
        }
        if ($type == 3) {
            $res = $this->handleMenShanMf($data, $fileName, $type);
        }
        if ($type == 4) {
            $res = $this->handleMenShanXl($data, $fileName, $type);
        }
        if ($type == 5) {
            $res = $this->handleMuMenXt($data, $fileName, $type);
        }
        return retmsg(0, $res[0], '导入成功,导入失败' . $res[1] . '条信息');
    }

    public function exportExcel($type = 1)
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
        $heard_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA');
        if ($type == 2) {
            $heard_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ');
        } elseif ($type == 4) {
            $heard_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE');
        }
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
        $fileName = $this->getExcelName($type);
        $this->downLoad($objExecl, $fileName);
    }

    public function getExcelName($type)
    {
        switch ($type) {
            case 1:
                $fileName = '木门封边条用料规则';
                break;
            case 2:
                $fileName = '木门门扇面板用料规则';
                break;
            case 3:
                $fileName = '木门门扇木方用料规则';
                break;
            case 4:
                $fileName = '木门门扇芯料用料规则';
                break;
            case 5:
                $fileName = '木门线条用料规则';
                break;
            default :
                break;
        }
        return $fileName;
    }

    /**
     * 得到需要导出的数据  返回所有数据
     * @param string $year
     * @param string $month
     * @param string $keyword
     * @param $type
     * @return mixed
     */
    public function getExportData($type)
    {
        $sheet1 = [];
        $sheet2 = [];
        $sheet3 = [];
        $sheet4 = [];
        $sheet5 = [];
        $sheet6 = [];
        $sheet7 = [];
        $sql1 = "select * from BOM_MUMEN_MENSHAN_RULE where type='$type' order by id asc";
        $all_data = Db::query($sql1);
        if ($type == 1) {
            foreach ($all_data as $key => $value) {
                if ($all_data[$key]['SORT'] == 1) {
                    array_push($sheet1, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 2) {
                    array_push($sheet2, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 3) {
                    array_push($sheet3, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 4) {
                    array_push($sheet4, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 5) {
                    array_push($sheet5, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 6) {
                    array_push($sheet6, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 7) {
                    array_push($sheet7, $all_data[$key]);
                }
            }
            $res = [$sheet1, $sheet2, $sheet3, $sheet4, $sheet5, $sheet6, $sheet7];
        } elseif ($type == 2) {
            foreach ($all_data as $key => $value) {
                if ($all_data[$key]['SORT'] == 1) {
                    array_push($sheet1, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 2) {
                    array_push($sheet2, $all_data[$key]);
                }
            }
            $res = [$sheet1, $sheet2];
        } elseif ($type == 3) {
            foreach ($all_data as $key => $value) {
                if ($all_data[$key]['SORT'] == 1) {
                    array_push($sheet1, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 2) {
                    array_push($sheet2, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 3) {
                    array_push($sheet3, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 4) {
                    array_push($sheet4, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 5) {
                    array_push($sheet5, $all_data[$key]);
                }
            }
            $res = [$sheet1, $sheet2, $sheet3, $sheet4, $sheet5];
        } elseif ($type == 4) {
            foreach ($all_data as $key => $value) {
                if ($all_data[$key]['SORT'] == 1) {
                    array_push($sheet1, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 2) {
                    array_push($sheet2, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 3) {
                    array_push($sheet3, $all_data[$key]);
                }
            }
            $res = [$sheet1, $sheet2, $sheet3];
        } elseif ($type == 5) {
            foreach ($all_data as $key => $value) {
                if ($all_data[$key]['SORT'] == 1) {
                    array_push($sheet1, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 2) {
                    array_push($sheet2, $all_data[$key]);
                } elseif ($all_data[$key]['SORT'] == 3) {
                    array_push($sheet3, $all_data[$key]);
                }
            }
            $res = [$sheet1, $sheet2, $sheet3];
        }
        return $res;
    }

    /**
     * 返回 对应表中 的字段名
     * @param $type
     * @return array
     */
    public function indexKey($type, $key)
    {
        if ($type == 1) {
            if ($key == 0) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'MENTAO_HD', 'MENTAO_YS', 'XIANTIAO_YS', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'BIAOMIAN_YQ', 'GUIGE_CD', 'YONGLIAO_CD', 'YONGLIAO_GS', 'QPA', 'LIAOHAO',
                    'PINGMING', 'GUIGE');
            }
            if ($key == 1) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'MENTAO_HD', 'MENTAO_YS', 'XIANTIAO_YS', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'BIAOMIAN_YQ', 'GUIGE_KD', 'YONGLIAO_CD', 'YONGLIAO_GS', 'QPA', 'LIAOHAO', 'PINGMING', 'GUIGE');
            }
            if ($key == 2) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'MENTAO_HD', 'MENTAO_YS', 'XIANTIAO_YS', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'BIAOMIAN_YQ', 'CHUANGHUA', 'GUIGE_KD', 'YONGLIAO_CD', 'YONGLIAO_GS', 'QPA', 'LIAOHAO', 'PINGMING', 'GUIGE');
            }
            if ($key == 3) {
                $indexKey = array('DEPT_NAME', 'DINGDAN_TYPE', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'MENTAO_HD', 'MENTAO_YS', 'XIANTIAO_YS', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'BIAOMIAN_YQ', 'GUIGE_KD', 'YONGLIAO_CD', 'YONGLIAO_GS', 'QPA', 'LIAOHAO', 'PINGMING', 'GUIGE');
            }
            if ($key == 4 || $key == 5) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'MENSHAN_HD', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'BIAOMIAN_YQ', 'MENSHAN_TYPE', 'CHUANGHUA',
                    'GUIGE_L_M', 'YONGLIAO_L_M', 'YONGLIAO_G_M', 'GUIGE_L_Z', 'YONGLIAO_L_Z', 'YONGLIAO_G_Z', 'MUMEN_QPA',
                    'ZIMEN_QPA', 'LIAOHAO', 'PINGMING', 'GUIGE');
            }
            if ($key == 6) {
                $indexKey = array('DEPT_NAME', 'DINGDAN_TYPE', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'MENSHAN_HD', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'BIAOMIAN_YQ', 'MENSHAN_TYPE', 'CHUANGHUA',
                    'GUIGE_L_M', 'YONGLIAO_L_M', 'YONGLIAO_G_M', 'GUIGE_L_Z', 'YONGLIAO_L_Z', 'YONGLIAO_G_Z',
                    'MUMEN_QPA', 'ZIMEN_QPA', 'LIAOHAO', 'PINGMING', 'GUIGE');
            }
        } elseif ($type == 2) {
            $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'KUAN_START', 'KUAN_END', 'MENLEI_JG', 'DANGCI', 'MENSHAN_TYPE', 'MENSHAN_HS',
                'BIAOMIAN_FS', 'BIAOMIAN_HW', 'CHUANGHUA', 'GUIGE_CD', 'GUIGE_K_M', 'YONGLIAO_L_M',
                'YONGLIAO_K_M', 'CAILIAO_L_M', 'CAILIAO_K_M',
                'GUIGE_L_Z', 'GUIGE_K_Z', 'YONGLIAO_L_Z', 'YONGLIAO_K_Z', 'CAILIAO_L_Z', 'CAILIAO_K_Z', 'MUMEN', 'ZIMEN', 'LIAOHAO', 'PINGMING', 'GUIGE',
                'GUIGE_KDXSM', 'CAILIAO_KDXSM', 'GUIGE_KDXSZ', 'CAILIAO_KDXSZ', 'QPA_MUMEN', 'QPA_ZIMEN');
        } elseif ($type == 3) {
            if ($key != 4) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'DANGCI', 'MENSHAN_TYPE', 'MENSHAN_HS', 'CHUANGHUA', 'GUIGE_CD', 'YONGLIAO_K_M', 'YONGLIAO_L_M', 'YONGLIAO_K_Z', 'YONGLIAO_L_Z', 'CAILIAO_CD', 'QPA', 'LIAOHAO', 'PINGMING', 'GUIGE', 'CAILIAO_KDXSM');
            } else {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'DANGCI', 'MENSHAN_TYPE', 'MENSHAN_HS', 'GUIGE_KD', 'YONGLIAO_L_M', 'CAILIAO_L_M', 'YONGLIAO_G_M', 'GUIGE_K_Z', 'YONGLIAO_L_Z', 'CAILIAO_L_Z', 'YONGLIAO_G_Z', 'MUMEN_QPA', 'ZIMEN_QPA', 'LIAOHAO', 'PINGMING', 'GUIGE', 'GUIGE_KDXSM', 'CAILIAO_KDXSM', 'GUIGE_KDXSZ', 'CAILIAO_KDXSZ');
            }
        } elseif ($type == 4) {
            if ($key == 0) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'DANGCI', 'MENSHAN_TYPE', 'MENSHAN_HS', 'CHUANGHUA', 'MENSHAN_YQ', 'TIANCHONG', 'GUIGE_CD', 'GUIGE_K_M', 'YONGLIAO_L_M', 'YONGLIAO_K_M', 'CAILIAO_L_M', 'CAILIAO_K_M', 'GUIGE_L_Z', 'GUIGE_K_Z', 'YONGLIAO_L_Z', 'YONGLIAO_K_Z', 'CAILIAO_L_Z', 'CAILIAO_K_Z', 'MUMEN_QPA', 'ZIMEN_QPA', 'LIAOHAO', 'PINGMING', 'GUIGE',
                    'GUIGE_KDXSM', 'CAILIAO_KDXSM', 'GUIGE_KDXSZ', 'CAILIAO_KDXSZ');
            } else {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'GUIGE_START', 'GUIGE_END', 'DANGCI', 'MENSHAN_TYPE', 'MENSHAN_HS', 'CHUANGHUA', 'MENSHAN_YQ', 'TIANCHONG', 'GUIGE_CD', 'GUIGE_K_M', 'YONGLIAO_L_M', 'YONGLIAO_K_M', 'CAILIAO_L_M', 'CAILIAO_K_M', 'GUIGE_L_Z', 'GUIGE_K_Z', 'YONGLIAO_L_Z', 'YONGLIAO_K_Z', 'CAILIAO_L_Z', 'CAILIAO_K_Z', 'MUMEN_QPA', 'ZIMEN_QPA', 'LIAOHAO', 'PINGMING', 'GUIGE',
                    'GUIGE_KDXSM', 'CAILIAO_KDXSM');
            }
        } elseif ($type == 5) {
            if ($key != 2) {
                $indexKey = array('DEPT_NAME', 'PRODUCT_TYPE', 'MENLEI_JG', 'XIANTIAO_TYPE', 'XIANTIAO_YS', 'XIANTIAO_JG', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'GUIGE_START', 'GUIGE_END', 'GUIGE_CD', 'YONGLIAO_CD', 'CAILIAO_CD', 'QPA',
                    'LIAOHAO', 'PINGMING', 'GUIGE');
            } else {
                $indexKey = array('DEPT_NAME', 'DINGDAN_TYPE', 'PRODUCT_TYPE', 'MENLEI_JG', 'XIANTIAO_TYPE', 'XIANTIAO_YS', 'XIANTIAO_JG', 'BIAOMIAN_FS', 'BIAOMIAN_HW', 'GUIGE_START', 'GUIGE_END', 'GUIGE_CD', 'YONGLIAO_CD', 'CAILIAO_CD', 'QPA',
                    'LIAOHAO', 'PINGMING', 'GUIGE');
            }
        }
        return $indexKey;
    }

    /**\
     * 添加表头信息
     * @param $data
     * @param $type
     * @return int
     */
    public function addHeadToData($data, $type, $key)
    {
        if ($type == 1) {
            if ($key == 0) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'MENTAO_HD' => '门套厚度', 'MENTAO_YS' => '门套样式', 'XIANTIAO_YS' => '线条样式', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'GUIGE_CD' => '规格长度', 'YONGLIAO_CD' => '用料长度', 'YONGLIAO_GS' => '用料根数',
                    'QPA' => 'QPA计算规则', 'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
            if ($key == 1) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格宽度区间', 'GUIGE_END' => '规格宽度区间', 'MENTAO_HD' => '门套厚度', 'MENTAO_YS' => '门套样式', 'XIANTIAO_YS' => '线条样式', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'GUIGE_KD' => '规格宽度', 'YONGLIAO_CD' => '用料长度', 'YONGLIAO_GS' => '用料根数',
                    'QPA' => 'QPA计算规则', 'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
            if ($key == 2) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格宽度区间', 'GUIGE_END' => '规格宽度区间', 'MENTAO_HD' => '门套厚度', 'MENTAO_YS' => '门套样式', 'XIANTIAO_YS' => '线条样式', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'CHUANGHUA' => '窗花', 'GUIGE_KD' => '规格宽度', 'YONGLIAO_CD' => '用料长度', 'YONGLIAO_GS' => '用料根数',
                    'QPA' => 'QPA计算规则', 'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
            if ($key == 3) {
                $heard = array('DEPT_NAME' => '部门', 'DINGDAN_TYPE' => '订单类别', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格宽度区间', 'GUIGE_END' => '规格宽度区间', 'MENTAO_HD' => '门套厚度', 'MENTAO_YS' => '门套样式', 'XIANTIAO_YS' => '线条样式', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'GUIGE_KD' => '规格宽度', 'YONGLIAO_CD' => '用料长度', 'YONGLIAO_GS' => '用料根数',
                    'QPA' => 'QPA计算规则', 'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
            if ($key == 4) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'MENSHAN_HD' => '档次', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'MENSHAN_TYPE' => '门扇类型', 'CHUANGHUA' => '窗花', 'GUIGE_L_M' => '规格长度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'YONGLIAO_G_M' => '用料根数(母门)', 'GUIGE_L_Z' => '规格长度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'YONGLIAO_G_Z' => '用料根数(子门)', 'MUMEN_QPA' => '母门QPA计算规则', 'ZIMEN_QPA' => '子门QPA计算规则',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
            if ($key == 5) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'MENSHAN_HD' => '档次', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'MENSHAN_TYPE' => '门扇类型', 'CHUANGHUA' => '窗花', 'GUIGE_L_M' => '规格宽度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'YONGLIAO_G_M' => '用料根数(母门)', 'GUIGE_L_Z' => '规格宽度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'YONGLIAO_G_Z' => '用料根数(子门)', 'MUMEN_QPA' => '母门QPA计算规则', 'ZIMEN_QPA' => '子门QPA计算规则',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
            if ($key == 6) {
                $heard = array('DEPT_NAME' => '部门', 'DINGDAN_TYPE' => '订单类别', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'MENSHAN_HD' => '档次', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'BIAOMIAN_YQ' => '表面要求', 'MENSHAN_TYPE' => '门扇类型', 'CHUANGHUA' => '窗花', 'GUIGE_L_M' => '规格宽度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'YONGLIAO_G_M' => '用料根数(母门)', 'GUIGE_L_Z' => '规格宽度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'YONGLIAO_G_Z' => '用料根数(子门)', 'MUMEN_QPA' => '母门QPA计算规则', 'ZIMEN_QPA' => '子门QPA计算规则',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
        } elseif ($type == 2) {
            $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'KUAN_START' => '规格宽度区间', 'KUAN_END' => '规格宽度区间', 'MENLEI_JG' => '门类结构', 'DANGCI' => '档次', 'MENSHAN_TYPE' => '门扇类型', 'MENSHAN_HS' => '门扇花色', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'CHUANGHUA' => '窗花', 'GUIGE_CD' => '规格长度(母门)', 'GUIGE_K_M' => '规格宽度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'YONGLIAO_K_M' => '用料宽度(母门)', 'CAILIAO_L_M' => '材料长度(母门)', 'CAILIAO_K_M' => '材料宽度(母门)',
                'GUIGE_L_Z' => '规格长度(子门)', 'GUIGE_K_Z' => '规格宽度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'YONGLIAO_K_Z' => '用料宽度(子门)', 'CAILIAO_L_Z' => '材料长度(子门)', 'CAILIAO_K_Z' => '材料宽度(子门)',
                'MUMEN' => '母门用量', 'ZIMEN' => '子门用量',
                'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格',
                'GUIGE_KDXSM' => '规格宽度系数(母门)', 'CAILIAO_KDXSM' => '材料宽度系数(母门)', 'GUIGE_KDXSZ' => '规格宽度系数(子门)', 'CAILIAO_KDXSZ' => '材料宽度系数(子门)', 'QPA_MUMEN' => '母门QPA', 'QPA_ZIMEN' => '子门QPA');
        } elseif ($type == 3) {
            $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'DANGCI' => '档次', 'MENSHAN_TYPE' => '门扇类型', 'MENSHAN_HS' => '门扇花色', 'CHUANGHUA' => '窗花', 'GUIGE_CD' => '规格长度', 'YONGLIAO_K_M' => '用料长度(母门铰方)', 'YONGLIAO_L_M' => '用料长度(母门锁方)', 'YONGLIAO_K_Z' => '用料长度(子门铰方)', 'YONGLIAO_L_Z' => '用料长度(子门锁方)', 'CAILIAO_CD' => '材料长度',
                'QPA' => '母门用量(铰方)', 'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格', 'CAILIAO_KDXSM' => '材料宽度系数');
            if ($key == 1) {
                $heard['QPA'] = '母门用量（锁方）';
            } elseif ($key == 2) {
                $heard['QPA'] = '子门用量(铰方)';
            } elseif ($key == 3) {
                $heard['QPA'] = '子门用量(锁方)';
            } elseif ($key == 4) {
                $heard = null;
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格高度区间', 'GUIGE_END' => '规格高度区间', 'DANGCI' => '档次', 'MENSHAN_TYPE' => '门扇类型', 'MENSHAN_HS' => '门扇花色', 'GUIGE_KD' => '规格宽度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'CAILIAO_L_M' => '材料长度(母门)', 'YONGLIAO_G_M' => '用料根数(母门)', 'GUIGE_K_Z' => '规格宽度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'CAILIAO_L_Z' => '材料长度(子门)', 'YONGLIAO_G_Z' => '用料根数(子门)', 'MUMEN_QPA' => '母门用量', 'ZIMEN_QPA' => '子门用量',
                    'QPA' => '母门用量(铰方)', 'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格',
                    'GUIGE_KDXSM' => '规格宽度系数(母门)', 'CAILIAO_KDXSM' => '材料宽度系数(母门)', 'GUIGE_KDXSZ' => '规格宽度系数(子门)', 'CAILIAO_KDXSZ' => '材料宽度系数(子门)');
            }
        } elseif ($type == 4) {
            if ($key == 0) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格宽度区间', 'GUIGE_END' => '规格宽度区间', 'DANGCI' => '档次', 'MENSHAN_TYPE' => '门扇类型', 'MENSHAN_HS' => '门扇花色', 'CHUANGHUA' => '窗花', 'MENSHAN_YQ' => '门扇要求', 'TIANCHONG' => '填充料', 'GUIGE_CD' => '规格长度(母门)', 'GUIGE_K_M' => '规格宽度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'YONGLIAO_K_M' => '用料宽度(母门)', 'CAILIAO_L_M' => '材料长度(母门)', 'CAILIAO_K_M' => '材料宽度(母门)', 'GUIGE_L_Z' => '规格长度(子门)', 'GUIGE_K_Z' => '规格宽度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'YONGLIAO_K_Z' => '用料宽度(子门)', 'CAILIAO_L_Z' => '材料长度(子门)', 'CAILIAO_K_Z' => '材料宽度(子门)', 'MUMEN_QPA' => '母门用量', 'ZIMEN_QPA' => '子门用量',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格',
                    "GUIGE_KDXSM" => '规格宽度系数(母门)', "CAILIAO_KDXSM" => '母门系数2', "GUIGE_KDXSZ" => '规格宽度系数(子门)', "CAILIAO_KDXSZ" => '子门系数2');
            } else {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'GUIGE_START' => '规格宽度区间', 'GUIGE_END' => '规格宽度区间', 'DANGCI' => '档次', 'MENSHAN_TYPE' => '门扇类型', 'MENSHAN_HS' => '门扇花色', 'CHUANGHUA' => '窗花', 'MENSHAN_YQ' => '门扇要求', 'TIANCHONG' => '填充料', 'GUIGE_CD' => '规格长度(母门)', 'GUIGE_K_M' => '规格宽度(母门)', 'YONGLIAO_L_M' => '用料长度(母门)', 'YONGLIAO_K_M' => '用料宽度(母门)', 'CAILIAO_L_M' => '材料长度(母门)', 'CAILIAO_K_M' => '材料宽度(母门)', 'GUIGE_L_Z' => '规格长度(子门)', 'GUIGE_K_Z' => '规格宽度(子门)', 'YONGLIAO_L_Z' => '用料长度(子门)', 'YONGLIAO_K_Z' => '用料宽度(子门)', 'CAILIAO_L_Z' => '材料长度(子门)', 'CAILIAO_K_Z' => '材料宽度(子门)', 'MUMEN_QPA' => '母门用量', 'ZIMEN_QPA' => '子门用量',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格',
                    "GUIGE_KDXSM" => '规格宽度系数(母门)', "CAILIAO_KDXSM" => '规格宽度系数(子门)');
            }
            if ($key == 1) {
                $heard['GUIGE_START'] = "规格高度区间";
                $heard['GUIGE_END'] = "规格高度区间";
            }
        } elseif ($type == 5) {
            if ($key == 0) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'MENLEI_JG' => '门类结构', 'XIANTIAO_TYPE' => '线条种类', 'XIANTIAO_YS' => '线条样式', 'XIANTIAO_JG' => '线条结构', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'GUIGE_START' => '高度区间', 'GUIGE_END' => '高度区间', 'GUIGE_CD' => '规格长度', 'YONGLIAO_CD' => '用料长度', 'CAILIAO_CD' => '材料长度', 'QPA' => '用量',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            } elseif ($key == 1) {
                $heard = array('DEPT_NAME' => '部门', 'PRODUCT_TYPE' => '产品种类', 'MENLEI_JG' => '门类结构', 'XIANTIAO_TYPE' => '线条种类', 'XIANTIAO_YS' => '线条样式', 'XIANTIAO_JG' => '线条结构', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'GUIGE_START' => '宽度区间', 'GUIGE_END' => '宽度区间', 'GUIGE_CD' => '规格宽度', 'YONGLIAO_CD' => '用料长度', 'CAILIAO_CD' => '材料长度', 'QPA' => '用量',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            } elseif ($key == 2) {
                $heard = array('DEPT_NAME' => '部门', 'DINGDAN_TYPE' => '订单类别', 'PRODUCT_TYPE' => '产品种类', 'MENLEI_JG' => '门类结构', 'XIANTIAO_TYPE' => '线条种类', 'XIANTIAO_YS' => '线条样式', 'XIANTIAO_JG' => '线条结构', 'BIAOMIAN_FS' => '表面方式', 'BIAOMIAN_HW' => '表面花纹', 'GUIGE_START' => '宽度区间', 'GUIGE_END' => '宽度区间', 'GUIGE_CD' => '规格宽度', 'YONGLIAO_CD' => '用料长度', 'CAILIAO_CD' => '材料长度', 'QPA' => '用量',
                    'LIAOHAO' => '料号', 'PINGMING' => '品名', 'GUIGE' => '规格');
            }
        }
        array_unshift($data, $heard);
        return $data;
    }

    public function addDataToExcel($data, $objActSheet, $indexKey, $heard_arr, $type, $key)
    {
        $startRow = 1;
        $objActSheet->getStyle('A1:X1')->getFont()->setBold(true);
        $objActSheet->freezePane('A2');
        if ($type == 1) {
            if ($key == 3 || $key == 6) {
                $objActSheet->mergeCells("D1:E1");
            } else {
                $objActSheet->mergeCells("C1:D1");
            }
            $objActSheet->getStyle("A1:V1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        } elseif ($type == 2) {
            $objActSheet->getStyle('A1:AJ1')->getFont()->setBold(true);
            $objActSheet->mergeCells("C1:D1");
            $objActSheet->mergeCells("E1:F1");
            $objActSheet->getStyle("A1:AJ1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        } elseif ($type == 3) {
            $objActSheet->mergeCells("C1:D1");
            $objActSheet->getStyle("A1:X1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        } elseif ($type == 4) {
            $objActSheet->mergeCells("C1:D1");
            if ($key == 0) {
                $objActSheet->getStyle('A1:AE1')->getFont()->setBold(true);
                $objActSheet->getStyle("A1:AE1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            } else {
                $objActSheet->getStyle('A1:AC1')->getFont()->setBold(true);
                $objActSheet->getStyle("A1:AC1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
        } elseif ($type == 5) {
            if ($key != 2) {
                $objActSheet->mergeCells("I1:J1");
            } else {
                $objActSheet->mergeCells("J1:K1");
            }
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

    public function handleMuMenFbt($data, $fileName, $type)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if (strpos($v, '门套封边条(侧方)') !== false) {
                $arr = ['sort' => 1, 'sheet_name' => '门套封边条(侧方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门套封边条(上方)') !== false) {
                $arr = ['sort' => 2, 'sheet_name' => '门套封边条(上方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门套封边条(中档)') !== false) {
                $arr = ['sort' => 3, 'sheet_name' => '门套封边条(中档)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门套封边条(下方)') !== false) {
                $arr = ['sort' => 4, 'sheet_name' => '门套封边条(下方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门扇封边条(侧方)') !== false) {
                $arr = ['sort' => 5, 'sheet_name' => '门扇封边条(侧方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门扇封边条(上方)') !== false) {
                $arr = ['sort' => 6, 'sheet_name' => '门扇封边条(上方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '门扇封边条(下方)') !== false) {
                $arr = ['sort' => 7, 'sheet_name' => '门扇封边条(下方)'];
                $names[$k] = $arr;
            }
        }
        $err_data = [];  //存放错误信息
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                if (empty($value['A'])) {
                    continue;
                }
                $sheet_name = $names[$j]['sheet_name'];
                $sort = $names[$j]['sort'];
                if ($sort == 1) {
                    $DEPT_NAME = $value['A'];
                    $PRODUCT_TYPE = $value['B'];
                    $GUIGE_START = $value['C'];
                    $GUIGE_END = $value['D'];
                    $MENTAO_HD = $value['E'];
                    $MENTAO_YS = $value['F'];
                    $XIANTIAO_YS = $value['G'];
                    $BIAOMIAN_FS = $value['H'];
                    $BIAOMIAN_HW = $value['I'];
                    $BIAOMIAN_YQ = $value['J'];
                    $GUIGE_CD = $value['K'];
                    $YONGLIAO_CD = $value['L'];
                    $YONGLIAO_GS = $value['M'];
                    $QPA = '';
                    $LIAOHAO = $value['O'];
                    $PINGMING = $value['P'];
                    $GUIGE = $value['Q'];
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "PRODUCT_TYPE" => $value['B'],
                        "GUIGE_START" => $value['C'],
                        "GUIGE_END" => $value['D'],
                        "MENTAO_HD" => $value['E'],
                        "MENTAO_YS" => $value['F'],
                        "XIANTIAO_YS" => $value['G'],
                        "BIAOMIAN_FS" => $value['H'],
                        "BIAOMIAN_HW" => $value['I'],
                        "BIAOMIAN_YQ" => $value['J'],
                        "GUIGE_CD" => $value['K'],
                        "YONGLIAO_CD" => $value['L'],
                        "YONGLIAO_GS" => $value['M'],
                        "QPA" => '',
                        "LIAOHAO" => $value['O'],
                        "PINGMING" => $value['P'],
                        "GUIGE" => $value['Q']
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENTAO_HD,
MENTAO_YS,XIANTIAO_YS,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,GUIGE_CD,YONGLIAO_CD,YONGLIAO_GS,QPA,LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$MENTAO_HD',
'$MENTAO_YS','$XIANTIAO_YS','$BIAOMIAN_FS','$BIAOMIAN_HW','$BIAOMIAN_YQ','$GUIGE_CD','$YONGLIAO_CD','$YONGLIAO_GS',
'$QPA','$LIAOHAO','$PINGMING','$GUIGE','$sort','$type','$sheet_name')";
                }
                if ($sort == 2) {
                    $DEPT_NAME = $value['A'];
                    $PRODUCT_TYPE = $value['B'];
                    $GUIGE_START = $value['C'];
                    $GUIGE_END = $value['D'];
                    $MENTAO_HD = $value['E'];
                    $MENTAO_YS = $value['F'];
                    $XIANTIAO_YS = $value['G'];
                    $BIAOMIAN_FS = $value['H'];
                    $BIAOMIAN_HW = $value['I'];
                    $BIAOMIAN_YQ = $value['J'];
                    $GUIGE_KD = $value['K'];
                    $YONGLIAO_CD = $value['L'];
                    $YONGLIAO_GS = $value['M'];
                    $QPA = '';
                    $LIAOHAO = $value['O'];
                    $PINGMING = $value['P'];
                    $GUIGE = $value['Q'];
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "PRODUCT_TYPE" => $value['B'],
                        "GUIGE_START" => $value['C'],
                        "GUIGE_END" => $value['D'],
                        "MENTAO_HD" => $value['E'],
                        "MENTAO_YS" => $value['F'],
                        "XIANTIAO_YS" => $value['G'],
                        "BIAOMIAN_FS" => $value['H'],
                        "BIAOMIAN_HW" => $value['I'],
                        "BIAOMIAN_YQ" => $value['J'],
                        "GUIGE_KD" => $value['K'],
                        "YONGLIAO_CD" => $value['L'],
                        "YONGLIAO_GS" => $value['M'],
                        "QPA" => '',
                        "LIAOHAO" => $value['O'],
                        "PINGMING" => $value['P'],
                        "GUIGE" => $value['Q']
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENTAO_HD,
MENTAO_YS,XIANTIAO_YS,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,GUIGE_KD,YONGLIAO_CD,YONGLIAO_GS,QPA,LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$MENTAO_HD',
'$MENTAO_YS','$XIANTIAO_YS','$BIAOMIAN_FS','$BIAOMIAN_HW','$BIAOMIAN_YQ','$GUIGE_KD','$YONGLIAO_CD','$YONGLIAO_GS',
'$QPA','$LIAOHAO','$PINGMING','$GUIGE','$sort','$type','$sheet_name')";
                }
                if ($sort == 3) {
                    $DEPT_NAME = $value['A'];
                    $PRODUCT_TYPE = $value['B'];
                    $GUIGE_START = $value['C'];
                    $GUIGE_END = $value['D'];
                    $MENTAO_HD = $value['E'];
                    $MENTAO_YS = $value['F'];
                    $XIANTIAO_YS = $value['G'];
                    $BIAOMIAN_FS = $value['H'];
                    $BIAOMIAN_HW = $value['I'];
                    $BIAOMIAN_YQ = $value['J'];
                    $CHUANGHUA = $value['K'];
                    $GUIGE_KD = $value['L'];
                    $YONGLIAO_CD = $value['M'];
                    $YONGLIAO_GS = $value['N'];
                    $QPA = '';
                    $LIAOHAO = $value['P'];
                    $PINGMING = $value['Q'];
                    $GUIGE = $value['R'];
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "PRODUCT_TYPE" => $value['B'],
                        "GUIGE_START" => $value['C'],
                        "GUIGE_END" => $value['D'],
                        "MENTAO_HD" => $value['E'],
                        "MENTAO_YS" => $value['F'],
                        "XIANTIAO_YS" => $value['G'],
                        "BIAOMIAN_FS" => $value['H'],
                        "BIAOMIAN_HW" => $value['I'],
                        "BIAOMIAN_YQ" => $value['J'],
                        "CHUANGHUA" => $value['K'],
                        "GUIGE_KD" => $value['L'],
                        "YONGLIAO_CD" => $value['M'],
                        "YONGLIAO_GS" => $value['N'],
                        "QPA" => '',
                        "LIAOHAO" => $value['P'],
                        "PINGMING" => $value['Q'],
                        "GUIGE" => $value['R']
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENTAO_HD,
MENTAO_YS,XIANTIAO_YS,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,CHUANGHUA,GUIGE_KD,YONGLIAO_CD,YONGLIAO_GS,QPA,LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$MENTAO_HD',
'$MENTAO_YS','$XIANTIAO_YS','$BIAOMIAN_FS','$BIAOMIAN_HW','$BIAOMIAN_YQ','$CHUANGHUA','$GUIGE_KD','$YONGLIAO_CD','$YONGLIAO_GS',
'$QPA','$LIAOHAO','$PINGMING','$GUIGE','$sort','$type','$sheet_name')";
                }
                if ($sort == 4) {
                    $DEPT_NAME = $value['A'];
                    $DINGDAN_TYPE = $value['B'];
                    $PRODUCT_TYPE = $value['C'];
                    $GUIGE_START = $value['D'];
                    $GUIGE_END = $value['E'];
                    $MENTAO_HD = $value['F'];
                    $MENTAO_YS = $value['G'];
                    $XIANTIAO_YS = $value['H'];
                    $BIAOMIAN_FS = $value['I'];
                    $BIAOMIAN_HW = $value['J'];
                    $BIAOMIAN_YQ = $value['K'];
                    $GUIGE_KD = $value['L'];
                    $YONGLIAO_CD = $value['M'];
                    $YONGLIAO_GS = $value['N'];
                    $QPA = '';
                    $LIAOHAO = $value['P'];
                    $PINGMING = $value['Q'];
                    $GUIGE = $value['R'];
                    $saveData = [
                        "DEPT_NAME" => $DEPT_NAME,
                        'DINGDAN_TYPE' => $DINGDAN_TYPE,
                        "PRODUCT_TYPE" => $PRODUCT_TYPE,
                        "GUIGE_START" => $GUIGE_START,
                        "GUIGE_END" => $GUIGE_END,
                        "MENTAO_HD" => $MENTAO_HD,
                        "MENTAO_YS" => $MENTAO_YS,
                        "XIANTIAO_YS" => $XIANTIAO_YS,
                        "BIAOMIAN_FS" => $BIAOMIAN_FS,
                        "BIAOMIAN_HW" => $BIAOMIAN_HW,
                        "BIAOMIAN_YQ" => $BIAOMIAN_YQ,
                        "GUIGE_KD" => $GUIGE_KD,
                        "YONGLIAO_CD" => $YONGLIAO_CD,
                        "YONGLIAO_GS" => $YONGLIAO_GS,
                        "QPA" => '',
                        "LIAOHAO" => $LIAOHAO,
                        "PINGMING" => $PINGMING,
                        "GUIGE" => $GUIGE
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,DINGDAN_TYPE,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENTAO_HD,
MENTAO_YS,XIANTIAO_YS,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,GUIGE_KD,YONGLIAO_CD,YONGLIAO_GS,QPA,LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$DINGDAN_TYPE','$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$MENTAO_HD',
'$MENTAO_YS','$XIANTIAO_YS','$BIAOMIAN_FS','$BIAOMIAN_HW','$BIAOMIAN_YQ','$GUIGE_KD','$YONGLIAO_CD','$YONGLIAO_GS',
'$QPA','$LIAOHAO','$PINGMING','$GUIGE','$sort','$type','$sheet_name')";
                }
                if ($sort == 5 || $sort == 6) {
                    $DEPT_NAME = $value['A'];
                    $PRODUCT_TYPE = $value['B'];
                    $GUIGE_START = $value['C'];
                    $GUIGE_END = $value['D'];
                    $MENSHAN_HD = $value['E'];
                    $BIAOMIAN_FS = $value['F'];
                    $BIAOMIAN_HW = $value['G'];
                    $BIAOMIAN_YQ = $value['H'];
                    $MENSHAN_TYPE = $value['I'];
                    $CHUANGHUA = $value['J'];
                    $GUIGE_L_M = $value['K'];
                    $YONGLIAO_L_M = $value['L'];
                    $YONGLIAO_G_M = $value['M'];
                    $GUIGE_L_Z = $value['N'];
                    $YONGLIAO_L_Z = $value['O'];
                    $YONGLIAO_G_Z = $value['P'];
                    $QPA_MUMEN = '';
                    $QPA_ZIMEN = '';
                    $LIAOHAO = $value['S'];
                    $PINGMING = $value['T'];
                    $GUIGE = $value['U'];
                    $saveData = [
                        "DEPT_NAME" => $DEPT_NAME,
                        "PRODUCT_TYPE" => $PRODUCT_TYPE,
                        "GUIGE_START" => $GUIGE_START,
                        "GUIGE_END" => $GUIGE_END,
                        "MENSHAN_HD" => $MENSHAN_HD,
                        "BIAOMIAN_FS" => $BIAOMIAN_FS,
                        "BIAOMIAN_HW" => $BIAOMIAN_HW,
                        "BIAOMIAN_YQ" => $BIAOMIAN_YQ,
                        "MENSHAN_TYPE" => $MENSHAN_TYPE,
                        "CHUANGHUA" => $CHUANGHUA,
                        "GUIGE_L_M" => $GUIGE_L_M,
                        "YONGLIAO_L_M" => $YONGLIAO_L_M,
                        "YONGLIAO_G_M" => $YONGLIAO_G_M,
                        "GUIGE_L_Z" => $GUIGE_L_Z,
                        "YONGLIAO_L_Z" => $YONGLIAO_L_Z,
                        "YONGLIAO_G_Z" => $YONGLIAO_G_Z,
                        "LIAOHAO" => $LIAOHAO,
                        "PINGMING" => $PINGMING,
                        "GUIGE" => $GUIGE
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENSHAN_HD,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,MENSHAN_TYPE,CHUANGHUA,
GUIGE_L_M,YONGLIAO_L_M,YONGLIAO_G_M,GUIGE_L_Z,YONGLIAO_L_Z,YONGLIAO_G_Z,
LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$MENSHAN_HD',
'$BIAOMIAN_FS','$BIAOMIAN_HW','$BIAOMIAN_YQ','$MENSHAN_TYPE','$CHUANGHUA',
'$GUIGE_L_M','$YONGLIAO_L_M','$YONGLIAO_G_M','$GUIGE_L_Z','$YONGLIAO_L_Z','$YONGLIAO_G_Z',
'$LIAOHAO','$PINGMING','$GUIGE','$sort','$type','$sheet_name')";
                }
                if ($sort == 7) {
                    $DEPT_NAME = $value['A'];
                    $DINGDAN_TYPE = $value['B'];
                    $PRODUCT_TYPE = $value['C'];
                    $GUIGE_START = $value['D'];
                    $GUIGE_END = $value['E'];
                    $MENSHAN_HD = $value['F'];
                    $BIAOMIAN_FS = $value['G'];
                    $BIAOMIAN_HW = $value['H'];
                    $BIAOMIAN_YQ = $value['I'];
                    $MENSHAN_TYPE = $value['J'];
                    $CHUANGHUA = $value['K'];
                    $GUIGE_L_M = $value['L'];
                    $YONGLIAO_L_M = $value['M'];
                    $YONGLIAO_G_M = $value['N'];
                    $GUIGE_L_Z = $value['O'];
                    $YONGLIAO_L_Z = $value['P'];
                    $YONGLIAO_G_Z = $value['Q'];
                    $QPA_MUMEN = '';
                    $QPA_ZIMEN = '';
                    $LIAOHAO = $value['T'];
                    $PINGMING = $value['U'];
                    $GUIGE = $value['V'];
                    $saveData = [
                        "DEPT_NAME" => $DEPT_NAME,
                        "DINGDAN_TYPE" => $DINGDAN_TYPE,
                        "PRODUCT_TYPE" => $PRODUCT_TYPE,
                        "GUIGE_START" => $GUIGE_START,
                        "GUIGE_END" => $GUIGE_END,
                        "MENSHAN_HD" => $MENSHAN_HD,
                        "BIAOMIAN_FS" => $BIAOMIAN_FS,
                        "BIAOMIAN_HW" => $BIAOMIAN_HW,
                        "BIAOMIAN_YQ" => $BIAOMIAN_YQ,
                        "MENSHAN_TYPE" => $MENSHAN_TYPE,
                        "CHUANGHUA" => $CHUANGHUA,
                        "GUIGE_L_M" => $GUIGE_L_M,
                        "YONGLIAO_L_M" => $YONGLIAO_L_M,
                        "YONGLIAO_G_M" => $YONGLIAO_G_M,
                        "GUIGE_L_Z" => $GUIGE_L_Z,
                        "YONGLIAO_L_Z" => $YONGLIAO_L_Z,
                        "YONGLIAO_G_Z" => $YONGLIAO_G_Z,
                        "LIAOHAO" => $LIAOHAO,
                        "PINGMING" => $PINGMING,
                        "GUIGE" => $GUIGE
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,DINGDAN_TYPE,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENSHAN_HD,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,MENSHAN_TYPE,CHUANGHUA,
GUIGE_L_M,YONGLIAO_L_M,YONGLIAO_G_M,GUIGE_L_Z,YONGLIAO_L_Z,YONGLIAO_G_Z,
LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$DINGDAN_TYPE',
'$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$MENSHAN_HD',
'$BIAOMIAN_FS','$BIAOMIAN_HW','$BIAOMIAN_YQ','$MENSHAN_TYPE','$CHUANGHUA',
'$GUIGE_L_M','$YONGLIAO_L_M','$YONGLIAO_G_M','$GUIGE_L_Z','$YONGLIAO_L_Z','$YONGLIAO_G_Z',
'$LIAOHAO','$PINGMING','$GUIGE','$sort','$type','$sheet_name')";
                }
                $res = Db::execute($sql);
                if (empty($res)) {
                    array_push($err_data, $saveData);
                }
            }
        }
        $count = count($err_data);
        return [$err_data, $count];
    }

    public function handleMenShanMb($data, $fileName, $type)
    {
        $names = [];
        foreach ($fileName as $k => $v) {
            if (strpos($v, '实木、转印木门面板用料规则') !== false) {
                $arr = ['sort' => 1, 'sheet_name' => '实木、转印木门面板用料规则'];
                $names[$k] = $arr;
            } elseif (strpos($v, '强化木门面板用料规则') !== false) {
                $arr = ['sort' => 2, 'sheet_name' => '强化木门面板用料规则'];
                $names[$k] = $arr;
            }
        }
        $err_data = [];  //存放错误信息
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                if (empty($value['A'])) {
                    continue;
                }
                $sheet_name = $names[$j]['sheet_name'];
                $sort = $names[$j]['sort'];
                $DEPT_NAME = $value['A'];
                $PRODUCT_TYPE = $value['B'];
                $GUIGE_START = $value['C'];
                $GUIGE_END = $value['D'];
                $KUAN_START = $value['E'];
                $KUAN_END = $value['F'];
                $MENLEI_JG = $value['G'];
                $DANGCI = $value['H'];
                $MENSHAN_TYPE = $value['I'];
                $MENSHAN_HS = $value['J'];
                $BIAOMIAN_FS = $value['K'];
                $BIAOMIAN_HW = $value['L'];
                $CHUANGHUA = $value['M'];
                $GUIGE_CD = $value['N'];
                $GUIGE_K_M = $value['O'];
                $YONGLIAO_L_M = $value['P'];
                $YONGLIAO_K_M = $value['Q'];
                $CAILIAO_L_M = $value['R'];
                $CAILIAO_K_M = $value['S'];
                $GUIGE_L_Z = $value['T'];
                $GUIGE_K_Z = $value['U'];
                $YONGLIAO_L_Z = $value['V'];
                $YONGLIAO_K_Z = $value['W'];
                $CAILIAO_L_Z = $value['X'];
                $CAILIAO_K_Z = $value['Y'];

                $LIAOHAO = $value['AB'];
                $PINGMING = $value['AC'];
                $GUIGE = $value['AD'];
                $GUIGE_KDXSM = $value['AE'];
                $CAILIAO_KDXSM = $value['AF'];
                $GUIGE_KDXSZ = $value['AG'];
                $CAILIAO_KDXSZ = $value['AH'];
                $QPA_MUMEN = $value['AI'];
                $QPA_ZIMEN = $value['AJ'];
                $saveData = [
                    "DEPT_NAME" => $value['A'],
                    "PRODUCT_TYPE" => $value['B'],
                    "GUIGE_START" => $value['C'],
                    "GUIGE_END" => $value['D'],
                    "KUAN_START" => $value['E'],
                    "KUAN_END" => $value['F'],

                    "MENLEI_JG" => $value['G'],
                    "DANGCI" => $value['H'],
                    "MENSHAN_TYPE" => $value['I'],
                    "MENSHAN_HS" => $value['J'],
                    "BIAOMIAN_FS" => $value['K'],
                    "BIAOMIAN_HW" => $value['L'],
                    "CHUANGHUA" => $value['M'],
                    "GUIGE_CD" => $value['N'],
                    "GUIGE_K_M" => $value['O'],
                    "YONGLIAO_L_M" => $value['P'],
                    "YONGLIAO_K_M" => $value['Q'],
                    "CAILIAO_L_M" => $value['R'],
                    "CAILIAO_K_M" => $value['S'],
                    "GUIGE_L_Z" => $value['T'],
                    "GUIGE_K_Z" => $value['U'],
                    "YONGLIAO_L_Z" => $value['V'],
                    "YONGLIAO_K_Z" => $value['W'],
                    "CAILIAO_L_Z" => $value['X'],
                    "CAILIAO_K_Z" => $value['Y'],

                    "LIAOHAO" => $value['AB'],
                    "PINGMING" => $value['AC'],
                    "GUIGE" => $value['AD'],
                    "QPA_MUMEN" => '',
                    "QPA_ZIMEN" => '',
                ];
                $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,KUAN_START,KUAN_END,MENLEI_JG,DANGCI,MENSHAN_TYPE,MENSHAN_HS,BIAOMIAN_FS,BIAOMIAN_HW,CHUANGHUA,GUIGE_CD,GUIGE_K_M,YONGLIAO_L_M,YONGLIAO_K_M,CAILIAO_L_M,CAILIAO_K_M,
GUIGE_L_Z,GUIGE_K_Z,YONGLIAO_L_Z,YONGLIAO_K_Z,CAILIAO_L_Z,CAILIAO_K_Z,QPA_MUMEN,QPA_ZIMEN,LIAOHAO,PINGMING,GUIGE,
GUIGE_KDXSM,CAILIAO_KDXSM,GUIGE_KDXSZ,CAILIAO_KDXSZ,
SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$PRODUCT_TYPE','$GUIGE_START','$GUIGE_END','$KUAN_START','$KUAN_END',
'$MENLEI_JG','$DANGCI','$MENSHAN_TYPE','$MENSHAN_HS','$BIAOMIAN_FS','$BIAOMIAN_HW','$CHUANGHUA','$GUIGE_CD','$GUIGE_K_M','$YONGLIAO_L_M','$YONGLIAO_K_M',
'$CAILIAO_L_M','$CAILIAO_K_M','$GUIGE_L_Z','$GUIGE_K_Z','$YONGLIAO_L_Z','$YONGLIAO_K_Z','$CAILIAO_L_Z','$CAILIAO_K_Z','$QPA_MUMEN','$QPA_ZIMEN','$LIAOHAO',
'$PINGMING','$GUIGE','$GUIGE_KDXSM','$CAILIAO_KDXSM','$GUIGE_KDXSZ','$CAILIAO_KDXSZ',
'$sort','$type','$sheet_name')";
                $res = Db::execute($sql);
                if (empty($res)) {
                    array_push($err_data, $saveData);
                }
            }
        }
        return $err_data;
    }

    public function handleMenShanMf($data, $fileName, $type)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if (strpos($v, '母门铰方') !== false) {
                $arr = ['sort' => 1, 'sheet_name' => '木门木方长边骨架用料规则(母门铰方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '母门锁方') !== false) {
                $arr = ['sort' => 2, 'sheet_name' => '木门木方长边骨架用料规则(母门锁方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '子门铰方') !== false) {
                $arr = ['sort' => 3, 'sheet_name' => '木门木方长边骨架用料规则(子门铰方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '子门锁方') !== false) {
                $arr = ['sort' => 4, 'sheet_name' => '木门木方长边骨架用料规则(子门锁方)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '木方短边骨架') !== false) {
                $arr = ['sort' => 5, 'sheet_name' => '木门木方短边骨架用料规则'];
                $names[$k] = $arr;
            }
        }
        $err_data = [];  //存放错误信息
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                if (empty($value['A'])) {
                    continue;
                }
                $sheet_name = $names[$j]['sheet_name'];
                $sort = $names[$j]['sort'];
                if ($sort != 5) {
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "PRODUCT_TYPE" => $value['B'],
                        "GUIGE_START" => $value['C'],
                        "GUIGE_END" => $value['D'],
                        "DANGCI" => $value['E'],
                        "MENSHAN_TYPE" => $value['F'],
                        "MENSHAN_HS" => $value['G'],
                        "CHUANGHUA" => $value['H'],
                        "GUIGE_CD" => $value['I'],
                        "YONGLIAO_K_M" => $value['J'],
                        "YONGLIAO_L_M" => $value['K'],
                        "YONGLIAO_K_Z" => $value['L'],
                        "YONGLIAO_L_Z" => $value['M'],
                        "CAILIAO_CD" => $value['N'],
                        "QPA" => '',
                        "LIAOHAO" => $value['P'],
                        "PINGMING" => $value['Q'],
                        "GUIGE" => $value['R'],
                        "CAILIAO_KDXSM" => $value['S']
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,DANGCI,
MENSHAN_TYPE,MENSHAN_HS,CHUANGHUA,GUIGE_CD,YONGLIAO_K_M,YONGLIAO_L_M,YONGLIAO_K_Z,YONGLIAO_L_Z,CAILIAO_CD,QPA,LIAOHAO,PINGMING,GUIGE,CAILIAO_KDXSM,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'" . $saveData['DEPT_NAME'] . "','" . $saveData['PRODUCT_TYPE'] . "','" . $saveData['GUIGE_START'] . "','" . $saveData['GUIGE_END'] . "','" . $saveData['DANGCI'] . "',
'" . $saveData['MENSHAN_TYPE'] . "','" . $saveData['MENSHAN_HS'] . "','" . $saveData['CHUANGHUA'] . "','" . $saveData['GUIGE_CD'] . "','" . $saveData['YONGLIAO_K_M'] . "','" . $saveData['YONGLIAO_L_M'] . "','" . $saveData['YONGLIAO_K_Z'] . "','" . $saveData['YONGLIAO_L_Z'] . "',
'" . $saveData['CAILIAO_CD'] . "','" . $saveData['QPA'] . "','" . $saveData['LIAOHAO'] . "','" . $saveData['PINGMING'] . "',
'" . $saveData['GUIGE'] . "','" . $saveData['CAILIAO_KDXSM'] . "','$sort','$type','$sheet_name')";
                } else {
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "PRODUCT_TYPE" => $value['B'],
                        "GUIGE_START" => $value['C'],
                        "GUIGE_END" => $value['D'],
                        "DANGCI" => $value['E'],
                        "MENSHAN_TYPE" => $value['F'],
                        "MENSHAN_HS" => $value['G'],
                        "GUIGE_KD" => $value['H'],
                        "YONGLIAO_L_M" => $value['I'],
                        "CAILIAO_L_M" => $value['J'],
                        "YONGLIAO_G_M" => $value['K'],
                        "GUIGE_K_Z" => $value['L'],
                        "YONGLIAO_L_Z" => $value['M'],
                        "CAILIAO_L_Z" => $value['N'],
                        "YONGLIAO_G_Z" => $value['O'],
                        "QPA_MUMEN" => '',
                        "QPA_ZIMEN" => '',
                        "LIAOHAO" => $value['R'],
                        "PINGMING" => $value['S'],
                        "GUIGE" => $value['T'],
                        "GUIGE_KDXSM" => $value['U'],
                        "CAILIAO_KDXSM" => $value['V'],
                        "GUIGE_KDXSZ" => $value['W'],
                        "CAILIAO_KDXSZ" => $value['X'],
                    ];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,DANGCI,
MENSHAN_TYPE,MENSHAN_HS,GUIGE_KD,YONGLIAO_L_M,CAILIAO_L_M,YONGLIAO_G_M,
GUIGE_K_Z,YONGLIAO_L_Z,CAILIAO_L_Z,YONGLIAO_G_Z,QPA_MUMEN,QPA_ZIMEN,
LIAOHAO,PINGMING,GUIGE,GUIGE_KDXSM,CAILIAO_KDXSM,GUIGE_KDXSZ,CAILIAO_KDXSZ,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'" . $saveData['DEPT_NAME'] . "','" . $saveData['PRODUCT_TYPE'] . "','" . $saveData['GUIGE_START'] . "','" . $saveData['GUIGE_END'] . "','" . $saveData['DANGCI'] . "',

'" . $saveData['MENSHAN_TYPE'] . "','" . $saveData['MENSHAN_HS'] . "','" . $saveData['GUIGE_KD'] . "','" . $saveData['YONGLIAO_L_M'] . "','" . $saveData['CAILIAO_L_M'] . "','" . $saveData['YONGLIAO_G_M'] . "',
'" . $saveData['GUIGE_K_Z'] . "','" . $saveData['YONGLIAO_L_Z'] . "',
'" . $saveData['CAILIAO_L_Z'] . "','" . $saveData['YONGLIAO_G_Z'] . "','" . $saveData['QPA_MUMEN'] . "','" . $saveData['QPA_ZIMEN'] . "','" . $saveData['LIAOHAO'] . "','" . $saveData['PINGMING'] . "','" . $saveData['GUIGE'] . "',
'" . $saveData['GUIGE_KDXSM'] . "','" . $saveData['CAILIAO_KDXSM'] . "','" . $saveData['GUIGE_KDXSZ'] . "','" . $saveData['CAILIAO_KDXSZ'] . "','$sort','$type','$sheet_name')";
                }
                $res = Db::execute($sql);
                if (empty($res)) {
                    array_push($err_data, $saveData);
                }
            }
        }
        return $err_data;
    }

    public function handleMenShanXl($data, $fileName, $type)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if (strpos($v, '上边填充') !== false) {
                $arr = ['sort' => 1, 'sheet_name' => '木门芯料用料规则(上边填充)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '中间填充') !== false) {
                $arr = ['sort' => 2, 'sheet_name' => '木门芯料用料规则(中间填充)'];
                $names[$k] = $arr;
            } elseif (strpos($v, '下边填充') !== false) {
                $arr = ['sort' => 3, 'sheet_name' => '木门芯料用料规则(下边填充)'];
                $names[$k] = $arr;
            }
        }
        $err_data = [];  //存放错误信息
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                if (empty($value['A'])) {
                    continue;
                }
                $sheet_name = $names[$j]['sheet_name'];
                $sort = $names[$j]['sort'];
                $saveData = [
                    "DEPT_NAME" => $value['A'],
                    "PRODUCT_TYPE" => $value['B'],
                    "GUIGE_START" => $value['C'],
                    "GUIGE_END" => $value['D'],
                    "DANGCI" => $value['E'],
                    "MENSHAN_TYPE" => $value['F'],
                    "MENSHAN_HS" => $value['G'],
                    "CHUANGHUA" => $value['H'],
                    "MENSHAN_YQ" => $value['I'],
                    "TIANCHONG" => $value['J'],
                    "GUIGE_CD" => $value['K'],
                    "GUIGE_K_M" => $value['L'],
                    "YONGLIAO_L_M" => $value['M'],
                    "YONGLIAO_K_M" => $value['N'],
                    "CAILIAO_L_M" => $value['O'],
                    "CAILIAO_K_M" => $value['P'],
                    "GUIGE_L_Z" => $value['Q'],
                    "GUIGE_K_Z" => $value['R'],
                    "YONGLIAO_L_Z" => $value['S'],
                    "YONGLIAO_K_Z" => $value['T'],
                    "CAILIAO_L_Z" => $value['U'],
                    "CAILIAO_K_Z" => $value['V'],
                    "QPA_MUMEN" => '',
                    "QPA_ZIMEN" => '',
                    "LIAOHAO" => $value['Y'],
                    "PINGMING" => $value['Z'],
                    "GUIGE" => $value['AA'],
                    "GUIGE_KDXSM" => $value['AB'],
                    "CAILIAO_KDXSM" => $value['AC'],
                    "GUIGE_KDXSZ" => $value['AD'],
                    "CAILIAO_KDXSZ" => $value['AE']
                ];
                $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,DANGCI,
MENSHAN_TYPE,MENSHAN_HS,CHUANGHUA,MENSHAN_YQ,TIANCHONG,
GUIGE_CD,GUIGE_K_M,YONGLIAO_L_M,YONGLIAO_K_M,CAILIAO_L_M,CAILIAO_K_M,
GUIGE_L_Z,GUIGE_K_Z,YONGLIAO_L_Z,YONGLIAO_K_Z,CAILIAO_L_Z,CAILIAO_K_Z,
QPA_MUMEN,QPA_ZIMEN,LIAOHAO,PINGMING,GUIGE,
GUIGE_KDXSM,CAILIAO_KDXSM,GUIGE_KDXSZ,CAILIAO_KDXSZ,
SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'" . $saveData['DEPT_NAME'] . "','" . $saveData['PRODUCT_TYPE'] . "','" . $saveData['GUIGE_START'] . "','" . $saveData['GUIGE_END'] . "','" . $saveData['DANGCI'] . "','" . $saveData['MENSHAN_TYPE'] . "','" . $saveData['MENSHAN_HS'] . "',
'" . $saveData['CHUANGHUA'] . "','" . $saveData['MENSHAN_YQ'] . "',
'" . $saveData['TIANCHONG'] . "','" . $saveData['GUIGE_CD'] . "','" . $saveData['GUIGE_K_M'] . "','" . $saveData['YONGLIAO_L_M'] . "','" . $saveData['YONGLIAO_K_M'] . "','" . $saveData['CAILIAO_L_M'] . "','" . $saveData['CAILIAO_K_M'] . "',
'" . $saveData['GUIGE_L_Z'] . "','" . $saveData['GUIGE_K_Z'] . "','" . $saveData['YONGLIAO_L_Z'] . "','" . $saveData['YONGLIAO_K_Z'] . "','" . $saveData['CAILIAO_L_Z'] . "','" . $saveData['CAILIAO_K_Z'] . "',
'" . $saveData['QPA_MUMEN'] . "','" . $saveData['QPA_ZIMEN'] . "',
'" . $saveData['LIAOHAO'] . "','" . $saveData['PINGMING'] . "','" . $saveData['GUIGE'] . "',
'" . $saveData['GUIGE_KDXSM'] . "','" . $saveData['CAILIAO_KDXSM'] . "','" . $saveData['GUIGE_KDXSZ'] . "','" . $saveData['CAILIAO_KDXSZ'] . "',
'$sort','$type','$sheet_name')";
                $res = Db::execute($sql);
                if (empty($res)) {
                    array_push($err_data, $saveData);
                }
            }
        }
        return $err_data;
    }
    public function handleMuMenXt($data, $fileName, $type)
    {
        $names = [];  //存放不同sort值和name
        foreach ($fileName as $k => $v) {
            if (strpos($v, '木门侧边线条') !== false) {
                $arr = ['sort' => 1, 'sheet_name' => '木门侧边线条'];
                $names[$k] = $arr;
            } elseif (strpos($v, '木门上边线条') !== false) {
                $arr = ['sort' => 2, 'sheet_name' => '木门上边线条'];
                $names[$k] = $arr;
            } elseif (strpos($v, '木门下边线条') !== false) {
                $arr = ['sort' => 3, 'sheet_name' => '木门下边线条'];
                $names[$k] = $arr;
            }
        }
        $err_data = [];  //存放错误信息
        for ($j = 0; $j < count($data, 0); $j++) {
            foreach ($data[$j] as $key => $value) {
                if (empty($value['A'])) {
                    continue;
                }
                $sheet_name = $names[$j]['sheet_name'];
                $sort = $names[$j]['sort'];
                if ($sort != 3) {
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "PRODUCT_TYPE" => $value['B'],
                        "MENLEI_JG" => $value['C'],
                        "XIANTIAO_TYPE" => $value['D'],
                        "XIANTIAO_YS" => $value['E'],
                        "XIANTIAO_JG" => $value['F'],
                        "BIAOMIAN_FS" => $value['G'],
                        "BIAOMIAN_HW" => $value['H'],
                        "GUIGE_START" => $value['I'],
                        "GUIGE_END" => $value['J'],
                        "GUIGE_CD" => $value['K'],     // 当sort=2时,GUIGE_CD表示规格宽度
                        "YONGLIAO_CD" => $value['L'],
                        "CAILIAO_CD" => $value['M'],
                        "QPA" => $value['N'],
                        "LIAOHAO" => $value['O'],
                        "PINGMING" => $value['P'],
                        "GUIGE" => $value['Q'],
                    ];
                    $DEPT_NAME = $value['A'];
                    $PRODUCT_TYPE = $value['B'];
                    $MENLEI_JG = $value['C'];
                    $XIANTIAO_TYPE = $value['D'];
                    $XIANTIAO_YS = $value['E'];
                    $XIANTIAO_JG = $value['F'];
                    $BIAOMIAN_FS = $value['G'];
                    $BIAOMIAN_HW = $value['H'];
                    $GUIGE_START = $value['I'];
                    $GUIGE_END = $value['J'];
                    $GUIGE_CD = $value['K'];
                    $YONGLIAO_CD = $value['L'];
                    $CAILIAO_CD = $value['M'];
                    $QPA = $value['N'];
                    $LIAOHAO = $value['O'];
                    $PINGMING = $value['P'];
                    $GUIGE = $value['Q'];
                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,PRODUCT_TYPE,MENLEI_JG,XIANTIAO_TYPE,XIANTIAO_YS,
XIANTIAO_JG,BIAOMIAN_FS,BIAOMIAN_HW,GUIGE_START,GUIGE_END,
GUIGE_CD,YONGLIAO_CD,CAILIAO_CD,QPA,
LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$PRODUCT_TYPE','$MENLEI_JG','$XIANTIAO_TYPE','$XIANTIAO_YS','$XIANTIAO_JG','$BIAOMIAN_FS','$BIAOMIAN_HW','$GUIGE_START','$GUIGE_END','$GUIGE_CD','$YONGLIAO_CD','$CAILIAO_CD','$QPA','$LIAOHAO','$PINGMING','$GUIGE'
,'$sort','$type','$sheet_name')";
                } else {
                    $saveData = [
                        "DEPT_NAME" => $value['A'],
                        "DINGDAN_TYPE" => $value['B'],
                        "PRODUCT_TYPE" => $value['C'],
                        "MENLEI_JG" => $value['D'],
                        "XIANTIAO_TYPE" => $value['E'],
                        "XIANTIAO_YS" => $value['F'],
                        "XIANTIAO_JG" => $value['G'],
                        "BIAOMIAN_FS" => $value['H'],
                        "BIAOMIAN_HW" => $value['I'],
                        "GUIGE_START" => $value['J'],
                        "GUIGE_END" => $value['K'],
                        "GUIGE_CD" => $value['L'],
                        "YONGLIAO_CD" => $value['M'],
                        "CAILIAO_CD" => $value['N'],
                        "QPA" => $value['O'],
                        "LIAOHAO" => $value['P'],
                        "PINGMING" => $value['Q'],
                        "GUIGE" => $value['R'],
                    ];
                    $DEPT_NAME = $value['A'];
                    $DINGDAN_TYPE = $value['B'];
                    $PRODUCT_TYPE = $value['C'];
                    $MENLEI_JG = $value['D'];
                    $XIANTIAO_TYPE = $value['E'];
                    $XIANTIAO_YS = $value['F'];
                    $XIANTIAO_JG = $value['G'];
                    $BIAOMIAN_FS = $value['H'];
                    $BIAOMIAN_HW = $value['I'];
                    $GUIGE_START = $value['J'];
                    $GUIGE_END = $value['K'];
                    $GUIGE_CD = $value['L'];
                    $YONGLIAO_CD = $value['M'];
                    $CAILIAO_CD = $value['N'];
                    $QPA = $value['O'];
                    $LIAOHAO = $value['P'];
                    $PINGMING = $value['Q'];
                    $GUIGE = $value['R'];

                    $sql = "insert into BOM_MUMEN_MENSHAN_RULE(ID,DEPT_NAME,DINGDAN_TYPE,PRODUCT_TYPE,MENLEI_JG,XIANTIAO_TYPE,XIANTIAO_YS,
XIANTIAO_JG,BIAOMIAN_FS,BIAOMIAN_HW,GUIGE_START,GUIGE_END,
GUIGE_CD,YONGLIAO_CD,CAILIAO_CD,QPA,
LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME) VALUES (BOM_FENGBIANTIAO_ID.nextval,'$DEPT_NAME','$DINGDAN_TYPE','$PRODUCT_TYPE','$MENLEI_JG','$XIANTIAO_TYPE','$XIANTIAO_YS','$XIANTIAO_JG','$BIAOMIAN_FS','$BIAOMIAN_HW','$GUIGE_START','$GUIGE_END','$GUIGE_CD','$YONGLIAO_CD','$CAILIAO_CD','$QPA','$LIAOHAO','$PINGMING','$GUIGE'
,'$sort','$type','$sheet_name')";
                }
                $res = Db::execute($sql);
                if (empty($res)) {
                    array_push($err_data, $saveData);
                }
            }
        }
        return $err_data;
    }
}