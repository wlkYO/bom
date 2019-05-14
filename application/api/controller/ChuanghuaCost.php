<?php
/**
 * Created by PhpStorm.
 * User: 000
 * Date: 2019/5/6
 * Time: 16:09
 */

namespace app\api\controller;


use think\Db;

class ChuanghuaCost
{
    public function getChuanghuaPrice($dingdanh = '', $xiangci = '')
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
        header('Access-Control-Allow-Credentials: true');

        $handleLh = Db::query("select distinct(chuanghua_chengpin_liaohao) from bom_chuanghua_liaohao where dingdanh = '$dingdanh' and xiangci =$xiangci", 1);

        foreach ($handleLh as $k => $v) {
            $chengpin = $v['chuanghua_chengpin_liaohao'];
            $liaohaoSql = "select liaohao,yongliang,zhizaobm,convert_zhizaobm,chuanghua_chengpin_liaohao,chuanghua_guige  from bom_chuanghua_liaohao where dingdanh = '$dingdanh' and xiangci = '$xiangci' and chuanghua_chengpin_liaohao = '$chengpin'";
            $chuanghuaData = Db::query($liaohaoSql, 1);
            $cost = 0;    //材料成本单价
            $chuanghua = Db::query("select chuanghua,operate_plan from oeb_file where oeb01 = '$dingdanh' and oeb03='$xiangci'", 1);
            $saveChuangh = $chuanghua[0]['chuanghua'];
            $saveBm = $chuanghua[0]['operate_plan'];
            if (in_array($saveBm, ['DS4', 'DS6', 'DS12', 'DS15'])) {
                $saveBm = '青白江';
            } elseif ($saveBm == 'DS16') {
                $saveBm = '咸宁';
            } elseif (in_array($saveBm, ['DS9', 'DS10'])) {
                $saveBm = '齐河';
            }
            if (strpos($saveChuangh, '中式') !== false) {
                if ($saveBm == '咸宁') {
                    $type = '中式窗花';
                } elseif (strpos($saveChuangh, '中式窗花Z06') !== false) {
                    $type = '中式窗花Z06';
                } else {
                    $type = '中式窗花Z01-Z05';
                }
            } elseif (strpos($saveChuangh, '常规') !== false) {
                $type = '常规窗花';
            } elseif (strpos($saveChuangh, '不锈铁') !== false) {
                $type = '不锈铁窗花';
            } elseif (strpos($saveChuangh, '不锈钢') !== false) {
                $type = '不锈钢窗花';
            } elseif (strpos($saveChuangh, '封闭式') !== false) {
                $type = '封闭式窗花';
            }
            foreach ($chuanghuaData as $k => $v) {
                $db = 'ta_cba_file@' . $v['convert_zhizaobm'] . '_link';
                $liaohao = $v['liaohao'];
                $sql = "select ta_cba05 from $db where ta_cba01='$liaohao' and rownum <2 order by ta_cba02 desc,ta_cba03 desc ";
                $ta_cba05 = Db::query($sql, 1);
                #材料成本=窗花BOM中QPA用量*asdi100窗花下阶料单价
                $cost += round($v['yongliang'] * $ta_cba05[0]['ta_cba05'], 6);
            }
            $guige = explode('×', $chuanghuaData[0]['chuanghua_guige']);
            if (empty($guige[1])) {
                $guige = explode('*', $chuanghuaData[0]['chuanghua_guige']);
            }
            $height = $guige[0];
            $width = $guige[1];
            $price = Db::query("select price from bom_chuanghua_price where chuanghua_type ='$type' and $width between start_width and end_width and zhizaobm ='$saveBm'", 1);
            $square = $price[0]['price'];
            #定价=长度*宽度*乘平方值
            $dingjia = round($height * $width * $square / 1000000, 6);
            # 价差/本阶直接材料成本  =    定价 —  材料成本单价
            $chajia = $dingjia - $cost;
            $chajia = $chajia < 0 ? 0 : $chajia;
            $dingjiaRound = round($dingjia, 2);    //axci900  调拨单价/不开票单价  == 定价 保留2位
            $cailiaoCb = round($cost, 4);         //axci900  成套发料单价  == 材料成本 保留4位

            #窗花料号
            $chuanghuaLiaohao = $chuanghuaData[0]['chuanghua_chengpin_liaohao'];
            $year = date('Y', time());
            $month = date('n', time()) - 3;
            $zhizaobm = $chuanghuaData[0]['zhizaobm'];
            $convert_zhizaobm = $chuanghuaData[0]['convert_zhizaobm'];
            $sta_file = 'sta_file@' . $zhizaobm . '_link';
            $date = date('Y-m-d h:i:s', time());
            $res = Db::execute("insert into bom_price_information (id,dingdanh,xiangci,zhizaobm,convert_zhizaobm,chengpin_liaohao,guige,cailiao_cost,dingjia,chajia,square_price,year,month,chuanghua,create_time) values (BOM_CHUANGHUA_ID.nextval,'$dingdanh','$xiangci','$zhizaobm','$convert_zhizaobm','$chuanghuaLiaohao','$height*$width','$cost','$dingjia','$chajia','$square','$year','$month','$saveChuangh',to_date('$date','YYYY-MM-DD HH24:MI:SS'))");

            #更新价差到sta_file中
            $staLhData = Db::query("select * from $sta_file where sta01 = '$chuanghuaLiaohao'");
            if (in_array($zhizaobm, ['DS9', 'DS6', 'DS16'])) {
                if (!empty($staLhData)) {
                    Db::execute("update  $sta_file set sta04 = '$chajia',ta_sta02 = '$chajia' where sta01 = '$chuanghuaLiaohao'");
                } else {
                    Db::execute("insert into $sta_file (sta01,sta04,ta_sta02) values ('$chuanghuaLiaohao','$chajia','$chajia')");
                }
            } else {
                if (!empty($staLhData)) {
                    Db::execute("update  $sta_file set sta04 = '$dingjiaRound',ta_sta02 = '$dingjiaRound' where sta01 = '$chuanghuaLiaohao'");
                } else {
                    Db::execute("insert into $sta_file (sta01,sta04,ta_sta02) values ('$chuanghuaLiaohao','$dingjiaRound','$dingjiaRound')");
                }
            }
            //将材料成本单价写入stb_file表中
            if (in_array($zhizaobm, ['DS9', 'DS6', 'DS16'])) {
                $stb_file = 'stb_file@' . $chuanghuaData[0]['zhizaobm'] . '_link';
                $stb_fileRes = Db::query("select * from $stb_file where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                if (!empty($stb_fileRes)) {
                    Db::execute("update $stb_file set stb04='$chajia',stb07='$dingjia',ta_stb01='$chajia' ,ta_stb02 = '$dingjia' where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                } else {
                    Db::execute("insert into $stb_file (stb01,stb02,stb03,stb04,stb05,stb06,stb06a,stb07,stb08,stb09,stb09a,stb10,ta_stb01,ta_stb02) values ('$chuanghuaLiaohao','$year','$month','$chajia',0.0,0.0,0.0,'$dingjia',0.0,0.0,0.0,0.0,'$chajia','$dingjia')");
                }
            }

            //处理900
            $ta_cbaFile = 'ta_cba_file@' . $zhizaobm . '_link';
            $ta_cbaData = Db::query("select * from $ta_cbaFile where ta_cba01 = '$chuanghuaLiaohao' and ta_cba01 = '$chuanghuaLiaohao' and ta_cba02 = '$year' and ta_cba03 = '$month' ");
            if (!empty($ta_cbaData)) {
                Db::execute("update $ta_cbaFile set ta_cba05 = '$dingjiaRound',ta_cba06='$dingjiaRound',ta_cba11 = '$cailiaoCb' where ta_cba01 = '$chuanghuaLiaohao' and ta_cba02 = '$year' and ta_cba03 = '$month' ");
            } else {
                Db::execute("insert into $ta_cbaFile(ta_cba01,ta_cba02,ta_cba03,ta_cba05,ta_cba06,ta_cba11,ta_cba13,ta_cba14) VALUES ('$chuanghuaLiaohao','$year','$month','$dingjiaRound','$dingjiaRound','$cailiaoCb','$year','$month')");
            }
            #处理转库的问题
            if ($zhizaobm == 'DS6') {
                $bmArr = ['DS4', 'DS12', 'DS15'];
                foreach ($bmArr as $key => $value) {
                    $sta_fileBm = 'sta_file@' . $value . '_link';
                    $bmMsg = Db::query("select * from $sta_fileBm where sta01 = '$chuanghuaLiaohao'", 1);
                    if (!empty($bmMsg)) {
                        Db::execute("update $sta_fileBm set sta04 = '$dingjiaRound',ta_sta02='$dingjiaRound' where sta01='$chuanghuaLiaohao'");
                    } else {
                        Db::execute("insert into $sta_fileBm select * from $sta_file where sta01 = '$chuanghuaLiaohao'");
                        Db::execute("update $sta_fileBm set sta04 = '$dingjiaRound',ta_sta02='$dingjiaRound' where sta01='$chuanghuaLiaohao'");
                    }
                    //stb
                    $stb_fileBm = 'stb_file@' . $value . '_link';
                    $stb_file = Db::query("select * from $stb_fileBm where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                    if (!empty($stb_file)) {
                        Db::execute("update $stb_fileBm set stb04='$dingjiaRound',stb07='$dingjiaRound',ta_stb01='$dingjiaRound' ,ta_stb02 = '$dingjiaRound' where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                    } else {
                        Db::execute("insert into $stb_fileBm (stb01,stb02,stb03,stb04,stb05,stb06,stb06a,stb07,stb08,stb09,stb09a,stb10,ta_stb01,ta_stb02) values ('$chuanghuaLiaohao','$year','$month','$dingjiaRound',0.0,0.0,0.0,'$dingjiaRound',0.0,0.0,0.0,0.0,'$dingjiaRound','$dingjiaRound')");
                    }
                }
            }
            if ($zhizaobm == 'DS9') {
                $tableName = 'sta_file@DS10_link';
                $bmMsg = Db::query("select * from $tableName where sta01 = '$chuanghuaLiaohao'", 1);
                if (!empty($bmMsg)) {
                    Db::execute("update $tableName set sta04 = '$dingjia',ta_sta02='$dingjia' where sta01='$chuanghuaLiaohao'");
                } else {
                    Db::execute("insert into '$tableName'select * from $sta_file where sta01 = '$chuanghuaLiaohao'");
                    Db::execute("update $tableName set sta04 = '$dingjia',ta_sta02='$dingjia' where sta01='$chuanghuaLiaohao'");
                }
                //stb
                $stb_file = Db::query("select * from $tableName where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                if (!empty($stb_file)) {
                    Db::execute("update $tableName set stb04='$dingjiaRound',stb07='$dingjiaRound',ta_stb01='$dingjiaRound' ,ta_stb02 = '$dingjiaRound' where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                } else {
                    Db::execute("insert into $tableName (stb01,stb02,stb03,stb04,stb05,stb06,stb06a,stb07,stb08,stb09,stb09a,stb10,ta_stb01,ta_stb02) values ('$chuanghuaLiaohao','$year','$month','$dingjiaRound',0.0,0.0,0.0,'$dingjiaRound',0.0,0.0,0.0,0.0,'$dingjiaRound','$dingjiaRound')");
                }
            }

            //  处理   covent_zhizaobm sta_file,stb_file,ta_cba_file
            if ($zhizaobm != 'DS6' && $zhizaobm != 'DS9' && $zhizaobm != 'DS16') {
                $ta_file_c = 'sta_file@' . $convert_zhizaobm . '_link';
                $tb_file_c = 'stb_file@' . $convert_zhizaobm . '_link';
                $ta_cba_c = 'ta_cba_file@' . $convert_zhizaobm . '_link';
                $staLhData_c = Db::query("select * from $ta_file_c where sta01 = '$chuanghuaLiaohao'");
                if (!empty($staLhData_c)) {
                    Db::execute("update  $ta_file_c set sta04 = '$chajia',ta_sta02 = '$chajia' where sta01 = '$chuanghuaLiaohao'");
                } else {
                    Db::execute("insert into $ta_file_c (sta01,sta04,ta_sta02) values ('$chuanghuaLiaohao','$chajia','$chajia')");
                }
                $stb_fileRes_c = Db::query("select * from $tb_file_c where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                if (!empty($stb_fileRes_c)) {
                    Db::execute("update $tb_file_c set stb04='$chajia',stb07='$dingjia',ta_stb01='$chajia' ,ta_stb02 = '$dingjia' where stb01 = '$chuanghuaLiaohao' and stb02 = '$year' and stb03 = '$month'");
                } else {
                    Db::execute("insert into $tb_file_c (stb01,stb02,stb03,stb04,stb05,stb06,stb06a,stb07,stb08,stb09,stb09a,stb10,ta_stb01,ta_stb02) values ('$chuanghuaLiaohao','$year','$month','$chajia',0.0,0.0,0.0,'$dingjia',0.0,0.0,0.0,0.0,'$chajia','$dingjia')");
                }
                $ta_cbaData = Db::query("select * from $ta_cba_c where ta_cba01 = '$chuanghuaLiaohao' and ta_cba01 = '$chuanghuaLiaohao' and ta_cba02 = '$year' and ta_cba03 = '$month' ");
                if (!empty($ta_cbaData)) {
                    Db::execute("update $ta_cba_c set ta_cba05 = '$dingjiaRound',ta_cba06='$dingjiaRound',ta_cba11 = '$cailiaoCb' where ta_cba01 = '$chuanghuaLiaohao' and ta_cba02 = '$year' and ta_cba03 = '$month' ");
                } else {
                    Db::execute("insert into $ta_cba_c(ta_cba01,ta_cba02,ta_cba03,ta_cba05,ta_cba06,ta_cba11,ta_cba13,ta_cba14) VALUES ('$chuanghuaLiaohao','$year','$month','$dingjiaRound','$dingjiaRound','$cailiaoCb','$year','$month')");
                }
            }

        }
    }

    public function handlePriceList($dingdanh = '', $xiangci = '', $zhizaobm = '', $sdate = '', $edate = '')
    {
        if (!empty($dingdanh) && !empty($xiangci)) {
            $this->getChuanghuaPrice($dingdanh, $xiangci);
            return retmsg(0, null, '导入成功');
        } else {
            $sql = "select oeb01, oeb03, chuanghua from oeb_file
            where (oeb01 like '%M9%' or oeb01 like '%M8%' or oeb01 like '%M5%')
               and jh_sh_date BETWEEN to_date('$sdate', 'yyyy-mm-dd') and
                   to_date('$edate', 'yyyy-mm-dd')
               and OPERATE_PLAN =
                   (select GYS_CODE from SELL_GYS where GYS_NAME = '$zhizaobm') and (chuanghua != '无副窗'and chuanghua is not null)";
            $res = Db::query($sql, 1);
            if (empty($res)) {
                return retmsg(-1, null, '没有查到相关单号');
            } else {
                foreach ($res as $key => $value) {
                    $dingdanh = $value['oeb01'];
                    $xiangci = $value['oeb03'];
                    $this->getChuanghuaPrice($dingdanh, $xiangci);
                }
                return retmsg(0, null, '导入成功');
            }
        }
    }

}