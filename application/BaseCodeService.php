<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5
 * Time: 11:11
 */

namespace app\admin\service;
use think\Db;

/**
 * 编码料号公共服务
 * Class BaseCode
 * @package app\admin\service
 */
class BaseCodeService{
    const CHENGPIN = "成品";//成品编码规则名称
    const CHENGPIN_FIXED_CODE = "KH";//成品规则前2位固定码
    const BANPINMK = "半品门框";//半品门框编码规则名称
    const BANPINMS = "半品门扇";//半品门扇编码规则名称
    const BANPINMK_FIXED_CODE = "KB";//半品门框前2位固定码
    const BANPINMS_FIXED_CODE = "KB";//半品门扇前2位固定码
    const CHUANGHUA = "窗花";//窗花编码规则名称
    const CHUANGHUA_FIXED_CODE = "KGMAP";//窗花规则前5位固定码
    const DANKUANG = "单框";//单框编码规则名称
    const MENBAN = "门板";//门板编码规则名称
    const MENBAN_FIXED_CODE = "KG";//门板前2位固定码
    const DANKUANG_FIXED_CODE = "KG";//单框前2位固定码
    const CAIZHI = "材质";//材质编码规则名称
    const CAIZHI_FIXED_CODE = "KM4";//材质前3位固定码
    const CAIZHI_DENSITY = 7.85;//材质密度
    const QPA_MENBAN_WIDTH_ADD = 2;//门板计算加此数


    /**
     * 根据订单信息获取料号
     * @param $ruleName 规则名称
     * @param $order 订单信息 包含用料信息
     * @param $dankuanglx 单框类型：上门框、低框、边框、中框，
     * @param $menshanlx 门扇类型 母，子，母子，子子 默认母
     * @param $menbanlx 门板类型 前门板、后门板
     * $param $chuanghualx 窗花类型 (只有四开窗花时才有)大、小
     */
    public function getMaterialCode($ruleName,$order,$dankuanglx='',$menshanlx='',$menbanlx='',$chuanghualx=''){
        $findAllData = "select t.* from bom_code_rule t where t.rule_name='$ruleName' and t.order_column_name!='GUIGE' order by t.code_sort asc";
        $sortNum = "select distinct(code_sort),order_column_name from bom_code_rule where rule_name='$ruleName' order by code_sort asc";
        $allData = DB::query($findAllData);
        //去空格
        foreach ($allData as $k => $v){
            foreach ($v as $key => $val){
                $allData[$k][$key] = excel_trim($val);
            }
        }
        $sorts = DB::query($sortNum);
        $handleSortAraay = array();//待处理的序号数组
        foreach ($sorts as $k=>$v){
            array_push($handleSortAraay,$v['CODE_SORT']);
        }

        //编码数组
        $code = array();
        //对规格进行单独处理
        $guige = $order['guige'];
        if($ruleName=='半品门扇'){
            $guige = $this->gaoduChange($order['menkuang'],$order['height']).'×'.$order['width'];
        }
        $guigeChuli = "select code,code_sort from bom_code_rule where rule_name='$ruleName' and attri_name='$guige'";
        $guigeCode = DB::query($guigeChuli);
        if(!empty($guigeCode)){
            $guigeMaterialCode = $guigeCode[0]['CODE'];
            $guigeCodeSort = $guigeCode[0]['CODE_SORT'];
            $guigeArr = array("sort_number"=>$guigeCodeSort,"code"=>$guigeMaterialCode);
            $code[$guigeCodeSort]=$guigeArr;
            unset($handleSortAraay[array_search($guigeCodeSort,$handleSortAraay)]);
        }
        //规格处理结束

        $fixedCode = "";
        foreach ($allData as $key=>$val){
            $attriName = $val['ATTRI_NAME'];//属性值
            $orderColumnName = $val['ORDER_COLUMN_NAME'];//关联的订单列名
            $orderAttrName=$order[strtolower($orderColumnName)];//关联的订单属性值
            $orderMultiName = "";//关联多订单属性，拼接字符串值
            if(strpos($orderColumnName,',')){
                $orderColumnNameArray=explode(',',$orderColumnName);
                foreach($orderColumnNameArray as $k=>$v){
                    $orderMultiName .= $order[strtolower($v)];
                }
            }
            $codeName=$val['CODE_NAME'];//编码含义
            $sortID = $val['CODE_SORT'];//排序码
            $isDefault=$val['IS_DEFAULT'];//默认占位符
            switch ($ruleName){
                //成品编码规则
                case self::CHENGPIN :
                    $fixedCode = self::CHENGPIN_FIXED_CODE;
                    if($codeName=='档次'){
                        $test=1;
                    }
                    if(in_array($sortID,$handleSortAraay)){
                        //规则属性值与订单属性值匹配
                        if(
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //门框 门扇类型
                            ($codeName=='门扇类型'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //主锁 门锁体类型及品牌
                            ($codeName=='主锁'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //锁芯
                            ($codeName=='锁芯'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //底框材料 底框厚度
                            ($codeName=='底框厚度'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName,$attriName,$order))||
                            //多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //半品门框编码规则
                case self::BANPINMK :
                    $fixedCode = self::BANPINMK_FIXED_CODE;
                    if($codeName=='门扇类型'){
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #门框 门扇类型 门框中提取
                            ($codeName=='门扇类型'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #主锁 门锁体类型及品牌
                            ($codeName=='主锁'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #底框材料 底框厚度
                            ($codeName=='底框厚度'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //半品门扇编码规则
                case self::BANPINMS:
                    $fixedCode = self::BANPINMS_FIXED_CODE;
                    if($codeName=='半成品类型'){
                        $test=1;
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            #属性值完全匹配
                            ($attriName == $orderAttrName)||
                            #半成品类型
                            ($codeName=='半成品类型'&&$this->match($codeName,$attriName,$orderAttrName,'',$menshanlx))||
                            #门框 门扇类型=门框+门框要求
                            ($codeName=='门扇类型'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #主锁 门锁体类型及品牌
                            ($codeName=='主锁'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #底框材料 底框类型
                            ($codeName=='底框类型'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #特殊要求 门类
                            ($codeName=='门类'&&$this->match($codeName,$attriName,$orderAttrName))||
                            #多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //窗花编码规则
                case self::CHUANGHUA :
                    $fixedCode = self::CHUANGHUA_FIXED_CODE;
                    if($codeName=='窗花材质'){
                        $test=1;
                    }
                    //获取窗花用料规则
                    if(!isset($chuanghuayl)){
                        $chuanghuayl=$order['chuanghuayl'];
                    }
                    if(in_array($sortID,$handleSortAraay)){
                        //规则属性值与订单属性值匹配
                        if(
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //半成品状态
                            ($codeName=='半成品状态'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //材质
                            ($codeName=='窗花材质'&&$this->match($codeName,$attriName,$order))||
                            //成品外形
                            ($codeName=='成品外形'&&$attriName==$this->orderValueConvert(strtolower($orderColumnName),$orderAttrName))
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        //高度宽度特殊处理
                        elseif ($codeName=='高度'){
                            $codeStr=$chuanghuayl['chuanghua_height'];
                            if($chuanghualx=='大'){
                                $codeStr=$chuanghuayl['chuanghua_height_d'];
                            }
                            elseif ($chuanghualx=='小'){
                                $codeStr=$chuanghuayl['chuanghua_height_x'];
                            }
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        //宽度特殊处理
                        elseif ($codeName=='宽度'){
                            $codeStr=$chuanghuayl['chuanghua_width'];
                            if($chuanghualx=='大'){
                                $codeStr=$chuanghuayl['chuanghua_width_d'];
                            }
                            elseif ($chuanghualx=='小'){
                                $codeStr=$chuanghuayl['chuanghua_width_x'];
                            }
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //单框编码规则
                case self::DANKUANG:
                    $fixedCode = self::DANKUANG_FIXED_CODE;
                    if($codeName=='单框材质'){
                        $test=1;
                    }
                    //获取门框用料规则
                    if(!isset($menkuangyl)){
                        $menkuangyl=$order['menkuangyl'];
                    }
                    if(in_array($sortID,$handleSortAraay)){
                        //规则属性值与订单属性值匹配
                        if(
                            //属性值完全匹配
                            ($attriName == $orderAttrName && $codeName!='门框厚度')||
                            //单框类型
                            ($codeName=='单框类型'&&$this->match($codeName,$attriName,$orderAttrName,$dankuanglx,$menshanlx))||
                            //门框型号
                            ($codeName=='门框型号'&&$this->match($codeName,$attriName,$orderAttrName))||
                            //锁芯
                            ($codeName=='单框材质'&&$this->match($codeName,$attriName,$order,$dankuanglx))||
                            //底框材料 底框类型
                            ($codeName=='开向'&&$this->match($codeName,$attriName,$orderAttrName,$dankuanglx))||
                            //多属性值匹配
                            ($orderMultiName!= "" && $attriName==$orderMultiName)
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        //门框长度特殊处理；边框用规格高度、其余用规格宽度，代码规则为4位用量尺寸，只有3位时第一位用0表示
                        elseif ($codeName=='门框长度'){
                            //上门框，下门框，中门框查询宽度,铰框和锁框查询长度
                            if(in_array($dankuanglx,['铰框','锁框'])){
                                $codeStr=$order['height'];
                            }
                            else{
                                $codeStr=$order['width'];
                            }
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        //门框厚度特殊处理;中框和底框按照实际用料厚度,其余用门框厚度
                        elseif ($codeName=='门框厚度'){
                            $codeStr = $orderAttrName;
                            if($dankuanglx=='中框'){
                                $zhongmk = $menkuangyl['shang_zhong_yongliao']['zhongmk'];
                                $codeStr = substr($zhongmk,0,strpos($zhongmk,'*'));
                            }
                            elseif($dankuanglx=='底框'){
                                $xiamk = $menkuangyl['xia_yongliao']['xiamk'];
                                $codeStr = substr($xiamk,0,strpos($xiamk,'*'));
                            }
                            $codeStr=sprintf("%04d", $codeStr*100);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //门板编码规则
                case self::MENBAN:
                    $fixedCode = self::MENBAN_FIXED_CODE;
                    if($codeName=='门板材质'){
                        $test=1;
                    }
                    if(($codeName=='后门板'&&$menbanlx!='后门板')||($codeName=='前门板'&&$menbanlx!='前门板')){
                        continue;
                    }
                    //获取门板用料规则
                    if(!isset($menbanyl)){
                        $menbanyl=$order['menbanyl'];
                    }
                    if(in_array($sortID,$handleSortAraay)){
                        //规则属性值与订单属性值匹配
                        if(
                            //属性值完全匹配
                            ($attriName == $orderAttrName)||
                            //前后门板
                            ($attriName == $menbanlx)||
                            //单框类型
                            ($codeName=='门板材质'&&$this->match($codeName,$attriName,$orderAttrName))
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        //门板长度,门板宽度特殊处理
                        elseif ($codeName=='门板宽度'||$codeName=='门板长度'){
                            $column=$codeName=='门板宽度'?'menban_width':'menban_length';
                            //母板单扇默认只有母板
                            $codeStr=$this->getMenbanYongliao($menshanlx,$menbanlx,$menbanyl,$column);
                            $codeStr=sprintf("%04d", $codeStr);
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                //材质编码规则
                case self::CAIZHI:
                    $fixedCode = self::CAIZHI_FIXED_CODE;
                    if($codeName=='特殊要求'){
                        $test=1;
                    }
                    //获取门板用料规则
                    if(!isset($menbanyl)){
                        $menbanyl=$order['menbanyl'];
                    }
                    //获取门框用料规则
                    if(!isset($menkuangyl)){
                        $menkuangyl=$order['menkuangyl'];
                    }
                    if (in_array($sortID, $handleSortAraay)) {
                        if (
                            //单框 （不包括下门框，取门框材质）
                            $dankuanglx!=''&&$dankuanglx!='底框'&&$attriName==$order['menkuangcz']||
                            //底框根据低框材料获取
                            $dankuanglx!='底框'&&strpos($order['dkcailiao'],$orderAttrName)!==false||
                            //门板(取门板材质)
                            $menbanlx!=''&&$attriName==$order['menshancz']||
                            //特殊要求
                            ($codeName=='特殊要求'&&$this->match($codeName,$attriName,$order,$dankuanglx,$menshanlx,$menbanlx))
                        ){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        //材质厚度、宽度
                        elseif ($codeName=='材质厚度'||$codeName=='材质宽度'){
                            //门板
                            //门板材质厚度为门板厚，宽为用料宽
                            if($menbanlx!=''){
                              //宽度读取用料
                              if($codeName=='材质宽度'){
                                 $codeStr=$this->getMenbanYongliao($menshanlx,$menbanlx,$menbanyl,'menban_width');
                                 $caizhiyl['width']=$codeStr;
                                 $caizhiyl['height']=$this->getMenbanYongliao($menshanlx,$menbanlx,$menbanyl,'menban_length');
                              }
                              //厚度读取门板厚度
                              else{
                                  $qianbanhReal=$this->orderValueConvert('qmenbhd',$order['qmenbhd']);
                                  $houbanhReal=$this->orderValueConvert('hmenbhd',$order['hmenbhd']);
                                  $caizhiyl['thickness_real']=$menbanlx=='前门板'?$qianbanhReal:$houbanhReal;
                                  $codeStr=$menbanlx=='前门板'?$order['qmenbhd']/100:$order['hmenbhd']/100;
                                  $caizhiyl['thickness']=$codeStr;
                                }
                            }
                            //门框
                            if($dankuanglx!=''){
                                switch ($dankuanglx){
                                    case'铰框':
                                        $yongliao=$menkuangyl['jiao_suo_yongliao'];
                                        $menkuang='jiaomk';
                                        break;
                                    case'锁框':
                                        $yongliao=$menkuangyl['jiao_suo_yongliao'];
                                        $menkuang='suomk';
                                        break;
                                    case'上门框':
                                        $yongliao=$menkuangyl['shang_zhong_yongliao'];
                                        $menkuang='shangmk';
                                        break;
                                    case'中框':
                                        $yongliao=$menkuangyl['shang_zhong_yongliao'];
                                        $menkuang='zhongmk';
                                        break;
                                    case'底框':
                                        $yongliao=$menkuangyl['xia_yongliao'];
                                        $menkuang='xiamk';
                                        $caizhiDensity=$yongliao['density'];
                                        break;
                                }
                                $codeStr=$yongliao[$menkuang];
                                $caizhiyl['height']=$yongliao[$menkuang.'_length'];
                                $caizhiyl['thickness']=substr($codeStr,0,strpos($codeStr,'*'));
                                $caizhiyl['thickness_real']=$yongliao[$menkuang.'_hd'];
                                $caizhiyl['width']=substr($codeStr,strpos($codeStr,'*')+1);
                                $codeStr=explode('*',$codeStr);
                                $codeStr=$codeName=='材质厚度'?$codeStr[0]:$codeStr[1];
                            }
                            //格式化厚度宽度
                            $codeStr=$codeName=='材质厚度'?sprintf("%03d", $codeStr*100):sprintf("%05d", str_replace('.','X',$codeStr));
                            $temp = array("sort_number"=>$sortID,"code"=>$codeStr);
                            $code[$sortID]=$temp;
                            unset($handleSortAraay[array_search($sortID,$handleSortAraay)]);
                        }
                        #默认占位编码
                        elseif($isDefault){
                            $temp = array("sort_number"=>$sortID,"code"=>$val['CODE']);
                            $code[$sortID]=$temp;
                        }
                    }
                    break;

                default:
                    $fixedCode = "";
            }
        }

        $liaohao=$fixedCode;
        ksort($code);//根据键顺序重新排列
        foreach ($code as $kk=>$vv){
            $liaohao .= $vv['code'];
        }
        $yuanjianName=$ruleName;
        $yuanjianLevel='成品级';
        if($ruleName=='门板'||$ruleName=='单框'){
            $yuanjianName=$ruleName=='门板'?$menbanlx:$dankuanglx;
            if($ruleName=='单框'){
                $yuanjianLevel='门框级';
            }
            else{
                $yuanjianLevel="门扇级(".$menshanlx."门)";
            }
        }

        $yongliang=1;//默认用量为1
        if($chuanghualx=='大'){
            $yongliang=$chuanghuayl['chuanghua_yongliang_d'];
        }
        elseif($chuanghualx=='小'){
            $yongliang=$chuanghuayl['chuanghua_yongliang_x'];
        }

        //除开单扇门铰框默认都是2
        if($dankuanglx=='铰框'&&strpos($order['menshan'],'单扇')===false){
            $yongliang=2;
        }

        //获取品名规格
        $name=$ruleName;
        if($ruleName=='单框'){
            $name=$dankuanglx;
        }
        elseif ($ruleName=='门板'){
            $name=$menbanlx;
        }

        //材质品名规格用量
        $caizhiDensity=empty($caizhiDensity)?self::CAIZHI_DENSITY:$caizhiDensity;
        if($ruleName=='材质'){
            $liaohaoGuige=$caizhiyl['thickness'].'*'.$caizhiyl['width'];
            $liaohaoPinming='铁卷';
            //门板材质
            if($menshanlx!=''&&!empty($order['menshancz'])){
                $liaohaoPinming=$this->orderValueConvert('menkuangcz,menshancz',$order['menshancz']);
            }
            //门框材质
            if($dankuanglx!=''&&$dankuanglx!='底框'&&!empty($order['menkuangcz'])){
                $liaohaoPinming=$this->orderValueConvert('menkuangcz,menkuangcz',$order['menkuangcz']);
            }
            if($dankuanglx=='底框'){
                $liaohaoPinming=$this->orderValueConvert('dkcailiao',$order['dkcailiao']);
            }
            //门板QPA计算时用料宽度未+2
            if($ruleName=='材质'&&$menshanlx!=''){
                $caizhiyl['width']+=self::QPA_MENBAN_WIDTH_ADD;
            }
            $yongliang=round($caizhiyl['thickness_real']*$caizhiyl['width']*$caizhiyl['height']*$caizhiDensity/1000000,4);
        }
        else{
            $liaohaoPinming=$this->getPingmingOrGuige($name.'品名规则',$order,$dankuanglx,$menshanlx,$menbanlx,$chuanghualx);
            $liaohaoGuige=$this->getPingmingOrGuige($name.'规格规则',$order,$dankuanglx,$menshanlx,$menbanlx,$chuanghualx);
        }
        return [
            'liaohao'=>$liaohao,
            'liaohao_guige'=>$liaohaoGuige,
            'liaohao_pinming'=>$liaohaoPinming,
            'yongliang'=>$yongliang,
            'liaohao_danwei'=>'',
            'yuanjian_name'=>$yuanjianName,
            'yuanjian_level'=>$yuanjianLevel,
        ];
    }

    /**
     * @param $codeName 待匹配的规则名称
     * @param $ruleAtrName
     * @param $orderAttrName
     * @param string $dankuanglx 单框类型
     * @param string $menshanlx 门扇类型
     * @param $string $menbanlx 门板类型
     * @return bool|int
     */
    public function match($codeName,$ruleAtrName,$orderAttrName='',$dankuanglx='',$menshanlx='',$menbanlx=''){
        switch ($codeName){
            //半成品类型
            case '半成品类型':
                if(strpos($orderAttrName,'单扇')!==false){
                    $str='单扇门';
                }
                elseif (strpos($orderAttrName,'对开')!==false){
                    $str='对开'.$menshanlx.'扇';
                }
                elseif (strpos($orderAttrName,'子母')!==false){
                    $str='子母'.$menshanlx.'扇';
                }
                if (strpos($orderAttrName,'子母四开')!==false){
                    $str='子母四开边门';
                    if($menshanlx=='母'||$menshanlx=='子'){
                        $str='子母四开'.$menshanlx.'扇';
                    }
                }
                elseif (strpos($orderAttrName,'均等四开')!==false){
                    $str='均等四开边门';
                    if($menshanlx=='母'||$menshanlx=='子'){
                        $str='均等四开'.$menshanlx.'扇';
                    }
                }

                return $ruleAtrName==$str;
            //门扇类型
            case '门扇类型':
                $str=findNum($orderAttrName).'门';
                return $ruleAtrName==$str;
            //主锁（门锁体类型及品牌）
            case '主锁':
                return strpos($orderAttrName,$ruleAtrName)!==false?1:0;
            //锁芯
            case '锁芯':
                return strpos($orderAttrName,$ruleAtrName)!==false?1:0;
            //底框类型
            case '底框类型':
                return strpos($orderAttrName,$ruleAtrName)!==false?1:0;
            //底框厚度
            case '底框厚度':
                $str=mb_substr($orderAttrName,-3);
                return $str==$ruleAtrName?1:0;
            //特殊要求 门类
            case '门类':
                if(in_array($orderAttrName['menshancz'],['镀锌-覆膜','冷轧-覆膜'])){
                    return $ruleAtrName=='覆塑门';
                }
                elseif($orderAttrName['menshancz']=='镀锌'){
                    if(strpos($orderAttrName['teshuyq'],'万科')!==false){
                        return $ruleAtrName=='万科镀锌门';
                    }
                    return $ruleAtrName=='镀锌门';
                }
                elseif($orderAttrName['menshancz']=='锌合金'){
                    return $ruleAtrName=='锌合金门';
                }
                return strpos($orderAttrName['teshuyq'],$ruleAtrName)!==false?1:0;
            //半成品状态
            case '半成品状态':
                $str='自产';
                $waigou='铁艺窗花';//只有铁艺窗花外购其它均自产
                if(strpos($orderAttrName,$waigou)!==false){
                    $str='外购';
                }
                return $ruleAtrName==$str;
            //窗花 材质:常规窗花，铁艺窗花，材质为常规铁。不锈钢窗花材质为不锈钢。不锈铁窗花材质为不锈铁。中式窗花材质为中式。其余为其它。
            case '窗花材质':
                if(strpos($orderAttrName['chuanghua'],'常规窗花')!==false||
                   strpos($orderAttrName['chuanghua'],'常规窗花')!==false){
                    $str='常规铁';
                }
                elseif (strpos($orderAttrName['chuanghua'],'不锈钢')!==false){
                    $str='不锈钢';
                }
                elseif (strpos($orderAttrName['chuanghua'],'不锈铁')!==false){
                    $str='不锈铁';
                }
                elseif (strpos($orderAttrName['chuanghua'],'中式窗花')!==false){
                    $str='中式';
                }
                return $ruleAtrName==$str;

            //单框
            //单框类型
            case '单框类型':
                $str=$dankuanglx;
                //边框分为铰框锁框
                if(in_array($dankuanglx,['铰框','锁框'])){
                    $str='边框';
                }
                return $ruleAtrName==$str;
            //门框型号
            case '门框型号':
                $str=findNum($orderAttrName).'门';
                return $ruleAtrName==$str;
            //材质 (单框)   普铁，代表门框门扇铁卷材质和普铁底框材质，都用P表示。底框读取底框材料，其余读取订单门框材质字段
            case '单框材质':
                if($dankuanglx=='底框'){
                    return strpos($orderAttrName['dkcailiao'],$ruleAtrName)!==false?1:0;
                }
                return $ruleAtrName==$orderAttrName['menkuangcz'];
            //开向 (单框) 开向为内左内右时为内开，开向为外左外右时为外开，边框要分铰方锁方，上门框，中框、底框只分内外
            case '开向':
                $str='';
                $kaixiang='';
                if(in_array($orderAttrName,['内右','内左'])){
                    $kaixiang='内开';
                }
                elseif (in_array($orderAttrName,['外左','外右'])){
                    $kaixiang='外开';
                }
                $str=$kaixiang;
                if(in_array($dankuanglx,['铰框','锁框'])){
                    $str=$kaixiang.$dankuanglx;
                }
                return $ruleAtrName==$str;

            //门板材质
            case '门板材质':
                return strpos($orderAttrName,$ruleAtrName)!==false?1:0;

            //材质规则
            //特殊要求
            case '特殊要求':
                $str='';
                //门框材质为镀锌卷/锌铁合金时用51D (单框不包含底框)
                if($dankuanglx!=''&&$dankuanglx!='底框'){
                    if(strpos($orderAttrName['menkuangcz'],'镀锌卷')!==false||strpos($orderAttrName['menkuangcz'],'锌铁合金')!==false){
                        $str='51D';
                    }
                }
                //底框材质为201不锈钢时用201,底框材质为304不锈钢时用304，底框
                elseif ($dankuanglx=='底框'){
                    if(strpos($orderAttrName['dkcailiao'],'不锈钢')){
                        $str='201';
                    }
                    elseif(strpos($orderAttrName['dkcailiao'],'不锈钢')){
                        $str='304';
                    }
                }
                //门板
                elseif($menbanlx!=''){
                    //门板材质为锌铁合金时用56D
                    if(strpos($orderAttrName['menshancz'],'锌铁合金')!==false){
                        $str='56D';
                    }
                    //门板材质为镀锌卷时用53D
                    elseif(strpos($orderAttrName['menshancz'],'镀锌卷')!==false){
                        $str='53D';
                    }
                }
                return $ruleAtrName==$str;
        }

    }

    /**
     * 根据订单获取门板 用料规则
     * @param null $data
     * @return array
     */
    public function getMenbanYongLiaoRule($data=null){
        $data = array_change_key_case($data);
        $zhizaobm = $data['zhizaobm'];
        $dangci = $data['dang_ci'];
        $menkuang = $data['menkuang'];
        $menshan = $data['menshan'];
        $dikuangcl = $data['dkcailiao'];
        $guige = $data['guige'];
        $kaixiang = $data['kaixiang'];
        $guigecArr = explode('*',$guige);
        if (empty($guigecArr[1])){
            $guigecArr = explode('×',$guige);
        }
        $guigeLength = $this->gaoduChange($menkuang,$guigecArr[0]);
        $guigeWide = $guigecArr[1];
        //获取门板长度用料规则
        $sql_menban_length = "select MUQIANMB,MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB from 
                                BOM_MENBAN_LENGTH_RULE where ZHIZAOBM LIKE '%/$zhizaobm%' and DANGCI like '%/$dangci%' and MENKUANG like '%/$menkuang%' and
                                MENSHAN like '%/$menshan%' and DIKUANGCL <> '$dikuangcl' and GUIGECD='$guigeLength'";
        //获取门板宽度用料规则
        $sql_menban_wide = "select MUQIANMB,MUHOUMB,ZIQIANMB,ZIHOUMB,MUZIQMB,MUZIHMB,ZIZIQMB,ZIZIHMB,CAIZHI from 
                                BOM_MENBAN_WIDE_RULE where ZHIZAOBM LIKE '%/$zhizaobm%' and DANGCI like '%/$dangci%' and MENKUANG like '%/$menkuang%' and
                                MENSHAN like '%/$menshan%' and GUIGECD='$guigeWide'";
        $result1 = Db::query($sql_menban_length);
        $result2 = Db::query($sql_menban_wide);
        //返回数据
        $data = array(
            'menban_length'=>empty($result1)?$result1:array_change_key_case($result1[0]),
            'menban_width'=>empty($result2)?$result2:array_change_key_case($result2[0])
        );
        return $data;
    }



    /**
     * 获取某一门板用料高宽
     * @param $menshanlx 门扇类型 母、子、母子、子子
     * @param $menbanlx 门板类型 前门板、后门板
     * @param $menbanylRule 门板用料规则
     * @param $column 高宽：menban_width、menban_height
     */
    public function getMenbanYongliao($menshanlx,$menbanlx,$menbanylRule,$column){
        if($menshanlx=='母'){
            return $menbanlx=='前门板'?$menbanylRule[$column]['muqianmb']:$menbanylRule[$column]['muhoumb'];
        }
        //子板
        if($menshanlx=='子'){
            return $menbanlx=='前门板'?$menbanylRule[$column]['ziqianmb']:$menbanylRule[$column]['zihoumb'];
        }
        //母子板
        if($menshanlx=='母子'){
            return $menbanlx=='前门板'?$menbanylRule[$column]['muziqmb']:$menbanylRule[$column]['muzihmb'];
        }
        //子子板
        if($menshanlx=='子子'){
            return $menbanlx=='前门板'?$menbanylRule[$column]['ziziqmb']:$menbanylRule[$column]['zizihmb'];
        }
    }

    /**
     * 根据副窗订单-门扇高度 转换成规格高度
     * @param $menkuang
     * @param $length
     * @return int
     */
    /**
     * 根据副窗订单-门扇高度 转换成规格高度
     * @param $menkuang
     * @param $length
     * @return int
     */
    public function gaoduChange($menkuang,$length){
        //判断90门
        if (strpos($menkuang,'90') === false){
            $yuewu_key = "M9:!90";
        }else{
            $yuewu_key = "M9:90";
        }
        $sql = "select VAL from BOM_INTERVAL_RULE where YEWU_KEY='$yuewu_key' and $length BETWEEN START_VAL and END_VAL";
        $result = Db::query($sql);
        return empty($result[0]['VAL'])?$length:$result[0]['VAL'];
    }

    /**
     * 根据订单获取门框 用料规则
     * @param null $data
     * @return array
     */
    public function getMenkuangYongLiaoRule($data=null){
        $data = array_change_key_case($data);
        $zhizaobm = $data['zhizaobm'];
        $menkuanghd = $data['mkhoudu'];
        $dangci = $data['dang_ci'];
        $menkuang = $data['menkuang'];
        $menshan = $data['menshan'];
        if(strpos($menshan,'四开门')!==false){
            $menshan = '四开门';
        }
        $dikuangcl = $data['dkcailiao'];
        $guige = $data['guige'];
        $kaixiang = $data['kaixiang'];
        if(strpos($kaixiang,'内') === false){
            $kaixiang = '外开';
        }else{
            $kaixiang = '内开';
        }
        $guigecArr = explode('*',$guige);
        if (empty($guigecArr[1])){
            $guigecArr = explode('×',$guige);
        }
        $guigeLength = $guigecArr[0];
        $guigeWide = $guigecArr[1];
        //获取门框长度用料规则
        $sql_menkuang_jiao_suo = "select (JIAOMK_LENGTH+$guigeLength)JIAOMK_LENGTH ,JIAOMK,JIAOMK_HD,JIAOMK_DENSITY,(SUOMK_LENGTH+$guigeLength)SUOMK_LENGTH,SUOMK,SUOMK_HD,SUOMK_DENSITY from BOM_MENKUANG_JIAOSUO_RULE where 
                                 ZHIZAOBM LIKE '%/$zhizaobm%' and DANGCI like '%/$dangci%' and MENKUANG like '%/$menkuang%' and KAIXIANG like '%/$kaixiang%' and MENKUANGHD ='$menkuanghd'";
        //获取门框宽度用料规则
        $sql_menkuang_shang_zhong = "select (SHANGMK_LENGTH+$guigeWide)SHANGMK_LENGTH,SHANGMK,SHANGMK_HD,SHANGMK_DENSITY,(ZHONGMK_LENGTH+$guigeWide)ZHONGMK_LENGTH,ZHONGMK,ZHONGMK_HD,ZHONGMK_DENSITY from 
                                BOM_MENKUANG_SHANGZHONG_RULE where ZHIZAOBM LIKE '%/$zhizaobm%' and DANGCI like '%/$dangci%' and MENKUANG like '%/$menkuang%'
                                and KAIXIANG like '%/$kaixiang%' and MENSHAN like '%/$menshan%' and MENKUANGHD ='$menkuanghd'";
        $sql_menkuang_xia = "select (XIAMK_LENGTH+$guigeWide)XIAMK_LENGTH,XIAMK,XIAMK_HD,XIAMK_DENSITY from BOM_MENKUANG_XIA_RULE 
                                where  ZHIZAOBM LIKE '%/$zhizaobm%' and DANGCI like '%/$dangci%' and MENKUANG like '%/$menkuang%'
                                and KAIXIANG like '%/$kaixiang%' and MENSHAN like '%/$menshan%' and MENKUANGHD ='$menkuanghd' and DIKUANGCL like '%/$dikuangcl%'";

        $result3 = Db::query($sql_menkuang_jiao_suo);
        $result4 = Db::query($sql_menkuang_shang_zhong);
        $result5 = Db::query($sql_menkuang_xia);
        //返回数据
        $data = array(
            'jiao_suo_yongliao'=>empty($result3)?$result3:array_change_key_case($result3[0]),
            'shang_zhong_yongliao'=>empty($result4)?$result4:array_change_key_case($result4[0]),
            'xia_yongliao'=>empty($result5)?$result5:array_change_key_case($result5[0])
        );
        return $data;
    }


    /**
     * 获取窗花用料规则
     * @param null $data
     * @return array|null
     */
    public function getChuanghuaYongliaoRule($data =null){
        $data = array_change_key_case($data);
        $zhizaobm = $data['zhizaobm'];
        $dangci = $data['dang_ci'];
        $menkuang = $data['menkuang'];
        $menshan = $data['menshan'];
        $guige = $data['guige'];
        $kaixiang = $data['kaixiang'];
        if(strpos($kaixiang,'内') === false){
            $kaixiang = '外开';
        }else{
            $kaixiang = '内开';
        }
        $chuanghua = $data['chuanghua'];
        $chuanghua = mb_substr($chuanghua,0,3,'utf-8');
        $guigecArr = explode('*',$guige);
        if (empty($guigecArr[1])){
            $guigecArr = explode('×',$guige);
        }
        $guigeLength = $guigecArr[0];
        $guigeWide = $guigecArr[1];
        //窗花非四开门用料规则
        if (strpos($menshan,'四开门') === false){
            $sql_chuanghua = "select (BOM_CHUANGHUA_RULE.MKHEIGHT_RULE || '+$guigeLength')as gaodu,
                                (BOM_CHUANGHUA_RULE.MKWIDE_RULE ||'+$guigeWide') as wide from BOM_CHUANGHUA_RULE 
                                where  ZHIZAOBM LIKE '%/$zhizaobm%' and DANGCI like '%/$dangci%' and MENKUANG like '%/$menkuang%' and 
                                MENSHAN like '%/$menshan%' and CHUANGHUA like '%/$chuanghua%' and KAIXIANG like '%/$kaixiang%'
                                and $guigeLength BETWEEN START_HEIGHT and END_HEIGHT ";
            $result = Db::query($sql_chuanghua);
            //返回数据
            $data = array(
                'chuanghua_height'=>empty($result)?0:eval("return ".$result[0]['GAODU'].";"),
                'chuanghua_width'=>empty($result)?0:eval("return ".$result[0]['WIDE'].";")
            );
        }else{
            //窗花非四开门用料规则
            $sql_chuanghua = "select * from BOM_CHUANGHUA_SIKAI_RULE where  ZHIZAOBM LIKE '%/$zhizaobm%' and MENKUANG like '%/$menkuang%' and MENSHAN like '%/$menshan%'
                              and CHUANGHUA  like '%/$chuanghua%' and XINGHAO ='$guige'";
            $result = Db::query($sql_chuanghua);
            $guigecX = explode('*',$result[0]['CHUANGHUAX']);
            if (empty($guigecX[1])){
                $guigecX = explode('×',$result[0]['CHUANGHUAX']);
            }
            $guigecD = explode('*',$result[0]['CHUANGHUAD']);
            if (empty($guigecD[1])){
                $guigecD = explode('×',$result[0]['CHUANGHUAD']);
            }
            //返回数据
            $data = array(
                'chuanghua_height_x' =>empty($guigecX[0])?0:$guigecX[0],
                'chuanghua_width_x' =>empty($guigecX[1])?0:$guigecX[1],
                'chuanghua_yongliang_x' =>empty($result[0]['YONGLIANGX'])?0:$result[0]['YONGLIANGX'],
                'chuanghua_height_d' =>empty($guigecD[0])?0:$guigecD[0],
                'chuanghua_width_d' =>empty($guigecD[1])?0:$guigecD[1],
                'chuanghua_yongliang_d' =>empty($result[0]['YONGLIANGD'])?0:$result[0]['YONGLIANGD'],
            );
        }
        return $data;
    }

    /**
     *获取元件列表
     * @param $order  订单信息
     * @return json
     */
    public function getYuanJianList($order){
        $orderID = $order['oeb01'];
        $doorType = DB::query("select order_db from oea_file where oea01='$orderID'",true);
        $doorType = empty($doorType) ? 'M9' : $doorType[0]['order_db'];
        $menKuang = $order['menkuang'];
        $sql = "select * from bom_yuanjian a where a.menkuang = '$menKuang' and a.door_type = '$doorType' order by id";
        $yuanjian_list = Db::query($sql,true);
        $data = array();
        foreach ($yuanjian_list as $key=>$val){
            if($val['yuanjian_name']=='70合页铰链'){
                $test=1;
            }
            $id = $val['id'];
            $columns = $val['order_column_name'];//该元件关联的订单属性

            //关联订单列不为空则动态拼接sql
            $where = "1=1";
            if(!empty($columns)){
                //如果字段中包含逗号，则有多个属性
                $cols = explode(',',$columns);
                //根据关联的订单属性动态拼接sql
                foreach ($cols as $k=>$v){
                    $columnName = $v;
                    $orderValue = $order[strtolower($v)];
                    $height = $order['height'];
                    $width = $order['width'];
                    //高宽
                    if($columnName=='start_height'){
                        $where.= " and  $height>=start_height";
                    }
                    elseif ($columnName=='end_height'){
                        $where.= " and  $height<=end_height";
                    }
                    elseif($columnName=='start_width'){
                        $where.= " and   $width>=start_width";
                    }
                    elseif ($columnName=='end_width'){
                        $where.= " and  $width<=end_width";
                    }
                    else{
                        $where.=" and $columnName like '%,$orderValue,%'";
                    }
                }
            }

            $where = rtrim($where,'and');
            $detail = "select  yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei  
                       from bom_yuanjian_rule t1,bom_yuanjian t2  
                       where yuanjian_id=$id and t1.yuanjian_id=t2.id and ($where or is_require=1)  
                       group by yuanjian_name,yuanjian_level,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei ";
            $liaohaoInfo = DB::query($detail,true);
           foreach ($liaohaoInfo as $k=>$v){

                //胶条用量
                if(strpos(excel_trim($v['yuanjian_name']),'胶条')!==false&&strpos(excel_trim($v['yongliang']),'门扇高度')!==false){
                    $v['yongliang'] = ($order['menshan_gaodu']-findNum($v['yongliang']))/1000;
                }
                if(strpos(excel_trim($v['yuanjian_name']),'胶条')!==false&&strpos(excel_trim($v['yongliang']),'门框宽度')!==false){
                    $v['yongliang'] = ($order['width']-findNum($v['yongliang']))/1000;
                }
                //胶纱网用量
               if(strpos(excel_trim($v['yuanjian_name']),'胶纱网')!==false){
                   $yongliang = implode('*',$v['yongliang']);
                   //$v['yongliang'] = $
                   $chuanghuaSquare = 0;//窗花面积
                   if(!empty($order['chuanghuayl']['chuanghua_height'])){
                       $chuanghuaSquare = $order['chuanghuayl']['chuanghua_height']*$order['chuanghuayl']['chuanghua_width'];
                   }
                   elseif (!empty($order['chuanghuayl']['chuanghua_height_x'])){
                       $chuanghuaSquare = $order['chuanghuayl']['chuanghua_height_x']*$order['chuanghuayl']['chuanghua_width_x']*$order['chuanghuayl']['chuanghua_yongliang_x']
                       +$order['chuanghuayl']['chuanghua_height_d']*$order['chuanghuayl']['chuanghua_width_d']*$order['chuanghuayl']['chuanghua_yongliang_d'];
                   }
                   $v['yongliang'] = $chuanghuaSquare/($yongliang[0]*$yongliang[1]);
               }
               array_push($data,$v);
            }

        }
        return $data;
    }

    /**
     * 根据订单信息获取品名规格
     * @param $ruleName 规则名称
     * @param $order 订单信息 包含用料信息
     * @param $dankuanglx 单框类型：上门框、低框、边框、中框，
     * @param $menshanlx 门扇类型 母，子，母子，子子 默认母
     * @param $menbanlx 门板类型 前门板、后门板
     * $param $chuanghualx 窗花类型 (只有四开窗花时才有)大、小
     */
    public function getPingmingOrGuige($ruleName,$orderInfo,$dankuanglx,$menshanlx,$menbanlx,$chuanghualx){
        $retstr = '';
        $guigelist = DB::query("select order_column_name from bom_code_rule where rule_name='$ruleName'",true);
        $guigearr = explode(",",$guigelist[0]["order_column_name"]);
        switch($ruleName)
        {
            case '成品品名规则':
            {
                foreach($guigearr as $k=>$guigename)
                {
                    if($guigename == 'KAIXIANG')
                    {
                        if(strpos($orderInfo[strtolower($guigename)],"内") !== false)
                        {
                            $retstr .= "内开";
                        }
                        if(strpos($orderInfo[strtolower($guigename)],"外") !== false)
                        {
                            $retstr .= "外开";
                        }
                    }
                    else
                        $retstr .= $orderInfo[strtolower($guigename)];
                }
                break;
            }
            case '成品规格规则':
            {
                $BzppBzfs = $this->orderValueConvert("baozhpack,baozhuangfs",$orderInfo[strtolower($guigearr[7])].$orderInfo[strtolower($guigearr[8])]);
                $retstr = $orderInfo[strtolower($guigearr[0])].             //档次
                    ($orderInfo[strtolower($guigearr[1])]/100)."*".         //前板厚
                    ($orderInfo[strtolower($guigearr[2])]/100)."*".         //后板厚
                    str_replace("×","*",$orderInfo[strtolower($guigearr[3])]).//规格
                    "(".$orderInfo[strtolower($guigearr[4])].")".           //框厚
                    $orderInfo[strtolower($guigearr[5])].                   //门框
                    $orderInfo[strtolower($guigearr[6])].                   //底框材料
                    $BzppBzfs.                                              //包装品牌&包装方式
                    $orderInfo[strtolower($guigearr[9])];                   //猫眼
                break;
            }
            case '半品门框品名规则':
            {
                $kaixiang = "";
                if(strpos($orderInfo[strtolower($guigearr[2])],"内") !== false)
                {
                    $kaixiang = "内开";
                }
                if(strpos($orderInfo[strtolower($guigearr[2])],"外") !== false)
                {
                    $kaixiang = "外开";
                }
                $menshan = $orderInfo[strtolower($guigearr[0])]=='单扇门'?'门框单扇':
                    ($orderInfo[strtolower($guigearr[0])]=='对开门' || $orderInfo[strtolower($guigearr[0])]=='子母门'?'门框双扇':
                        ($orderInfo[strtolower($guigearr[0])]=='子母四开门' || $orderInfo[strtolower($guigearr[0])]=='均等四开门'?'门框四扇':"未知门扇"));
                $retstr = $menshan.//门扇(门扇需转换，单扇门=门框单扇，对开门、子母门=门框双扇，子母四开门、均等四开门=门框四扇)
                    $orderInfo[strtolower($guigearr[1])].   //表面方式
                    $kaixiang.                              //开向
                    $orderInfo[strtolower($guigearr[3])].   //窗花
                    $orderInfo[strtolower($guigearr[4])];   //特殊要求
                break;
            }
            case '半品门框规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[1])]);
                $retstr = $orderInfo[strtolower($guigearr[0])]. //档次
                    $guige_replace."*".                         //规格
                    $orderInfo[strtolower($guigearr[2])].       //框厚
                    $orderInfo[strtolower($guigearr[3])].       //铰链
                    $orderInfo[strtolower($guigearr[4])].       //门框
                    $orderInfo[strtolower($guigearr[5])];       //底框
                break;
            }
            case '半品门扇品名规则':
            {
                //门扇(需转换，单扇门=门扇，子母门=子母母扇、子母子扇，对开门=对开母扇、对开子扇，子母四开门=子母四开边门*2、子母四开母扇、子母四开子扇，均等四开门=均等四开边门*2、均等四开母扇、均等四开子扇)
                if($orderInfo[strtolower($guigearr[0])]=='单扇门')
                {
                    if($menshanlx == "母")
                        $retstr = '门扇'.$orderInfo[strtolower($guigearr[1])].$orderInfo[strtolower($guigearr[2])];
                }
                else if($orderInfo[strtolower($guigearr[0])]=='子母门')
                {
                    if($menshanlx == "母")
                        $retstr = '子母母扇'.$orderInfo[strtolower($guigearr[1])].$orderInfo[strtolower($guigearr[2])];
                    else if($menshanlx == "子")
                        $retstr = '子母子扇'.$orderInfo[strtolower($guigearr[1])];
                }
                else if($orderInfo[strtolower($guigearr[0])]=='对开门')
                {
                    if($menshanlx == "母")
                        $retstr = '对开母扇'.$orderInfo[strtolower($guigearr[1])].$orderInfo[strtolower($guigearr[2])];
                    else if($menshanlx == "子")
                        $retstr = '对开子扇'.$orderInfo[strtolower($guigearr[1])];
                }
                else if($orderInfo[strtolower($guigearr[0])]=='子母四开门')
                {
                    if($menshanlx == "母子" || $menshanlx == "子子")
                        $retstr = '子母四开边门'.$orderInfo[strtolower($guigearr[1])];
                    else if($menshanlx == '母')
                        $retstr = '子母四开母扇'.$orderInfo[strtolower($guigearr[1])].$orderInfo[strtolower($guigearr[2])];
                    else if($menshanlx == "子")
                        $retstr = '子母四开子扇'.$orderInfo[strtolower($guigearr[1])];
                }
                else if($orderInfo[strtolower($guigearr[0])]=='均等四开门')
                {
                    if($menshanlx == "母子" || $menshanlx == "子子")
                        $retstr = '均等四开边门'.$orderInfo[strtolower($guigearr[1])];
                    else if($menshanlx == '母')
                        $retstr = '均等四开母扇'.$orderInfo[strtolower($guigearr[1])].$orderInfo[strtolower($guigearr[2])];
                    else if($menshanlx == "子")
                        $retstr = '均等四开子扇'.$orderInfo[strtolower($guigearr[1])];
                }
                else
                {
                    $retstr = "未知门扇";
                }
                break;
            }
            case '半品门扇规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[1])]);
                $guige_replace_arr = explode("*",$guige_replace);
                $guige_change_high = $this->gaoduChange($orderInfo[strtolower($guigearr[5])],$guige_replace_arr[0]);
                $retstr = $orderInfo[strtolower($guigearr[0])].       //档次
                    $guige_change_high."*".$guige_replace_arr[1]."*". //规格
                    ($orderInfo[strtolower($guigearr[2])]/100)."*".   //前板厚
                    ($orderInfo[strtolower($guigearr[3])]/100).       //后板厚
                    $orderInfo[strtolower($guigearr[4])].       //铰链
                    $orderInfo[strtolower($guigearr[5])].       //门框
                    $orderInfo[strtolower($guigearr[6])];       //花色
                break;
            }
            case '上门框品名规则':
            {
                $kaixiang = "";
                if(strpos($orderInfo[strtolower($guigearr[1])],"内") !== false)
                {
                    $kaixiang = "内开";
                }
                if(strpos($orderInfo[strtolower($guigearr[1])],"外") !== false)
                {
                    $kaixiang = "外开";
                }
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]). //门厚度
                    "上门框".                                                //上门框
                    $kaixiang;                                              //开向
                break;
            }
            case '上门框规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*",$guige_replace);
                $retstr = $guige_width[1]."*".                  //规格宽度
                    $orderInfo[strtolower($guigearr[1])].       //门框厚度
                    $orderInfo[strtolower($guigearr[2])];       //门框
                break;
            }
            case '铰框品名规则':
            {
                $kaixiang = "";
                if(strpos($orderInfo[strtolower($guigearr[1])],"内") !== false)
                {
                    $kaixiang = "内开";
                }
                if(strpos($orderInfo[strtolower($guigearr[1])],"外") !== false)
                {
                    $kaixiang = "外开";
                }
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]). //门厚度
                    "边框".                                              //边框
                    $kaixiang.                                          //开向
                    "铰框";                                              //铰框
                break;
            }
            case '铰框规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*",$guige_replace);
                $retstr = $guige_width[0]."*".                      //规格高度
                    $orderInfo[strtolower($guigearr[1])].           //门框厚度
                    $orderInfo[strtolower($guigearr[2])];           //门框
                break;
            }
            case '锁框品名规则':
            {
                $kaixiang = "";
                if(strpos($orderInfo[strtolower($guigearr[1])],"内") !== false)
                {
                    $kaixiang = "内开";
                }
                if(strpos($orderInfo[strtolower($guigearr[1])],"外") !== false)
                {
                    $kaixiang = "外开";
                }
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).    //门厚度
                    "边框".                                                  //边框
                    $kaixiang.                                              //开向
                    "锁框";                                                  //锁框
                break;
            }
            case '锁框规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*",$guige_replace);
                $retstr = $guige_width[0]."*".                  //规格高度
                    $orderInfo[strtolower($guigearr[1])].       //门框厚度
                    $orderInfo[strtolower($guigearr[2])];       //门框
                break;
            }
            case '底框品名规则':
            {
                $kaixiang = "";
                if(strpos($orderInfo[strtolower($guigearr[1])],"内") !== false)
                {
                    $kaixiang = "内开";
                }
                if(strpos($orderInfo[strtolower($guigearr[1])],"外") !== false)
                {
                    $kaixiang = "外开";
                }
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).  //门厚度
                    "底框".                                               //底框
                    $kaixiang.                                              //开向
                    str_replace("底框","",$orderInfo[strtolower($guigearr[2])]);//底框材料
                break;
            }
            case '底框规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*",$guige_replace);
                $dkhd_arr = explode('*',$orderInfo[strtolower($guigearr[1])]["xia_yongliao"]["xiamk"]);
                $retstr = $guige_width[1]."*".              //规格宽度
                    $dkhd_arr[0].                           //底框厚度（底框厚度见用料规则)
                    $orderInfo[strtolower($guigearr[2])];   //门框
                break;
            }
            case '中框品名规则':
            {
                $kaixiang = "";
                if(strpos($orderInfo[strtolower($guigearr[2])],"内") !== false)
                {
                    $kaixiang = "内开";
                }
                if(strpos($orderInfo[strtolower($guigearr[2])],"外") !== false)
                {
                    $kaixiang = "外开";
                }
                //判断窗花有无
                $retstr = $this->findNum($orderInfo[strtolower($guigearr[0])]).     //门厚度
                    (empty($orderInfo[strtolower($guigearr[1])])?"无中框":"中框").   //中框(无窗花就没有中框)
                    $kaixiang;                                                      //开向
                break;
            }
            case '中框规格规则':
            {
                $guige_replace = str_replace("×","*",$orderInfo[strtolower($guigearr[0])]);
                $guige_width = explode("*",$guige_replace);
                $dkhd = $this->findNum($orderInfo[strtolower($guigearr[1])]["shang_zhong_yongliao"]["zhongmk"]);
                $retstr = $guige_width[1]."*".  //规格宽度
                    $dkhd.                      //中框厚度（中框厚度见用料规则)
                    $orderInfo[strtolower($guigearr[2])]; //门框
                break;
            }
            case '前门板品名规则':
            {
                $retstr = "前门板";  //前门板（原件料号尾字母为Q默认前门板）
                break;
            }
            case '前门板规格规则':
            {
                //根据门扇类型获取不同的用料规格
                $menbanLength = $this->getMenbanYongliao($menshanlx,"前门板",$orderInfo[strtolower($guigearr[1])],"menban_length");
                $menbanWidth = $this->getMenbanYongliao($menshanlx,"前门板",$orderInfo[strtolower($guigearr[1])],"menban_width");
                $retstr = ($orderInfo[strtolower($guigearr[0])]/100)."*".$menbanWidth."*".$menbanLength;  //（前门板板厚&用料宽度&用料高度）
                break;
            }
            case '后门板品名规则':
            {
                $retstr = "后门板";  //后门板（原件料号尾字母为H默认后门板）
                break;
            }
            case '后门板规格规则':
            {
                //根据门扇类型获取不同的用料规格
                $menbanLength = $this->getMenbanYongliao($menshanlx,"后门板",$orderInfo[strtolower($guigearr[1])],"menban_length");
                $menbanWidth = $this->getMenbanYongliao($menshanlx,"后门板",$orderInfo[strtolower($guigearr[1])],"menban_width");
                $retstr = ($orderInfo[strtolower($guigearr[0])]/100)."*".$menbanWidth."*".$menbanLength;  //（后门板板厚&用料宽度&用料高度）
                break;
            }
            case '窗花品名规则':
            {
                $retstr = $this->orderValueConvert(strtolower($guigearr[0]),$orderInfo[strtolower($guigearr[0])]);//窗花
                break;
            }
            case '窗花规格规则':
            {
                if($chuanghualx == '大')
                    $retstr = $orderInfo[strtolower($guigearr[0])]["chuanghua_height_d"]."*".   //大窗花用料 高
                        $orderInfo[strtolower($guigearr[0])]["chuanghua_width_d"];              //大窗花用料 宽
                else if($chuanghualx == '小')
                    $retstr = $orderInfo[strtolower($guigearr[0])]["chuanghua_height_x"]."*".   //小窗花用料 高
                        $orderInfo[strtolower($guigearr[0])]["chuanghua_width_x"];              //小窗花用料 宽
                else
                    $retstr = $orderInfo[strtolower($guigearr[0])]["chuanghua_height"]."*".     //单窗花用料 高
                        $orderInfo[strtolower($guigearr[0])]["chuanghua_width"];                //单窗花用料 宽
                break;
            }
        }
        return $retstr;
    }
    /**
     * @param $str 含数字的字符串
     * @return 第一个数字值
     */
    function findNum($str=''){
        $retstr = '';
        $str=trim($str);
        if(empty($str)){return '';}
        $temp=array('1','2','3','4','5','6','7','8','9','0','.','-');
        for($i=0;$i<strlen($str);$i++){
            if(in_array($str[$i],$temp)){
                $retstr .= $str[$i];
            }
            else{
                if($retstr !='') break;
            }
        }
        return $retstr;
    }

    /**
     * @param $orderColumn 订单列名
     * @param $value 订单值
     * @return 转换过后的值
     */
    public function orderValueConvert($orderColumn,$value)
    {
        $convertValues = DB::query("select converted_val from bom_orderval_convert where order_column_name='$orderColumn' and order_val='$value'",true);
        return $convertValues[0]["converted_val"];
    }



}
