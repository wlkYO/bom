<?php

namespace app\admin\model;


use think\Db;

class MaterialRuleModel
{
    public function saveExcelData($insertSql)
    {
        Db::startTrans();
        $ret = Db::execute($insertSql);    //插入新数据
        Db::commit();
        return $ret;
    }

    //获取当前表所有类型，sheetName用到
    public function getTableTypes($table)
    {
        $sql = "select distinct(type),sort from $table order by sort";
        $ret = Db::query($sql, true);
        return $ret;
    }

    //获取当前sheet所有数据
    public function getSheetData($key, $table, $where)
    {
        $sql = "select $key from $table where $where order by id";
        $ret = Db::query($sql, true);
        return $ret;
    }

    //删除表数据
    public function delTableData($table, $product_category)
    {
        Db::startTrans();
        $sql = "delete from $table where product_category = '$product_category'";
        $ret = Db::execute($sql);
        Db::commit();
        return $ret;
    }
}

?>