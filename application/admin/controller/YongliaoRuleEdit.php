<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/4
 * Time: 17:16
 */
namespace app\admin\controller;
use Firebase\JWT\JWT;
use Think\Action;
use think\Db;
use think\controller\Rest;
use \think\Request;
//编码规则类
class YongliaoRuleEdit extends Rest{
    /**
     * 用料规则 添加/修改
     * @return array
     */
    public function yongLiaoRuleSave(){
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');
        $postJson=file_get_contents("php://input");
        $data = json_decode($postJson,true);
        switch ($data['sheet_name']){
            //门板长度用料规则
            case 'manbanLength':{
                $this->menbanLength($data);
                break;
            }
            //门板宽度用料规则
            case 'menbanWide':{
                $this->menbanWide($data);
                break;
            }
            //门框长度用料规则
            case 'menkuangJiaoSuo':{
                $this->menkuangJiaoSuo($data);
                break;
            }
            //门框宽度用料规则
            case 'menkuangShangZhong':{
                $this->menkuangShangZhong($data);
                break;
            }
            //下门框
            case 'menkuangXia':{
                $this->menkuangXia($data);
                break;
            }
            //窗花用料计算规则
            case 'chuanghuaJisuan':{
                $this->chuanghuaYongLiao($data);
                break;
            }
            //四开窗花用料计算规则
            case 'sikaiChuanghuaJisuan':{
                $this->sikaiChuanghuaYongLiao($data);
                break;
            }
            case 'gangkuangMushan':{
                $this->gangkuangMushanYongLiao($data);
                break;
            }
            case 'tianxinMenshan':{
                $this->tianxinMenshanYongLiao($data);
                break;
            }
            case 'gangkuangMushanQH':{
                $this->gangkuangMushanQHYongLiao($data);
                break;
            }
            case 'tianxinMenshanQH':{
                $this->tianxinMenshanQHYongLiao($data);
                break;
            }
            case 'mianQi':{
                $this->mianQiYongLiao($data);
                break;
            }
            case 'fengBian':{
                $this->fengBianYongLiao($data);
                break;
            }
            case 'chuanghuaZhongshi':{
                $this->chuanghuaZhongshiYongLiao($data);
                break;
            }
            case 'peBaozhuang':{
                $this->peBaozhuangYongLiao($data);
                break;
            }
            case 'changbianGujiaYongliao':{
                $this->changbianGujiaYongLiao($data);
                break;
            }
            case 'changbianGujiaPinming':{
                $this->changbianGujiaPinmingYongLiao($data);
                break;
            }
            case 'delYongliaoRule':{
                $this->delYongliaoRule($data);
                break;
            }
        }
        return retmsg(0);
    }

    /**
     * 门板长度用料 添加/修改
     * @param $data
     */
    public function menbanLength($data)
    {
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $mkhoudu = $val['mkhoudu'];
            $kaixiang = $val['kaixiang'];
            $menshan = $val['menshan'];
            $dikuangcl = $val['dikuangcl'];
            $guigecd = $val['guigecd'];
            $muqianmb = $val['muqianmb'];
            $muhoumb = $val['muhoumb'];
            $ziqianmb = $val['ziqianmb'];
            $zihoumb = $val['zihoumb'];
            $muziqmb = $val['muziqmb'];
            $muzihmb = $val['muzihmb'];
            $ziziqmb = $val['ziziqmb'];
            $zizihmb = $val['zizihmb'];
            $id = $val['id'];
            //查询数据
            $sql_check = "select ID from BOM_MENBAN_LENGTH_RULE where ID ='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENBAN_LENGTH_RULE set ZHIZAOBM='/$zhizaobm/',DANGCI='/$dangci/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',
                                    DIKUANGCL='/$dikuangcl/',GUIGECD='$guigecd',MUQIANMB='$muqianmb',MUHOUMB='$muhoumb',
                                    ZIQIANMB='$ziqianmb',ZIHOUMB='$zihoumb',MUZIQMB='$muziqmb',MUZIHMB='$muzihmb',
                                    ZIZIQMB='$ziziqmb',ZIZIHMB='$zizihmb',MKHOUDU='/$mkhoudu/',KAIXIANG='/$kaixiang/' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            } else {
                //插入数据
                $sql_ins = "insert into BOM_MENBAN_LENGTH_RULE (ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,DIKUANGCL,GUIGECD,MUQIANMB,
                              MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB,MKHOUDU,KAIXIANG)VALUES (bom_rule_id.nextval,'/$zhizaobm/',
                              '/$dangci/','/$menkuang/','/$menshan/','/$dikuangcl/','$guigecd','$muqianmb','$muhoumb','$ziqianmb',
                              '$zihoumb','$muziqmb','$muzihmb','$ziziqmb','$zizihmb','/$mkhoudu/','/$kaixiang/')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * 门板宽度用料 添加/修改
     * @param $data
     */
    public function menbanWide($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $guigecd = $val['guigecd'];
            $mkhoudu = $val['mkhoudu'];
            $kaixiang = $val['kaixiang'];
            $muqianmb = $val['muqianmb'];
            $muhoumb = $val['muhoumb'];
            $ziqianmb = $val['ziqianmb'];
            $zihoumb = $val['zihoumb'];
            $muziqmb = $val['muziqmb'];
            $muzihmb = $val['muzihmb'];
            $ziziqmb = $val['ziziqmb'];
            $zizihmb = $val['zizihmb'];
            $caizhi = $val['caizhi'];
            $qianmb = $val['qianmb'];
            $houmb = $val['houmb'];
            $id = $val['id'];
            //查询数据
            $sql_check = "select ID from BOM_MENBAN_WIDE_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENBAN_WIDE_RULE set ZHIZAOBM='/$zhizaobm/',DANGCI='/$dangci/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',
                                    GUIGECD='$guigecd',MUQIANMB='$muqianmb',MUHOUMB='$muhoumb',
                                    ZIQIANMB='$ziqianmb',ZIHOUMB='$zihoumb',MUZIQMB='$muziqmb',MUZIHMB='$muzihmb',
                                    ZIZIQMB='$ziziqmb',ZIZIHMB='$zizihmb',CAIZHI='$caizhi',QIANMB='$qianmb',
                                    HOUMB='$houmb',MKHOUDU='/$mkhoudu/',KAIXIANG='/$kaixiang/'  where ID='$id'";
                $updateResult = Db::execute($sql_update);
            } else {
                //插入数据
                $sql_ins = "insert into BOM_MENBAN_WIDE_RULE (ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,GUIGECD,MUQIANMB,
                              MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB,CAIZHI,QIANMB,HOUMB,MKHOUDU,KAIXIANG)VALUES (bom_rule_id.nextval,
                              '/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','$guigecd','$muqianmb','$muhoumb','$ziqianmb',
                              '$zihoumb','$muziqmb','$muzihmb','$ziziqmb','$zizihmb','$caizhi','$qianmb','$houmb','/$mkhoudu/','/$kaixiang/')";
                $insResult = DB::execute($sql_ins);;
            }
        }
    }

    /**
     * 铰锁门框用料 添加/修改
     * @param $data
     */
    public function menkuangJiaoSuo($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $kaixiang = $val['kaixiang'];
            $guigecd = $val['guigecd'];
            $jiaomk_length = $val['jiaomk_length'];
            $jiaomk = $val['jiaomk'];
            $jiaomk_hd = $val['jiaomk_hd'];
            $jiaomk_density = $val['jiaomk_density'];
            $suomk_length = $val['suomk_length'];
            $suomk_hd = $val['suomk_hd'];
            $suomk = $val['suomk'];
            $suomk_density = $val['suomk_density'];
            $menkuanghd = $val['menkuanghd'];
            $menkuangcz = $val['menkuangcz'];
            $id = $val['id'];
            //查询数据
            $sql_check = "select ID from BOM_MENKUANG_JIAOSUO_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENKUANG_JIAOSUO_RULE set ZHIZAOBM='/$zhizaobm/',DANGCI='/$dangci/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',
                              KAIXIANG='/$kaixiang/',MENKUANGHD='$menkuanghd',GUIGECD='$guigecd',
                              JIAOMK_LENGTH='$jiaomk_length',JIAOMK='$jiaomk',JIAOMK_HD='$jiaomk_hd',JIAOMK_DENSITY ='$jiaomk_density',
                              SUOMK_LENGTH='$suomk_length',SUOMK='$suomk_hd',SUOMK_HD='$suomk',SUOMK_DENSITY ='$suomk_density',
                              MENKUANGCZ='/$menkuangcz/' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            } else {
                //插入数据
                $sql_ins = "insert into BOM_MENKUANG_JIAOSUO_RULE (ID,ZHIZAOBM,DANGCI,MENKUANG,KAIXIANG,MENKUANGHD,
                              GUIGECD,JIAOMK_LENGTH,JIAOMK,JIAOMK_HD,JIAOMK_DENSITY,SUOMK_LENGTH,SUOMK,SUOMK_HD,
                              SUOMK_DENSITY,MENKUANGCZ,MENSHAN)VALUES (bom_rule_id.nextval,'/$zhizaobm/',
                              '/$dangci/','/$menkuang/','/$kaixiang/','$menkuanghd','$guigecd',
                              '$jiaomk_length','$jiaomk','$jiaomk_hd','$jiaomk_density','$suomk_length','$suomk_hd',
                              '$suomk','$suomk_density','/$menkuangcz/','/$menshan/')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * 上中门框用料添加/修改
     * @param $data
     */
    public function menkuangShangZhong($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $kaixiang = $val['kaixiang'];
            $guigecd = $val['guigecd'];
            $menshan = $val['menshan'];
            $caizhi = $val['caizhi'];
            $shangmk_length = $val['shangmk_length'];
            $shangmk = $val['shangmk'];
            $shangmk_hd = $val['shangmk_hd'];
            $shangmk_density = $val['shangmk_density'];
            $zhongmk_length = $val['zhongmk_length'];
            $zhongmk_hd = $val['zhongmk_hd'];
            $zhongmk = $val['zhongmk'];
            $zhongmk_density = $val['zhongmk_density'];
            $menkuanghd = $val['menkuanghd'];
            $menkuangcz = $val['menkuangcz'];
            $id = $val['id'];
            //查询数据
            $sql_check = "select ID from BOM_MENKUANG_SHANGZHONG_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENKUANG_SHANGZHONG_RULE set ZHIZAOBM='/$zhizaobm/',DANGCI='/$dangci/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',
                              KAIXIANG='/$kaixiang/',MENKUANGHD='$menkuanghd',CAIZHI='$caizhi',GUIGECD='$guigecd',
                              SHANGMK_LENGTH='$shangmk_length',SHANGMK='$shangmk',SHANGMK_HD='$shangmk_hd',SHANGMK_DENSITY ='$shangmk_density',
                              ZHONGMK_LENGTH='$zhongmk_length',ZHONGMK='$zhongmk',ZHONGMK_HD='$zhongmk_hd',ZHONGMK_DENSITY ='$zhongmk_density',
                              MENKUANGCZ ='/$menkuangcz/' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            } else {
                //插入数据
                $sql_ins = "insert into BOM_MENKUANG_SHANGZHONG_RULE (ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,KAIXIANG,MENKUANGHD,
                              CAIZHI,GUIGECD,SHANGMK_LENGTH,SHANGMK,SHANGMK_HD,SHANGMK_DENSITY,ZHONGMK_LENGTH,ZHONGMK,ZHONGMK_HD,
                              ZHONGMK_DENSITY,MENKUANGCZ)VALUES (bom_rule_id.nextval,'/$zhizaobm/',
                              '/$dangci/','/$menkuang/','/$menshan/','/$kaixiang/','$menkuanghd','$caizhi','$guigecd',
                              '$shangmk_length','$shangmk','$shangmk_hd','$shangmk_density','$zhongmk_length','$zhongmk',
                              '$zhongmk_hd','$zhongmk_density','/$menkuangcz/')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }
    /**
     * 下门框用料添加/修改
     * @param $data
     */
    public function menkuangXia($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $kaixiang = $val['kaixiang'];
            $guigecd = $val['guigecd'];
            $menshan = $val['menshan'];
            $dikuangcl = $val['dikuangcl'];
            $xiamkhd = $val['xiamk_hd'];
            $xiamk_density = $val['xiamk_density'];
            $xiamk_length = $val['xiamk_length'];
            $xiamk = $val['xiamk'];
            $menkuanghd = $val['menkuanghd'];
            $menkuangcz = $val['menkuangcz'];
            $id = $val['id'];
            //查询数据
            $sql_check = "select ID from BOM_MENKUANG_XIA_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENKUANG_XIA_RULE set ZHIZAOBM='/$zhizaobm/',DANGCI='/$dangci/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',
                                    KAIXIANG='/$kaixiang/',MENKUANGHD='$menkuanghd',DIKUANGCL='/$dikuangcl/',GUIGECD='$guigecd',
                                    XIAMK_LENGTH='$xiamk_length',XIAMK='$xiamk',XIAMK_HD='$xiamkhd',XIAMK_DENSITY='$xiamk_density',
                                    MENKUANGCZ='/$menkuangcz/' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            } else {
                //插入数据
                $sql_ins = "insert into BOM_MENKUANG_XIA_RULE (ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,KAIXIANG,MENKUANGHD,DIKUANGCL,GUIGECD,
                              XIAMK_LENGTH,XIAMK,XIAMK_HD,XIAMK_DENSITY,MENKUANGCZ)VALUES (bom_rule_id.nextval,'/$zhizaobm/',
                              '/$dangci/','/$menkuang/','/$menshan/','/$kaixiang/','$menkuanghd','/$dikuangcl/','$guigecd',
                              '$xiamk_length','$xiamk','$xiamkhd','$xiamk_density','/$menkuangcz/')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }
    /**
     * 窗花用料添加/修改
     * @param $data
     */
    public function chuanghuaYongLiao($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $kaixiang = $val['kaixiang'];
            $chuanghua = $val['chuanghua'];
            $menshan = $val['menshan'];
            $mkhoudu = $val['mkhoudu'];
            $start_height = $val['start_height'];
            $end_height = $val['end_height'];
            $mkheight_rule = $val['mkheight_rule'];
            $mkwide_rule = $val['mkwide_rule'];
            $usage = $val['usage'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_CHUANGHUA_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_CHUANGHUA_RULE set ZHIZAOBM='/$zhizaobm/',MENKUANG='/$menkuang/',DANGCI='/$dangci/',CHUANGHUA='/$chuanghua/',
                                  KAIXIANG='/$kaixiang/',MENSHAN='/$menshan/',MKHOUDU='/$mkhoudu/',START_HEIGHT='$start_height',
                                  END_HEIGHT='$end_height',MKHEIGHT_RULE='$mkheight_rule',MKWIDE_RULE='$mkwide_rule',USAGE ='$usage' 
                                  where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_CHUANGHUA_RULE(ID,ZHIZAOBM,MENKUANG,DANGCI,CHUANGHUA,KAIXIANG,MENSHAN,MKHOUDU,START_HEIGHT,
                                END_HEIGHT,MKHEIGHT_RULE,MKWIDE_RULE,USAGE)VALUES (bom_rule_id.nextval,'/$zhizaobm/','/$menkuang/',
                                '/$dangci/','/$chuanghua/','/$kaixiang/','/$menshan/','/$mkhoudu/','$start_height','$end_height',
                                '$mkheight_rule','$mkwide_rule','$usage')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    public function chuanghuaZhongshiYongLiao($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $kaixiang = $val['kaixiang'];
            $chuanghua = $val['chuanghua'];
            $menshan = $val['menshan'];
            $mkhoudu = $val['mkhoudu'];
            $start_height = $val['start_height'];
            $end_height = $val['end_height'];
            $mkheight_rule = $val['mkheight_rule'];
            $mkwide_rule = $val['mkwide_rule'];
            $liaohao = $val['liaohao'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_CHUANGHUA_ZHONGSHI_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_CHUANGHUA_ZHONGSHI_RULE set ZHIZAOBM='/$zhizaobm/',MENKUANG='/$menkuang/',DANGCI='/$dangci/',CHUANGHUA='/$chuanghua/',
                                  KAIXIANG='/$kaixiang/',MENSHAN='/$menshan/',MKHOUDU='/$mkhoudu/',START_HEIGHT='$start_height',
                                  END_HEIGHT='$end_height',MKHEIGHT_RULE='$mkheight_rule',MKWIDE_RULE='$mkwide_rule',LIAOHAO ='$liaohao',PINMING='$pinming',GUIGE='$guige'
                                  where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_CHUANGHUA_ZHONGSHI_RULE(ID,ZHIZAOBM,MENKUANG,DANGCI,CHUANGHUA,KAIXIANG,MENSHAN,MKHOUDU,START_HEIGHT,
                                END_HEIGHT,MKHEIGHT_RULE,MKWIDE_RULE,LIAOHAO,PINMING,GUIGE)VALUES (bom_rule_id.nextval,'/$zhizaobm/','/$menkuang/',
                                '/$dangci/','/$chuanghua/','/$kaixiang/','/$menshan/','/$mkhoudu/','$start_height','$end_height',
                                '$mkheight_rule','$mkwide_rule','$liaohao','$pinming','$guige')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * PE包装用料规则
     * @param $data
     */
    public function peBaozhuangYongLiao($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $dangci = $val['dangci'];
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $baozhuangfs = $val['baozhuangfs'];
            $baozhpack = $val['baozhpack'];
            $start_height = $val['start_height'];
            $end_height = $val['end_height'];
            $start_width = $val['start_width'];
            $end_width = $val['end_width'];
            $yongliao_length = $val['yongliao_length'];
            $yongliao_width = $val['yongliao_width'];
            $cailiao_length = $val['cailiao_length'];
            $shuliang = $val['shuliang'];
            $liaohao = $val['liaohao'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_PEBAOZHUANG_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_PEBAOZHUANG_RULE set ZHIZAOBM='/$zhizaobm/',DANGCI='/$dangci/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',BAOZHUANGFS='/$baozhuangfs/',BAOZHPACK='/$baozhpack/',START_HEIGHT='$start_height',
                                  END_HEIGHT='$end_height',START_WIDTH='$start_width',END_WIDTH='$end_width',YONGLIAO_LENGTH='$yongliao_length',YONGLIAO_WIDTH='$yongliao_width',CAILIAO_LENGTH='$cailiao_length',SHULIANG='$shuliang',
                                  LIAOHAO ='$liaohao',PINMING='$pinming',GUIGE='$guige' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_PEBAOZHUANG_RULE(ID,ZHIZAOBM,DANGCI,MENKUANG,MENSHAN,BAOZHUANGFS,BAOZHPACK,START_HEIGHT,END_HEIGHT,START_WIDTH,END_WIDTH,
YONGLIAO_LENGTH,YONGLIAO_WIDTH,CAILIAO_LENGTH,SHULIANG,LIAOHAO,PINMING,GUIGE)VALUES (bom_rule_id.nextval,'/$zhizaobm/','/$dangci/','/$menkuang/','/$menshan/','/$baozhuangfs/',
'/$baozhpack/','$start_height','$end_height','$start_width','$end_width','$yongliao_length','$yongliao_width','$cailiao_length','$shuliang','$liaohao','$pinming','$guige')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * PE包装用料规则
     * @param $data
     */
    public function changbianGujiaYongLiao($data){
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $gaodu_start = $val['gaodu_start'];
            $gaodu_end = $val['gaodu_end'];
            $menshan = $val['menshan'];
            $dkcailiao = $val['dkcailiao'];
            $guige_cd = $val['guige_cd'];
            $mumen_jiao_yongliao = $val['mumen_jiao_yongliao'];
            $mumen_suo_yongliao = $val['mumen_suo_yongliao'];
            $zimen_jiao_yongliao = $val['zimen_jiao_yongliao'];
            $zimen_suo_yongliao = $val['zimen_suo_yongliao'];
            $cailiao_cd = $val['cailiao_cd'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_GK_CBGUJIA_YLIAO_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_GK_CBGUJIA_YLIAO_RULE set MENKUANG='/$menkuang/',GAODU_START='$gaodu_start',GAODU_END='$gaodu_end',MENSHAN='/$menshan/',DKCAILIAO='/$dkcailiao/',GUIGE_CD='$guige_cd',
                      MUMEN_JIAO_YONGLIAO='$mumen_jiao_yongliao',MUMEN_SUO_YONGLIAO='$mumen_suo_yongliao',ZIMEN_JIAO_YONGLIAO='$zimen_jiao_yongliao',ZIMEN_SUO_YONGLIAO='$zimen_suo_yongliao',CAILIAO_CD='$cailiao_cd' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_GK_CBGUJIA_YLIAO_RULE(ID,MENKUANG,GAODU_START,GAODU_END,MENSHAN,DKCAILIAO,GUIGE_CD,MUMEN_JIAO_YONGLIAO,MUMEN_SUO_YONGLIAO,ZIMEN_JIAO_YONGLIAO,ZIMEN_SUO_YONGLIAO,CAILIAO_CD)VALUES (bom_rule_id.nextval,'/$menkuang/','$gaodu_start','$gaodu_end',
                      '/$menshan/','$mumen_jiao_yongliao','$mumen_suo_yongliao','$zimen_jiao_yongliao','$zimen_suo_yongliao','$cailiao_cd')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * PE包装用料规则
     * @param $data
     */
    public function changbianGujiaPinmingYongLiao($data){
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $width_start = $val['width_start'];
            $width_end = $val['width_end'];
            $menshan = $val['menshan'];
            $mumen_jiao_liaohao = $val['mumen_jiao_liaohao'];
            $mumen_jiao_pinming = $val['mumen_jiao_pinming'];
            $mumen_jiao_guige = $val['mumen_jiao_guige'];
            $mumen_suo_liaohao = $val['mumen_suo_liaohao'];
            $mumen_suo_pinming = $val['mumen_suo_pinming'];
            $mumen_suo_guige = $val['mumen_suo_guige'];

            $zimen_jiao_liaohao = $val['zimen_jiao_liaohao'];
            $zimen_jiao_pinming = $val['zimen_jiao_pinming'];
            $zimen_jiao_guige = $val['zimen_jiao_guige'];
            $zimen_suo_liaohao = $val['zimen_suo_liaohao'];
            $zimen_suo_pinming = $val['zimen_suo_pinming'];
            $zimen_suo_guige = $val['zimen_suo_guige'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_GK_CBGUJIA_PINMING_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_GK_CBGUJIA_PINMING_RULE set MENKUANG='/$menkuang/',WIDTH_START='$width_start',WIDTH_END='$width_end',
                  MENSHAN='/$menshan/',MUMEN_JIAO_LIAOHAO='$mumen_jiao_liaohao',MUMEN_JIAO_PINMING='$mumen_jiao_pinming',MUMEN_JIAO_GUIGE='$mumen_jiao_guige',
                  MUMEN_SUO_LIAOHAO='$mumen_suo_liaohao',MUMEN_SUO_PINMING='$mumen_suo_pinming',MUMEN_SUO_GUIGE='$mumen_suo_guige',ZIMEN_JIAO_LIAOHAO='$zimen_jiao_liaohao',
                  ZIMEN_JIAO_PINMING='$zimen_jiao_pinming',ZIMEN_JIAO_GUIGE='$zimen_jiao_guige',ZIMEN_SUO_LIAOHAO='$zimen_suo_liaohao',ZIMEN_SUO_PINMING='$zimen_suo_pinming',ZIMEN_SUO_GUIGE='$zimen_suo_guige' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_GK_CBGUJIA_PINMING_RULE(ID,MENKUANG,WIDTH_START,WIDTH_END,MENSHAN,MUMEN_JIAO_LIAOHAO,MUMEN_JIAO_PINMING,MUMEN_JIAO_GUIGE,MUMEN_SUO_LIAOHAO,MUMEN_SUO_PINMING,MUMEN_SUO_GUIGE,ZIMEN_JIAO_LIAOHAO,ZIMEN_JIAO_PINMING,ZIMEN_JIAO_GUIGE,ZIMEN_SUO_LIAOHAO,ZIMEN_SUO_PINMING,ZIMEN_SUO_GUIGE)
              VALUES (bom_rule_id.nextval,'/$menkuang/','$width_start','$width_end','/$menshan/','$mumen_jiao_liaohao','$mumen_jiao_pinming','$mumen_jiao_guige','$mumen_suo_liaohao','$mumen_suo_pinming','$mumen_suo_guige','$zimen_jiao_liaohao','$zimen_jiao_pinming','$zimen_jiao_guige','$zimen_suo_liaohao','$zimen_suo_pinming','$zimen_suo_guige')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * 四开窗花用料添加/修改
     * @param $data
     */
    public function sikaiChuanghuaYongLiao($data){
        foreach ($data['data'] as $val) {
            $zhizaobm = $val['zhizaobm'];
            $menkuang = $val['menkuang'];
            $chuanghua = $val['chuanghua'];
            $menshan = $val['menshan'];
            $xinghao = $val['xinghao'];
            $chuanghuax = $val['chuanghuax'];
            $yongliangx = $val['yongliangx'];
            $chuanghuad = $val['chuanghuad'];
            $yongliangd = $val['yongliangd'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_CHUANGHUA_SIKAI_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_CHUANGHUA_SIKAI_RULE set ZHIZAOBM='/$zhizaobm/',MENKUANG='/$menkuang/',MENSHAN='/$menshan/',CHUANGHUA='/$chuanghua/',
                                XINGHAO='$xinghao',CHUANGHUAX='$chuanghuax',YONGLIANGX='$yongliangx',
                                CHUANGHUAD='$chuanghuad',YONGLIANGD='$yongliangd' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_CHUANGHUA_SIKAI_RULE(ID,ZHIZAOBM,MENKUANG,MENSHAN,CHUANGHUA,XINGHAO,CHUANGHUAX,YONGLIANGX,
                                CHUANGHUAD,YONGLIANGD)VALUES (bom_rule_id.nextval,'/$zhizaobm/','/$menkuang/',
                                '/$chuanghua/','/$menshan/','$xinghao','$chuanghuax',$yongliangx,'$chuanghuad','$yongliangd')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * 钢框木扇用料添加/修改
     * @param $data
     */
    public function gangkuangMushanYongLiao($data)
    {
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $mskaikong = $val['mskaikong'];
            $dkcailiao = $val['dkcailiao'];
            $mumen_guigecd = $val['mumen_guigecd'];
            $mumen_guigekd = $val['mumen_guigekd'];
            $mumen_yongliaocd = $val['mumen_yongliaocd'];
            $mumen_yongliaokd = $val['mumen_yongliaokd'];
            $mumen_cailiaocd = $val['mumen_cailiaocd'];
            $mumen_cailiaokd = $val['mumen_cailiaokd'];
            $mumen_genshu = $val['mumen_genshu'];
            $zimen_guigecd = $val['zimen_guigecd'];
            $zimen_guigekd = $val['zimen_guigekd'];
            $zimen_yongliaocd = $val['zimen_yongliaocd'];
            $zimen_yongliaokd = $val['zimen_yongliaokd'];
            $zimen_cailiaocd = $val['zimen_cailiaocd'];
            $zimen_cailiaokd = $val['zimen_cailiaokd'];
            $zimen_genshu = $val['zimen_genshu'];
            $liaohao = $val['liaohao'];
            $classify = $val['classify'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_MENBAN_GANGKUANG_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENBAN_GANGKUANG_RULE set MENKUANG='/$menkuang/',MENSHAN='/$menshan/',MSKAIKONG='/$mskaikong/',DKCAILIAO='/$dkcailiao/',MUMEN_GUIGECD='$mumen_guigecd',MUMEN_GUIGEKD='$mumen_guigekd',MUMEN_YONGLIAOCD='$mumen_yongliaocd',MUMEN_YONGLIAOKD='$mumen_yongliaokd',
MUMEN_CAILIAOCD='$mumen_cailiaocd',MUMEN_CAILIAOKD='$mumen_cailiaokd',MUMEN_GENSHU='$mumen_genshu',ZIMEN_GUIGECD='$zimen_guigecd',ZIMEN_GUIGEKD='$zimen_guigekd',ZIMEN_YONGLIAOCD='$zimen_yongliaocd',ZIMEN_YONGLIAOKD='$zimen_yongliaokd',ZIMEN_CAILIAOCD='$zimen_cailiaocd',ZIMEN_CAILIAOKD='$zimen_cailiaokd',
ZIMEN_GENSHU='$zimen_genshu',LIAOHAO='$liaohao',CLASSIFY='$classify',PINMING='$pinming',GUIGE='$guige' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_MENBAN_GANGKUANG_RULE(ID,MENKUANG,MENSHAN,MSKAIKONG,DKCAILIAO,MUMEN_GUIGECD,MUMEN_GUIGEKD,MUMEN_YONGLIAOCD,MUMEN_YONGLIAOKD,
MUMEN_CAILIAOCD,MUMEN_CAILIAOKD,MUMEN_GENSHU,ZIMEN_GUIGECD,ZIMEN_GUIGEKD,ZIMEN_YONGLIAOCD,ZIMEN_YONGLIAOKD,ZIMEN_CAILIAOCD,ZIMEN_CAILIAOKD,ZIMEN_GENSHU,LIAOHAO,CLASSIFY,PINMING,GUIGE)
VALUES (bom_rule_id.nextval,'/$menkuang/','/$menshan/','$mskaikong','$dkcailiao','$mumen_guigecd','$mumen_guigekd','$mumen_yongliaocd','$mumen_yongliaokd','$mumen_cailiaocd','$mumen_cailiaokd','$mumen_genshu',
'$zimen_guigecd','$zimen_guigekd','$zimen_yongliaocd','$zimen_yongliaokd','$zimen_cailiaocd','$zimen_cailiaokd','$zimen_genshu','$liaohao','$classify','$pinming','$guige')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * 齐河基地钢框木扇用料添加/修改
     * @param $data
     */
    public function gangkuangMushanQHYongLiao($data)
    {
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $interval_start = $val['interval_start'];
            $interval_end = $val['interval_end'];
            $menshan = $val['menshan'];
            $mskaikong = $val['mskaikong'];
            $dkcailiao = $val['dkcailiao'];
            $mumen_guigecd = $val['mumen_guigecd'];
            $mumen_guigekd = $val['mumen_guigekd'];
            $mumen_yongliaocd = $val['mumen_yongliaocd'];
            $mumen_yongliaokd = $val['mumen_yongliaokd'];
            $mumen_cailiaocd = $val['mumen_cailiaocd'];
            $mumen_cailiaokd = $val['mumen_cailiaokd'];
            $mumen_genshu = $val['mumen_genshu'];
            $zimen_guigecd = $val['zimen_guigecd'];
            $zimen_guigekd = $val['zimen_guigekd'];
            $zimen_yongliaocd = $val['zimen_yongliaocd'];
            $zimen_yongliaokd = $val['zimen_yongliaokd'];
            $zimen_cailiaocd = $val['zimen_cailiaocd'];
            $zimen_cailiaokd = $val['zimen_cailiaokd'];
            $zimen_genshu = $val['zimen_genshu'];
            $liaohao = $val['liaohao'];
            $classify = $val['classify'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_MENBAN_GANGKUANG_QH_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MENBAN_GANGKUANG_QH_RULE set MENKUANG='/$menkuang/',INTERVAL_START='$interval_start',INTERVAL_END='$interval_end',MENSHAN='/$menshan/',MSKAIKONG='/$mskaikong/',DKCAILIAO='/$dkcailiao/',MUMEN_GUIGECD='$mumen_guigecd',MUMEN_GUIGEKD='$mumen_guigekd',MUMEN_YONGLIAOCD='$mumen_yongliaocd',MUMEN_YONGLIAOKD='$mumen_yongliaokd',
MUMEN_CAILIAOCD='$mumen_cailiaocd',MUMEN_CAILIAOKD='$mumen_cailiaokd',MUMEN_GENSHU='$mumen_genshu',ZIMEN_GUIGECD='$zimen_guigecd',ZIMEN_GUIGEKD='$zimen_guigekd',ZIMEN_YONGLIAOCD='$zimen_yongliaocd',ZIMEN_YONGLIAOKD='$zimen_yongliaokd',ZIMEN_CAILIAOCD='$zimen_cailiaocd',ZIMEN_CAILIAOKD='$zimen_cailiaokd',
ZIMEN_GENSHU='$zimen_genshu',LIAOHAO='$liaohao',CLASSIFY='$classify',PINMING='$pinming',GUIGE='$guige' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_MENBAN_GANGKUANG_QH_RULE(ID,MENKUANG,INTERVAL_START,INTERVAL_END,MENSHAN,MSKAIKONG,DKCAILIAO,MUMEN_GUIGECD,MUMEN_GUIGEKD,MUMEN_YONGLIAOCD,MUMEN_YONGLIAOKD,
MUMEN_CAILIAOCD,MUMEN_CAILIAOKD,MUMEN_GENSHU,ZIMEN_GUIGECD,ZIMEN_GUIGEKD,ZIMEN_YONGLIAOCD,ZIMEN_YONGLIAOKD,ZIMEN_CAILIAOCD,ZIMEN_CAILIAOKD,ZIMEN_GENSHU,LIAOHAO,CLASSIFY,PINMING,GUIGE)
VALUES (bom_rule_id.nextval,'/$menkuang/','$interval_start','$interval_end','/$menshan/','$mskaikong','$dkcailiao','$mumen_guigecd','$mumen_guigekd','$mumen_yongliaocd','$mumen_yongliaokd','$mumen_cailiaocd','$mumen_cailiaokd','$mumen_genshu',
'$zimen_guigecd','$zimen_guigekd','$zimen_yongliaocd','$zimen_yongliaokd','$zimen_cailiaocd','$zimen_cailiaokd','$zimen_genshu','$liaohao','$classify','$pinming','$guige')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }


    /**
     * 填芯料用料添加/修改
     * @param $data
     */
    public function tianxinMenshanYongLiao($data)
    {
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $dkcailiao = $val['dkcailiao'];
            $huase = $val['huase'];
            $yongliao_mumen = $val['yongliao_mumen'];
            $yongliao_zimen = $val['yongliao_zimen'];
            $liaohao = $val['liaohao'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $classify = $val['classify'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_TIANXIN_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_TIANXIN_RULE set MENKUANG='/$menkuang/',MENSHAN='/$menshan/',DKCAILIAO='/$dkcailiao/',HUASE='$huase',YONGLIAO_MUMEN='$yongliao_mumen',YONGLIAO_ZIMEN='$yongliao_zimen',
LIAOHAO='$liaohao',PINMING='$pinming',GUIGE='$guige',CLASSIFY='$classify' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_TIANXIN_RULE(ID,MENKUANG,MENSHAN,DKCAILIAO,HUASE,YONGLIAO_MUMEN,YONGLIAO_ZIMEN,LIAOHAO,PINMING,GUIGE,CLASSIFY)
VALUES (bom_rule_id.nextval,'/$menkuang/','/$menshan/','$dkcailiao','$huase','$yongliao_mumen','$yongliao_zimen','$liaohao','$pinming','$guige','$classify')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    /**
     * 齐河基地填芯料用料添加/修改
     * @param $data
     */
    public function tianxinMenshanQHYongLiao($data)
    {
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $interval_start = $val['interval_start'];
            $interval_end = $val['interval_end'];
            $menshan = $val['menshan'];
            $dkcailiao = $val['dkcailiao'];
            $huase = $val['huase'];
            $yongliao_mumen = $val['yongliao_mumen'];
            $yongliao_zimen = $val['yongliao_zimen'];
            $liaohao = $val['liaohao'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $classify = $val['classify'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_TIANXIN_QH_RULE where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_TIANXIN_QH_RULE set MENKUANG='/$menkuang/',INTERVAL_START='$interval_start',INTERVAL_END='$interval_end',MENSHAN='/$menshan/',DKCAILIAO='/$dkcailiao/',HUASE='$huase',YONGLIAO_MUMEN='$yongliao_mumen',YONGLIAO_ZIMEN='$yongliao_zimen',
LIAOHAO='$liaohao',PINMING='$pinming',GUIGE='$guige',CLASSIFY='$classify' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_TIANXIN_QH_RULE(ID,MENKUANG,INTERVAL_START,INTERVAL_END,MENSHAN,DKCAILIAO,HUASE,YONGLIAO_MUMEN,YONGLIAO_ZIMEN,LIAOHAO,PINMING,GUIGE,CLASSIFY)
VALUES (bom_rule_id.nextval,'/$menkuang/','$interval_start','$interval_end','/$menshan/','$dkcailiao','$huase','$yongliao_mumen','$yongliao_zimen','$liaohao','$pinming','$guige','$classify')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    public function mianQiYongLiao($data)
    {
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $start_len = $val['start_len'];
            $end_len = $val['end_len'];
            $biaomcl = $val['biaomcl'];
            $biaomiantsyq = $val['biaomiantsyq'];
            $mumen = $val['mumen'];
            $zimen = $val['zimen'];
            $liaohao = $val['liaohao'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $zhizaobm = $val['zhizaobm'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_MIANQIB where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_MIANQIB set MENKUANG='/$menkuang/',MENSHAN='/$menshan/',START_LEN='$start_len',END_LEN='$end_len',BIAOMCL='$biaomcl',BIAOMIANTSYQ='$biaomiantsyq',
MUMEN='$mumen',ZIMEN='$zimen',LIAOHAO='$liaohao',PINMING='$pinming',GUIGE='$guige',ZHIZAOBM='/$zhizaobm/' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_MIANQIB(ID,MENKUANG,MENSHAN,START_LEN.END_LEN,BIAOMCL,BIAOMIANTSYQ,MUMEN,ZIMEN,LIAOHAO,PINMING,GUIGE,ZHIZAOBM)
VALUES (bom_rule_id.nextval,'/$menkuang/','/$menshan/','$start_len','$end_len','$biaomcl','$biaomiantsyq','$mumen','$zimen','$liaohao','$pinming','$guige','$zhizaobm')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }

    public function fengBianYongLiao($data)
    {
        foreach ($data['data'] as $val) {
            $menkuang = $val['menkuang'];
            $menshan = $val['menshan'];
            $biaomcl = $val['biaomcl'];
            $biaomiantsyq = $val['biaomiantsyq'];
            $dkcailiao = $val['dkcailiao'];
            $guige_cd = $val['guige_cd'];
            $mumen = $val['mumen'];
            $zimen = $val['zimen'];
            $liaohao = $val['liaohao'];
            $pinming = $val['pinming'];
            $guige = $val['guige'];
            $zhizaobm = $val['zhizaobm'];
            $id = $val['id'];
            $sql_check = "select ID from BOM_FENGBIANT where ID='$id'";
            $checkResult = Db::query($sql_check);
            if (count($checkResult)) {
                //修改已有数据
                $sql_update = "update BOM_FENGBIANT set MENKUANG='/$menkuang/',MENSHAN='/$menshan/',BIAOMCL='$biaomcl',BIAOMIANTSYQ='$biaomiantsyq',DKCAILIAO='/$dkcailiao/',GUIGE_CD='$guige_cd',MUMEN='$mumen',ZIMEN='$zimen',LIAOHAO='$liaohao',PINMING='$pinming',GUIGE='$guige',ZHIZAOBM='/$zhizaobm/' where ID='$id'";
                $updateResult = Db::execute($sql_update);
            }else{
                //插入数据
                $sql_ins = "insert into BOM_FENGBIANT(ID,MENKUANG,MENSHAN,BIAOMCL,BIAOMIANTSYQ,GUIGE_CD,MUMEN,ZIMEN,LIAOHAO,PINMING,GUIGE,ZHIZAOBM,DKCAILIAO)
VALUES (bom_rule_id.nextval,'/$menkuang/','/$menshan/','$biaomcl','$biaomiantsyq','$guige_cd','$mumen','$zimen','$liaohao','$pinming','$guige','/$zhizaobm/','/$dkcailiao/')";
                $insResult = DB::execute($sql_ins);
            }
        }
    }


    public function delYongliaoRule($data){
        $biao = $data["biao"];
        switch ($biao){
            //门板长度用料规则
            case 'manbanLength':{
                $biao = "BOM_MENBAN_LENGTH_RULE";
                break;
            }
            //门板宽度用料规则
            case 'menbanWide':{
                $biao = "BOM_MENBAN_WIDE_RULE";
                break;
            }
            //铰锁用料规则
            case 'menkuangJiaoSuo':{
                $biao = "BOM_MENKUANG_JIAOSUO_RULE";
                break;
            }
            //上中用料规则
            case 'menkuangShangZhong':{
                $biao = "BOM_MENKUANG_SHANGZHONG_RULE";
                break;
            }
            //下门框用料规则
            case 'menkuangXia':{
                $biao = "BOM_MENKUANG_XIA_RULE";
                break;
            }
            //窗花用料计算规则
            case 'chuanghuaJisuan':{
                $biao = "BOM_CHUANGHUA_RULE";
                break;
            }
            //四开窗花用料计算规则
            case 'sikaiChuanghuaJisuan':{
                $biao = "BOM_CHUANGHUA_SIKAI_RULE";
                break;
            }
            case 'gangkuangMushan':{
                $biao = "BOM_MENBAN_GANGKUANG_RULE";
                break;
            }
            case 'tianxinMenshan':{
                $biao = "BOM_TIANXIN_RULE";
                break;
            }
            case 'gangkuangMushanQH':{
                $biao = "BOM_MENBAN_GANGKUANG_QH_RULE";
                break;
            }
            case 'tianxinMenshanQH':{
                $biao = "BOM_TIANXIN_QH_RULE";
                break;
            }
            case 'mianQi':{
                $biao = "BOM_MIANQIB";
                break;
            }
            case 'fengBian':{
                $biao = "BOM_FENGBIANT";
                break;
            }
            case 'chuanghuaZhongshi':{
                $biao = "BOM_CHUANGHUA_ZHONGSHI_RULE";
                break;
            }
            case 'peBaozhuang':{
                $biao = "BOM_PEBAOZHUANG_RULE";
                break;
            }
            case 'changbianGujiaYongliao':{
                $biao = "BOM_GK_CBGUJIA_YLIAO_RULE";
                break;
            }
            case 'changbianGujiaPinming':{
                $biao = "BOM_GK_CBGUJIA_PINMING_RULE";
                break;
            }
        }
        foreach ($data['data'] as $val) {
            $sql = " delete from $biao where ID ='$val'";
            $result = Db::execute($sql);
        }
    }
}