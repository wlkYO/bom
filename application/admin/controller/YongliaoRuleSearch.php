<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/12
 * Time: 14:04
 */

namespace app\admin\controller;

use Firebase\JWT\JWT;
use phpDocumentor\Reflection\Types\Array_;
use Think\Action;
use think\Db;
use think\controller\Rest;

//编码规则类
class YongliaoRuleSearch extends Rest
{
    /**
     * 获取用料规则数据
     * @param $sheet_name
     * @param null $like
     * @return array
     */
    public function getYongLiaoRule($sheet_name = '', $page = 1, $pageSize = 30, $like = null, $zhizaobm = null, $dangci = null, $menkuang = null, $menshan = null, $kaixiang = null, $menkuanghd = null, $guigecd = null, $xinghao = null, $dikuangcl = null, $dikuang = null, $dept = null, $doorStructure = null, $doorWall = null, $doorPattern = null, $surfacePattern = null, $productName = null, $type = null)
    {
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        if (empty($sheet_name)) {
            $sheet_name = 'manbanLength';
        }

        $arrParam = [   //参数数组维护模块，字段名=>参数
            'zhizaobm' => $zhizaobm,
            'dangci' => $dangci,
            'menkuang' => $menkuang,
            'menshan' => $menshan,
            'kaixiang' => $kaixiang,
            'menkuanghd' => $menkuanghd,
            'guigecd' => $guigecd,
            'xinghao' => $xinghao,
            'dikuangcl' => $dikuangcl,
            'dikuang' => $dikuang,
            'dept' => $dept,
            'door_structure' => $doorStructure,
            'door_wall' => $doorWall,
            'door_pattern' => $doorPattern,
            'surface_pattern' => $surfacePattern,
            'product_name' => $productName,
            'type' => $type
        ];

        foreach ($arrParam as $paramK => $paramV) {
            if (!empty($param)) {
                $where [] = " $paramK like '%$paramV%'";
            }
        }

        $data = array(
            array('sheet' => '门板长度用料规则', 'sheet_name' => 'manbanLength', 'info' => array(), 'title' => array()),
            array('sheet' => '门板宽度用料规则', 'sheet_name' => 'menbanWide', 'info' => array(), 'title' => array()),
            array('sheet' => '门框铰方、锁方用料规则', 'sheet_name' => 'menkuangJiaoSuo', 'info' => array(), 'title' => array()),
            array('sheet' => '门框上门框、中门框用料规则', 'sheet_name' => 'menkuangShangZhong', 'info' => array(), 'title' => array()),
            array('sheet' => '下门框用料规则', 'sheet_name' => 'menkuangXia', 'info' => array(), 'title' => array()),
            array('sheet' => '窗花计算规则', 'sheet_name' => 'chuanghuaJisuan', 'info' => array(), 'title' => array()),
            array('sheet' => '四开窗花计算规则', 'sheet_name' => 'sikaiChuanghuaJisuan', 'info' => array(), 'title' => array()),
            array('sheet' => '钢框木扇用料规则', 'sheet_name' => 'gangkuangMushan', 'info' => array(), 'title' => array()),
            array('sheet' => '防火门填芯用料规则', 'sheet_name' => 'tianxinMenshan', 'info' => array(), 'title' => array()),
            array('sheet' => '齐河基地钢框木扇用料规则', 'sheet_name' => 'gangkuangMushanQH', 'info' => array(), 'title' => array()),
            array('sheet' => '齐河基地防火门填芯用料规则', 'sheet_name' => 'tianxinMenshanQH', 'info' => array(), 'title' => array()),
            array('sheet' => '防火门钢框木扇免漆板用料规则', 'sheet_name' => 'mianQib', 'info' => array(), 'title' => array()),
            array('sheet' => '防火门钢框木扇封边条用料规则', 'sheet_name' => 'fengBiant', 'info' => array(), 'title' => array()),
            array('sheet' => '中式窗花带玻胶条用料规则', 'sheet_name' => 'chuanghuaZhongshiJisuan', 'info' => array(), 'title' => array()),
            array('sheet' => 'pe包装用料规则', 'sheet_name' => 'peBaozhuang', 'info' => array(), 'title' => array()),
            array('sheet' => '成都基地防火门钢框木扇长边骨架用料规则', 'sheet_name' => 'changbianGujiaYongliao', 'info' => array(), 'title' => array()),
            array('sheet' => '成都基地防火门钢框木扇长边骨架品名规则', 'sheet_name' => 'changbianGujiaPinming', 'info' => array(), 'title' => array()),
            array('sheet' => '常规窗花用料规则', 'sheet_name' => 'chuanghua_cg', 'info' => array(), 'title' => array()),
            array('sheet' => '不锈钢窗花用料规则', 'sheet_name' => 'chuanghua_bxg', 'info' => array(), 'title' => array()),
            array('sheet' => '中式窗花用料规则', 'sheet_name' => 'chuanghua_zs', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门套用料规则(复合实木)', 'sheet_name' => 'fuhe_shimu', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门套用料规则(集成实木)', 'sheet_name' => 'jicheng_shimu', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门套用料规则(强化木门)', 'sheet_name' => 'qianghua_mumen', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门套用料规则(转印木门)', 'sheet_name' => 'zhuanyin_mumen', 'info' => array(), 'title' => array()),
            array('sheet' => '木门封边条用料规则', 'sheet_name' => 'mumen_fbt', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门扇面板用料规则', 'sheet_name' => 'mumen_menshan_mb', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门扇木方用料规则', 'sheet_name' => 'mumen_menshan_mf', 'info' => array(), 'title' => array()),
            array('sheet' => '木门门扇芯料用料规则', 'sheet_name' => 'mumen_menshan_xl', 'info' => array(), 'title' => array()),
            array('sheet' => '木门线条用料规则', 'sheet_name' => 'mumen_xt', 'info' => array(), 'title' => array()),
            array('sheet' => '成都封闭式窗花用料规则', 'sheet_name' => 'chuanghua_cd_fbs', 'info' => array(), 'title' => array()),
            array('sheet' => '齐河封闭式窗花用料规则', 'sheet_name' => 'chuanghua_qh_fbs', 'info' => array(), 'title' => array()),
            array('sheet' => '窗花玻璃用料规则', 'sheet_name' => 'glass_rule', 'info' => array(), 'title' => array()),
        );
        switch ($sheet_name) {
            //门板长度用料规则
            case 'manbanLength':
                {
                    $i = 0;
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or menshan like '%$like%' or
                     guigecd like '%$like%' or dikaungcl like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $title = array('制造部门', '档次', '门框', '门扇', '底框材料', '规格长度', '用料长度(母前门板)', '用料长度(母后门板)', '用料长度(子前门板)', '用料长度(子后门板)', '用料长度(母子前门板)', '用料长度(母子后门板)', '用料长度(子子前门板)', '用料长度(子子后门板)', '框厚', '开向');
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510)) ZHIZAOBM,cast(DANGCI as VARCHAR2 (510)) DANGCI,cast(MENKUANG as VARCHAR2 (510)) MENKUANG,cast(MENSHAN as VARCHAR2 (510)) MENSHAN,cast(DIKUANGCL as VARCHAR2 (510)) DIKUANGCL,GUIGECD,MUQIANMB,MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,
                          MUZIHMB,ZIZIQMB,ZIZIHMB,MKHOUDU,KAIXIANG from BOM_MENBAN_LENGTH_RULE $whereStr order by DANGCI,MENKUANG,MENSHAN,GUIGECD";
                    break;
                }
            //门板宽度用料规则
            case 'menbanWide':
                {
                    $i = 1;
                    $title = array('制造部门', '档次', '门框', '门扇', '前门板', '后门板', '规格宽度', '用料宽度(母前门板)', '用料宽度(母后门板)', '用料宽度(子前门板)', '用料宽度(子后门板)', '用料宽度(母子前门板)', '用料宽度(母子后门板)', '用料宽度(子子前门板)', '用料宽度(子子后门板)', '门扇材质', '框厚', '开向');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or menshan like '%$like%' or 
                    guigecd like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510)) ZHIZAOBM,cast(DANGCI as VARCHAR2 (510)) DANGCI,cast(MENKUANG as VARCHAR2 (510)) MENKUANG,cast(MENSHAN as VARCHAR2 (510)) MENSHAN,QIANMB,HOUMB,GUIGECD,MUQIANMB,MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,
                          MUZIHMB,ZIZIQMB,ZIZIHMB,MENSHANCZ,MKHOUDU,KAIXIANG from BOM_MENBAN_WIDE_RULE $whereStr order by DANGCI,MENKUANG,MENSHAN,GUIGECD";
                    break;
                }
            //铰,锁门框
            case 'menkuangJiaoSuo':
                {
                    $i = 2;
                    $title = array('制造部门', '档次', '门框', '开向', '门框厚度', '规格长度', '用料长度(铰门框)', '用料(铰门框)', '用量厚度', '密度', '用料长度(锁门框)', '用料(锁门框)', '用量厚度', '密度', '门框材质', '门扇');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or kaixiang like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510))ZHIZAOBM,cast(DANGCI as VARCHAR2 (510))DANGCI,cast(MENKUANG as VARCHAR2 (510))MENKUANG,cast(KAIXIANG as VARCHAR2 (510))KAIXIANG,MENKUANGHD,GUIGECD,JIAOMK_LENGTH,JIAOMK,JIAOMK_HD,
                          JIAOMK_DENSITY,SUOMK_LENGTH,SUOMK,SUOMK_HD,SUOMK_DENSITY,cast(MENKUANGCZ as VARCHAR2 (510))MENKUANGCZ,cast(MENSHAN as VARCHAR2 (510))MENSHAN from BOM_MENKUANG_JIAOSUO_RULE $whereStr order by DANGCI,MENKUANG,KAIXIANG";
                    break;
                }
            //上,中门框
            case 'menkuangShangZhong':
                {
                    $i = 3;
                    $title = array('制造部门', '档次', '门框', '门扇', '开向', '门框厚度', '规格宽度', '用料长度(上门框)', '用料(上门框)', '用量厚度', '密度', '用料长度(中门框)', '用料(中门框)', '用量厚度', '密度', '门框材质');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or menshan like '%$like%' or 
                    kaixiang like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510))ZHIZAOBM,cast(DANGCI as VARCHAR2 (510))DANGCI,cast(MENKUANG as VARCHAR2 (510))MENKUANG,cast(MENSHAN as VARCHAR2 (510))MENSHAN,cast(KAIXIANG as VARCHAR2 (510))KAIXIANG,MENKUANGHD,GUIGECD,SHANGMK_LENGTH,SHANGMK,
                           SHANGMK_HD,SHANGMK_DENSITY,ZHONGMK_LENGTH,ZHONGMK,ZHONGMK_HD,ZHONGMK_DENSITY,cast(MENKUANGCZ as VARCHAR2 (510))MENKUANGCZ from BOM_MENKUANG_SHANGZHONG_RULE $whereStr order by DANGCI,MENKUANG,MENSHAN,KAIXIANG";
                    break;
                }
            //下门框
            case 'menkuangXia':
                {
                    $i = 4;
                    $title = array('制造部门', '档次', '门框', '门扇', '开向', '门框厚度', '底框材料', '规格宽度', '用料长度(下门框)', '用料(下门框)', '用量厚度', '密度', '门框材质');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or menshan like '%$like%' or 
                    kaixiang like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510))ZHIZAOBM,cast(DANGCI as VARCHAR2 (510))DANGCI,cast(MENKUANG as VARCHAR2 (510))MENKUANG,cast(MENSHAN as VARCHAR2 (510))MENSHAN,cast(KAIXIANG as VARCHAR2 (510))KAIXIANG,MENKUANGHD,cast(DIKUANGCL as VARCHAR2 (510))DIKUANGCL,GUIGECD,XIAMK_LENGTH,
                            XIAMK,XIAMK_HD,XIAMK_DENSITY,cast(MENKUANGCZ as VARCHAR2 (510))MENKUANGCZ from BOM_MENKUANG_XIA_RULE $whereStr order by DANGCI,MENKUANG,MENSHAN,KAIXIANG";
                    break;
                }
            //窗花
            case 'chuanghuaJisuan':
                {
                    $i = 5;
                    $title = array('制造部门', '门框', '档次', '窗花', '开向', '门扇', '门框厚度', '开始区间', '结束区间', '高度规则', '宽度规则', '用量');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or chuanghua like '%$like%' or 
                    kaixiang like '%$like%' or menshan like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510)) as ZHIZAOBM, cast(MENKUANG as VARCHAR2 (1000)) as MENKUANG, cast(DANGCI as VARCHAR2 (510)) as DANGCI, cast(CHUANGHUA as VARCHAR2 (1000)) as CHUANGHUA, cast(KAIXIANG as VARCHAR2 (510)) as KAIXIANG, cast(MENSHAN as VARCHAR2 (510)) as MENSHAN, cast(MKHOUDU as VARCHAR2 (510)) as MKHOUDU, cast(START_HEIGHT as VARCHAR2 (510)) as START_HEIGHT, cast(END_HEIGHT as VARCHAR2 (510)) as END_HEIGHT, cast(MKHEIGHT_RULE as VARCHAR2 (510)) as MKHEIGHT_RULE, cast(MKWIDE_RULE as VARCHAR2 (510)) as MKWIDE_RULE, cast(USAGE as VARCHAR2 (510)) as USAGE from BOM_CHUANGHUA_RULE $whereStr order by DANGCI,MENKUANG,CHUANGHUA,KAIXIANG,MENSHAN,START_HEIGHT";
                    break;
                }
            //四开窗花
            case 'sikaiChuanghuaJisuan':
                {
                    $i = 6;
                    $title = array('制造部门', '门框', '门扇', '窗花', '型号', '窗花(小)', '用量', '窗花(大)', '用量');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or chuanghua like '%$like%' or xinghao like '%$like%' or
                     menshan like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select  rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510)) as ZHIZAOBM, cast(MENKUANG as VARCHAR2 (510)) as MENKUANG, cast(MENSHAN as VARCHAR2 (510)) as MENSHAN, cast(CHUANGHUA as VARCHAR2 (510)) as CHUANGHUA, cast(XINGHAO as VARCHAR2 (510)) as XINGHAO, cast(CHUANGHUAX as VARCHAR2 (510)) as CHUANGHUAX, cast(YONGLIANGX as VARCHAR2 (510)) as YONGLIANGX, cast(CHUANGHUAD as VARCHAR2 (510)) as CHUANGHUAD, cast(YONGLIANGD as VARCHAR2 (510)) as YONGLIANGD from BOM_CHUANGHUA_SIKAI_RULE $whereStr ORDER by MENKUANG,MENSHAN,CHUANGHUA,XINGHAO";
                    break;
                }
            /*钢框木扇*/
            case 'gangkuangMushan':
                {
                    $i = 7;
                    $title = array('门框', '门扇分类', '门扇开孔', '底框材料', '规格长度(母门)', '规格宽度(母门)', '用料长度(母门)', '用料长度(母门)', '材料长度(母门)', '材料宽度(母门)', '用料根数(母门)', '规格长度(子门)', '规格宽度(子门)', '用料长度(子门)', '用料长度(子门)', '材料长度(子门)', '材料宽度(子门)', '用料根数(子门)', '料号', '类别', '品名', '规格');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%' or mskaikong like '%$like%' or
                     dkcailiao like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select  rownum rn,ID,cast(MENKUANG as VARCHAR2 (510)) as MENKUANG, cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,MSKAIKONG,DKCAILIAO,MUMEN_GUIGECD,MUMEN_GUIGEKD,MUMEN_YONGLIAOCD,MUMEN_YONGLIAOKD,MUMEN_CAILIAOCD,MUMEN_CAILIAOKD,MUMEN_GENSHU,ZIMEN_GUIGECD,ZIMEN_GUIGEKD,ZIMEN_YONGLIAOCD,ZIMEN_YONGLIAOKD,ZIMEN_CAILIAOCD,ZIMEN_CAILIAOKD,ZIMEN_GENSHU,LIAOHAO,CLASSIFY,PINMING,GUIGE from BOM_MENBAN_GANGKUANG_RULE $whereStr ORDER by ID";
                    break;
                }
            /*填芯门扇*/
            case 'tianxinMenshan':
                {
                    $i = 8;
                    $title = array('门框', '门扇分类', '底框材料', '花色', '母门用料', '子门用料', '料号', '品名', '规格', '规则类别');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%' or dkcailiao like '%$like%' or huase like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select  rownum rn,ID,cast(MENKUANG as VARCHAR2 (510)) as MENKUANG, cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,DKCAILIAO,HUASE,YONGLIAO_MUMEN,YONGLIAO_ZIMEN,LIAOHAO,PINMING,GUIGE,CLASSIFY from BOM_TIANXIN_RULE $whereStr ORDER by ID";
                    break;
                }
            /*齐河基地钢框木扇*/
            case 'gangkuangMushanQH':
                {
                    $i = 9;
                    $title = array('门框', '规格(高度/宽度)起始区间', '规格(高度/宽度)结束区间', '门扇分类', '门扇开孔', '底框材料', '规格长度(母门)', '规格宽度(母门)', '用料长度(母门)', '用料长度(母门)', '材料长度(母门)', '材料宽度(母门)', '用料根数(母门)', '规格长度(子门)', '规格宽度(子门)', '用料长度(子门)', '用料长度(子门)', '材料长度(子门)', '材料宽度(子门)', '用料根数(子门)', '料号', '类别', '品名', '规格');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%' or mskaikong like '%$like%' or
                     dkcailiao like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select  rownum rn,ID,cast(MENKUANG as VARCHAR2 (510)) as MENKUANG, INTERVAL_START,INTERVAL_END,cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,MSKAIKONG,DKCAILIAO,MUMEN_GUIGECD,MUMEN_GUIGEKD,MUMEN_YONGLIAOCD,MUMEN_YONGLIAOKD,MUMEN_CAILIAOCD,MUMEN_CAILIAOKD,MUMEN_GENSHU,ZIMEN_GUIGECD,ZIMEN_GUIGEKD,ZIMEN_YONGLIAOCD,ZIMEN_YONGLIAOKD,ZIMEN_CAILIAOCD,ZIMEN_CAILIAOKD,ZIMEN_GENSHU,LIAOHAO,CLASSIFY,PINMING,GUIGE from BOM_MENBAN_GANGKUANG_QH_RULE $whereStr ORDER by ID";
                    break;
                }
            /*齐河基地填芯门扇*/
            case 'tianxinMenshanQH':
                {
                    $i = 10;
                    $title = array('门框', '规格(高度/宽度)起始区间', '规格(高度/宽度)结束区间', '门扇分类', '底框材料', '花色', '母门用料', '子门用料', '料号', '品名', '规格', '规则类别');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%' or dkcailiao like '%$like%' or huase like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select  rownum rn,ID,cast(MENKUANG as VARCHAR2 (510)) as MENKUANG,INTERVAL_START,INTERVAL_END,cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,DKCAILIAO,HUASE,YONGLIAO_MUMEN,YONGLIAO_ZIMEN,LIAOHAO,PINMING,GUIGE,CLASSIFY from BOM_TIANXIN_QH_RULE $whereStr ORDER by ID";
                    break;
                }
            case 'mianQib':
                {
                    $i = 11;
                    $title = array('制造部门', '门框', '门扇分类', '高度起始区间', '高度结束区间', '表面方式', '表面要求', '母门', '子门', '料号', '品名', '规格');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%' or biaomcl like '%$like%' or biaomiantsyq like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select  rownum rn,ID,ZHIZAOBM,cast(MENKUANG as VARCHAR2 (510)) as MENKUANG,cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,START_LEN,END_LEN,BIAOMCL,BIAOMIANTSYQ,MUMEN,ZIMEN,LIAOHAO,PINMING,GUIGE from BOM_MIANQIB $whereStr ORDER by ID";
                    break;
                }
            case 'fengBiant':
                {
                    $i = 12;
                    $title = array('制造部门', '门框', '门扇分类', '表面方式', '表面特殊要求', '底框材料', '规格长度', '母门', '子门', '料号', '品名', '规格');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%' or dkcailiao like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,ZHIZAOBM,cast(MENKUANG as VARCHAR2 (510)) as MENKUANG,cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,BIAOMCL,BIAOMIANTSYQ,DKCAILIAO,GUIGE_CD,MUMEN,ZIMEN,LIAOHAO,PINMING,GUIGE from BOM_FENGBIANT $whereStr ORDER by ID";
                    break;
                }
            case 'chuanghuaZhongshiJisuan':
                {
                    $i = 13;
                    $title = array('制造部门', '门框', '档次', '窗花', '开向', '门扇', '门框厚度', '开始区间', '结束区间', '高度规则', '宽度规则', '料号', '品名', '规格');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or chuanghua like '%$like%' or 
                    kaixiang like '%$like%' or menshan like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510)) as ZHIZAOBM, cast(MENKUANG as VARCHAR2 (1000)) as MENKUANG, cast(DANGCI as VARCHAR2 (510)) as DANGCI, cast(CHUANGHUA as VARCHAR2 (510)) as CHUANGHUA, cast(KAIXIANG as VARCHAR2 (510)) as KAIXIANG, cast(MENSHAN as VARCHAR2 (510)) as MENSHAN, cast(MKHOUDU as VARCHAR2 (510)) as MKHOUDU, cast(START_HEIGHT as VARCHAR2 (510)) as START_HEIGHT, cast(END_HEIGHT as VARCHAR2 (510)) as END_HEIGHT, cast(MKHEIGHT_RULE as VARCHAR2 (510)) as MKHEIGHT_RULE, cast(MKWIDE_RULE as VARCHAR2 (510)) as MKWIDE_RULE, cast(LIAOHAO as VARCHAR2 (510)) as LIAOHAO,PINMING,GUIGE from BOM_CHUANGHUA_ZHONGSHI_RULE $whereStr order by DANGCI,MENKUANG,CHUANGHUA,KAIXIANG,MENSHAN,START_HEIGHT";
                    break;
                }
            case 'peBaozhuang':
                {
                    $i = 14;
                    $title = array('制造部门', '档次', '门框', '门扇', '包装方式', '包装品牌', '起始高度', '结束高度', '起始宽度', '结束宽度', '用料长度', '用料宽度', '材料长度', '数量', '料号', '品名', '规格');
                    if (!empty($like)) {
                        $like = " (dangci like '%$like%' or menkuang like '%$like%' or menshan like '%$like%' or baozhuangfs like '%$like%' or baozhpack like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,cast(ZHIZAOBM as VARCHAR2 (510)) as ZHIZAOBM,cast(DANGCI as VARCHAR2 (510)) as DANGCI,cast(MENKUANG as VARCHAR2 (1000)) as MENKUANG,cast(MENSHAN as VARCHAR2 (510)) as MENSHAN,BAOZHUANGFS,BAOZHPACK,START_HEIGHT,END_HEIGHT,START_WIDTH,END_WIDTH,YONGLIAO_LENGTH,YONGLIAO_WIDTH,CAILIAO_LENGTH,SHULIANG,LIAOHAO,PINMING,GUIGE from BOM_PEBAOZHUANG_RULE $whereStr order by DANGCI,MENKUANG,MENSHAN,BAOZHUANGFS,BAOZHPACK,START_HEIGHT,END_HEIGHT,START_WIDTH,END_WIDTH,YONGLIAO_LENGTH";
                    break;
                }
            case 'changbianGujiaYongliao':
                {
                    $i = 15;
                    $title = array('门框', '高度起始值', '高度结束值', '门扇分类', '底框材料', '规格长度', '用料长度(母门铰方)', '用料长度(母门锁方)', '用料长度(子门铰方)', '用料长度(子门锁方)', '材料长度');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,MENKUANG,GAODU_START,GAODU_END,MENSHAN,DKCAILIAO,GUIGE_CD,MUMEN_JIAO_YONGLIAO,MUMEN_SUO_YONGLIAO,ZIMEN_JIAO_YONGLIAO,ZIMEN_SUO_YONGLIAO,CAILIAO_CD from BOM_GK_CBGUJIA_YLIAO_RULE $whereStr order by MENKUANG,GAODU_START,GAODU_END,MENSHAN";
                    break;
                }
            case 'changbianGujiaPinming':
                {
                    $i = 16;
                    $title = array('门框', '宽度起始值', '宽度结束值', '门扇分类', '母铰料号', '母铰品名', '母铰规格', '母锁料号', '母锁品名', '母锁规格', '子铰料号', '子铰品名', '子铰规格', '子锁料号', '子锁品名', '子锁规格');
                    if (!empty($like)) {
                        $like = " (menkuang like '%$like%' or menshan like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $sql = "select rownum rn,ID,MENKUANG,WIDTH_START,WIDTH_END,MENSHAN,MUMEN_JIAO_LIAOHAO,MUMEN_JIAO_PINMING,MUMEN_JIAO_GUIGE,MUMEN_SUO_LIAOHAO,MUMEN_SUO_PINMING,MUMEN_SUO_GUIGE,ZIMEN_JIAO_LIAOHAO,ZIMEN_JIAO_PINMING,ZIMEN_JIAO_GUIGE,ZIMEN_SUO_LIAOHAO,ZIMEN_SUO_PINMING,ZIMEN_SUO_GUIGE from BOM_GK_CBGUJIA_PINMING_RULE $whereStr order by MENKUANG,WIDTH_START,WIDTH_END,MENSHAN";
                    break;
                }
            case 'chuanghua_cg':
                {
                    $i = 17;
                    $title = array('窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间', '用料', 'a值', '角度', 'c值', 'n值', '用料1', '用料2', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格', '类型');
                    $sql = "select rownum rn,id,chuanghua_type,start_height,end_height,start_width,end_width,yongliao,a_value,jiaodu,c_value,n_value,yongliao1,yongliao2,cailiao_width,cailiao_houdu,cailiao_midu,num,liaohao,pinming,guige,type from bom_chuanghua_cg_rule  order by type asc";
                    break;
                }
            case 'chuanghua_bxg':
                {
                    $i = 18;
                    $title = array('窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间', '用料', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格', 'QPA', '类型');
                    $sql = "select rownum rn,id,chuanghua_type,start_height,end_height,start_width,end_width,yongliao,cailiao_width,cailiao_houdu,cailiao_midu,num,liaohao,pinming,guige,qpa,type from bom_chuanghua_bxg_rule  order by type asc";
                    break;
                }
            case 'chuanghua_zs':
                {
                    $i = 19;
                    $title = array('窗花类型', '窗花高度左区间', '窗花高度右区间', '窗花宽度左区间', '窗花宽度右区间', '窗花高度', '窗花宽度', '用料', '用料1', '用料2', '用料3', '材料宽度', '材料厚度', '材料密度', '数量', '料号', '品名', '规格', '类型');
                    $sql = "select rownum rn,id,chuanghua_type,start_height,end_height,start_width,end_width,chuanghua_height,chuanghua_width,yongliao,yongliao1,yongliao2,yongliao3,cailiao_width,cailiao_houdu,cailiao_midu,num,liaohao,pinming,guige,type from bom_chuanghua_zs_rule  order by type asc";
                    break;
                }
            case 'fuhe_shimu':  //木门门套-复合实木
                {
                    $i = 20;
                    $title = array('部门', '产品种类', '门类结构', '门套墙体', '门套厚度', '门套结构', '门套样式', '表面方式', '表面花纹', '高度左区间', '高度右区间', '用料长度', '用料宽度', '材料长度', '材料宽度', '料号', '品名', '规格', '类型');
                    if (!empty($like)) {
                        $like = " (door_wall like '%$like%' or surface_pattern like '%$like%' or product_name like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $whereStr = $this->checkWhereStr($sheet_name, $whereStr);
                    $sql = "select rownum rn,id,dept,product_category,door_structure,door_wall,doorcover_thickness,doorcover_structure,door_pattern,surface_mode,surface_pattern,hw_left,hw_right,yongliao_length,yongliao_width,material_length,material_width,material_num,product_name,spec,type from bom_mumen_mentao_rule $whereStr and type = '侧方套板（主板面板）' order by type asc";
                    break;
                }
            case 'jicheng_shimu':   //木门门套-集成实木
                {
                    $i = 21;
                    $title = array('部门', '产品种类', '门类结构', '门套墙体', '门套厚度', '门套结构', '门套样式', '表面方式', '表面花纹', '高度左区间', '高度右区间', '用料长度', '用料宽度', '材料长度', '材料宽度', '料号', '品名', '规格', '类型');
                    if (!empty($like)) {
                        $like = " (door_wall like '%$like%' or surface_pattern like '%$like%' or product_name like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $whereStr = $this->checkWhereStr($sheet_name, $whereStr);
                    $sql = "select rownum rn,id,dept,product_category,door_structure,door_wall,doorcover_thickness,doorcover_structure,door_pattern,surface_mode,surface_pattern,hw_left,hw_right,yongliao_length,yongliao_width,material_length,material_width,material_num,product_name,spec,type from bom_mumen_mentao_rule $whereStr and type = '侧方套板（主板面板）' order by type asc";
                    break;
                }
            case 'qianghua_mumen':  //木门门套-强化木门
                {
                    $i = 22;
                    $title = array('部门', '产品种类', '门类结构', '门套墙体', '门套厚度', '门套结构', '门套样式', '表面方式', '表面花纹', '线条样式', '高度左区间', '高度右区间', '用料长度', '用料宽度', '材料长度', '材料宽度', '料号', '品名', '规格', '类型');
                    if (!empty($like)) {
                        $like = " (door_wall like '%$like%' or surface_pattern like '%$like%' or product_name like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $whereStr = $this->checkWhereStr($sheet_name, $whereStr);
                    $sql = "select rownum rn,id,dept,product_category,door_structure,door_wall,doorcover_thickness,doorcover_structure,door_pattern,surface_mode,surface_pattern,line_style,hw_left,hw_right,yongliao_length,yongliao_width,material_length,material_width,material_num,product_name,spec,type from bom_mumen_mentao_rule $whereStr and type = '侧方套板（主板面板）' order by type asc";
                    break;
                }
            case 'zhuanyin_mumen':  //木门门套-转印木门
                {
                    $i = 23;
                    $title = array('部门', '产品种类', '门类结构', '门套墙体', '门套厚度', '门套结构', '门套样式', '表面方式', '表面花纹', '档次', '高度左区间', '高度右区间', '用料长度', '用料宽度', '材料长度', '材料宽度', '料号', '品名', '规格', '类型');
                    if (!empty($like)) {
                        $like = " (door_wall like '%$like%' or surface_pattern like '%$like%' or product_name like '%$like%') ";
                        $where[] = $like;
                    }
                    $whereStr = $this->doWhereStr($where);
                    $whereStr = $this->checkWhereStr($sheet_name, $whereStr);
                    $sql = "select rownum rn,id,dept,product_category,door_structure,door_wall,doorcover_thickness,doorcover_structure,door_pattern,surface_mode,surface_pattern,line_style,hw_left,hw_right,yongliao_length,yongliao_width,material_length,material_width,material_num,product_name,spec,type from bom_mumen_mentao_rule $whereStr and type = '侧方套板（主板面板）' order by type asc";
                    break;
                }
            case 'mumen_fbt': //木门封边条
                {
                    $i = 24;
                    $title = array('部门', '产品种类', '起始宽度', '结束宽度', '门套厚度', '门套样式', '线条样式', '表面方式', '表面花纹', '表面要求', '规格宽度', '用料长度', '用料根数', '料号', '品名', '规格');
                    $sql = " select rownum rn,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,MENTAO_HD,
MENTAO_YS,XIANTIAO_YS,BIAOMIAN_FS,BIAOMIAN_HW,BIAOMIAN_YQ,GUIGE_CD,YONGLIAO_CD,YONGLIAO_GS,QPA,LIAOHAO,PINGMING,GUIGE,SORT,TYPE,SHEET_NAME from BOM_MUMEN_MENSHAN_RULE where type = 1 order by id";
                    break;
                }
            case 'mumen_menshan_mb' : //木门门扇面板
                {
                    $i = 25;
                    $title = array('部门', '产品种类', '起始高度', '结束高度', '起始宽度', '结束宽度', '门类结构', '档次', '门扇类型', '门扇花色', '表面方式', '表面花纹', '窗花', '母门用量', '子门用量', '料号', '品名', '规格');
                    $sql = " select rownum rn,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,KUAN_START,KUAN_END,MENLEI_JG,DANGCI,MENSHAN_TYPE,MENSHAN_HS,BIAOMIAN_FS,BIAOMIAN_HW,CHUANGHUA,QPA_MUMEN,QPA_ZIMEN,LIAOHAO,PINGMING,GUIGE from BOM_MUMEN_MENSHAN_RULE where type = 2 order by id";
                    break;
                }
            case 'mumen_menshan_mf' : //门扇木方
                {
                    $i = 26;
                    $title = array('部门', '产品种类', '起始高度', '结束高度', '档次', '门扇类型', '门扇花色', '窗花', '规格长度', '用料长度（母门铰方）', '用料长度（母门锁方）', '用料长度（子门铰方）', '用料长度（子门锁方）', '材料长度', '母门用量（铰方）', '料号', '品名', '规格');
                    $sql = " select rownum rn,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,DANGCI,
MENSHAN_TYPE,MENSHAN_HS,CHUANGHUA,GUIGE_CD,YONGLIAO_K_M,YONGLIAO_L_M,YONGLIAO_K_Z,YONGLIAO_L_Z,CAILIAO_CD,QPA,LIAOHAO,PINGMING,GUIGE from BOM_MUMEN_MENSHAN_RULE where type = 3 order by id";
                    break;
                }
            case 'mumen_menshan_xl' :  //门扇芯料
                $i = 27;
                $title = array('部门', '产品种类', '起始宽度', '结束宽度', '档次', '门扇类型', '门扇花色', '窗花', '门扇要求', '填充料', '规格长度(母门)', '规格宽度(母门)', '用料长度(母门)', '用料宽度(母门)', '材料长度(母门)', '材料宽度(母门)', '规格长度(子门)', '规格宽度(子门)', '用料长度(子门)', '用料宽度(子门)', '材料长度(子门)', '材料宽度(子门)', '料号', '品名', '规格');
                $sql = " select rownum rn,DEPT_NAME,PRODUCT_TYPE,GUIGE_START,GUIGE_END,DANGCI,
MENSHAN_TYPE,MENSHAN_HS,CHUANGHUA,MENSHAN_YQ,TIANCHONG,
GUIGE_CD,GUIGE_K_M,YONGLIAO_L_M,YONGLIAO_K_M,CAILIAO_L_M,CAILIAO_K_M,
GUIGE_L_Z,GUIGE_K_Z,YONGLIAO_L_Z,YONGLIAO_K_Z,CAILIAO_L_Z,CAILIAO_K_Z,
LIAOHAO,PINGMING,GUIGE from BOM_MUMEN_MENSHAN_RULE where type = 4 order by id";
                break;
            case 'mumen_xt':
                {
                    $i = 28;
                    $title = array('部门', '产品种类', '门类结构', '线条种类', '线条样式', '线条结构', '表面方式', '表面花纹', '起始高度', '结束高度', '规格长度', '用料长度', '材料长度', '用量', '料号', '品名', '规格');
                    $sql = " select rownum rn,DEPT_NAME,PRODUCT_TYPE,MENLEI_JG,XIANTIAO_TYPE,XIANTIAO_YS,
XIANTIAO_JG,BIAOMIAN_FS,BIAOMIAN_HW,GUIGE_START,GUIGE_END,
GUIGE_CD,YONGLIAO_CD,CAILIAO_CD,QPA,
LIAOHAO,PINGMING,GUIGE from BOM_MUMEN_MENSHAN_RULE where type = 5 order by id";
                    break;
                }
            case 'chuanghua_cd_fbs':
                {
                    $i = 29;
                    $title = array('部门', '窗花类型', '窗花宽度区间', '窗花宽度区间', 'QPA用量', '料号', '品名', '规格');
                    $sql = " select rownum rn,DEPT_NAME,CHUANGHUA_TYPE,WIDTH_START,WIDTH_END,QPA
LIAOHAO,PINMING,GUIGE from BOM_CHUANGHUA_FBS where type = 1 order by id";
                    break;
                }
            case 'chuanghua_qh_fbs':
                {
                    $i = 30;
                    $title = array('部门', '窗花类型', '窗花高度区间', '窗花高度区', '窗花宽度区间', '窗花宽度区间', '材料宽度', '材料厚度', '材料密度', '数量', 'QPA用量', '料号', '品名', '规格');
                    $sql = " select rownum rn,DEPT_NAME,CHUANGHUA_TYPE,HEIGHT_START,HEIGHT_END,WIDTH_START,WIDTH_END,
 CAILIAO_WIDTH,CAILIAO_HD,CAILIAO_MD,SHULIANG,QPA,
LIAOHAO,PINMING,GUIGE from BOM_CHUANGHUA_FBS where type = 2 order by id";
                    break;
                }
            case 'glass_rule':
                {
                    $i = 31;
                    $title = array('制造部门', '门框', '档次', '窗花', '开向', '门扇', '玻璃厚度', '用量');
                    $sql = " select rownum rn,DEPT_NAME,MENKUANG,DANGCI,CHUANGHUA,KAIXIANG,MENSHAN,BOLI_HOUDU,QPA1 from BOM_BOLI_RULE order by id";
                    break;
                }
            default :
                {
                    $i = 0;
                }
        }
        $result = Db::query($sql);
        $count = count($result);
        $pages = ($page - 1) * $pageSize;
        $pagess = $pageSize * $page;
        $sql = "select a1.* from ($sql) a1 where rn between $pages and $pagess";
        $result = Db::query($sql);
        $dataInfo = array();
        foreach ($result as $val) {
            array_shift($val);
            foreach ($val as $key => $value) {
                $val[$key] = ltrim($value, '/');
            }
            $dataInfo[] = array_change_key_case($val);
        }
        $data[$i]['info'] = $dataInfo;
        $data[$i]['title'] = $title;
//        $data['page']=$page;
//        $data['cntpage'] = ceil($count/$pageSize);
        return $this->response(retmsg(0, $data), 'json');
    }

    public function doWhereStr($where)
    {
        $whereStr = '';
        if (!empty(count($where))) {
            foreach ($where as $vwhere) {
                $whereStr .= $vwhere . ' and';
            }
            $whereStr = rtrim($whereStr, 'and');
            $whereStr = " where $whereStr";
        }
        return $whereStr;
    }

    /**
     * 获取可选列信息
     * @param string $sheet_name
     * @return \think\response\Json
     */
    public function searchList($sheet_name = '', $zhizaobm = null, $dangci = null, $menkuang = null, $menshan = null, $kaixiang = null, $menkuanghd = null, $guigecd = null, $xinghao = null, $dikuangcl = null, $dikuang = null, $dept = null, $doorStructure = null, $doorWall = null, $doorPattern = null, $surfacePattern = null, $productName = null, $type = null)
    {
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', "-1");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        if (empty($sheet_name)) {
            $sheet_name = 'manbanLength';
        }
        $whereStr = '';

        $arrParam = [   //参数数组维护模块，字段名=>参数
            'zhizaobm' => $zhizaobm,
            'dangci' => $dangci,
            'menkuang' => $menkuang,
            'menshan' => $menshan,
            'kaixiang' => $kaixiang,
            'menkuanghd' => $menkuanghd,
            'guigecd' => $guigecd,
            'xinghao' => $xinghao,
            'dikuangcl' => $dikuangcl,
            'dikuang' => $dikuang,
            'dept' => $dept,
            'door_structure' => $doorStructure,
            'door_wall' => $doorWall,
            'door_pattern' => $doorPattern,
            'surface_pattern' => $surfacePattern,
            'product_name' => $productName,
            'type' => $type
        ];

        foreach ($arrParam as $paramK => $paramV) {
            if (!empty($param)) {
                $where [] = " $paramK like '%$paramV%'";
            }
        }

        if (!empty(count($where))) {
            foreach ($where as $vwhere) {
                $whereStr .= $vwhere . ' and';
            }
            $whereStr = rtrim($whereStr, 'and');
            $whereStr = " where $whereStr";
        }

        //别名=>表名模块维护
        $aliseDatabaseArr = [
            'manbanLength' => 'BOM_MENBAN_LENGTH_RULE',//门板长度用料规则
            'menbanWide' => 'BOM_MENBAN_WIDE_RULE',//门板宽度用料规则
            'menkuangJiaoSuo' => 'BOM_MENKUANG_JIAOSUO_RULE',//铰 锁门框
            'menkuangShangZhong' => 'BOM_MENKUANG_SHANGZHONG_RULE',//上中门框
            'menkuangXia' => 'BOM_MENKUANG_XIA_RULE',//下门框
            'chuanghuaJisuan' => 'BOM_CHUANGHUA_RULE',//窗花
            'sikaiChuanghuaJisuan' => 'BOM_CHUANGHUA_SIKAI_RULE',//四开窗花
            'gangkuangMushan' => 'BOM_MENBAN_GANGKUANG_RULE',//钢框木扇
            'tianxinMenshan' => 'BOM_TIANXIN_RULE',//防火门填芯
            'gangkuangMushanQH' => 'BOM_MENBAN_GANGKUANG_QH_RULE',//齐河基地钢框木扇
            'tianxinMenshanQH' => 'BOM_TIANXIN_QH_RULE',//齐河基地防火门填芯
            'mianQib' => 'BOM_MIANQIB',//免漆板
            'fengBiant' => 'BOM_FENGBIANT',//封边条
            'chuanghuaZhongshiJisuan' => 'BOM_CHUANGHUA_ZHONGSHI_RULE',//中式窗花
            'peBaozhuang' => 'BOM_PEBAOZHUANG_RULE',//PE包装
            'changbianGujiaYongliao' => 'BOM_GK_CBGUJIA_YLIAO_RULE',//长边骨架用料规则
            'changbianGujiaPinming' => 'BOM_GK_CBGUJIA_PINMING_RULE',//长边骨架品名规格规则
            'chuanghua_cg' => 'BOM_CHUANGHUA_CG_RULE',//常规窗花
            'chuanghua_bxg' => 'BOM_CHUANGHUA_BXG_RULE',//不锈钢窗花
            'chuanghua_zs' => 'BOM_CHUANGHUA_ZS_RULE',//中式窗花
            'fuhe_shimu' => 'BOM_MUMEN_MENTAO_RULE',//复合实木门
            'jicheng_shimu' => 'BOM_MUMEN_MENTAO_RULE',//集成实木门
            'qianghua_mumen' => 'BOM_MUMEN_MENTAO_RULE',//强化木门
            'zhuanyin_mumen' => 'BOM_MUMEN_MENTAO_RULE',//转印木门
            'mumen_menshan_xl' => 'BOM_MUMEN_MENSHAN_RULE', // 木门门扇芯料
            'mumen_menshan_mb' => 'BOM_MUMEN_MENSHAN_RULE', // 木门门扇面板
            'mumen_menshan_mf' => 'BOM_MUMEN_MENSHAN_RULE', // 木门门扇木方
            'mumen_fbt' => 'BOM_MUMEN_MENSHAN_RULE', // 木门封边条
            'mumen_xt' => 'BOM_MUMEN_MENSHAN_RULE', // 木门线条
            'chuanghua_cd_fbs' => 'BOM_CHUANGHUA_FBS', //成都封闭式窗花
            'chuanghua_qh_fbs' => 'BOM_CHUANGHUA_FBS', //齐河封闭式窗花
            'glass_rule' => 'BOM_BOLI_RULE', // 窗花玻璃
        ];

        $whereStr = $this->checkWhereStr($sheet_name, $whereStr);

        $tableName = $aliseDatabaseArr[$sheet_name];
        $str = getColumnName($tableName);
        $sql = "select $str from $tableName $whereStr";


        $result = Db::query($sql);
        foreach ($result as $val) {
            $menkuangArr[] = trim($val['MENKUANG'], '/');
            $zhizaobmArr[] = trim($val['ZHIZAOBM'], '/');
            $dangciArr[] = trim($val['DANGCI'], '/');
            $chuanghuaArr[] = trim($val['CHUANGHUA'], '/');
            $kaixiangArr[] = trim($val['KAIXIANG'], '/');
            $menshanArr[] = trim($val['MENSHAN'], '/');
            $xinghaoArr[] = trim($val['XINGHAO'], '/');
            $dikuangclArr[] = trim($val['DIKUANGCL'], '/');
            $guigecdArr[] = trim($val['GUIGECD'], '/');
            $menkuanghdArr[] = trim($val['MENKUANGHD'], '/');
            $qianmbArr[] = trim($val['QIANMB'], '/');
            $houmbArr[] = trim($val['HOUMB'], '/');
            $mskaikongArr[] = trim($val['MSKAIKONG'], '/');
            $huaseArr[] = trim($val['HUASE'], '/');
            $baozhuangfsArr[] = trim($val['BAOZHUANGFS'], '/');
            $baozhpackArr[] = trim($val['BAOZHPACK'], '/');
//            $deptArr[] = trim($val['DEPT'], '/');
//            $doorStructure[] = trim($val['DOOR_STRUCTURE'], '/');
//            $doorWall[] = trim($val['DOOR_WALL'], '/');
//            $doorPattern[] = trim($val['DOOR_PATTERN'], '/');
//            $surfacePattern[] = trim($val['SURFACE_PATTERN'], '/');
//            $productName[] = trim($val['PRODUCT_NAME'], '/');
//            $type[] = trim($val['TYPE'], '/');
        }
        switch ($sheet_name) {
            //门板长度用料规则
            case 'manbanLength':
                {
                    $title = array('制造部门', '档次', '门框', '门扇', '底框材料', '规格长度');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'dangci' => $dangciArr,
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'dikuangcl' => $dikuangclArr,
                        'guigecd' => $guigecdArr,
                    );
                    break;
                }
            //门板宽度用料规则
            case 'menbanWide':
                {
                    $title = array('制造部门', '档次', '门框', '门扇', '前门板', '后门板', '规格宽度');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'dangci' => $dangciArr,
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'qianmb' => $qianmbArr,
                        'houmb' => $houmbArr,
                        'guigecd' => $guigecdArr,
                    );
                    break;
                }
            //铰 锁门框
            case 'menkuangJiaoSuo':
                {
                    $title = array('制造部门', '档次', '门框', '开向', '门框厚度');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'dangci' => $dangciArr,
                        'menkuang' => $menkuangArr,
                        'kaixiang' => $kaixiangArr,
                        'menkuanghd' => $menkuanghdArr,
                    );
                    break;
                }
            //上中门框
            case 'menkuangShangZhong':
                {
                    $title = array('制造部门', '档次', '门框', '门扇', '开向', '门框厚度',);
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'dangci' => $dangciArr,
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'kaixiang' => $kaixiangArr,
                        'menkuanghd' => $menkuanghdArr,
                    );
                    break;
                }
            //下门框
            case 'menkuangXia':
                {
                    $title = array('制造部门', '档次', '门框', '门扇', '开向', '门框厚度', '底框材料');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'dangci' => $dangciArr,
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'kaixiang' => $kaixiangArr,
                        'menkuanghd' => $menkuanghdArr,
                        'dikuangcl' => $dikuangclArr,
                    );
                    break;
                }
            //窗花
            case 'chuanghuaJisuan':
                {
                    $title = array('制造部门', '门框', '档次', '窗花', '开向', '门扇');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'menkuang' => $menkuangArr,
                        'dangci' => $dangciArr,
                        'chuanghua' => $chuanghuaArr,
                        'kaixiang' => $kaixiangArr,
                        'menshan' => $menshanArr,
                    );
                    break;
                }
            //四开窗花
            case 'sikaiChuanghuaJisuan':
                {
                    $title = array('制造部门', '门框', '门扇', '窗花', '型号',);
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'chuanghua' => $chuanghuaArr,
                        'xinghao' => $xinghaoArr,
                    );
                    break;
                }
            //钢框木扇
            case 'gangkuangMushan':
                //齐河基地钢框木扇
            case 'gangkuangMushanQH':
                {
                    $title = array('门框', '门扇', '门扇开孔', '底框材料',);
                    $data = array(
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'mskaikong' => $mskaikongArr,
                        'dkcailiao' => $dikuangclArr,
                    );
                    break;
                }
            //防火门填芯
            case 'tianxinMenshan':
                //齐河基地防火门填芯
            case 'tianxinMenshanQH':
                {
                    $title = array('门框', '门扇', '底框材料', '花色');
                    $data = array(
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'dkcailiao' => $dikuangclArr,
                        'huase' => $huaseArr,
                    );
                    break;
                }
            case 'mianQib':
            case 'fengBiant':
            case 'changbianGujiaYongliao':
            case 'changbianGujiaPinming':
                {
                    $title = array('门框', '门扇');
                    $data = array(
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                    );
                    break;
                }
            case 'chuanghuaZhongshiJisuan' :
                {
                    $title = array('制造部门', '门框', '档次', '窗花', '开向', '门扇');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'menkuang' => $menkuangArr,
                        'dangci' => $dangciArr,
                        'chuanghua' => $chuanghuaArr,
                        'kaixiang' => $kaixiangArr,
                        'menshan' => $menshanArr,
                    );
                    break;
                }
            case 'peBaozhuang':
                {
                    $title = array('制造部门', '档次', '门框', '门扇', '包装方式', '包装品牌');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'dangci' => $dangciArr,
                        'menkuang' => $menkuangArr,
                        'menshan' => $menshanArr,
                        'baozhuangfs' => $baozhuangfsArr,
                        'baozhpack' => $baozhpackArr,
                    );
                    break;
                }
            case 'chuanghua_cg':
            case 'chuanghua_bxg':
            case 'chuanghua_zs':
                {
                    $title = array('制造部门');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                    );
                    break;
                }
            case 'fuhe_shimu':
            case 'jicheng_shimu':
            case 'qianghua_mumen':
            case 'zhuanyin_mumen':
                {
                    $dept = Db::query("select WMSYS.WM_CONCAT(distinct(dept)) as dept from bom_mumen_mentao_rule", 1);
                    $door_structure = Db::query("select WMSYS.WM_CONCAT(distinct(door_structure)) as door_structure from bom_mumen_mentao_rule", 1);
                    $door_wall = Db::query("select WMSYS.WM_CONCAT(distinct(door_wall)) as door_wall from bom_mumen_mentao_rule", 1);
                    $door_pattern = Db::query("select WMSYS.WM_CONCAT(distinct(door_pattern)) as door_pattern from bom_mumen_mentao_rule", 1);
                    $surface_pattern = Db::query("select WMSYS.WM_CONCAT(distinct(surface_pattern)) as surface_pattern from bom_mumen_mentao_rule", 1);
                    $product_name = Db::query("select WMSYS.WM_CONCAT(distinct(product_name)) as product_name from bom_mumen_mentao_rule", 1);
                    $type = Db::query("select WMSYS.WM_CONCAT(distinct(type)) as type from bom_mumen_mentao_rule", 1);
                    $title = array('部门', '门类结构', '门套墙体', '门套样式', '表面花纹', '品名', '类型');
                    $data = array(
                        'dept' => explode(',', $dept[0]['dept']),
                        'door_structure' => explode(',', $door_structure[0]['door_structure']),
                        'door_wall' => explode(',', $door_wall[0]['door_wall']),
                        'door_pattern' => explode(',', $door_pattern[0]['door_pattern']),
                        'surface_pattern' => explode(',', $surface_pattern[0]['surface_pattern']),
                        'product_name' => explode(',', $product_name[0]['product_name']),
                        'type' => explode(',', $type[0]['type']),
                    );
                    break;
                }
            case 'mumen_fbt':
            case 'mumen_menshan_mb':
            case 'mumen_menshan_mf':
            case 'mumen_menshan_xl':
            case 'mumen_xt':
                {
                    $title = array('制造部门');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr
                    );
                    break;
                }
            case 'chuanghua_cd_fbs':
            case 'chuanghua_qh_fbs':
                {
                    $title = array('部门');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr
                    );
                    break;
                }
            case 'glass_rule':
                {
                    $title = array('制造部门', '门框', '档次', '门扇', '');
                    $data = array(
                        'zhizaobm' => $zhizaobmArr,
                        'menkuang' => $menkuang,
                        'dangci' => $dangci,
                        'menshan' => $menshan
                    );
                    break;
                }
        }
        $info = array();
        foreach ($data as $key => $val) {
            $arr = empty($val) ? array_unique([]) : array_unique($val);
            if (empty($arr[0]))
                continue;
            foreach ($arr as $value) {
                $new = explode('/', $value);
                foreach ($new as $v) {
                    $info[$key][] = $v;
                }
            }
            $info[$key] = array_unique($info[$key]);
            sort($info[$key]);
        }
        foreach ($info as $k => $v) {
            $tt[] = $k;
        }
        foreach ($title as $k => $v) {
            $dataInfo[] = array(
                'biaoti' => $v,
                'title' => $tt[$k],
                'data' => $info[$tt[$k]]
            );
        }
        return json($dataInfo);
    }

    //木门门套类，where条件
    public function checkWhereStr($sheet_name, $whereStr)
    {
        switch ($sheet_name) {   //木门门框存在一张表，故要加条件
            case 'fuhe_shimu':
                empty($whereStr) ? $whereStr = "where product_category = '复合实木门'" : $whereStr .= " and product_category = '复合实木门'";
                return $whereStr;
                break;
            case 'jicheng_shimu':
                empty($whereStr) ? $whereStr = "where product_category = '集成实木门'" : $whereStr .= " and product_category = '集成实木门'";
                return $whereStr;
                break;
            case 'qianghua_mumen':
                empty($whereStr) ? $whereStr = "where product_category = '强化木门'" : $whereStr .= " and product_category = '强化木门'";
                return $whereStr;
                break;
            case 'zhuanyin_mumen':
                empty($whereStr) ? $whereStr = "where product_category = '转印木门'" : $whereStr .= " and product_category = '转印木门'";
                return $whereStr;
                break;
            case 'mumen_fbt':
                empty($whereStr) ? $whereStr = " where type = 1" : $whereStr .= " and where type = 1";
                return $whereStr;
                break;
            case 'mumen_menshan_mb':
                empty($whereStr) ? $whereStr = " where type = 2" : $whereStr .= " and where type = 2";
                return $whereStr;
                break;
            case 'mumen_menshan_mf':
                empty($whereStr) ? $whereStr = " where type = 3" : $whereStr .= " and where type = 3";
                return $whereStr;
                break;
            case 'mumen_menshan_xl':
                empty($whereStr) ? $whereStr = " where type = 4" : $whereStr .= " and where type = 4";
                return $whereStr;
                break;
            case 'mumen_xt':
                empty($whereStr) ? $whereStr = " where type = 5" : $whereStr .= " and where type = 5";
                return $whereStr;
                break;
        }
    }
}