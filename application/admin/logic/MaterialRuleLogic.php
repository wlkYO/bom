<?php

namespace app\admin\logic;

use think\Db;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use app\admin\model\MaterialRuleModel;

class MaterialRuleLogic
{
    //文件上传路径
    private static $uploadPath;
    private static $PHPExcel;
    //表的类型
    protected $_tableType;
    //产品种类
    protected $productCategory;

    public function __construct($sheet)
    {
        $this->_tableType = $sheet;
    }

    public function __get($paramName)
    {
        return $this->$paramName;
    }

    public function __set($paramName, $value)
    {
        $this->$paramName = $value;
    }

    //根据规则类型、sheetName获取相应字段
    public function getOption($tableType = '', $sheetName = '')
    {
        switch ($tableType) {
            case "木门门套用料规则(复合实木)":
            case "木门门套用料规则(集成实木)":
                {
                    $option = [
                        'titleLine' => 1,
                        'firstLine' => 2,
                        'columnMap' => [
                            '部门' => 'dept',
                            '订单类别' => 'chuanghua_order',    //都存在chuanghau_order字段中
                            '产品种类' => 'product_category',
                            '门类结构' => 'door_structure',
                            '门套墙体' => 'door_wall',
                            '门套厚度' => 'doorcover_thickness',
                            '门套结构' => 'doorcover_structure',
                            '门套样式' => 'door_pattern',
                            '表面方式' => 'surface_mode',
                            '表面花纹' => 'surface_pattern',
                            '窗花' => 'chuanghua_order',  //都存在chuanghau_order字段中
                            '高度左区间' => 'hw_left',       //高/宽左区间
                            '高度右区间' => 'hw_right',      //高/宽右区间
                            '宽度左区间' => 'hw_left',
                            '宽度右区间' => 'hw_right',
                            '规格长度' => 'spec_lw',    //都存在spec_lw字段
                            '规格宽度' => 'spec_lw',    //都存在spec_lw字段
                            '用料长度' => 'yongliao_length',
                            '用料宽度' => 'yongliao_width',
                            '材料长度' => 'material_length',
                            '材料宽度' => 'material_width',
                            '料号' => 'material_num',
                            '品名' => 'product_name',
                            '规格' => 'spec',
                            '类型' => 'type',
                        ]
                    ];

                    $columnMap = &$option['columnMap'];
                    switch ($sheetName) {
                        case "侧方套板（主板面板）":
                        case "侧方套板（副板面板）":
                        case "侧方套板（主板背板）":
                        case "侧方套板（副板背板）":
                        case "侧方套板（主板芯料）":
                        case "侧方套板（副板芯料）":
                            unset($columnMap['宽度左区间']);
                            unset($columnMap['宽度右区间']);
                            unset($columnMap['规格宽度']);
                            unset($columnMap['窗花']);
                            unset($columnMap['订单类别']);
                            break;
                        case "上方套板（主板面板）":
                        case "上方套板（副板面板）":
                        case "上方套板（主板背板）":
                        case "上方套板（副板背板）":
                        case "上方套板（主板芯料）":
                        case "上方套板（副板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['规格长度']);
                            unset($columnMap['窗花']);
                            unset($columnMap['订单类别']);
                            break;
                        case "中档套板（主板面板）":
                        case "中档套板（副板面板）":
                        case "中档套板（主板背板）":
                        case "中档套板（副板背板）":
                        case "中档套板（主板芯料）":
                        case "中档套板（副板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['规格长度']);
                            unset($columnMap['订单类别']);
                            break;
                        case "下方套板（主板面板）":
                        case "下方套板（主板背板）":
                        case "下方套板（主板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['窗花']);
                            unset($columnMap['规格长度']);
                            break;
                    }
                    break;
                }
            case "木门门套用料规则(强化木门)":  //与其他三个多了线条样式
                {
                    $option = [
                        'titleLine' => 1,
                        'firstLine' => 2,
                        'columnMap' => [
                            '部门' => 'dept',
                            '订单类别' => 'chuanghua_order',    //都存在chuanghau_order字段中
                            '产品种类' => 'product_category',
                            '门类结构' => 'door_structure',
                            '门套墙体' => 'door_wall',
                            '门套厚度' => 'doorcover_thickness',
                            '门套结构' => 'doorcover_structure',
                            '门套样式' => 'door_pattern',
                            '表面方式' => 'surface_mode',
                            '表面花纹' => 'surface_pattern',
                            '线条样式' => 'line_style',
                            '窗花' => 'chuanghua_order',  //都存在chuanghau_order字段中
                            '高度左区间' => 'hw_left',       //高/宽左区间
                            '高度右区间' => 'hw_right',      //高/宽右区间
                            '宽度左区间' => 'hw_left',
                            '宽度右区间' => 'hw_right',
                            '规格长度' => 'spec_lw',    //都存在spec_lw字段
                            '规格宽度' => 'spec_lw',    //都存在spec_lw字段
                            '用料长度' => 'yongliao_length',
                            '用料宽度' => 'yongliao_width',
                            '材料长度' => 'material_length',
                            '材料宽度' => 'material_width',
                            '料号' => 'material_num',
                            '品名' => 'product_name',
                            '规格' => 'spec',
                            '类型' => 'type',
                        ]
                    ];

                    $columnMap = &$option['columnMap'];
                    switch ($sheetName) {
                        case "侧方套板（主板面板）":
                        case "侧方套板（副板面板）":
                        case "侧方套板（主板背板）":
                        case "侧方套板（副板背板）":
                        case "侧方套板（主板芯料）":
                        case "侧方套板（副板芯料）":
                            unset($columnMap['宽度左区间']);
                            unset($columnMap['宽度右区间']);
                            unset($columnMap['规格宽度']);
                            unset($columnMap['窗花']);
                            unset($columnMap['订单类别']);
                            break;
                        case "上方套板（主板面板）":
                        case "上方套板（副板面板）":
                        case "上方套板（主板背板）":
                        case "上方套板（副板背板）":
                        case "上方套板（主板芯料）":
                        case "上方套板（副板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['规格长度']);
                            unset($columnMap['窗花']);
                            unset($columnMap['订单类别']);
                            break;
                        case "中档套板（主板面板）":
                        case "中档套板（副板面板）":
                        case "中档套板（主板背板）":
                        case "中档套板（副板背板）":
                        case "中档套板（主板芯料）":
                        case "中档套板（副板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['规格长度']);
                            unset($columnMap['订单类别']);
                            break;
                        case "下方套板（主板面板）":
                        case "下方套板（主板背板）":
                        case "下方套板（主板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['窗花']);
                            unset($columnMap['规格长度']);
                            break;
                    }
                    break;
                }
            case "木门门套用料规则(转印木门)":
                {
                    $option = [
                        'titleLine' => 1,
                        'firstLine' => 2,
                        'columnMap' => [
                            '部门' => 'dept',
                            '订单类别' => 'chuanghua_order',    //都存在chuanghau_order字段中
                            '产品种类' => 'product_category',
                            '门类结构' => 'door_structure',
                            '门套墙体' => 'door_wall',
                            '门套厚度' => 'doorcover_thickness',
                            '门套结构' => 'doorcover_structure',
                            '门套样式' => 'door_pattern',
                            '表面方式' => 'surface_mode',
                            '表面花纹' => 'surface_pattern',
                            '档次' => 'line_style',   //线条样式（强化木门）/档次（转印木门）
                            '窗花' => 'chuanghua_order',  //都存在chuanghau_order字段中
                            '高度左区间' => 'hw_left',       //高/宽左区间
                            '高度右区间' => 'hw_right',      //高/宽右区间
                            '宽度左区间' => 'hw_left',
                            '宽度右区间' => 'hw_right',
                            '规格长度' => 'spec_lw',    //都存在spec_lw字段
                            '规格宽度' => 'spec_lw',    //都存在spec_lw字段
                            '用料长度' => 'yongliao_length',
                            '用料宽度' => 'yongliao_width',
                            '材料长度' => 'material_length',
                            '材料宽度' => 'material_width',
                            '料号' => 'material_num',
                            '品名' => 'product_name',
                            '规格' => 'spec',
                            '类型' => 'type',
                        ]
                    ];

                    $columnMap = &$option['columnMap'];
                    switch ($sheetName) {
                        case "侧方套板（主板面板）":
                        case "侧方套板（副板面板）":
                        case "侧方套板（主板背板）":
                        case "侧方套板（副板背板）":
                        case "侧方套板（主板芯料）":
                        case "侧方套板（副板芯料）":
                            unset($columnMap['宽度左区间']);
                            unset($columnMap['宽度右区间']);
                            unset($columnMap['规格宽度']);
                            unset($columnMap['窗花']);
                            unset($columnMap['订单类别']);
                            break;
                        case "上方套板（主板面板）":
                        case "上方套板（副板面板）":
                        case "上方套板（主板背板）":
                        case "上方套板（副板背板）":
                        case "上方套板（主板芯料）":
                        case "上方套板（副板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['规格长度']);
                            unset($columnMap['窗花']);
                            unset($columnMap['订单类别']);
                            break;
                        case "中档套板（主板面板）":
                        case "中档套板（副板面板）":
                        case "中档套板（主板背板）":
                        case "中档套板（副板背板）":
                        case "中档套板（主板芯料）":
                        case "中档套板（副板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['规格长度']);
                            unset($columnMap['订单类别']);
                            break;
                        case "下方套板（主板面板）":
                        case "下方套板（主板背板）":
                        case "下方套板（主板芯料）":
                            unset($columnMap['高度左区间']);
                            unset($columnMap['高度右区间']);
                            unset($columnMap['窗花']);
                            unset($columnMap['规格长度']);
                            break;
                    }
                    break;
                }
        }
        return $option;
    }

    /**导入部分**/
    //窗花用料规则导入文件处理
    public function importExcel($extension, $tempPath, $sheet, $tempPath)
    {
        $materialRuleModel = new MaterialRuleModel();
        $table = $this->getTable($sheet);
        $ret = $materialRuleModel->delTableData($table, $this->productCategory); //清空该类型表
        //box/spout
        vendor('boxspout.src.Spout.Autoloader.autoload');
        $reader = ReaderFactory::create($extension);
        $reader->open($tempPath);

        $sort = 0;  //sheet序号
        $retNum = '';
        foreach ($reader->getSheetIterator() as $objSheet) {
            $sheetName = $objSheet->getName();
            $option = $this->getOption($sheet, $sheetName);
            $columnMap = $option['columnMap'];  //自己维护的标题栏

            $sort++;
            $inertStr = '';
            $union = '';
            $insertSql = '';
            $emptyNum = '';     //当前sheet为空的该列下标
            $productCategoryNum = '';   //产品种类下标
            foreach ($objSheet->getRowIterator() as $row) {
                // do stuff with the row
                if (empty($inertStr)) {    //第一行是标题
                    foreach ($row as $rk => $rv) {
                        if ($columnMap[$rv] === null) {  //找到为空的下标，目前只考虑QPA为空值的情况
                            $emptyNum = $rk;
                        } else {
                            $inertStr[] = $columnMap[$rv];  //根据自定义，筛选要存的字段
                        }
                        if ($columnMap[$rv] === 'product_category') {
                            $productCategoryNum = $rk;
                        }
                    }
                    //$insertArr = array_intersect_key(array_values($columnMap),$row);
                    array_push($inertStr, 'type', 'sort');
                    $inertStr = implode(',', $inertStr);
                } else {  //后面是数值
                    if (!empty($row[$productCategoryNum])) {  //产品种类列为空，表示该行无数据，不进行存储
                        unset($row[$emptyNum]); //移除为空的那一列
                        array_push($row, $sheetName, $sort);
                        $valueStr = "'" . implode("','", $row) . "'";
                        $union .= " select $valueStr from dual union";
                    }
                }
            }
            $union = rtrim($union, 'union');
            $insertSql = "insert into $table($inertStr) $union";
            $retNum += $materialRuleModel->saveExcelData($insertSql);
        }
        $reader->close();
        return $retNum;
    }

    /**导出部分*/
    //用料规则导出

    public function exportExcel()
    {
        $table = $this->getTable($this->_tableType);
        $types = $this->getTableTypes($table);
        $isFirst = 0;
        //box/spout
        vendor('boxspout.src.Spout.Autoloader.autoload');

        $filePath = str_replace('\\', '/', realpath(__DIR__ . '/../../../')) . '/upload/' . $this->_tableType . '.xlsx';
        $writer = WriterFactory::create('xlsx');
        $writer->openToFile($filePath);
        $style = (new StyleBuilder())
            ->setFontBold()
            ->build();
        foreach ($types as $type) {
            $sheetName = $type['type'];
            $option = $this->getOption($this->_tableType, $sheetName);
            unset($option['columnMap']['类型']);
            $key = array_values($option['columnMap']);

            $key = implode($key, ',');
            $where = "product_category = '$this->productCategory' and type = '$sheetName'";
            $sheetData = $this->getSheetData($key, $table, $where);

            if ($isFirst) {
                $newSheet = $writer->addNewSheetAndMakeItCurrent(); //新增sheet
                $newSheet->setName($sheetName);
                $writer->addRowWithStyle(array_keys($option['columnMap']), $style);  //添加表头
                $writer->addRows($sheetData);   //添加数据部分
            } else {
                $sheet = $writer->getCurrentSheet();
                $sheet->setName($sheetName);
                $writer->addRowWithStyle(array_keys($option['columnMap']), $style);  //添加表头
                $writer->addRows($sheetData);   //添加数据部分
                $isFirst++;
            }
        }
        $writer->openToBrowser($filePath);
        $writer->close();
        unlink($filePath);
    }

    //根据类型获取表
    public function getTable($tableType = null)
    {
        if ($tableType) {
            switch ($tableType) {
                case "木门门套用料规则(复合实木)":
                    $this->productCategory = '复合实木门';
                    $table = 'BOM_MUMEN_MENTAO_RULE';
                    break;
                case "木门门套用料规则(集成实木)":
                    $this->productCategory = '集成实木门';
                    $table = 'BOM_MUMEN_MENTAO_RULE';
                    break;
                case "木门门套用料规则(强化木门)":
                    $this->productCategory = '强化木门';
                    $table = 'BOM_MUMEN_MENTAO_RULE';
                    break;
                case "木门门套用料规则(转印木门)":
                    $this->productCategory = '转印木门';
                    $table = 'BOM_MUMEN_MENTAO_RULE';
                    break;
            }
        } else {
            return retmsg(-1, '', '未能获取到导入表的类型');
        }
        return $table;
    }

    //获取当前表所有类型，sheetName用到
    public function getTableTypes($table)
    {
        $materialRuleModel = new MaterialRuleModel();
        $ret = $materialRuleModel->getTableTypes($table);
        return $ret;
    }

    //获取当前sheet所有数据
    public function getSheetData($key, $table, $where)
    {
        $materialRuleModel = new MaterialRuleModel();
        $ret = $materialRuleModel->getSheetData($key, $table, $where);
        return $ret;
    }

}

?>