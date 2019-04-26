<?php

namespace app\api\service;

use app\admin\service\BaseCodeService;
use app\admin\service\MuzhiCodeService;
use app\admin\service\NaihcCodeService;
use function PHPSTORM_META\type;
use think\Db;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/13
 * Time: 17:31
 */
class LiaohaoService
{

    /**
     * @param $orderInfo
     * @return array
     */
    public function createLiaohaoByOrder($orderInfo)
    {
        $guigecArr = explode('*', $orderInfo['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $orderInfo['guige']);
        }
        $orderInfo['height'] = $guigecArr[0];//高
        $orderInfo['width'] = $guigecArr[1];//宽
        $zhizaobm = M()->query("select gys_name from sell_gys where gys_code='" . $orderInfo['operate_plan'] . "'", true);
        $orderInfo['zhizaobm'] = $zhizaobm[0]['gys_name'];
        $baseCodeService = new BaseCodeService();
        $orderInfo['menshan_gaodu'] = $baseCodeService->gaoduChange($orderInfo['menkuang'], $orderInfo['height'], $orderInfo['chuanghua'], $orderInfo['zhizaobm']);//门扇高度
        $orderInfo['menkuangyl'] = $baseCodeService->getMenkuangYongLiaoRule($orderInfo);//获取单框用料信息
        $orderInfo['menbanyl'] = $baseCodeService->getMenbanYongLiaoRule($orderInfo);//获取门板用料信息
        $orderInfo['chuanghuayl'] = $baseCodeService->getChuanghuaYongliaoRule($orderInfo);//获取窗花用料规则
        if ($orderInfo['menkuangyq'] == '无') {
            $orderInfo['menkuangyq'] = '';
        }
        # M7 木质门料号的生成
        if ($orderInfo['order_db'] == 'M7') {
            return $this->getMuzhiLiaohaoInfo($orderInfo);
        }
        # M4 耐火窗bom的生成
        if ($orderInfo['order_db'] == 'M4') {
            return $this->getNaihcLiaohaoInfo($orderInfo);
        }

        $menshan = $orderInfo['menshan']; //门扇
        $isDanshan = 1; //是否是单扇门
        $isDuikai = 0; //是否是对开门
        $isSikai = 0; //是否是四开门
        $isZimu = 0; //是否是子母门
        $hasFuchuang = 1;//是否带副窗
        $isBieshuDoor = 0;//是否是别墅门
        $hasMenkuangyq = 0;
        if (in_array($orderInfo['menkuangyq'], ['左开左无边', '右开右无边', '左开右无边', '右开左无边'])) {
            $hasMenkuangyq = 1;
        }
        $orderType = $orderInfo['order_type'];//2018-07-12：订单类别，根据订单类别控制数据输出，成品输出所有，半品门框仅输出半品门框及下阶，半品门扇仅输出半品门扇及下阶，母扇仅输出母扇及下阶，子扇仅输出子扇及下阶

        if (strpos($menshan, '对开') !== false) {
            $isDanshan = 0;
            $isDuikai = 1;
        } elseif (strpos($menshan, '四开') !== false) {
            $isDanshan = 0;
            $isSikai = 1;
        } elseif (strpos($menshan, '子母') !== false) {
            $isDanshan = 0;
            $isZimu = 1;
        }

        if ($orderInfo['chuanghua'] == '无' || strpos($orderInfo['chuanghua'], '无副窗') !== false || empty($orderInfo['chuanghua'])) {
            $hasFuchuang = 0;
        }

        if (strpos($orderInfo['menkuang'], '别墅') !== false) {
            $isBieshuDoor = 1;
        }

        /*元件层级关系
         * 成品料号
              半品门扇（母，子，母子，子子，单扇门默认母）
                  前门板
                      材质
                  后门板
                      材质
                  门扇下阶
              半品门框
                  上框
                      材质
                  中门框（有副窗才有）
                      材质
                  底框
                      材质
                  铰框（默认都有）
                      材质
                  锁框（单扇门默认有铰框、锁框）
                      材质
                  门框下阶
              窗花（四开窗花有大小之分）
              玻璃卡槽
              其他元件
        */
        //生成料号，成品料号=>半品门框，半品门扇，窗花，玻璃卡槽
        //半品门框=>半品门框下阶（上框，底框，铰框，中框（带副窗有），锁框（单扇门有），元件列表匹配料号）=>铁卷
        //半品门扇=>半品门扇下阶（前门板，后门板，元件列表匹配料号）=>铁卷，
        $yuanjianList = $baseCodeService->getYuanJianList($orderInfo);//获取元件列表
        if ($orderInfo['order_db'] == 'M8') {
            if (strpos($orderType, '门框') !== false) {
                $banpinmk = $baseCodeService->getMaterialCode('防火门半品门框', $orderInfo);//获取半品门框
            } elseif (strpos($orderType, '门扇') !== false) {
                $banpinms = $baseCodeService->getMaterialCode('防火门半品门扇', $orderInfo, '', '母'); //获取半品门扇下阶
                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinmsZi = $baseCodeService->getMaterialCode('防火门半品门扇', $orderInfo, '', '子'); //半品门扇子门
                }
            } elseif (strpos($orderType, '母扇') !== false) {
                $banpinms = $baseCodeService->getMaterialCode('防火门半品门扇', $orderInfo, '', '母'); //获取半品门扇下阶
            } elseif (strpos($orderType, '子扇') !== false) {
                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinmsZi = $baseCodeService->getMaterialCode('防火门半品门扇', $orderInfo, '', '子'); //半品门扇子门
                }
            } else {
                $chengpinlh = $baseCodeService->getMaterialCode('防火门成品', $orderInfo); //获取成品料号
                $banpinmk = $baseCodeService->getMaterialCode('防火门半品门框', $orderInfo);//获取半品门框
                $banpinms = $baseCodeService->getMaterialCode('防火门半品门扇', $orderInfo, '', '母'); //获取半品门扇下阶
                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinmsZi = $baseCodeService->getMaterialCode('防火门半品门扇', $orderInfo, '', '子'); //半品门扇子门
                }
            }
        } else {
            if (strpos($orderType, '门框') !== false) {
                $banpinmk = $baseCodeService->getMaterialCode('半品门框', $orderInfo);//获取半品门框
            } elseif (strpos($orderType, '门扇') !== false) {
                $banpinms = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '母'); //获取半品门扇下阶
                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinmsZi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '子'); //半品门扇子门
                }
                if ($isSikai) {
                    $banpinmsMuzi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '母子');//半品门扇母子门
                    $banpinmsZizi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '子子'); //半品门扇子子门
                }
            } elseif (strpos($orderType, '母扇') !== false) {
                $banpinms = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '母'); //获取半品门扇下阶
            } elseif (strpos($orderType, '子扇') !== false) {
                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinmsZi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '子'); //半品门扇子门
                }
            } else {
                //成品
                $chengpinlh = $baseCodeService->getMaterialCode('成品', $orderInfo); //获取成品料号
                $banpinmk = $baseCodeService->getMaterialCode('半品门框', $orderInfo);//获取半品门框
                $banpinms = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '母'); //获取半品门扇下阶

                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinmsZi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '子'); //半品门扇子门
                }
                if ($isSikai) {
                    $banpinmsMuzi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '母子');//半品门扇母子门
                    $banpinmsZizi = $baseCodeService->getMaterialCode('半品门扇', $orderInfo, '', '子子'); //半品门扇子子门
                }
            }
        }

//        $chengpinlh = $baseCodeService->getMaterialCode('成品',$orderInfo); //获取成品料号
        $chengpinXiajie = $this->findXiajie($yuanjianList, '成品级');//成品下阶
        DB::startTrans();
        $orderProductMd5 = $orderInfo['order_product_md5'];
        //料号位数不足35位，则errorCode==1
        $hasErrorCode = (strpos($chengpinlh['liaohao'], '$') !== false || strlen($chengpinlh['liaohao']) != 35) ? 1 : 0;
        $userName = $GLOBALS['uname'];
        $nowTime = date('Y-m-d H:i:s');
        $dingdanhao = $orderInfo['oeb01'];
        $xiangci = $orderInfo['oeb03'];
        if (!empty($chengpinlh)) {//有成品则入成品料号表
            $sql = "insert into bom_chengpin_liaohao(id,liaohao,pinming,guige,order_product_md5,has_error_code,created_at,created_by,dingdanh,xiangci) 
              values(bom_chengpin_liaohao_seq.nextval,'" . $chengpinlh['liaohao'] . "','" . $chengpinlh['liaohao_pinming'] . "','" . $chengpinlh['liaohao_guige'] . "','$orderProductMd5',$hasErrorCode,to_date('$nowTime','yyyy-mm-dd hh24:mi:ss'),'$userName','$dingdanhao','$xiangci') ";
            M()->execute($sql); //成品料号
            //如果有窗花则添加窗花到成品下阶
            if ($hasFuchuang) {
                //四开窗花有大小
                if ($isSikai) {
                    $chuanghualhXiao = $baseCodeService->getMaterialCode('窗花', $orderInfo, '', '', '', '小'); //获取小窗花料号
                    $chuanghuaXiajieXiao = $this->getChuanghuaXiajie($chuanghualhXiao, $orderInfo);//小窗花下阶
                    $chuanghualhXiao['child'] = $chuanghuaXiajieXiao;
                    array_push($chengpinXiajie, $chuanghualhXiao);
                    $chuanghualhDa = $baseCodeService->getMaterialCode('窗花', $orderInfo, '', '', '', '大'); //获取大窗花料号
                    $chuanghuaXiajieDa = $this->getChuanghuaXiajie($chuanghualhDa, $orderInfo);//大窗花下阶
                    $chuanghualhDa['child'] = $chuanghuaXiajieDa;
                    array_push($chengpinXiajie, $chuanghualhDa);
                } else {
                    $chuanghualh = $baseCodeService->getMaterialCode('窗花', $orderInfo); //获取窗花料号
                    # 2019-01-24 新增窗花下阶
                    $chuanghuaXiajie = $this->getChuanghuaXiajie($chuanghualh, $orderInfo);
                    $chuanghualh['child'] = $chuanghuaXiajie;
                    array_push($chengpinXiajie, $chuanghualh);
                }
                //中式窗花带玻胶条
                $zhongshiJiaotiao = $this->getChuanghuaZhongshiJiaotiao($orderInfo);
                if (!empty($zhongshiJiaotiao)) {
                    array_push($chengpinXiajie, $zhongshiJiaotiao);
                }
            }

            //如果是别墅门
            if ($isBieshuDoor) {
                $bieshuMentou = $baseCodeService->getMaterialCode('别墅门', $orderInfo, '', '', '', '', '', '门头');//一个门头
                array_push($chengpinXiajie, $bieshuMentou);
                $bieshuMenzhu = $baseCodeService->getMaterialCode('别墅门', $orderInfo, '', '', '', '', '', '门柱');//两个门柱
                array_push($chengpinXiajie, $bieshuMenzhu);
            }

            //如是采用PE的包装材料--包装（成品级）
            $pebaozh = $this->pebaozhuang($orderInfo);
            if (!empty($pebaozh)) {
                foreach ($pebaozh as $key => $val) {
                    array_push($chengpinXiajie, $val);
                }
            }
            #添加玻璃下阶
            $boli = $this->getGlassXiaJie($orderInfo);
            if(!empty($boli)){
                foreach ($boli as $kk => $vv){
                    array_push($chengpinXiajie,$vv);
                }
            }
            $this->insertXiajie($chengpinXiajie, 0, $chengpinlh, $orderInfo);//插入成品下阶
        }
        //门框
        //获取门框料号信息,半品门框=>半品门框下阶（上框，底框，铰框，中框（带副窗有），锁框（单扇门有），元件列表匹配料号）
//        $banpinmk = $baseCodeService->getMaterialCode('半品门框',$orderInfo);//获取半品门框
        if (!empty($this->teshuMenkuang($orderInfo))) {//特殊门框
            $banpinmkXiajie = $this->teshuMenkuang($orderInfo);
        } else {
            //获取半品门框下阶，带副窗有中框,单门扇默认有一边铰方一边锁方，对开、子母、四开默认两边都是铰方
            $shangmengk = $baseCodeService->getMaterialCode('单框', $orderInfo, '上门框');//获取上框料号
            $shangmengk['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '上框');//获取上框材质
            $dimengk = $baseCodeService->getMaterialCode('单框', $orderInfo, '底框');//获取底框框料号
            $dimengk['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '底框');//获取低框材质
//            $jiaokuang = $baseCodeService->getMaterialCode('单框',$orderInfo,'铰框'); //获取铰框料号
//            $jiaokuang['child'][0] = $baseCodeService->getMaterialCode('材质',$orderInfo,'铰框');//获取铰框材质
//            $banpinmkXiajie = array($shangmengk,$dimengk,$jiaokuang);//半品门框下阶
            $banpinmkXiajie = array($shangmengk, $dimengk);//半品门框下阶
            //非单扇门有门框要求两边都为绞框的处理
            //非单扇门有门框要求两边都为绞框的处理
            if ($hasMenkuangyq && !$isDanshan) {//非单扇门---有门框要求
                if (in_array($orderInfo['menkuangyq'], ['左开左无边', '右开右无边'])) {
                    $jiaokuang1 = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框', '半花边框'); //获取铰框料号
                    $jiaokuang1['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '铰框');//获取铰框材质
                    $jiaokuang2 = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框'); //获取铰框料号
                    $jiaokuang2['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '锁框');//第二个铰方材质取锁框材质
//                    $jiaokuang2['liaohao_pinming'] = str_replace('普框边框','边框',$jiaokuang2['liaohao_pinming']);
                    array_push($banpinmkXiajie, $jiaokuang1);
                    array_push($banpinmkXiajie, $jiaokuang2);
                }
                if (in_array($orderInfo['menkuangyq'], ['左开右无边', '右开左无边'])) {
                    $jiaokuang1 = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框'); //获取铰框料号
                    $jiaokuang1['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '铰框');//获取铰框材质
                    $jiaokuang2 = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框', '半花边框'); //获取铰框料号
                    $jiaokuang2['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '锁框');//第二个铰方材质取锁框材质
//                    $jiaokuang2['liaohao_pinming'] = str_replace('边框', '普框边框', $jiaokuang2['liaohao_pinming']);
                    array_push($banpinmkXiajie, $jiaokuang1);
                    array_push($banpinmkXiajie, $jiaokuang2);
                }
            } elseif ($hasMenkuangyq && $isDanshan) {//单扇门--有门框要求
                if (in_array($orderInfo['menkuangyq'], ['左开左无边', '右开右无边'])) {
                    $jiaokuang1 = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框', '半花边框'); //获取铰框料号
                    $jiaokuang1['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '铰框');//获取铰框材质
                    $jiaokuang2 = $baseCodeService->getMaterialCode('单框', $orderInfo, '锁框'); //获取铰框料号
                    $jiaokuang2['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '锁框');//第二个铰方材质取锁框材质
//                    $jiaokuang2['liaohao_pinming'] = str_replace('普框边框','边框',$jiaokuang2['liaohao_pinming']);
                    array_push($banpinmkXiajie, $jiaokuang1);
                    array_push($banpinmkXiajie, $jiaokuang2);
                }
                if (in_array($orderInfo['menkuangyq'], ['左开右无边', '右开左无边'])) {
                    $jiaokuang1 = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框'); //获取铰框料号
                    $jiaokuang1['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '铰框');//获取铰框材质
                    $jiaokuang2 = $baseCodeService->getMaterialCode('单框', $orderInfo, '锁框', '半花边框'); //获取铰框料号
                    $jiaokuang2['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '锁框');//第二个铰方材质取锁框材质
//                    $jiaokuang2['liaohao_pinming'] = str_replace('边框','普框边框',$jiaokuang2['liaohao_pinming']);
                    array_push($banpinmkXiajie, $jiaokuang1);
                    array_push($banpinmkXiajie, $jiaokuang2);
                }
            } elseif ($isDanshan && !$hasMenkuangyq) {
                $jiaokuang = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框'); //获取铰框料号
                $jiaokuang['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '铰框');//获取铰框材质
                array_push($banpinmkXiajie, $jiaokuang);
                $suokuang = $baseCodeService->getMaterialCode('单框', $orderInfo, '锁框');//获取锁框料号
                $suokuang['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '锁框');//获取锁框材质
                array_push($banpinmkXiajie, $suokuang);
            } elseif (!$isDanshan && !$hasMenkuangyq) {
                $jiaokuang = $baseCodeService->getMaterialCode('单框', $orderInfo, '铰框'); //获取铰框料号
                $jiaokuang['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '铰框');//获取铰框材质
                array_push($banpinmkXiajie, $jiaokuang);
            }

            if ($hasFuchuang) {   //如果有副窗则需要中框
                $zhongmengk = $baseCodeService->getMaterialCode('单框', $orderInfo, '中框');//获取中框料号
                $zhongmengk['child'] = array();
                $zhongmengk['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '中框');//获取中框材质
                array_push($banpinmkXiajie, $zhongmengk);
            }

            //锁门框：单扇及有门框要求的门有  || (!$isDanshan && $hasMenkuangyq)
//            if($isDanshan){  //单门扇默认有一边铰方一边锁方，对开、子母、四开默认两边都是铰方
//                $suokuang = $baseCodeService->getMaterialCode('单框',$orderInfo,'锁框');//获取锁框料号
//                $suokuang['child'][0] = $baseCodeService->getMaterialCode('材质',$orderInfo,'锁框');//获取锁框材质
//                array_push($banpinmkXiajie,$suokuang);
//            }
        }
        two_array_merge($banpinmkXiajie, $this->findXiajie($yuanjianList, '门框级'));
        if (strpos($orderType, '门框') !== false) {
            $banpinlh = $this->insertBanpinLiaohao($banpinmk, $banpinmkXiajie, $chengpinlh);//插入半品门框及下阶料号
            return [
                'chengpinlh' => "",
                'banpinlh' => $banpinlh,
            ];
        } elseif (strpos($orderType, '门扇') !== false || strpos($orderType, '母扇') !== false || strpos($orderType, '子扇') !== false) {
//            continue;
        } else {
            $this->insertBanpinLiaohao($banpinmk, $banpinmkXiajie, $chengpinlh);//插入半品门框及下阶料号
        }

        //门扇
        //获取门扇料号信息,半品门扇=>半品门扇下阶（前门板，后门板，元件列表匹配料号），
        //单扇门默认是母门扇，子母，对开还有子扇，四开有母、子、母子、子子
        //母门 单扇门默认为母门
//        $banpinms = $baseCodeService->getMaterialCode('半品门扇',$orderInfo,'','母'); //获取半品门扇下阶
        $qianmenb = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '母', '前门板');//前门板
        $qianmenb['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '母', '前门板');//前门板材质
        $houmenb = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '母', '后门板'); //后门板
        $houmenb['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '母', '后门板');//后门板


        if (strpos($orderInfo['menkuang'], '钢框木扇') !== false || strpos($orderInfo['menkuang'], '50室内防火门框') !== false) {//钢框木扇门的特殊处理
            if (strpos($orderInfo['menshan'], '单扇') !== false) {
                if (in_array($orderInfo['operate_plan'], ['DS9', 'DS10'])) {
                    $tianxinInfo1 = $this->getTianXinMenshanQH($orderInfo, '母门');
                    $mianqib1 = $this->getMianqib($orderInfo, '母门');
                    $fengbiant1 = $this->getFengbiant($orderInfo, '母门');
                    $banpinmsXiajie1 = array(
                        $this->getGangKuangMuShanQH($orderInfo, '长边骨架', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '短外边骨架', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '短内边骨架', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '面板', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '背板', '母门'),
//                        $this->getMianqib($orderInfo,'母门'),
//                        $this->getFengbiant($orderInfo,'母门'),
                    );
                } else {
                    $tianxinInfo1 = $this->getTianXinMenshan($orderInfo, '母门');
                    $mianqib1 = $this->getMianqib($orderInfo, '母门');
                    $fengbiant1 = $this->getFengbiant($orderInfo, '母门');
                    $banpinmsXiajie1 = array(
//                        $this->getGangKuangChangbGujia($orderInfo,'母门','铰方'),
//                        $this->getGangKuangMuShan($orderInfo,'短边骨架','母门'),
//                        $this->getGangKuangMuShan($orderInfo,'面板','母门'),
//                        $this->getGangKuangMuShan($orderInfo,'背板','母门'),
//                        $this->getMianqib($orderInfo,'母门'),
//                        $this->getFengbiant($orderInfo,'母门'),
                    );
                    # 短边骨架，面板，背板多个处理
                    $duanbian = $this->getGangKuangMuShan($orderInfo, '短边骨架', '母门');
                    if (!empty($duanbian)) {
                        foreach ($duanbian as $key => $val) {
                            array_push($banpinmsXiajie1, $val);
                        }
                    }
                    # 面板
                    $mianban = $this->getGangKuangMuShan($orderInfo, '面板', '母门');
                    if (!empty($mianban)) {
                        foreach ($mianban as $key => $val) {
                            array_push($banpinmsXiajie1, $val);
                        }
                    }
                    # 背板
                    $beiban = $this->getGangKuangMuShan($orderInfo, '背板', '母门');
                    if (!empty($beiban)) {
                        foreach ($beiban as $key => $val) {
                            array_push($banpinmsXiajie1, $val);
                        }
                    }
                    $changbiangujia_jiao = $this->getGangKuangChangbGujia($orderInfo, '母门', '铰方');
                    if (!empty($changbiangujia_jiao)) {
                        array_push($banpinmsXiajie1, $changbiangujia_jiao);
                    }
                    $changbiangujia_suo = $this->getGangKuangChangbGujia($orderInfo, '母门', '锁方');
                    if (!empty($changbiangujia_suo)) {
                        array_push($banpinmsXiajie1, $changbiangujia_suo);
                    }
                }
                if (!empty($tianxinInfo1)) {
                    array_push($banpinmsXiajie1, $tianxinInfo1);
                }
                if (!empty($mianqib1)) {
                    array_push($banpinmsXiajie1, $mianqib1);
                }
                # 封边条
                if (!empty($fengbiant1)) {
                    foreach ($fengbiant1 as $k => $v) {
                        array_push($banpinmsXiajie1, $v);
                    }
                }

                two_array_merge($banpinmsXiajie1, $this->findXiajie($yuanjianList, '门扇级(母门)', $orderInfo['height']));
            } else {//对开、子母门
                if (in_array($orderInfo['operate_plan'], ['DS9', 'DS10'])) { #齐河基地钢框木扇区别处理---齐河一部，齐河二部
                    $tianxinInfo1 = $this->getTianXinMenshanQH($orderInfo, '母门');//母门填芯料
                    $mianqib1 = $this->getMianqib($orderInfo, '母门');
                    $fengbiant1 = $this->getFengbiant($orderInfo, '母门');
                    $banpinmsXiajie1 = array(
                        $this->getGangKuangMuShanQH($orderInfo, '长边骨架', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '短外边骨架', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '短内边骨架', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '面板', '母门'),
                        $this->getGangKuangMuShanQH($orderInfo, '背板', '母门'),
//                        $this->getMianqib($orderInfo,'母门'),
//                        $this->getFengbiant($orderInfo,'母门'),
                    );
                    $tianxinInfo2 = $this->getTianXinMenshanQH($orderInfo, '子门');//子门填芯料
                    $mianqib2 = $this->getMianqib($orderInfo, '子门');
                    $fengbiant2 = $this->getFengbiant($orderInfo, '子门');
                    $banpinmsXiajie2 = array(
                        $this->getGangKuangMuShanQH($orderInfo, '长边骨架', '子门'),
                        $this->getGangKuangMuShanQH($orderInfo, '短外边骨架', '子门'),
                        $this->getGangKuangMuShanQH($orderInfo, '短内边骨架', '子门'),
                        $this->getGangKuangMuShanQH($orderInfo, '面板', '子门'),
                        $this->getGangKuangMuShanQH($orderInfo, '背板', '子门'),
//                        $this->getMianqib($orderInfo,'子门'),
//                        $this->getFengbiant($orderInfo,'子门'),
                    );
                } else {
                    $tianxinInfo1 = $this->getTianXinMenshan($orderInfo, '母门');
                    $mianqib1 = $this->getMianqib($orderInfo, '母门');
                    $fengbiant1 = $this->getFengbiant($orderInfo, '母门');
                    $banpinmsXiajie1 = array(
//                        $this->getGangKuangChangbGujia($orderInfo,'母门','铰方'),
//                        $this->getGangKuangChangbGujia($orderInfo,'母门','锁方'),
//                        $this->getGangKuangMuShan($orderInfo,'短边骨架','母门'),
//                        $this->getGangKuangMuShan($orderInfo,'面板','母门'),
//                        $this->getGangKuangMuShan($orderInfo,'背板','母门'),
//                        $this->getMianqib($orderInfo,'母门'),
//                        $this->getFengbiant($orderInfo,'母门'),
                    );
                    # 短边骨架，面板，背板多个处理
                    $duanbian = $this->getGangKuangMuShan($orderInfo, '短边骨架', '母门');
                    if (!empty($duanbian)) {
                        foreach ($duanbian as $key => $val) {
                            array_push($banpinmsXiajie1, $val);
                        }
                    }
                    # 面板
                    $mianban = $this->getGangKuangMuShan($orderInfo, '面板', '母门');
                    if (!empty($mianban)) {
                        foreach ($mianban as $key => $val) {
                            array_push($banpinmsXiajie1, $val);
                        }
                    }
                    # 背板
                    $beiban = $this->getGangKuangMuShan($orderInfo, '背板', '母门');
                    if (!empty($beiban)) {
                        foreach ($beiban as $key => $val) {
                            array_push($banpinmsXiajie1, $val);
                        }
                    }
                    $changbiangujia_jiao_mumen = $this->getGangKuangChangbGujia($orderInfo, '母门', '铰方');
                    if (!empty($changbiangujia_jiao_mumen)) {
                        array_push($banpinmsXiajie1, $changbiangujia_jiao_mumen);
                    }
                    $changbiangujia_suo_mumen = $this->getGangKuangChangbGujia($orderInfo, '母门', '锁方');
                    if (!empty($changbiangujia_suo_mumen)) {
                        array_push($banpinmsXiajie1, $changbiangujia_suo_mumen);
                    }
                    $tianxinInfo2 = $this->getTianXinMenshan($orderInfo, '子门');
                    $mianqib2 = $this->getMianqib($orderInfo, '子门');
                    $fengbiant2 = $this->getFengbiant($orderInfo, '子门');
                    $banpinmsXiajie2 = array(
//                        $this->getGangKuangChangbGujia($orderInfo,'子门','铰方'),
//                        $this->getGangKuangChangbGujia($orderInfo,'子门','锁方'),
//                        $this->getGangKuangMuShan($orderInfo,'短边骨架','子门'),
//                        $this->getGangKuangMuShan($orderInfo,'面板','子门'),
//                        $this->getGangKuangMuShan($orderInfo,'背板','子门'),
//                        $this->getMianqib($orderInfo,'子门'),
//                        $this->getFengbiant($orderInfo, '子门'),
                    );
                    # 短边骨架，面板，背板多个处理
                    $duanbian = $this->getGangKuangMuShan($orderInfo, '短边骨架', '子门');
                    if (!empty($duanbian)) {
                        foreach ($duanbian as $key => $val) {
                            array_push($banpinmsXiajie2, $val);
                        }
                    }
                    # 面板
                    $mianban = $this->getGangKuangMuShan($orderInfo, '面板', '子门');
                    if (!empty($mianban)) {
                        foreach ($mianban as $key => $val) {
                            array_push($banpinmsXiajie2, $val);
                        }
                    }
                    # 背板
                    $beiban = $this->getGangKuangMuShan($orderInfo, '背板', '子门');
                    if (!empty($beiban)) {
                        foreach ($beiban as $key => $val) {
                            array_push($banpinmsXiajie2, $val);
                        }
                    }
                    $changbiangujia_jiao_zimen = $this->getGangKuangChangbGujia($orderInfo, '子门', '铰方');
                    if (!empty($changbiangujia_jiao_zimen)) {
                        array_push($banpinmsXiajie2, $changbiangujia_jiao_zimen);
                    }
                    $changbiangujia_suo_zimen = $this->getGangKuangChangbGujia($orderInfo, '子门', '锁方');
                    if (!empty($changbiangujia_suo_zimen)) {
                        array_push($banpinmsXiajie2, $changbiangujia_suo_zimen);
                    }
                }
                if (!empty($tianxinInfo1)) {
                    array_push($banpinmsXiajie1, $tianxinInfo1);
                }
                if (!empty($mianqib1)) {
                    array_push($banpinmsXiajie1, $mianqib1);
                }
                if (!empty($fengbiant1)) {
                    foreach ($fengbiant1 as $k => $v) {
                        array_push($banpinmsXiajie1, $v);
                    }
                }
                two_array_merge($banpinmsXiajie1, $this->findXiajie($yuanjianList, '门扇级(母门)', $orderInfo['height']));

                if (!empty($tianxinInfo2)) {
                    array_push($banpinmsXiajie2, $tianxinInfo2);
                }
                if (!empty($mianqib2)) {
                    array_push($banpinmsXiajie2, $mianqib2);
                }
                if (!empty($fengbiant2)) {
                    foreach ($fengbiant2 as $k => $v) {
                        array_push($banpinmsXiajie2, $v);
                    }
                }
                two_array_merge($banpinmsXiajie2, $this->findXiajie($yuanjianList, '门扇级(子门)', $orderInfo['height']));
            }
            if (strpos($orderType, '母扇') !== false) {
                $banpinlh1 = $this->insertBanpinLiaohao($banpinms, $banpinmsXiajie1, $chengpinlh);//插入半品门扇（母门）及下阶料号
                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinlh1,
                ];
            } elseif (strpos($orderType, '子扇') !== false) {
                $banpinlh2 = $this->insertBanpinLiaohao($banpinmsZi, $banpinmsXiajie2, $chengpinlh);//插入半品门扇（子门）及下阶料号
                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinlh2,
                ];
            } elseif (strpos($orderType, '门扇') !== false) {
                $banpinlh1 = $this->insertBanpinLiaohao($banpinms, $banpinmsXiajie1, $chengpinlh);//插入半品门扇（母门）及下阶料号
                if ($isDuikai || $isZimu) {
                    $banpinlh2 = $this->insertBanpinLiaohao($banpinmsZi, $banpinmsXiajie2, $chengpinlh);//插入半品门扇（母门）及下阶料号
                    $banpinlh = array_merge($banpinlh1, $banpinlh2);
                }
                return [
                    'chengpinlh' => "",
                    'banpinlh' => empty($banpinlh2) ? $banpinlh1 : $banpinlh,
                ];
            } else {
                $this->insertBanpinLiaohao($banpinms, $banpinmsXiajie1, $chengpinlh);
                if ($isDuikai || $isZimu) {
                    $this->insertBanpinLiaohao($banpinmsZi, $banpinmsXiajie2, $chengpinlh);
                }
            }
        } else {
            if (in_array($orderInfo['operate_plan'], ['DS9', 'DS10'])) {
                $tianxinInfo = $this->getTianXinMenshanQH($orderInfo, '母门');
            } else {
                $tianxinInfo = $this->getTianXinMenshan($orderInfo, '母门');
            }
            if ($orderInfo['order_db'] == 'M8' && !empty($tianxinInfo)) {
                $banpinmsXiajie1 = array($qianmenb, $houmenb, $tianxinInfo);
            } else {
                $banpinmsXiajie1 = array($qianmenb, $houmenb);//半品门扇下阶（单门母扇）
            }
            two_array_merge($banpinmsXiajie1, $this->findXiajie($yuanjianList, '门扇级(母门)'));
            //如果是对开门或子母门或四开门则还需要获取子门的半品门扇以及前后门板
            if ($isDuikai || $isZimu || $isSikai) {
                //子门
//            $banpinmsZi = $baseCodeService->getMaterialCode('半品门扇',$orderInfo,'','子'); //半品门扇子门
                $qianmenbZi = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '子', '前门板');//前门板子门
                $qianmenbZi['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '子', '前门板');//前门板子门材质
                $houmenbZi = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '子', '后门板');//后门板子门
                $houmenbZi['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '子', '后门板');//后门板子门材质
                if (in_array($orderInfo['operate_plan'], ['DS9', 'DS10'])) {
                    $tianxinInfo = $this->getTianXinMenshanQH($orderInfo, '子门');
                } else {
                    $tianxinInfo = $this->getTianXinMenshan($orderInfo, '子门');
                }
                if ($orderInfo['order_db'] == 'M8' && !empty($tianxinInfo)) {
                    $banpinmsZiXiajie2 = array($qianmenbZi, $houmenbZi, $tianxinInfo);
                } else {
                    $banpinmsZiXiajie2 = array($qianmenbZi, $houmenbZi);//半品门扇下阶（对开子母门子扇）
                }
                two_array_merge($banpinmsZiXiajie2, $this->findXiajie($yuanjianList, '门扇级(子门)'));
//                $this->insertBanpinLiaohao($banpinmsZi,$banpinmsZiXiajie2,$chengpinlh);//插入半品门扇（子门）及下阶料号
            }
            //如果是四开门则还需要获取母子门，子子门的门扇以及前后门板
            if ($isSikai) {
                //母子门
//            $banpinmsMuzi = $baseCodeService->getMaterialCode('半品门扇',$orderInfo,'','母子');//半品门扇母子门
                $qianmenbMuzi = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '母子', '前门板');//前门板母子门
                $qianmenbMuzi['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '母子', '前门板');//前门板母子门材质
                $houmenbMuzi = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '母子', '后门板');//后门板母子门
                $houmenbMuzi['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '母子', '后门板');//后门板母子门材质
                $banpinmsMuziXiajie3 = array($qianmenbMuzi, $houmenbMuzi);//半品门扇下阶（母子门）
                two_array_merge($banpinmsMuziXiajie3, $this->findXiajie($yuanjianList, '门扇级(母子门)'));
//                $this->insertBanpinLiaohao($banpinmsMuzi,$banpinmsMuziXiajie3,$chengpinlh);//插入半品门扇（母子门）及下阶料号
                //子子门
//            $banpinmsZizi = $baseCodeService->getMaterialCode('半品门扇',$orderInfo,'','子子'); //半品门扇子子门
                $qianmenbZizi = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '子子', '前门板');//前门板子子门
                $qianmenbZizi['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '子子', '前门板');//前门板子子门材质
                $houmenbZizi = $baseCodeService->getMaterialCode('门板', $orderInfo, '', '子子', '后门板'); //后门板子子门
                $houmenbZizi['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '', '子子', '后门板');//后门板子子门材质
                $banpinmsmuZiziXiajie4 = array($qianmenbZizi, $houmenbZizi);//半品门扇下阶（子子门）
                two_array_merge($banpinmsmuZiziXiajie4, $this->findXiajie($yuanjianList, '门扇级(子子门)'));
//                $this->insertBanpinLiaohao($banpinmsZizi,$banpinmsmuZiziXiajie3,$chengpinlh);//插入半品门扇（子子门）及下阶料号
            }
            if (strpos($orderType, '母扇') !== false) {
                $banpinlh1 = $this->insertBanpinLiaohao($banpinms, $banpinmsXiajie1, $chengpinlh);//插入半品门扇（母门）及下阶料号
                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinlh1,
                ];
            } elseif (strpos($orderType, '子扇') !== false && ($isDuikai || $isZimu || $isSikai)) {
                $banpinlh2 = $this->insertBanpinLiaohao($banpinmsZi, $banpinmsZiXiajie2, $chengpinlh);//插入半品门扇（子门）及下阶料号
                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinlh2,
                ];
            } elseif (strpos($orderType, '门扇') !== false) {
                $banpinlh1 = $this->insertBanpinLiaohao($banpinms, $banpinmsXiajie1, $chengpinlh);//插入半品门扇（母门）及下阶料号
                if ($isDuikai || $isZimu || $isSikai) {
                    $banpinlh2 = $this->insertBanpinLiaohao($banpinmsZi, $banpinmsZiXiajie2, $chengpinlh);
                    $banpinlh = array_merge($banpinlh1, $banpinlh2);
                }
                if ($isSikai) {
                    $banpinlh3 = $this->insertBanpinLiaohao($banpinmsMuzi, $banpinmsMuziXiajie3, $chengpinlh);
                    $banpinlh4 = $this->insertBanpinLiaohao($banpinmsZizi, $banpinmsmuZiziXiajie4, $chengpinlh);
                    $banpinlh = array_merge($banpinlh1, $banpinlh3, $banpinlh4);
                }
                return [
                    'chengpinlh' => "",
                    'banpinlh' => empty($banpinlh) ? $banpinlh1 : $banpinlh,
                ];
            } else {
                $this->insertBanpinLiaohao($banpinms, $banpinmsXiajie1, $chengpinlh);//插入半品门扇（母门）及下阶料号

                if ($isDuikai || $isZimu || $isSikai) {
                    $this->insertBanpinLiaohao($banpinmsZi, $banpinmsZiXiajie2, $chengpinlh);
                }
                if ($isSikai) {
                    $this->insertBanpinLiaohao($banpinmsMuzi, $banpinmsMuziXiajie3, $chengpinlh);
                    $this->insertBanpinLiaohao($banpinmsZizi, $banpinmsmuZiziXiajie4, $chengpinlh);
                }
            }
        }
        DB::commit();
        $chengpinlhID = M()->query("select bom_chengpin_liaohao_seq.currval as id from dual", true);
        $chengpinlhID = $chengpinlhID[0]['id'];
        $chengpinlh['id'] = $chengpinlhID;
        $column = getColumnName('bom_banpin_liaohao');
        $banpinlh = M()->query("select $column from bom_banpin_liaohao where chengpin_liaohao_id=$chengpinlhID ", true);
        return [
            'chengpinlh' => $chengpinlh,
            'banpinlh' => $banpinlh,
        ];
    }

    /**
     * 根据层级查询该层级下阶
     * @param $yuanjianList
     * @param $yuanjianLevel
     */
    public function findXiajie($yuanjianList, $yuanjianLevel, $length = '')
    {
        $result = array();
        foreach ($yuanjianList as $k => $v) {
            if ($v['yuanjian_name'] == '胶纱网') {//胶纱网数据小数保留4位小数
                $v['yongliang'] = round($v['yongliang'], 4);
            }
            if (!empty($length) && strpos($v['yuanjian_name'], '封边条') !== false) {
                $v['yongliang'] = round(($length + 13) / 1000, 4);
            }
            if ($v['yuanjian_level'] == $yuanjianLevel) {
                array_push($result, $v);
            }
        }
        return $result;
    }

    /**
     * 插入半品（包括成品中的元件料号）下阶料号
     * @param $xiajie 下阶数组
     * @param $parentID 上级料号id
     */
    public function insertXiajie($xiajie, $parentID = 0, $chengpinlh, $orderInfo = '')
    {
        if (empty($chengpinlh)) {
            $chengpinID = 0;
        } else {
            $chengpinID = "bom_chengpin_liaohao_seq.currval";
        }
        $userName = $GLOBALS['uname'];
        $nowTime = date('Y-m-d H:i:s');
        //门框下阶
        $mbType = array('前门板', '后门板');
        foreach ($xiajie as $k => $v) {
            $liaohao = $v['liaohao'];
            $liaohaoGuige = $v['liaohao_guige'];
            $liaohaoPinming = $v['liaohao_pinming'];
            $liaohaoDanwei = $v['liaohao_danwei'];
            $yongliang = $v['yongliang'];
            $yuanjianName = $v['yuanjian_name'];
            $yuanjianLevel = $v['yuanjian_level'];
            $hasErrorCode = strpos($liaohao, '$') !== false ? 1 : 0;
            if ($yuanjianName == '窗花') {
                $string1 = "000000";
                $compare1 = substr($liaohao, 9, 6) === $string1 ? 1 : 0;
                if ($compare1) {
                    $hasErrorCode = 1;
                }
            }
            if (in_array($yuanjianName, $mbType)) {//前门板、后门板位数为16，并且8-15码不为00000000，则料号正确，否则错误
                $string = "00000000";
                $compare = substr($liaohao, 7, 8) === $string ? 1 : 0;
                if ($compare) {
                    $hasErrorCode = 1;
                }
            }
            if ($yuanjianName == '材质') {
                $string2 = "00000";
                $compare2 = substr($liaohao, 17, 5) === $string2 ? 1 : 0;
                $string3 = "000000000000";
                $compare3 = substr($liaohao, 11, 12) === $string2 ? 1 : 0;
                if ($compare2 || $compare3) {
                    $hasErrorCode = 1;
                }
            }
            $sql = "insert into bom_banpin_liaohao(id,liaohao,liaohao_guige,liaohao_pinming,yongliang,liaohao_danwei,yuanjian_name,yuanjian_level,chengpin_liaohao_id,parent_id,has_error_code,created_at,created_by) 
                  values(bom_banpin_liaohao_seq.nextval,'$liaohao','$liaohaoGuige','$liaohaoPinming','$yongliang','$liaohaoDanwei','$yuanjianName','$yuanjianLevel',$chengpinID,$parentID,$hasErrorCode,to_date('$nowTime','yyyy-mm-dd hh24:mi:ss'),'$userName') ";
            DB::execute($sql);//下阶
            Db::commit();

            //如果半品还有下阶则继续插入
            if (!empty($v['child'])) {
                $banpinID = DB::query("select bom_banpin_liaohao_seq.currval as id from dual", true);
                $banpinID = $banpinID[0]['id'];
                $this->insertXiajie($v['child'], $banpinID, $chengpinlh, $orderInfo, $orderInfo);
            }
            //成都基地二三四部的窗花下阶数据转移到一部去
            if (($yuanjianName == '窗花' || $yuanjianName == '封闭式窗花') && !empty($v['child'])) {
                $chuanghuaXiajieLiao = array();
                foreach ($v['child'] as $key => $val) {
                    $temp = $val;
                    $temp['chuanghua_guige'] = $liaohaoGuige;
                    $temp['dingdanh'] = $orderInfo['oeb01'];
                    $temp['xiangci'] = $orderInfo['oeb03'];
                    $temp['zhizaobm'] = $orderInfo['operate_plan'];
                    if ($yuanjianName == '封闭式窗花') {
                        # 齐河基地封闭式窗花只生成在DS-9库
                        if (in_array($orderInfo['operate_plan'], ['DS9', 'DS10'])) {
                            $convert_zhizaobm = 'DS9';
                        } else {
                            $convert_zhizaobm = $orderInfo['operate_plan'];
                        }
                    } else {
                        if (in_array($orderInfo['operate_plan'], ['DS6', 'DS4', 'DS12', 'DS15'])) {//成都基地统一转DS6
                            $convert_zhizaobm = 'DS6';
                        } elseif (in_array($orderInfo['operate_plan'], ['DS9', 'DS10'])) {//成都基地统一转DS9
                            $convert_zhizaobm = 'DS9';
                        } else {
                            $convert_zhizaobm = 'DS16';
                        }
                    }

                    $temp['convert_zhizaobm'] = $convert_zhizaobm;
                    $temp['chuanghua_chengpin_liaohao'] = $liaohao;
                    array_push($chuanghuaXiajieLiao, $temp);
                }
                if (!empty($chuanghuaXiajieLiao)) {
                    $this->insertChuanghuaData($chuanghuaXiajieLiao, $banpinID);
                }
            }
        }
    }

    public function insertChuanghuaData($chuanghuaXiajieLiao, $banpinID)
    {
        $userName = $GLOBALS['uname'];
        $nowTime = date('Y-m-d H:i:s');
        Db::startTrans();
        $dingdanh = $chuanghuaXiajieLiao[0]['dingdanh'];
        $xiangci = $chuanghuaXiajieLiao[0]['xiangci'];
        $chuanghua_chengpin_liaohao = $chuanghuaXiajieLiao[0]['chuanghua_chengpin_liaohao'];
        Db::execute("delete from bom_chuanghua_liaohao where dingdanh='$dingdanh' and xiangci='$xiangci' and chuanghua_chengpin_liaohao='$chuanghua_chengpin_liaohao'");
        foreach ($chuanghuaXiajieLiao as $key => $val) {
            $liaohao = $val['liaohao'];
            $liaohaoGuige = $val['liaohao_guige'];
            $liaohaoPinming = $val['liaohao_pinming'];
            $yongliang = $val['yongliang'];
            $liaohaoDanwei = $val['liaohao_danwei'];
            $yuanjianName = $val['yuanjian_name'];
            $yuanjianLevel = $val['yuanjian_level'];
            $chuanghuaGuige = $val['chuanghua_guige'];
            $dingdanh = $val['dingdanh'];
            $xiangci = $val['xiangci'];
            $zhizaobm = $val['zhizaobm'];
            $convertZhizaobm = $val['convert_zhizaobm'];
            $chuanghuaChengpinliaohao = $val['chuanghua_chengpin_liaohao'];
            $sql = "insert into bom_chuanghua_liaohao(id,liaohao,guige,pinming,yongliang,liaohao_danwei,yuanjian_name,yuanjian_level,chuanghua_guige,dingdanh,xiangci,zhizaobm,convert_zhizaobm,chuanghua_chengpin_liaohao,parent_id,created_at,created_by) 
            values(bom_banpin_liaohao_seq.nextval,'$liaohao','$liaohaoGuige','$liaohaoPinming','$yongliang','$liaohaoDanwei','$yuanjianName','$yuanjianLevel','$chuanghuaGuige','$dingdanh','$xiangci','$zhizaobm','$convertZhizaobm','$chuanghuaChengpinliaohao','$banpinID',to_date('$nowTime','yyyy-mm-dd hh24:mi:ss'),'$userName') ";
            DB::execute($sql);//下阶
        }
        Db::commit();
    }

    /**
     * @param $banpinglx 半品类型 :半品门框 、半品门扇料号（母，子，母子，子子）
     * @param $xiajie 该半品门扇对应的下阶
     */
    public function insertBanpinLiaohao($banpinglx, $xiajie, $chengpinlh)
    {
        if (empty($chengpinlh)) {
            $chengpinID = 0;
        } else {
            $chengpinID = "bom_chengpin_liaohao_seq.currval";
        }
        $hasErrorCode = strpos($banpinglx['liaohao'], '$') !== false ? 1 : 0;
        if (in_array($banpinglx['yuanjian_name'], ['半品门框', '半品门扇'])) {
            $hasErrorCode = (strpos($banpinglx['liaohao'], '$') !== false || strlen($banpinglx['liaohao']) != 25) ? 1 : 0;
        }
        #添加半品窗框 和半品扇框处理
        if (in_array($banpinglx['yuanjian_name'], ['半品窗框', '半品扇框', '耐火窗单框'])) {
            $hasErrorCode = (strpos($banpinglx['liaohao'], '$') !== false || strlen($banpinglx['liaohao']) != 25) ? 1 : 0;
        }

        $userName = $GLOBALS['uname'];
        $nowTime = date('Y-m-d H:i:s');
        $sql = "insert into bom_banpin_liaohao(id,liaohao,chengpin_liaohao_id,yuanjian_name,yuanjian_level,liaohao_pinming,liaohao_guige,has_error_code,created_at,created_by) 
                values(bom_banpin_liaohao_seq.nextval,'" . $banpinglx['liaohao'] . "',$chengpinID,'" . $banpinglx['yuanjian_name'] . "','" . $banpinglx['yuanjian_level'] . "','" . $banpinglx['liaohao_pinming'] . "','" . $banpinglx['liaohao_guige'] . "',$hasErrorCode,to_date('$nowTime','yyyy-mm-dd hh24:mi:ss'),'$userName') ";
        M()->execute($sql);//写入半品门框料号
        Db::commit();
        $banpinID = DB::query("select bom_banpin_liaohao_seq.currval as id from dual", true);
        $banpinID = $banpinID[0]['id'];
        $this->insertXiajie($xiajie, $banpinID, $chengpinlh);//插入半品下阶
        if (empty($chengpinlh)) {
            $banpinlh = Db::query("select * from bom_banpin_liaohao start with id=$banpinID connect by prior id = parent_id", true);
            return $banpinlh;
        }
    }

    public function createOrderProductMd5($order)
    {
        $sql = "select * from order_config_class t where t.order_column_name is not null order by sort_num";
        $orderColumn = DB::query($sql, true);
        array_push($orderColumn, array('order_column_name' => 'customer_name'));
        array_push($orderColumn, array('order_column_name' => 'order_type'));
        $teshuyq = array_reduce(DB::query("select * from bom_teshuyq"), function ($carry, $item) {
            return array_merge($carry, $item);
        }, []);
        $md5Key = '';
        foreach ($orderColumn as $k => $v) {
            //如果订单中中的特殊要求未参与到料号编码，则生成的MD5不包含teshuyq字段
            if ($v == 'TESHUYQ' && in_array($order['teshuyq'], $teshuyq)) {
                continue;
            }
            $md5Key .= $order[strtolower($v['order_column_name'])];
        }
        return md5(excel_trim($md5Key));
    }

    /**
     * 获取半品树状料号
     * @param $banpinlh 半品料号数组
     * @param $pid 上级id
     */
    public function getBanpinTree($banpinlh, $pid = 0)
    {
        $data = array();
        foreach ($banpinlh as $k => $v) {
            if (isset($v['parent_id']) && $v['parent_id'] == $pid) {
                $v['child'] = array();
                $child = $this->getBanpinTree($banpinlh, $v['id']);
                $v['child'] = $child;
                array_push($data, $v);
            }
        }
        return $data;
    }

    //外购门框的规则，现在只适合用于产品：60花边框75  框厚为1.2的产品
    public function teshuMenkuang($orderInfo)
    {
        $menshan = $orderInfo['menshan']; //门扇
        $isDanshan = 1; //是否是单扇门
        $isDuikai = 0; //是否是对开门
        $isSikai = 0; //是否是四开门
        $isZimu = 0; //是否是子母门
        $hasFuchuang = 1;//是否带副窗

        if (strpos($menshan, '对开') !== false) {
            $isDanshan = 0;
            $isDuikai = 1;
        } elseif (strpos($menshan, '四开') !== false) {
            $isDanshan = 0;
            $isSikai = 1;
        } elseif (strpos($menshan, '子母') !== false) {
            $isDanshan = 0;
            $isZimu = 1;
        }

        //strpos($orderInfo['chuanghua'], '无窗花') !== false ||
        if (strpos($orderInfo['chuanghua'], '无副窗') !== false || $orderInfo['chuanghua'] == '无' || empty($orderInfo['chuanghua'])) {
            $hasFuchuang = 0;
        }

        if (trim($orderInfo['menkuang']) == '60花边框75' && $orderInfo['mkhoudu'] == '1.2') {
            $baseCodeService = new BaseCodeService();
            $shangmk = $baseCodeService->getMaterialCode('外购门框', $orderInfo, '', '', '', '', '外购上门框');//获取外购上门框料号
            $dimengk = $baseCodeService->getMaterialCode('单框', $orderInfo, '底框');//获取底框框料号
            $dimengk['child'][0] = $baseCodeService->getMaterialCode('材质', $orderInfo, '底框');//获取低框材质
            $jiaomk = $baseCodeService->getMaterialCode('外购门框', $orderInfo, '', '', '', '', '外购铰门框');//获取外购铰门框料号
            $banpinmkXiajie = array($shangmk, $dimengk, $jiaomk);//半品门框下阶
            //有副窗则有中框
            if ($hasFuchuang) {
                $zhongmk = $baseCodeService->getMaterialCode('外购门框', $orderInfo, '', '', '', '', '外购中门框');//获取外购中框料号
                array_push($banpinmkXiajie, $zhongmk);
            }
            if ($isDanshan) {  //单门扇默认有一边铰方一边锁方，对开、子母、四开默认两边都是铰方
                $suomk = $baseCodeService->getMaterialCode('外购门框', $orderInfo, '', '', '', '', '外购锁门框');//获取外购锁门框料号
                array_push($banpinmkXiajie, $suomk);
            }
            return $banpinmkXiajie;
        } else {
            return 0;
        }
    }

    //钢框木扇特殊处理(成都基地)
    //classify:短边骨架、面板、背板
    public function getGangKuangMuShan($order, $classify, $menlei = '母门')
    {
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $mskaikong = (empty($order['mskaikong']) || trim($order['mskaikong']) == '无') ? '无' : '带观察孔';
        $dkcailiao = (strpos($order['dkcailiao'], '矩管') !== false) ? '矩管' : '非矩管';
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        if ($classify == '长边骨架') {
            $length = $guigecArr[0];//规格长度
            $width = 0;
        } elseif ($classify == '短边骨架') {
            $length = $guigecArr[1];//规格宽度
            $width = 0;
        } else {
            $length = $guigecArr[0];//规格长度
            $width = $guigecArr[1];//规格宽度
        }
        switch ($menlei) {
            case "母门":
                if (strpos($classify, '骨架') !== false) {//骨架母门
                    $column = "mumen_yongliaocd as yongliaocd,mumen_cailiaocd as cailiaocd,mumen_genshu as genshu,liaohao,pinming,guige";
                } else {//玻镁板母门
                    $column = "mumen_yongliaocd as yongliaocd,mumen_yongliaokd as yongliaokd,mumen_cailiaocd as cailiaocd,mumen_cailiaokd as cailiaokd,liaohao,pinming,guige";
                }
                break;
            case "子门":
                if (strpos($classify, '骨架') !== false) {//骨架子门
                    $column = "zimen_guigekd as kuandu,zimen_yongliaocd as yongliaocd,zimen_cailiaocd as cailiaocd,zimen_genshu as genshu,liaohao,pinming,guige";
                } else {//玻镁板子门
                    $column = "zimen_guigekd as kuandu,zimen_yongliaocd as yongliaocd,zimen_yongliaokd as yongliaokd,zimen_cailiaocd as cailiaocd,zimen_cailiaokd as cailiaokd,liaohao,pinming,guige";
                }
                break;
        }
        if (strpos($classify, '骨架') !== false) {//骨架---长边，短边
            $sql = "select $column from bom_menban_gangkuang_rule where menkuang like '%/$menkuang/%' and menshan like '%/$menshan/%' 
and mskaikong like '%/$mskaikong/%' and dkcailiao like '%/$dkcailiao/%' and classify like '%$classify%'";
        } else {//玻镁板---面板，背板
            $sql = "select $column from bom_menban_gangkuang_rule where menkuang like '%/$menkuang/%' and menshan like '%/$menshan/%' and dkcailiao like '%/$dkcailiao/%'
and classify like '%$classify%'";
        }
        $result = Db::query($sql, true);
        //料号组装
        $yongliaoRule = array();
        if (strpos($classify, '骨架') !== false) {
            foreach ($result as $key => $val) {
                if ($classify == '短边骨架' && strpos($menshan, '对开') !== false) {
                    $changdu = empty($result[$key]['kuandu']) ? ($length / 2) + $result[$key]['yongliaocd'] : $result[$key]['kuandu'];
                } else {
                    $changdu = empty($result[$key]['kuandu']) ? $length + $result[$key]['yongliaocd'] : $result[$key]['kuandu'];
                }
                $yongliaoRule[$key]['liaohao'] = $result[$key]['liaohao'];
                $yongliaoRule[$key]['liaohao_guige'] = $result[$key]['guige'];
                $yongliaoRule[$key]['liaohao_pinming'] = $result[$key]['pinming'];
                $yongliaoRule[$key]['yongliang'] = round($changdu / 1000 / $result[$key]['cailiaocd'] * $result[$key]['genshu'], 4);//QPA=(规格长度+用料长度)/1000/材料长度*用料根数
                $yongliaoRule[$key]['liaohao_danwei'] = '';
                $yongliaoRule[$key]['yuanjian_name'] = $classify;
                $yongliaoRule[$key]['yuanjian_level'] = "门扇级($menlei)";
            }
        } else {//面板，背板
            foreach ($result as $key => $val) {
                $changdu = $length + $result[$key]['yongliaocd'];
                if (strpos($menshan, '对开') !== false) {
                    $width /= 2;
                }
                $kuandu = empty($result[$key]['kuandu']) ? $width + $result[$key]['yongliaokd'] : $result[$key]['kuandu'];
                $yongliaoRule[$key]['liaohao'] = $result[$key]['liaohao'];
                $yongliaoRule[$key]['liaohao_guige'] = $result[$key]['guige'];
                $yongliaoRule[$key]['liaohao_pinming'] = $result[$key]['pinming'];
                $yongliaoRule[$key]['yongliang'] = round($changdu * $kuandu / $result[$key]['cailiaocd'] / $result[$key]['cailiaokd'] / 1000000, 4);//QPA=(规格长度+用料长度)/1000/材料长度*用料根数
                $yongliaoRule[$key]['liaohao_danwei'] = '';
                $yongliaoRule[$key]['yuanjian_name'] = $classify;
                $yongliaoRule[$key]['yuanjian_level'] = "门扇级($menlei)";
                //对开门用料减半1/2
//                if (strpos($menshan,'对开') !== false) {
//                    $yongliaoRule[$key]['yongliang'] = round($yongliaoRule[$key]['yongliang']/2,4);
//                }
            }
        }
        return $yongliaoRule;
    }

    /**
     * 成都基地防火门钢框木扇长边骨架QPA料号获取
     * @param $order
     * @param $classify
     * @param string $menlei
     * @return array
     */
    public function getGangKuangChangbGujia($order, $menlei = '母门', $jiaosuo = '铰方')
    {
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $dkcailiao = (strpos($order['dkcailiao'], '矩管') !== false) ? '矩管' : '非矩管';
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $length = $guigecArr[0];//规格长度
        $width = $guigecArr[1];//规格宽度
        switch ($menlei) {
            case "母门":
                if ($jiaosuo == '铰方') {
                    $column = "(case when guige_cd>0 then guige_cd else $length end)+nvl(mumen_jiao_yongliao,0) as yongliao_cd,cailiao_cd";
                    $columnPinming = "mumen_jiao_liaohao as liaohao,mumen_jiao_pinming as pinming,mumen_jiao_guige as guige";
                } else {
                    $column = "(case when guige_cd>0 then guige_cd else $length end)+nvl(mumen_suo_yongliao,0) as yongliao_cd,cailiao_cd";
                    $columnPinming = "mumen_suo_liaohao as liaohao,mumen_suo_pinming as pinming,mumen_suo_guige as guige";
                }
                break;
            case "子门":
                if ($jiaosuo == '铰方') {//长边骨架子门铰锁方
                    $column = "(case when guige_cd>0 then guige_cd else $length end)+nvl(zimen_jiao_yongliao,0) as yongliao_cd,cailiao_cd";
                    $columnPinming = "zimen_jiao_liaohao as liaohao,zimen_jiao_pinming as pinming,zimen_jiao_guige as guige";
                } else {
                    $column = "(case when guige_cd>0 then guige_cd else $length end)+nvl(zimen_suo_yongliao,0) as yongliao_cd,cailiao_cd";
                    $columnPinming = "zimen_suo_liaohao as liaohao,zimen_suo_pinming as pinming,zimen_suo_guige as guige";
                }
                break;
        }
        $sql = "select $column from bom_gk_cbgujia_yliao_rule where menkuang like '%/$menkuang/%' and 
              $length between gaodu_start and gaodu_end and menshan like '%/$menshan/%' and dkcailiao like '%/$dkcailiao/%'";
        $sqlPinming = "select $columnPinming from bom_gk_cbgujia_pinming_rule where menkuang like '%/$menkuang/%' and $width between width_start and width_end and menshan like '%/$menshan/%'";
        $result = Db::query($sql, true);
        $resultPinming = Db::query($sqlPinming, true);
        $result = $result[0];
        $resultPinming = $resultPinming[0];
        if (empty($result)) {
            return [];
        } else {
            //用料规格查询完，查询料号品名规则
            if (empty($resultPinming)) {
                return [];
            } else {
                //料号组装
                $yongliaoRule = array();
                $yongliaoRule['liaohao'] = $resultPinming['liaohao'];
                $yongliaoRule['liaohao_guige'] = $resultPinming['guige'];
                $yongliaoRule['liaohao_pinming'] = $resultPinming['pinming'];
                $yongliaoRule['yongliang'] = round($result['yongliao_cd'] / 1000 / $result['cailiao_cd'], 4);//QPA=(规格长度+用料长度)/1000/材料长度*用料根数
                $yongliaoRule['liaohao_danwei'] = '';
                $yongliaoRule['yuanjian_name'] = "长边骨架($menlei" . $jiaosuo . ")";
                $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
                return $yongliaoRule;
            }
        }
    }

    //钢框木扇特殊处理(齐河基地)
    //classify:长边骨架、短内边骨架、短外边骨架、面板、背板
    public function getGangKuangMuShanQH($order, $classify, $menlei = '母门')
    {
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $mskaikong = (empty($order['mskaikong']) || trim($order['mskaikong']) == '无') ? '无' : '带观察孔';
        $dkcailiao = (strpos($order['dkcailiao'], '矩管') !== false) ? '矩管' : '非矩管';
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        if ($classify == '长边骨架') {
            $length = $guigecArr[0];//规格长度
            $width = 0;
        } elseif ($classify == '短外边骨架' || $classify == '短内边骨架') {
            $length = $guigecArr[1];//规格宽度
            $width = 0;
        } else {
            $length = $guigecArr[0];//规格长度
            $width = $guigecArr[1];//规格宽度
        }
        switch ($menlei) {
            case "母门":
                if (strpos($classify, '骨架') !== false) {//骨架母门
                    $column = "mumen_yongliaocd as yongliaocd,mumen_cailiaocd as cailiaocd,mumen_genshu as genshu,liaohao,pinming,guige";
                } else {//玻镁板母门
                    $column = "mumen_yongliaocd as yongliaocd,mumen_yongliaokd as yongliaokd,mumen_cailiaocd as cailiaocd,mumen_cailiaokd as cailiaokd,liaohao,pinming,guige";
                }
                break;
            case "子门":
                if (strpos($classify, '骨架') !== false) {//骨架子门
                    $column = "zimen_guigekd as kuandu,zimen_yongliaocd as yongliaocd,zimen_cailiaocd as cailiaocd,zimen_genshu as genshu,liaohao,pinming,guige";
                } else {//玻镁板子门
                    $column = "zimen_guigekd as kuandu,zimen_yongliaocd as yongliaocd,zimen_yongliaokd as yongliaokd,zimen_cailiaocd as cailiaocd,zimen_cailiaokd as cailiaokd,liaohao,pinming,guige";
                }
                break;
        }
        if (strpos($classify, '骨架') !== false) {//长边骨架---高度区间匹配,短边骨架---宽度区间匹配
            $sql = "select $column from bom_menban_gangkuang_qh_rule where menkuang like '%/$menkuang/%' and menshan like '%/$menshan/%' 
and mskaikong like '%/$mskaikong/%' and dkcailiao like '%/$dkcailiao/%' and $length BETWEEN interval_start and interval_end and classify like '%$classify%'";
        } else {//玻镁板---面板，背板
            $sql = "select $column from bom_menban_gangkuang_qh_rule where menkuang like '%/$menkuang/%' and menshan like '%/$menshan/%' and $length BETWEEN interval_start and interval_end
and classify like '%$classify%'";
        }
        $result = Db::query($sql, true);
        $result = $result[0];
        //料号组装
        $yongliaoRule = array();
        if (strpos($classify, '骨架') !== false) {
            $changdu = empty($result['kuandu']) ? $length + $result['yongliaocd'] : $result['kuandu'];
            $yongliaoRule['liaohao'] = $result['liaohao'];
            $yongliaoRule['liaohao_guige'] = $result['guige'];
            $yongliaoRule['liaohao_pinming'] = $result['pinming'];
            if ($classify == '短内边骨架') {//短内边骨架用量计算不同
                $yongliaoRule['yongliang'] = round($changdu * 38 / 1000000 / $result['cailiaocd'] / 1.22 * $result['genshu'], 4);//QPA=(规格宽度+用料长度)*38/1000000/材料长度/1.22*用料根数
            } elseif ($classify == '短外边骨架' && strpos($menshan, '对开') !== false) {//短外边骨架 && 门扇为对开门，QPA在除以2
                $yongliaoRule['yongliang'] = round($changdu / 2 / 1000 / $result['cailiaocd'] * $result['genshu'], 4);//QPA=(规格宽度+用料长度)/2/1000/材料长度*用料根数
            } else {
                $yongliaoRule['yongliang'] = round($changdu / 1000 / $result['cailiaocd'] * $result['genshu'], 4);//QPA=(规格长度+用料长度)/1000/材料长度*用料根数
            }
            $yongliaoRule['liaohao_danwei'] = '';
            $yongliaoRule['yuanjian_name'] = $classify;
            $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
        } else {//面板，背板
            $changdu = $length + $result['yongliaocd'];
            $kuandu = empty($result['kuandu']) ? $width + $result['yongliaokd'] : $result['kuandu'];
            $yongliaoRule['liaohao'] = $result['liaohao'];
            $yongliaoRule['liaohao_guige'] = $result['guige'];
            $yongliaoRule['liaohao_pinming'] = $result['pinming'];
            $yongliaoRule['yongliang'] = round($changdu * $kuandu / $result['cailiaocd'] / $result['cailiaokd'] / 1000000, 4);//QPA=(规格长度+用料长度)*(规格宽度+用料宽度)/材料长度/材料宽度/1000000
            $yongliaoRule['liaohao_danwei'] = '';
            $yongliaoRule['yuanjian_name'] = $classify;
            $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
            //对开门用料减半1/2
            if (strpos($menshan, '对开') !== false) {
                $yongliaoRule['yongliang'] = round($yongliaoRule['yongliang'] / 2, 4);
            }
        }
        return $yongliaoRule;
    }

    /*防火门填芯用料规则：高度，宽度规则---成都基地规则*/
    public function getTianXinMenshan($order, $menlei = '母门')
    {
        $baseCodeService = new BaseCodeService();
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $dkcailiao = (strpos($order['dkcailiao'], '矩管') !== false) ? '矩管' : '非矩管';
        $huase = (strpos($order['huase'], '平板') !== false) ? '平板' : '非平板';

        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $guigeLength = $baseCodeService->gaoduChange($menkuang, $guigecArr[0], $order['chuanghua'], $order['zhizaobm']);//长度、高度---高度根据规则转换
        $guigeWidth = $guigecArr[1];//宽度
        $where = " menkuang like '%/$menkuang/%' and menshan like '%/$menshan/%' and huase like '%/$huase/%'";
        switch ($menlei) {
            case "母门"://母门高度宽度规则--生成料号
                $lenthRule = "select ($guigeLength+yongliao_mumen) as yongliao_length,liaohao,pinming,guige from bom_tianxin_rule where $where and dkcailiao like '%/$dkcailiao/%' and classify='高度'";
                $widthRule = "select decode(sign(yongliao_mumen),-1,$guigeWidth+yongliao_mumen,yongliao_mumen) as yongliao_width,liaohao,pinming,guige from bom_tianxin_rule where $where and classify='宽度'";
                $lengthYongliao = Db::query($lenthRule, 1);
                $widthYongliao = Db::query($widthRule, 1);
                break;
            case "子门":
                $lenthRule = "select ($guigeLength+yongliao_zimen) as yongliao_length,liaohao,pinming,guige from bom_tianxin_rule where $where and dkcailiao like '%/$dkcailiao/%' and classify='高度'";
                $widthRule = "select decode(sign(yongliao_zimen),-1,$guigeWidth+yongliao_zimen,yongliao_zimen) as yongliao_width,liaohao,pinming,guige from bom_tianxin_rule where $where and classify='宽度'";
                $lengthYongliao = Db::query($lenthRule, 1);
                $widthYongliao = Db::query($widthRule, 1);
                break;
        }
        $lengthYongliao = $lengthYongliao[0];
        $widthYongliao = $widthYongliao[0];
        if (strpos($menshan, '对开门') !== false) {//对开门---母门子门用料宽度/2
            $widthYongliao['yongliao_width'] /= 2;
        }
        $widthYongliao['yongliao_width'] = empty($widthYongliao['yongliao_width']) ? $guigeWidth : $widthYongliao['yongliao_width'];
        $yongliaoRule = array();
        if (!empty($lengthYongliao) && !empty($widthYongliao)) {
            $yongliaoRule['liaohao'] = $lengthYongliao['liaohao'];
            $yongliaoRule['liaohao_guige'] = $lengthYongliao['guige'];
            $yongliaoRule['liaohao_pinming'] = $lengthYongliao['pinming'];
            $yongliaoRule['yongliang'] = round($lengthYongliao['yongliao_length'] * $widthYongliao['yongliao_width'] * 1 / 1000000, 4);//QPA=(规格长度*用料长度)/1000000
            $yongliaoRule['liaohao_danwei'] = '';
            $yongliaoRule['yuanjian_name'] = "填芯($menlei)";
            $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
        }
        return $yongliaoRule;
    }

    /*防火门填芯用料规则：高度，宽度规则---齐河基地规则*/
    public function getTianXinMenshanQH($order, $menlei = '母门')
    {
        $baseCodeService = new BaseCodeService();
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $dkcailiao = (strpos($order['dkcailiao'], '矩管') !== false) ? '矩管' : '非矩管';
        $huase = (strpos($order['huase'], '平板') !== false) ? '平板' : '非平板';

        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $zhizaobm = M()->query("select gys_name from sell_gys where gys_code='" . $order['operate_plan'] . "'", true);
        $zhizaobm = $zhizaobm[0]['gys_name'];
//        $guigeLength = $baseCodeService->gaoduChange($menkuang,$guigecArr[0],$order['chuanghua'],$order['zhizaobm']);//长度、高度---高度根据规则转换
        $guigeLength = $baseCodeService->gaoduChange($menkuang, $guigecArr[0], $order['chuanghua'], $zhizaobm);//长度、高度---高度根据规则转换
        $guigeLength = empty($guigeLength) ? 0 : $guigeLength;
        $guigeWidth = $guigecArr[1];//宽度
        $where = " menkuang like '%/$menkuang/%' and menshan like '%/$menshan/%' and huase like '%/$huase/%'";
        switch ($menlei) {
            case "母门"://母门高度宽度规则--生成料号
                $lenthRule = "select ($guigeLength+yongliao_mumen) as yongliao_length,liaohao,pinming,guige from bom_tianxin_qh_rule where $where and dkcailiao like '%/$dkcailiao/%' and classify='高度' and $guigeLength BETWEEN interval_start and interval_end";
                $widthRule = "select decode(sign(yongliao_mumen),-1,$guigeWidth+yongliao_mumen,yongliao_mumen) as yongliao_width,liaohao,pinming,guige from bom_tianxin_qh_rule where $where and classify='宽度' and $guigeWidth BETWEEN interval_start and interval_end";
                $lengthYongliao = Db::query($lenthRule, 1);
                $widthYongliao = Db::query($widthRule, 1);
                break;
            case "子门":
                $lenthRule = "select ($guigeLength+yongliao_zimen) as yongliao_length,liaohao,pinming,guige from bom_tianxin_qh_rule where $where and dkcailiao like '%/$dkcailiao/%' and classify='高度' and $guigeLength BETWEEN interval_start and interval_end";
                $widthRule = "select decode(sign(yongliao_zimen),-1,$guigeWidth+yongliao_zimen,yongliao_zimen) as yongliao_width,liaohao,pinming,guige from bom_tianxin_qh_rule where $where and classify='宽度' and $guigeWidth BETWEEN interval_start and interval_end";
                $lengthYongliao = Db::query($lenthRule, 1);
                $widthYongliao = Db::query($widthRule, 1);
                break;
        }
        $lengthYongliao = $lengthYongliao[0];
        $widthYongliao = $widthYongliao[0];
        if (strpos($menshan, '对开门') !== false) {//对开门---母门子门用料宽度/2
            $widthYongliao['yongliao_width'] /= 2;
        }
        $widthYongliao['yongliao_width'] = empty($widthYongliao['yongliao_width']) ? $guigeWidth : $widthYongliao['yongliao_width'];
        $yongliaoRule = array();
        if (!empty($lengthYongliao) && !empty($widthYongliao)) {
            $yongliaoRule['liaohao'] = $lengthYongliao['liaohao'];
            $yongliaoRule['liaohao_guige'] = $lengthYongliao['guige'];
            $yongliaoRule['liaohao_pinming'] = $lengthYongliao['pinming'];
            $yongliaoRule['yongliang'] = round($lengthYongliao['yongliao_length'] * $widthYongliao['yongliao_width'] * 1 / 1000000, 4);//QPA=(规格长度*用料长度*1.1)/1000000,11-21取消10%损耗
            $yongliaoRule['liaohao_danwei'] = '';
            $yongliaoRule['yuanjian_name'] = "填芯($menlei)";
            $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
        }
        return $yongliaoRule;
    }

    public function getMianqib($order, $menlei)
    {
        $column = "";
        if ($menlei == '母门') {
            $column = "mumen,liaohao,pinming,guige";
        } elseif ($menlei == '子门') {
            $column = "zimen,liaohao,pinming,guige";
        }
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $ms = $menshan;
        if (strpos($menshan, '单扇') !== false) {
            $ms = '单扇门';
        } elseif (strpos($menshan, '对开') !== false) {
            $ms = '对开门';
        } elseif (strpos($menshan, '子母') !== false) {
            $ms = '子母门';
        }
        $biaomcl = $order['biaomcl'];
        $biaomiantsyq = $order['biaomiantsyq'];
        $zhizaobm = $order['zhizaobm'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $wide = $guigecArr[1];
        $sql = "select $column from bom_mianqib where menkuang like '%/$menkuang/%' and menshan like '%/$ms/%'
                and biaomcl like '%$biaomcl%' and biaomiantsyq like '%$biaomiantsyq%' and $wide between start_len and end_len and zhizaobm like '%/$zhizaobm/%'";

        $re = Db::query($sql, 1);
        $mianqiYongliao = $re[0];
        $yongliaoRule = array();
        if (!empty($re)) {
            $yongliaoRule['liaohao'] = $mianqiYongliao['liaohao'];
            $yongliaoRule['liaohao_guige'] = $mianqiYongliao['guige'];
            $yongliaoRule['liaohao_pinming'] = $mianqiYongliao['pinming'];
            if ($menlei == '母门') {
                $qpa = empty($mianqiYongliao['mumen']) ? 0 : $mianqiYongliao['mumen'];
            } else {
                $qpa = empty($mianqiYongliao['zimen']) ? 0 : $mianqiYongliao['zimen'];
            }
            $yongliaoRule['yongliang'] = $qpa;
            $yongliaoRule['liaohao_danwei'] = '';
            $yongliaoRule['yuanjian_name'] = "免漆板($menlei)";
            $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
        }
        return $yongliaoRule;
    }

    /*public function getFengbiant($order, $menlei)
    {
        $column = "";
        if ($menlei == '母门') {
            $column = "mumen,liaohao,pinming,guige";
        } elseif ($menlei == '子门') {
            $column = "zimen,liaohao,pinming,guige";
        }
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $ms = $menshan;
        if (strpos($menshan, '单扇') !== false) {
            $ms = '单扇门';
        } elseif(strpos($menshan, '对开') !== false) {
            $ms = '对开门';
        } elseif(strpos($menshan, '子母') !== false) {
            $ms = '子母门';
        }
        $biaomcl = $order['biaomcl'];
        $biaomiantsyq = $order['biaomiantsyq'];
        $zhizaobm = $order['zhizaobm'];
        $dkcailiao = (strpos($order['dkcailiao'],'矩管') !== false)?'矩管':'非矩管';//封边条新增底框材料匹配条件
        $guigecArr = explode('*',$order['guige']);
        if (empty($guigecArr[1])){
            $guigecArr = explode('×',$order['guige']);
        }
        $height = $guigecArr[0];
        $sql = "select $column from bom_fengbiant where menkuang like '%/$menkuang/%' and menshan like '%/$ms/%'
                and biaomcl like '%$biaomcl%' and biaomiantsyq like '%$biaomiantsyq%' and zhizaobm like '%/$zhizaobm/%' and dkcailiao like '%/$dkcailiao/%'";
        $re = Db::query($sql,1);
        $fengbianYongliao = $re[0];
        $yongliaoRule = array();
        if (!empty($re)) {
            $yongliaoRule['liaohao'] = $fengbianYongliao['liaohao'];
            $yongliaoRule['liaohao_guige'] = $fengbianYongliao['guige'];
            $yongliaoRule['liaohao_pinming'] = $fengbianYongliao['pinming'];
            if ($menlei == '母门') {
                $qpa = empty($fengbianYongliao['mumen'])?0:$fengbianYongliao['mumen'];
            } else {
                $qpa = empty($fengbianYongliao['zimen'])?0:$fengbianYongliao['zimen'];
            }
            # 2018-12-04齐河二部子门QPA公式计算
            if ($menlei == '子门' && $zhizaobm == '齐河制造二部' && strpos($ms, '单扇') === false) {
                # 公式：(规格高度+13)/1000*3
//                $yongliaoRule['yongliang'] = round(($height+13)/1000*3,4);
                $yongliaoRule['yongliang'] = round(($height+$qpa)/1000*3,4);
            } else {
                $yongliaoRule['yongliang'] = ($height + $qpa)/1000*2;
            }
            $yongliaoRule['liaohao_danwei'] = '';
            $yongliaoRule['yuanjian_name'] = "封边条($menlei)";
            $yongliaoRule['yuanjian_level'] = "门扇级($menlei)";
        }
        return $yongliaoRule;
    }*/

    public function getFengbiant($order, $menlei)
    {
        $column = "qpa,yongliang,liaohao,pinming,guige,type";
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $ms = $menshan;
        if (strpos($menshan, '单扇') !== false) {
            $ms = '单扇门';
        } elseif (strpos($menshan, '对开') !== false) {
            $ms = '对开门';
        } elseif (strpos($menshan, '子母') !== false) {
            $ms = '子母门';
        }
        $biaomcl = $order['biaomcl'];
        $biaomiantsyq = $order['biaomiantsyq'];
        $zhizaobm = $order['zhizaobm'];
        $dkcailiao = (strpos($order['dkcailiao'], '矩管') !== false) ? '矩管' : '非矩管';//封边条新增底框材料匹配条件
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $sql = "select $column from bom_fengbiant_rule where menkuang like '%/$menkuang/%' and menshan like '%$ms%'
                and biaomcl like '%$biaomcl%' and biaomiantsyq like '%$biaomiantsyq%' and zhizaobm like '%$zhizaobm%' and dkcailiao like '%/$dkcailiao/%' and type like '%$menlei%'";
        $re = Db::query($sql, 1);
        $yongliaoRule = array();
        if (!empty($re)) {
            foreach ($re as $key => $val) {
                $type = $val['type'];
                $temp = array();
                $temp['liaohao'] = $val['liaohao'];
                $temp['liaohao_guige'] = $val['guige'];
                $temp['liaohao_pinming'] = $val['pinming'];
                # QPA计算公式：(规格高度+固定值)/1000*用量
                $temp['yongliang'] = round(($height + $val['qpa']) / 1000 * $val['yongliang'], 4);
                $temp['liaohao_danwei'] = '';
                $temp['yuanjian_name'] = "封边条($type)";
                $temp['yuanjian_level'] = "门扇级($menlei)";
                array_push($yongliaoRule, $temp);
            }
        }
        return $yongliaoRule;
    }

    /**
     * 窗花中式胶条用料规则
     * @param $order
     * @return array
     */
    public function getChuanghuaZhongshiJiaotiao($order)
    {
        $zhizaobm = $order['zhizaobm'];
        $dangci = $order['dang_ci'];
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $guige = $order['guige'];
        $kaixiang = $order['kaixiang'];
        $mkhoudu = $order['mkhoudu'];
        if (strpos($kaixiang, '内') === false) {
            $kaixiang = '外开';
        } else {
            $kaixiang = '内开';
        }
        $chuanghua = $order['chuanghua'];
        $guigecArr = explode('*', $guige);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $guige);
        }
        $guigeLength = $guigecArr[0];
        $guigeWide = $guigecArr[1];

        $sql_chuanghua = "select (MKHEIGHT_RULE || '+$guigeLength')as gaodu,
                                (MKWIDE_RULE ||'+$guigeWide') as wide,liaohao,pinming,guige from BOM_CHUANGHUA_ZHONGSHI_RULE 
                                where  ZHIZAOBM LIKE '%/$zhizaobm/%' and DANGCI like '%/$dangci/%' and MENKUANG like '%/$menkuang/%' and 
                                MENSHAN like '%/$menshan/%' and CHUANGHUA like '%/$chuanghua/%' and KAIXIANG like '%/$kaixiang/%' and MKHOUDU like '%/$mkhoudu/%'
                                and $guigeLength BETWEEN START_HEIGHT and END_HEIGHT ";
        $result = Db::query($sql_chuanghua, true);
        $re = $result[0];
        $zhongshiYongliao = array();
        if (!empty($re)) {
            $zhongshiYongliao['yuanjian_name'] = "中式窗花带玻胶条";
            $zhongshiYongliao['yuanjian_level'] = "成品级";
            $zhongshiYongliao['liaohao'] = $re['liaohao'];
            $zhongshiYongliao['liaohao_guige'] = $re['guige'];
            $zhongshiYongliao['liaohao_pinming'] = $re['pinming'];
            $gaodu = empty($re['gaodu']) ? 0 : eval("return " . $re['gaodu'] . ";");
            $wide = empty($re['wide']) ? 0 : eval("return " . $re['wide'] . ";");
            $zhongshiYongliao['yongliang'] = ($gaodu * 2 + $wide * 1) / 1000;
            $zhongshiYongliao['liaohao_danwei'] = '';
        }
        return $zhongshiYongliao;
    }

    /**
     * PE包装用料规则---成都制造二部
     * @param $order
     */
    public function pebaozhuang($order)
    {
        $dangci = $order['dang_ci'];
        $menkuang = $order['menkuang'];
        $menshan = $order['menshan'];
        $ms = "";
        if (strpos($menshan, '对开')) {
            $ms = '对开门';
        } elseif (strpos($menshan, '子母')) {
            $ms = '子母门';
        } else {
            $ms = $menshan;
        }
        $baozhuangfs = $order['baozhuangfs'];
        $baozhpack = $order['baozhpack'];
        $zhizaobm = $order['zhizaobm'];
        $guige = $order['guige'];
        $guigecArr = explode('*', $guige);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $guige);
        }
        $guigeLength = $guigecArr[0];
        $guigeWide = $guigecArr[1];
        $sql = "select yongliao_length,yongliao_width,cailiao_length,shuliang,liaohao,pinming,guige from bom_pebaozhuang_rule where dangci like '%/$dangci/%' and menkuang like '%/$menkuang/%' and menshan like '%/$ms/%' and
baozhuangfs like '%/$baozhuangfs/%' and baozhpack like '%/$baozhpack/%' and $guigeLength between start_height and end_height 
and $guigeWide between start_width and end_width and zhizaobm like '%/$zhizaobm/%'";
        $re = Db::query($sql, true);
        $yongliao = array();//PE用料规则
        if (!empty($re)) {
            foreach ($re as $key => $val) {
                $peYongliao = array();//PE用料规则
                $peYongliao['yuanjian_name'] = "PE覆膜包装" . ($key + 1);
                $peYongliao['yuanjian_level'] = "成品级";
                $peYongliao['liaohao'] = $val['liaohao'];
                $peYongliao['liaohao_guige'] = $val['guige'];
                $peYongliao['liaohao_pinming'] = $val['pinming'];
                $peYongliao['yongliang'] = round($val['yongliao_length'] / 1000 / $val['cailiao_length'] * $val['shuliang'], 4);
                $peYongliao['liaohao_danwei'] = '';
                array_push($yongliao, $peYongliao);
            }
        }
        return $yongliao;
    }

    public function getMuzhiLiaohaoInfo($orderInfo)
    {
        $hasFuchuang = 1;
        if (strpos($orderInfo['chuanghua'], '无副窗') !== false || $orderInfo['chuanghua'] == '无' || empty($orderInfo['chuanghua'])) {
            $hasFuchuang = 0;
        }
        $orderType = $this->getOrderType($orderInfo['order_type']);
        $muzhiCodeService = new MuzhiCodeService();
        $yuanjianList = $muzhiCodeService->getYuanJianList($orderInfo);//获取元件列表
        switch ($orderType) {
            case '成品':
                $chengpinlh = $muzhiCodeService->getMaterialCode('成品', $orderInfo); //获取成品料号
                $chengpinXiajie = $this->findXiajie($yuanjianList, '成品级');//成品下阶
                DB::startTrans();
                $orderProductMd5 = $orderInfo['order_product_md5'];
                //料号位数不足35位，则errorCode==1
                $hasErrorCode = (strpos($chengpinlh['liaohao'], '$') !== false || strlen($chengpinlh['liaohao']) != 35) ? 1 : 0;
                $userName = $GLOBALS['uname'];
                $nowTime = date('Y-m-d H:i:s');
                $dingdanhao = $orderInfo['oeb01'];
                $xiangci = $orderInfo['oeb03'];
                if (!empty($chengpinlh)) {//有成品则入成品料号表
                    $sql = "insert into bom_chengpin_liaohao(id,liaohao,pinming,guige,order_product_md5,has_error_code,created_at,created_by,dingdanh,xiangci) 
              values(bom_chengpin_liaohao_seq.nextval,'" . $chengpinlh['liaohao'] . "','" . $chengpinlh['liaohao_pinming'] . "','" . $chengpinlh['liaohao_guige'] . "','$orderProductMd5',$hasErrorCode,to_date('$nowTime','yyyy-mm-dd hh24:mi:ss'),'$userName','$dingdanhao','$xiangci') ";
                    M()->execute($sql); //成品料号
                    //如果有门套、门扇则添加到成品下阶
                    $menshan_ms = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '母'); //获取母扇料号

                    $menshan_ms['child'] = $this->getMumenMenshanXiajie($orderInfo, '母门');
                    $menshan_fbt = $this->getMenShanFbt($orderInfo, '母门', '');
                    foreach ($menshan_fbt as $k => $v) {
                        array_push($menshan_ms['child'], $v);
                    }
                    array_push($chengpinXiajie, $menshan_ms);

                    # 如果非单扇门则需要加入子扇料号
                    if (!in_array($orderInfo['menshan'], ['单扇门', '单推门'])) {
                        //成品级门扇子扇
                        $menshan_zs = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '子');//获取门扇子门料号

                        $menshan_zs['child'] = $this->getMumenMenshanXiajie($orderInfo, '子门');
                        $menshan_fbt = $this->getMenShanFbt($orderInfo, '子门', '');
                        foreach ($menshan_fbt as $k => $v) {
                            array_push($menshan_zs['child'], $v);
                        }
                        array_push($chengpinXiajie, $menshan_zs);
                    }
                    $this->insertXiajie($chengpinXiajie, 0, $chengpinlh);//插入成品下阶
                }
                //半品门扇母扇及其下阶料号
                /*$menshan = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '母'); //获取门扇母扇料号
                $menshanmsXiajie = array();//半品门扇母扇下阶
                two_array_merge($menshanmsXiajie, $this->findXiajie($yuanjianList, '门扇级(母门)'));
                if (!empty($menshanmsXiajie)) {
                    $banpinms = $this->insertBanpinLiaohao($menshan, $menshanmsXiajie, $chengpinlh);//插入门扇母扇及下阶
                }*/
                if (!in_array($orderInfo['menshan'], ['单扇门', '单推门'])) {
                    //成品级门扇子扇
                    $menshan_zs = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '子');//获取门扇子门料号
                    $menshanzsXiajie = array();//半品门扇子扇下阶
                    two_array_merge($menshanzsXiajie, $this->findXiajie($yuanjianList, '门扇级(子门)'));
                    if (!empty($menshanmsXiajie)) {
                        $banpinzs = $this->insertBanpinLiaohao($menshan_zs, $menshanzsXiajie, $chengpinlh);//插入门扇子扇及下阶
                    }
                }
                //门套下阶料号---单套板和线条
                //获取门套下阶料号信息,单套板=>门套下阶（上方套板 侧方套板 中方套板 下方套板 线条料号）
                $mentao = $muzhiCodeService->getMaterialCode('门套', $orderInfo); //获取门套料号
                $shangtaoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '上方套板');//获取上方套板料号
                $shangtaoban['child'] = $this->getMuZhiMenTao($orderInfo, '上方套板');
                $cetaoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '侧方套板');//获取侧方套板料号
                $cetaoban['child'] = $this->getMuZhiMenTao($orderInfo, '侧方套板');

                $xiantiao_shang = $muzhiCodeService->getMaterialCode('线条', $orderInfo, '上方线条');//获取上方线条料号
                $xiantiao_shang['child'] = $this->MuMenXt($orderInfo, '上边');

                $xiantiao_ce = $muzhiCodeService->getMaterialCode('线条', $orderInfo, '侧方线条');//获取侧方线条料号
                $xiantiao_ce['child'] = $this->MuMenXt($orderInfo, '侧边');

                $mentaoXiajie = array($shangtaoban, $cetaoban, $xiantiao_shang, $xiantiao_ce);//门套下阶

                if ($hasFuchuang) {//有副窗才有中方套板
                    $zhongtaoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '中方套板');//获取中方套板料号
                    $zhongtaoban['child'] = $this->getMuZhiMenTao($orderInfo, '中档套板');
                    array_push($mentaoXiajie, $zhongtaoban);
                    $zhongfang = $this->getMenTaoFbt($orderInfo, '中档');
                    foreach ($zhongfang as $k => $v) {
                        array_push($mentaoXiajie, $v);
                    }
                }
                $shangfang = $this->getMenTaoFbt($orderInfo, '上方');
                if (!empty($shangfang)) {
                    foreach ($shangfang as $k => $v) {
                        array_push($mentaoXiajie, $v);
                    }
                }
                $cefang = $this->getMenTaoFbt($orderInfo, '侧方');
                if (!empty($cefang)) {
                    foreach ($cefang as $k => $v) {
                        array_push($mentaoXiajie, $v);
                    }
                }
                two_array_merge($mentaoXiajie, $this->findXiajie($yuanjianList, '门套级'));
                $banpinlh = $this->insertBanpinLiaohao($mentao, $mentaoXiajie, $chengpinlh);//插入门套及门套下阶料号
                DB::commit();
                $chengpinlhID = M()->query("select bom_chengpin_liaohao_seq.currval as id from dual", true);
                $chengpinlhID = $chengpinlhID[0]['id'];
                $chengpinlh['id'] = $chengpinlhID;
                $column = getColumnName('bom_banpin_liaohao');
                $banpinlh = M()->query("select $column from bom_banpin_liaohao where chengpin_liaohao_id=$chengpinlhID ", true);
                $liaohaoInfo = ['chengpinlh' => $chengpinlh, 'banpinlh' => $banpinlh];
                return $liaohaoInfo;
                break;

            case '窗套'://订单类别含窗套则只生成窗套及其下阶料号
                $chengpinlh = $muzhiCodeService->getMaterialCode('成品', $orderInfo); //获取成品料号
                $chuangtaoXiajie = array();
                $chuangtao = $muzhiCodeService->getMaterialCode('窗套', $orderInfo); //获取窗套料号
                //获取窗套下阶料号---下方套板和下方线条
                array_push($chuangtaoXiajie, $chuangtao);
                $xiataoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '下方套板');//获取下方套板料号
                array_push($chuangtaoXiajie, $xiataoban);
                $xiantiao_xia = $muzhiCodeService->getMaterialCode('线条', $orderInfo, '下方线条');//获取下方线条料号
                array_push($chuangtaoXiajie, $xiantiao_xia);

                $mentao_fbt = $this->getMenTaoFbt($orderInfo, '下方');
                if (!empty($mentao_fbt)) {
                    foreach ($mentao_fbt as $k => $v) {
                        array_push($chuangtaoXiajie, $v);
                    }
                }
                $menshan_fbt = $this->getMenShanFbt($orderInfo, '母门', '下方');
                if (!empty($menshan_fbt)) {
                    foreach ($menshan_fbt as $k => $v) {
                        array_push($chuangtaoXiajie, $v);
                    }
                }
                $menshan_fbt = $this->getMenShanFbt($orderInfo, '子门', '下方');
                if (!empty($menshan_fbt)) {
                    foreach ($menshan_fbt as $k => $v) {
                        array_push($chuangtaoXiajie, $v);
                    }
                }
                $xiantiao3 = $this->MuMenXt($orderInfo, '下边');
                if (!empty($xiantiao3)) {
                    foreach ($xiantiao3 as $k => $v) {
                        array_push($chuangtaoXiajie, $v);
                    }
                }
                $banpinlh = $this->insertBanpinLiaohao($chuangtao, $chuangtaoXiajie, []);//插入半品门框及下阶料号
                return [
                    'chengpinlh' => $chengpinlh,
                    'banpinlh' => $banpinlh,
                ];
                break;
            case '门套'://订单类别含窗套则只生成窗套及其下阶料号
                //获取门套下阶料号信息,单套板=>门套下阶（上方套板 侧方套板 中方套板 下方套板 线条料号）
                $mentao = $muzhiCodeService->getMaterialCode('门套', $orderInfo); //获取门套料号
                $shangtaoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '上方套板');//获取上方套板料号
                $shangtaoban['child'] = $this->getMuZhiMenTao($orderInfo, '上方套板');
                $cetaoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '侧方套板');//获取侧方套板料号
                $cetaoban['child'] = $this->getMuZhiMenTao($orderInfo, '侧方套板');
                $xiantiao_shang = $muzhiCodeService->getMaterialCode('线条', $orderInfo, '上方线条');//获取上方线条料号
//                $xiantiao_shang['child'] = $this->MuMenXt($orderInfo, '上边');
                $xiantiao_ce = $muzhiCodeService->getMaterialCode('线条', $orderInfo, '侧方线条');//获取侧方线条料号
//                $xiantiao_ce['child'] = $this->MuMenXt($orderInfo, '侧边');

                $mentaoFbt_ce = $this->getMenTaoFbt($orderInfo, '侧方');
                $mentaoFbt_shang = $this->getMenTaoFbt($orderInfo, '上方');
                $mentaoXiajie = array($shangtaoban, $cetaoban, $xiantiao_shang, $xiantiao_ce);//门套下阶
                if (!empty($mentaoFbt_ce)) {
                    array_push($mentaoXiajie, $mentaoFbt_ce);
                }
                if (!empty($mentaoFbt_shang)) {
                    array_push($mentaoXiajie, $mentaoFbt_shang);
                }

                if ($hasFuchuang) {//有副窗才有中方套板
                    $zhongtaoban = $muzhiCodeService->getMaterialCode('单套板', $orderInfo, '中方套板');//获取中方套板料号
                    $zhongtaoban['child'] = $this->getMuZhiMenTao($orderInfo, '中档套板');
                    array_push($mentaoXiajie, $zhongtaoban);

                    $mentaoFbt_zhong = $this->getMenTaoFbt($orderInfo, '中档');
                    if (!empty($mentaoFbt_zhong)) {
                        array_push($mentaoXiajie, $mentaoFbt_zhong);
                    }
                }
                two_array_merge($mentaoXiajie, $this->findXiajie($yuanjianList, '门套级'));
                $banpinlh = $this->insertBanpinLiaohao($mentao, $mentaoXiajie, []);//插入门套及门套下阶料号

                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinlh,
                ];
                break;

            case '门扇'://订单类别含窗套则只生成窗套及其下阶料号
                //半品门扇母扇及其下阶料号
                $menshan = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '母'); //获取门扇母扇料号
                $menshan['child'] = $this->getMumenMenshanXiajie($orderInfo, '母门');
                $ms = $this->getMenShanFbt($orderInfo, '母门', '');
                foreach ($ms as $k => $v) {
                    array_push($menshan['child'], $v);
                }
                $menshanmsXiajie = array();//半品门扇母扇下阶
                two_array_merge($menshanmsXiajie, $this->findXiajie($yuanjianList, '门扇级(母门)'));
                $banpinms = $this->insertBanpinLiaohao($menshan, $menshanmsXiajie, []);//插入门扇母扇及下阶
                if (!in_array($orderInfo['menshan'], ['单扇门', '单推门'])) {
                    //成品级门扇子扇
                    $menshan_zs = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '子');//获取门扇子门料号
                    $menshan_zs['child'] = $this->getMumenMenshanXiajie($orderInfo, '子门');
                    $ms = $this->getMenShanFbt($orderInfo, '子门', '');
                    foreach ($ms as $k => $v) {
                        array_push($menshan_zs['child'], $v);
                    }
                    $menshanzsXiajie = array();//半品门扇子扇下阶
                    two_array_merge($menshanzsXiajie, $this->findXiajie($yuanjianList, '门扇级(子门)'));
                    $banpinzs = $this->insertBanpinLiaohao($menshan_zs, $menshanzsXiajie, []);//插入门扇子扇及下阶
                }
                return [
                    'chengpinlh' => "",
                    'banpinlh' => empty($banpinzs) ? $banpinms : array_merge($banpinms, $banpinzs),
                ];
                break;
            case '母扇'://订单类别含窗套则只生成窗套及其下阶料号
                //半品门扇母扇及其下阶料号
                $menshan = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '母'); //获取门扇母扇料号
                $menshan['child'] = $this->getMumenMenshanXiajie($orderInfo, '母门');
                $ms = $this->getMenShanFbt($orderInfo, '母门', '');
                foreach ($ms as $k => $v) {
                    array_push($menshan['child'], $v);
                }
                $menshanmsXiajie = array();//半品门扇母扇下阶
                two_array_merge($menshanmsXiajie, $this->findXiajie($yuanjianList, '门扇级(母门)'));
                $banpinms = $this->insertBanpinLiaohao($menshan, $menshanmsXiajie, []);//插入门扇母扇及下阶
                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinms,
                ];
                break;
            case '子扇'://订单类别含窗套则只生成窗套及其下阶料号
                //半品门扇子扇及其下阶料号
                $menshan_zs = $muzhiCodeService->getMaterialCode('门扇', $orderInfo, '', '子');//获取门扇子门料号
                $menshan_zs['child'] = $this->getMumenMenshanXiajie($orderInfo, '子门');
                $ms = $this->getMenShanFbt($orderInfo, '子门', '');
                foreach ($ms as $k => $v) {
                    array_push($menshan_zs['child'], $v);
                }
                $menshanzsXiajie = array();//半品门扇子扇下阶
                two_array_merge($menshanzsXiajie, $this->findXiajie($yuanjianList, '门扇级(子门)'));
                $banpinzs = $this->insertBanpinLiaohao($menshan_zs, $menshanzsXiajie, []);//插入门扇子扇及下阶
                return [
                    'chengpinlh' => "",
                    'banpinlh' => $banpinzs,
                ];
                break;
        }
    }

    public function getMuZhiMenTao($order, $type = '')
    {
        $dept = $order['zhizaobm'];
        $product_category = $order['door_style'];
        $door_structure = $order['menkuangcz'];
        $door_wall = $order['mkhoudu'];
        $doorcover_thickness = $order['maoyan'];
        $doorcover_structure = $order['menkuangyq'];
        $door_pattern = $order['menkuang'];
        $surface_mode = $order['biaomcl'];
        $surface_pattern = $order['biao_pai'];
        $xiantiao_ys = $order['dkcailiao'];
        $dang_ci = $order['dang_ci'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = "type,material_num,product_name,spec,spec_lw,yongliao_length,yongliao_width,material_length,material_width";
        if ($type == '侧方套板') {
            $pipeizhi = $height;
        } else {
            $pipeizhi = $width;
        }
        $where = '';
        $line_style = '';
        if (in_array($product_category, ['强化木门', '转印木门'])) {
            $line_style = ($product_category == '强化木门') ? $xiantiao_ys : $dang_ci;
            $where = " and line_style like '%$line_style%'";
        }
        $sql = "select $colum from bom_mumen_mentao_rule where dept LIKE '%$dept%' and product_category like '%$product_category%' and door_structure like '%$door_structure%' and door_wall like '%$door_wall%' and doorcover_thickness like '%$doorcover_thickness%' and doorcover_structure like '%$doorcover_structure%' and door_pattern like '%$door_pattern%' and surface_mode like '%$surface_mode%' and surface_pattern like '%$surface_pattern%' and type like '%$type%' and $pipeizhi BETWEEN  hw_left and hw_right $where";
        $res = Db::query($sql, 1);
        $muzhimentao = array();
        if (!empty($res)) {
            foreach ($res as $key => $val) {
                $temp = array();
                //判断门套类型
                if (strpos($product_category, '复合实木') !== false) {
                    if (strpos($type, '侧方套板') !== false) {
                        # QPA计算公式：QPA=（（规格长度+用料长度）*用料宽度）/（材料长度*材料宽度）*2
                        $temp['yongliang'] = round(($height + $val['yongliao_length']) * $val['yongliao_width'] / ($val['material_length'] * $val['material_width']) * 2, 4);
                    } else {
                        # QPA=（（规格宽度+用料长度）*用料宽度）/（材料长度*材料宽度）
                        $temp['yongliang'] = round(($width + $val['yongliao_length']) * $val['yongliao_width'] / ($val['material_length'] * $val['material_width']), 4);
                    }
                } elseif (strpos($product_category, '集成实木') !== false) {
                    if (strpos($type, '侧方套板') !== false) {
                        # QPA计算公式：QPA=（用料长度*用料宽度）/（材料长度*材料宽度）* 2
                        $temp['yongliang'] = round(($val['yongliao_length'] * $val['yongliao_width']) / ($val['material_length'] * $val['material_width']) * 2, 4);
                    } else {
                        # QPA计算公式：QPA=（用料长度*用料宽度）/（材料长度*材料宽度）
                        $temp['yongliang'] = round(($val['yongliao_length'] * $val['yongliao_width']) / ($val['material_length'] * $val['material_width']), 4);
                    }
                } elseif (strpos($product_category, '强化木门') !== false) {
                    if (strpos($type, '侧方套板') !== false) {
                        # QPA计算公式：QPA=（（规格长度+用料长度）*用料宽度）/（材料长度*材料宽度）*2
                        $temp['yongliang'] = round(($height + $val['yongliao_length']) * $val['yongliao_width'] / ($val['material_length'] * $val['material_width']) * 2, 4);
                    } elseif (strpos($type, '上方套板') !== false || strpos($type, '中档套板') !== false) {
                        # QPA计算公式：QPA=（（规格宽度+用料长度）*用料宽度）/（材料长度*材料宽度）
                        $temp['yongliang'] = round(($width + $val['yongliao_length']) * $val['yongliao_width'] / ($val['material_length'] * $val['material_width']), 4);
                    } elseif (strpos($type, '下方套板') !== false) {
                        # QPA计算公式：QPA=（用料长度*用料宽度）/（材料长度*材料宽度）* 2
                        $temp['yongliang'] = round(($val['yongliao_length'] * $val['yongliao_width']) / ($val['material_length'] * $val['material_width']) * 2, 4);
                    }
                } elseif (strpos($product_category, '转印木门') !== false) {
                    if (strpos($type, '侧方套板') !== false) {
                        # QPA计算公式：QPA=（（规格长度+用料长度）*用料宽度）/（材料长度*材料宽度）*2
                        $temp['yongliang'] = round(($height + $val['yongliao_length']) * $val['yongliao_width'] / ($val['material_length'] * $val['material_width']) * 2, 4);
                    } elseif (strpos($type, '上方套板') !== false || strpos($type, '中档套板') !== false) {
                        # QPA计算公式：QPA=（（规格宽度+用料长度）*用料宽度）/（材料长度*材料宽度）
                        $temp['yongliang'] = round(($width + $val['yongliao_length']) * $val['yongliao_width'] / ($val['material_length'] * $val['material_width']), 4);
                    } elseif (strpos($type, '下方套板') !== false) {
                        # QPA计算公式：QPA=（用料长度*用料宽度）/（材料长度*材料宽度）
                        $temp['yongliang'] = round(($val['yongliao_length'] * $val['yongliao_width']) / ($val['material_length'] * $val['material_width']), 4);
                    }
                }
                $temp['yuanjian_name'] = $val['type'];
                $temp['liaohao'] = $val['material_num'];
                $temp['liaohao_guige'] = $val['spec'];
                $temp['liaohao_pinming'] = $val['product_name'];
                $temp['yuanjian_level'] = $type . "级";
                array_push($muzhimentao, $temp);
            }
        }
        return $muzhimentao;
    }

    /**
     * 根据订单类别获取产品类型：成品，门套，线条，门扇，母扇，子扇，窗套
     * @param $ordertype
     */
    public function getOrderType($ordertype)
    {
        if (strpos($ordertype, '线条') !== false) {
            return '线条';
        } elseif (strpos($ordertype, '门套') !== false) {
            return '门套';
        } elseif (strpos($ordertype, '窗套') !== false) {
            return '窗套';
        } elseif (strpos($ordertype, '门扇') !== false) {
            return '门扇';
        } elseif (strpos($ordertype, '母扇') !== false) {
            return '母扇';
        } elseif (strpos($ordertype, '子扇') !== false) {
            return '子扇';
        } else {
            return '成品';
        }
    }

    public function getChuanghuaXiajie($chuanghualh, $orderInfo)
    {
        $zhizaobm = $orderInfo['zhizaobm'];//制造部门
        $chuanghua = $orderInfo['chuanghua'];//窗花类型
        $chuanghua_guige = explode('*', $chuanghualh['liaohao_guige']);
        $chuanghua_height = $chuanghua_guige[0];//窗花的高度
        $chuanghua_width = $chuanghua_guige[1];//窗花的宽度
        if (strpos($chuanghua, '不锈钢') !== false) {//不锈钢窗花独有下阶用料
            $sql = "select yongliao,cailiao_width,cailiao_houdu,cailiao_midu,num,liaohao,pinming,guige,qpa,type,gaokuandu from  bom_chuanghua_bxg_rule where dept like '%$zhizaobm%' and chuanghua_type like '%$chuanghua%' 
and $chuanghua_height between start_height and end_height and $chuanghua_width between start_width and end_width order by sort";
            $data = Db::query($sql, true);
            $yongliao = array();
            foreach ($data as $kdata => $vdata) {
                $yongliao[$kdata]['liaohao'] = $vdata['liaohao'];
                $yongliao[$kdata]['liaohao_guige'] = $vdata['guige'];
                $yongliao[$kdata]['liaohao_pinming'] = $vdata['pinming'];
                if (!empty($vdata['qpa'])) {//自带qpa的下阶用料数据
                    $yongliao[$kdata]['yongliang'] = $vdata['qpa'];
                } else {
                    if ($vdata['type'] == '红色装饰管') {//QPA=下料规格/材料长度*数量
                        $yongliao[$kdata]['yongliang'] = $vdata['gaokuandu'] / $vdata['cailiao_width'] * $vdata['num'];
                    } else {
                        if (in_array($vdata['type'], ['上下边框', '中档'])) {
                            $kuandu = $chuanghua_width;//这两种类型计算时取窗花宽度
                        } else {
                            $kuandu = $chuanghua_height;//其余类型取窗花高度
                        }
                        if ($chuanghua == '不锈钢窗花B02' && in_array($vdata['type'], ['中档', '圆管B', '圆管C', '圆管D'])) {
                            $kuandu = $kuandu / 2;
                        }
                        $yongliao[$kdata]['yongliang'] = round(($kuandu + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);//带公式QPA---QPA=（窗花高度+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                    }
                }
                $yongliao[$kdata]['liaohao_danwei'] = '';
                $yongliao[$kdata]['yuanjian_name'] = $vdata['type'];
                $yongliao[$kdata]['yuanjian_level'] = '窗花级';
            }
            return $yongliao;
        } elseif (strpos($chuanghua, '常规') !== false) {//常规窗花独有下阶用料
            $sql = "select yongliao,cailiao_width,cailiao_houdu,cailiao_midu,num,liaohao,pinming,guige,type,chuanghua_height,chuanghua_width,a_value,jiaodu,c_value,n_value,yongliao1,yongliao2 from  bom_chuanghua_cg_rule where dept like '%$zhizaobm%' and chuanghua_type like '%$chuanghua%' 
and $chuanghua_height between start_height and end_height and $chuanghua_width between start_width and end_width order by sort";

            $data = Db::query($sql, true);
            $yongliao = array();
            foreach ($data as $kdata => $vdata) {
                $yongliao[$kdata]['liaohao'] = $vdata['liaohao'];
                $yongliao[$kdata]['liaohao_guige'] = $vdata['guige'];
                $yongliao[$kdata]['liaohao_pinming'] = $vdata['pinming'];
                if (in_array($vdata['type'], ['上下边框', '中档', '横筋01', '横筋02'])) {
                    //带公式QPA---QPA=（窗花宽度+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                    $kuandu = empty($vdata['chuanghua_width']) ? $chuanghua_width : $vdata['chuanghua_width'];//这两种类型计算时取窗花宽度
                    $yongliao[$kdata]['yongliang'] = round(($kuandu + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                } elseif (in_array($vdata['type'], ['左右边框', '竖筋01', '竖筋02', '竖筋03'])) {
                    //带公式QPA---QPA=（窗花高度+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                    $kuandu = empty($vdata['chuanghua_height']) ? $chuanghua_height : $vdata['chuanghua_height'];//这两种类型计算时取窗花高度
                    if ($vdata['type'] == '竖筋02') {
                        //QPA=固定值*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round($kuandu * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        $yongliao[$kdata]['yongliang'] = round(($kuandu + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }

                }
                //花筋1的计算公式
                if ($vdata['type'] == '花筋1') {
                    //窗花高度<=326 并且是齐河制造部的数据，按照公式1计算
                    if (($chuanghua_height >= 166 && $chuanghua_height <= 375) && $zhizaobm == '齐河制造部') {
                        //QPA=（A值/sin（角度）+（窗花高度+C值）+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($vdata['a_value'] / sin(deg2rad($vdata['jiaodu'])) + ($chuanghua_height + $vdata['c_value']) + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        //QPA=（A值/sin（角度）+C值+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($vdata['a_value'] / sin(deg2rad($vdata['jiaodu'])) + $vdata['c_value'] + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }
                }

                //花筋2的计算公式
                if ($vdata['type'] == '花筋2') {
                    //窗花高度<=326 并且是齐河制造部的数据，按照公式1计算
                    if (($chuanghua_height >= 166 && $chuanghua_height <= 375) && $zhizaobm == '齐河制造部') {
                        //QPA=((A值+（窗花宽度+用料1-A值*n值）/2）/sin（角度）+（窗花高度+C值）+用料2)*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round((($vdata['a_value'] + ($chuanghua_width + $vdata['yongliao1'] - $vdata['a_value'] * $vdata['n_value']) / 2) / sin(deg2rad($vdata['jiaodu'])) + ($chuanghua_height + $vdata['c_value']) + $vdata['yongliao2']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        //QPA=((A值+（窗花宽度+用料1-A值*n值）/2）/sin（角度）+C值+用料2)*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round((($vdata['a_value'] + ($chuanghua_width + $vdata['yongliao1'] - $vdata['a_value'] * $vdata['n_value']) / 2) / sin(deg2rad($vdata['jiaodu'])) + $vdata['c_value'] + $vdata['yongliao2']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }
                }
                //花筋3的计算公式
                if ($vdata['type'] == '花筋3') {
                    //窗花高度<=326 并且是齐河制造部的数据，按照公式1计算
                    if (($chuanghua_height >= 166 && $chuanghua_height <= 375) && $zhizaobm == '齐河制造部') {
                        //QPA=（（（窗花宽度+用料1-A值*n值）/2）/sin（角度）+（窗花高度+C值）+用料2）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(((($chuanghua_width + $vdata['yongliao1'] - $vdata['a_value'] * $vdata['n_value']) / 2) / sin(deg2rad($vdata['jiaodu'])) + ($chuanghua_height + $vdata['c_value']) + $vdata['yongliao2']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        //QPA=（（（窗花宽度+用料1-A值*n值）/2）/sin（角度）+C值+用料2）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(((($chuanghua_width + $vdata['yongliao1'] - $vdata['a_value'] * $vdata['n_value']) / 2) / sin(deg2rad($vdata['jiaodu'])) + $vdata['c_value'] + $vdata['yongliao2']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }
                }
                //斜筋1的计算公式
                if ($vdata['type'] == '斜筋1') {
                    //QPA=（（（窗花宽度+用料1-A值*n值/2）/sin（角度）+用料2）*材料宽度*材料厚度*材料密度/1000000*数量
                    //0220斜筋1变更为：QPA=（（（窗花宽度+用料1-A值*n值）/2）/sin（角度）+用料2）*材料宽度*材料厚度*材料密度/1000000*数量
                    $yongliao[$kdata]['yongliang'] = round(((($chuanghua_width + $vdata['yongliao1'] - $vdata['a_value'] * $vdata['n_value']) / 2) / sin(deg2rad($vdata['jiaodu'])) + $vdata['yongliao2']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                }
                //斜筋2的计算公式
                if ($vdata['type'] == '斜筋2') {
                    //QPA=((A值+（窗花宽度+用料1-A值*n值）/2）/sin（角度）+用料2)*材料宽度*材料厚度*材料密度/1000000*数量
                    $yongliao[$kdata]['yongliang'] = round((($vdata['a_value'] + ($chuanghua_width + $vdata['yongliao1'] - $vdata['a_value'] * $vdata['n_value']) / 2) / sin(deg2rad($vdata['jiaodu'])) + $vdata['yongliao2']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                }
                //圆花
                if ($vdata['type'] == '圆花') {
                    $yongliao[$kdata]['yongliang'] = $vdata['num'];
                }
                $yongliao[$kdata]['liaohao_danwei'] = '';
                $yongliao[$kdata]['yuanjian_name'] = $vdata['type'];
                $yongliao[$kdata]['yuanjian_level'] = '窗花级';
            }
            return $yongliao;
        } elseif (strpos($chuanghua, '中式') !== false) {//中式窗花独有下阶用料
            //去除窗花后面的单玻双玻在进行用料规则匹配
            $chuanghua_zhongshi = $this->ConvertChuanghua($chuanghua);
            $sql = "select yongliao,cailiao_width,cailiao_houdu,cailiao_midu,num,liaohao,pinming,guige,type,yongliao1,yongliao2,yongliao3 from bom_chuanghua_zs_rule where dept like '%$zhizaobm%' and chuanghua_type like '%$chuanghua_zhongshi%' 
and $chuanghua_height between start_height and end_height and $chuanghua_width between start_width and end_width order by sort";
            $data = Db::query($sql, true);
            $yongliao = array();
            foreach ($data as $kdata => $vdata) {
                $yongliao[$kdata]['liaohao'] = $vdata['liaohao'];
                $yongliao[$kdata]['liaohao_guige'] = $vdata['guige'];
                $yongliao[$kdata]['liaohao_pinming'] = $vdata['pinming'];
                if (in_array($vdata['type'], ['上下边框', '左右边框', '横筋01', '横筋02'])) {
                    //横筋01---中式窗花Z02计算公式差异
                    if ($chuanghua_zhongshi == '中式窗花Z02' && $vdata['type'] == '横筋01') {
                        //QPA=窗花宽度*用料*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round($chuanghua_width * $vdata['yongliao'] * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($zhizaobm == '齐河制造部' && $chuanghua_zhongshi == '中式窗花Z02' && $vdata['type'] == '横筋02' && ($chuanghua_height >= 200 && $chuanghua_height <= 399) && ($chuanghua_width >= 260 && $chuanghua_width <= 299)) {
                        //QPA=（窗花宽度*0.25+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round($chuanghua_width * 0.25 * $vdata['yongliao'] * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($zhizaobm == '齐河制造部' && $chuanghua_zhongshi == '中式窗花Z02' && $vdata['type'] == '横筋02' && ($chuanghua_height >= 200 && $chuanghua_height <= 599) && ($chuanghua_width >= 260 && $chuanghua_width <= 1699)) {
                        //QPA=（窗花宽度*0.2+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round($chuanghua_width * 0.2 * $vdata['yongliao'] * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($zhizaobm != '齐河制造部' && $chuanghua_zhongshi == '中式窗花Z02' && $vdata['type'] == '横筋02' && ($chuanghua_height >= 200 && $chuanghua_height <= 599) && ($chuanghua_width >= 300 && $chuanghua_width <= 1699)) {
                        //QPA=（窗花宽度*0.3+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_width * 0.3 + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($chuanghua_zhongshi == '中式窗花Z01' && $vdata['type'] == '横筋02' && ($chuanghua_height >= 200 && $chuanghua_height <= 599) && ($chuanghua_width >= 1000 && $chuanghua_width <= 10000)) {
                        //QPA=（窗花宽度/2+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_width / 2 + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($vdata['type'] == '左右边框') {//左右边框QPA取值高度
                        //QPA=（窗花宽度+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        //QPA=（窗花宽度+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_width + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }
                } elseif (in_array($vdata['type'], ['竖筋01', '竖筋02'])) {
                    if (abs($vdata['yongliao']) < 1) {
                        //QPA=（窗花高度*用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height * $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($chuanghua_zhongshi == '中式窗花Z01' && $vdata['type'] == '竖筋02') {
                        //QPA=（窗花高度/2+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height / 2 + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($zhizaobm == '齐河制造部' && $vdata['type'] == '竖筋02' && ($chuanghua_height >= 200 && $chuanghua_height <= 399)) {
                        //QPA=（窗花高度*0.25+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height * 0.25 + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($zhizaobm == '齐河制造部' && $vdata['type'] == '竖筋02' && ($chuanghua_height >= 400 && $chuanghua_height <= 599)) {
                        //QPA=（窗花高度*0.15+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height * 0.15 + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } elseif ($zhizaobm != '齐河制造部' && $vdata['type'] == '竖筋02' && ($chuanghua_height >= 200 && $chuanghua_height <= 599) && ($chuanghua_width >= 300 && $chuanghua_width <= 1699)) {
                        //QPA=（窗花高度*0.35+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height * 0.35 + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        //QPA=（窗花高度+用料）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(($chuanghua_height + $vdata['yongliao']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }
                } elseif (in_array($vdata['type'], ['斜筋01', '斜筋02'])) {
                    if ($vdata['num'] == 2) {
                        //QPA=（(（（窗花宽度+用料1）/2）^2+（窗花高度+用料2）^2)^(1/2)+用料3）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round((sqrt((pow(($chuanghua_width + $vdata['yongliao1']) / 2, 2) + pow($chuanghua_height + $vdata['yongliao2'], 2))) + $vdata['yongliao3']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    } else {
                        //QPA=（(（窗花宽度+用料1）^2+（窗花高度+用料2）^2)^(1/2)+用料3）*材料宽度*材料厚度*材料密度/1000000*数量
                        $yongliao[$kdata]['yongliang'] = round(sqrt((pow($chuanghua_width + $vdata['yongliao1'], 2) + pow($chuanghua_height + $vdata['yongliao2'], 2)) + $vdata['yongliao3']) * $vdata['cailiao_width'] * $vdata['cailiao_houdu'] * $vdata['cailiao_midu'] / 1000000 * $vdata['num'], 4);
                    }
                } elseif (in_array($vdata['type'], ['方花', '圆花'])) {//方花圆花的QPA直接等于其数量值
                    $yongliao[$kdata]['yongliang'] = $vdata['num'];
                }
                $yongliao[$kdata]['liaohao_danwei'] = '';
                $yongliao[$kdata]['yuanjian_name'] = $vdata['type'];
                $yongliao[$kdata]['yuanjian_level'] = '窗花级';
            }
            return $yongliao;
        } elseif (strpos($chuanghua, '封闭式窗花') !== false) {
            $menshan_type = $orderInfo['menshan'];
            $yongliao = array();
            $operate_plan = $orderInfo['operate_plan'];
            if (in_array($operate_plan, ['DS10', 'DS16'])) {
                $sql = "select YONGLIAO1,YONGLIAO2,CAILIAO_WIDTH,CAILIAO_HD,CAILIAO_MD,SHULIANG,QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME from BOM_CHUANGHUA_FBS where dept_name like '%$zhizaobm%' and chuanghua_type like '%$chuanghua%' and $chuanghua_width BETWEEN WIDTH_START and WIDTH_END AND $chuanghua_height BETWEEN HEIGHT_START and HEIGHT_END and type=2 ";
            } else {
                $sql = "select QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME from BOM_CHUANGHUA_FBS where dept_name like '%$zhizaobm%' and chuanghua_type like '%$chuanghua%' and $chuanghua_width BETWEEN WIDTH_START and WIDTH_END  and type=1 and sort = 1";
                $fengwozhi = Db::query("select QPA,LIAOHAO,PINMING,GUIGE,TYPE,SORT,SHEET_NAME from BOM_CHUANGHUA_FBS where dept_name like '%$zhizaobm%' and chuanghua_type like '%$chuanghua%' and menshan_type like '%$menshan_type%' and type =1 and sort =2", true);
            }
            $data = Db::query($sql, true);
            if (!empty($fengwozhi)) {
                foreach ($fengwozhi as $kk => $vv) {
                    array_push($data, $vv);
                }
            }
            foreach ($data as $k => $v) {
                $temp = array();
                if ($v['type'] == 1) {
                    $temp[$k]['yongliang'] = $v['qpa'];
                } elseif ($v['type'] == 2) {
                    if ($v['sort'] == 1) {
                        $temp[$k]['yongliang'] = round(($chuanghua_width + $v['yongliao1']) * $v['cailiao_width'] * $v['cailiao_hd'] * $v['cailiao_md'] / 1000000 * $v['shuliang'], 4);
                    } elseif ($v['sort'] == 2 || $v['sort'] == 3) {
                        $temp[$k]['yongliang'] = round(($chuanghua_height + $v['yongliao1']) * $v['cailiao_width'] * $v['cailiao_hd'] * $v['cailiao_md'] / 1000000 * $v['shuliang'], 4);
                    } elseif ($v['sort'] == 4) {
                        $temp[$k]['yongliang'] = round(($chuanghua_height + $v['yongliao1']) * ($chuanghua_width + $v['yongliao2']) * $v['cailiao_hd'] * $v['cailiao_md'] / 1000000 * $v['shuliang'], 4);
                    }
                }
                $temp[$k]['liaohao'] = $v['liaohao'];
                $temp[$k]['liaohao_guige'] = $v['guige'];
                $temp[$k]['liaohao_pinming'] = $v['pinming'];
                $temp[$k]['liaohao_danwei'] = '';
                $temp[$k]['yuanjian_name'] = $v['sheet_name'];
                $temp[$k]['yuanjian_level'] = '窗花级';
                array_push($yongliao, $temp[$k]);
            }
            return $yongliao;
        }
    }

    public function ConvertChuanghua($chuanghua)
    {
        if (strpos($chuanghua, '中式窗花Z01') !== false) {
            $str = '中式窗花Z01';
        } elseif (strpos($chuanghua, '中式窗花Z02') !== false) {
            $str = '中式窗花Z02';
        } elseif (strpos($chuanghua, '中式窗花Z03') !== false) {
            $str = '中式窗花Z03';
        } elseif (strpos($chuanghua, '中式窗花Z04') !== false) {
            $str = '中式窗花Z04';
        } elseif (strpos($chuanghua, '中式窗花Z05') !== false) {
            $str = '中式窗花Z05';
        } elseif (strpos($chuanghua, '中式窗花Z06') !== false) {
            $str = '中式窗花Z06';
        } else {
            $str = $chuanghua;
        }
        return $str;
    }

    public function getMumenMenshanXiajie($order, $menshan_type = '')
    {
        $menshanXiajie = array();
        $msmb = $this->getMenShanMb($order, $menshan_type);
        $mmmf = $this->getMenShanMf($order, $menshan_type);
        $mmxl = $this->getMenShanXl($order, $menshan_type);
        foreach ($msmb as $k => $v) {
            array_push($menshanXiajie, $v);
        }
        foreach ($mmmf as $k => $v) {
            array_push($menshanXiajie, $v);
        }
        foreach ($mmxl as $k => $v) {
            array_push($menshanXiajie, $v);
        }
        return $menshanXiajie;
    }

    public function getMenTaoFbt($order, $mentao_type)
    {
        $menTaoFbt = array();
        $dept = $order['zhizaobm'];
        $product_type = $order['door_style'];
        $dingdan_type = $order['order_type'];
        $mentao_hd = $order['maoyan'];
        $mentao_ys = $order['menkuang'];
        $xiantiao_ys = $order['dkcailiao'];
        $biaomian_fs = $order['biaomcl'];
        $biaomian_hw = $order['biao_pai'];
        $biaomian_yq = $order['biaomiantsyq'];

        $chuanghua = $order['chuanghua'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = 'yongliao_gs,yongliao_cd,guige_l_m,yongliao_l_m,yongliao_g_m,guige_l_z,yongliao_l_z,yongliao_g_z,liaohao,pingming,guige,sheet_name,qpa_mumen,qpa_zimen,sheet_name,sort';
        if ($mentao_type == '侧方') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 1 and sort in (1,2,3,4) and dept_name like '%$dept%' and product_type like '%$product_type%' and mentao_hd like '%$mentao_hd%' and mentao_ys like '%$mentao_ys%' and xiantiao_ys like '%$xiantiao_ys%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and biaomian_yq like '%$biaomian_yq%' and sheet_name like '%$mentao_type%'  and $height between guige_start and guige_end ";
        } elseif ($mentao_type == '中档') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 1 and sort in (1,2,3,4) and dept_name like '%$dept%' and product_type like '%$product_type%' and mentao_hd = '$mentao_hd' and mentao_ys like '%$mentao_ys%' and xiantiao_ys like '%$xiantiao_ys%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and biaomian_yq like '%$biaomian_yq%' and sheet_name like '%$mentao_type%' and chuanghua like '%$chuanghua%' and $width between guige_start and guige_end ";
        } elseif ($mentao_type == '上方' || $mentao_type == '下方') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 1 and sort in (1,2,3,4) and dept_name like '%$dept%' and product_type like '%$product_type%' and mentao_hd = '$mentao_hd' and mentao_ys like '%$mentao_ys%' and xiantiao_ys like '%$xiantiao_ys%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and biaomian_yq like '%$biaomian_yq%' and sheet_name like '%$mentao_type%'  and $height between guige_start and guige_end ";
        }
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                if ($value['sort'] == 1 || $value['sort'] == 2 || $value['sort'] == 3 || $value['sort'] == 4) {
                    if ($value['sort'] == 1) {
                        #QPA=（规格长度+用料长度）/1000*用料根数
                        $temp['yongliang'] = round(($height + $value['yongliao_cd']) / 1000 * $value['yongliao_gs'], '4');
                    } else {
                        #QPA=（规格宽度+用料长度）/1000*用料根数
                        $temp['yongliang'] = round(($width + $value['yongliao_cd']) / 1000 * $value['yongliao_gs'], '4');
                    }
                }
                $temp['yuanjian_name'] = $value['sheet_name'];
                $temp['liaohao'] = $value['liaohao'];
                $temp['liaohao_guige'] = $value['guige'];
                $temp['liaohao_pinming'] = $value['pingming'];
                $temp['yuanjian_level'] = "门套级";
                array_push($menTaoFbt, $temp);
            }
        }
        return $menTaoFbt;
    }

    public function getMenShanFbt($order, $type, $postion)
    {
        $where = $postion == '下边' ? ' and sort in (7)' : ' and sort in (5,6)';
        $MenShanFbt = array();
        $dept = $order['zhizaobm'];
        $product_type = $order['door_style'];
        $dingdan_type = $order['order_type'];
        $dangci = $order['dang_ci'];
        $biaomian_fs = $order['biaomcl'];
        $biaomian_hw = $order['biao_pai'];
        $biaomian_yq = $order['biaomiantsyq'];
        $menshan_type = $order['menshan'];
        $chuanghua = $order['chuanghua'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = 'guige_l_m,yongliao_l_m,yongliao_g_m,guige_l_z,yongliao_l_z,yongliao_g_z,liaohao,pingming,guige,sheet_name,qpa_mumen,qpa_zimen,sheet_name,sort';
        $sql = " select $colum from bom_mumen_menshan_rule where type = 1 and dept_name like '%$dept%' and product_type like '%$product_type%' and menshan_hd like '%$dangci%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and biaomian_yq like '%$biaomian_yq%' and menshan_type like '%$menshan_type%' and chuanghua like '%$chuanghua%'  $where  and $height between guige_start and guige_end ";
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                if ($type == '母门') {
                    if ($value['sort'] == 5 || $value['sort'] == 6 || $value['sort'] == 7) {
                        if ($value['sort'] == 5) {
                            #QPA=（规格长度+用料长度）/1000*用料根数
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) / 1000 * $value['yongliao_g_m'], '4');
                        } elseif ($value['sort'] == 6 || $value['sort'] == 7) {
                            #QPA=（规格宽度+用料长度）/1000*用料根数
                            $temp['yongliang'] = round(($width + $value['yongliao_l_m']) / 1000 * $value['yongliao_g_m'], '4');
                        }
                    }
                }
                if ($type == '子门') {
                    if ($value['sort'] == 5 || $value['sort'] == 6 || $value['sort'] == 7) {
                        if ($value['sort'] == 5) {
                            #QPA=（规格长度+用料长度）/1000*用料根数
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) / 1000 * $value['yongliao_g_z'], '4');
                        } elseif ($value['sort'] == 6 || $value['sort'] == 7) {
                            #QPA=（规格宽度+用料长度）/1000*用料根数
                            $temp['yongliang'] = round(($width + $value['yongliao_l_z']) / 1000 * $value['yongliao_g_z'], '4');
                        }
                    }
                }
                $temp['yuanjian_name'] = $value['sheet_name'];
                $temp['liaohao'] = $value['liaohao'];
                $temp['liaohao_guige'] = $value['guige'];
                $temp['liaohao_pinming'] = $value['pingming'];
                $temp['yuanjian_level'] = "门扇级" . '(' . $type . ')';
                array_push($MenShanFbt, $temp);
            }
        }
        return $MenShanFbt;
    }

    public function getMenShanMb($order, $type)
    {
        $muzhiMenshan = array();
        $dept = $order['zhizaobm'];
        $product_type = $order['door_style'];
        $menlei_jg = $order['menkuangcz'];
        $dang_ci = $order['dang_ci'];
        $menshan_type = $order['menshan'];
        $menshan_hs = $order['huase'];
        $biaomian_fs = $order['biaomcl'];
        $biaomian_hw = $order['biao_pai'];
        $chuanghua = $order['chuanghua'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = 'sort,product_type,yongliao_l_m,yongliao_k_m,cailiao_l_m,cailiao_k_m,liaohao,pingming,guige,sheet_name,qpa_mumen,qpa_zimen,guige_kdxsm,cailiao_kdxsm';
        //处理木门门扇面板用料规则
        $sql = " select $colum from bom_mumen_menshan_rule where type = 2 and dept_name like '%$dept%' and product_type like '%$product_type%' and menlei_jg like '%$menlei_jg%' and dangci = '$dang_ci' and menshan_type like '%$menshan_type%' and menshan_hs like '%$menshan_hs%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and chuanghua like '%$chuanghua%' and $height between guige_start and guige_end and $width BETWEEN kuan_start and kuan_end ";
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                if ($type == '母门') {
                    if (!empty($value['qpa_mumen'])) {
                        $temp['yongliang'] = $value['qpa_mumen'];
                    } else {
                        #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）/材料长度/材料宽度*材料系数
                        $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * ($width * $value['guige_kdxsm'] + $value['yongliao_k_m']) / $value['cailiao_l_m'] / $value['cailiao_k_m'] * $value['cailiao_kdxsm'], '4');
                    }
                }
                if ($type == '子门') {
                    if (!empty($value['qpa_zimen'])) {
                        $temp['yongliang'] = $value['qpa_zimen'];
                    } else {
                        #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）/材料长度/材料宽度*材料系数
                        $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * ($width * $value['guige_kdxsz'] + $value['yongliao_k_z'] / $value['cailiao_l_z'] / $value['cailiao_k_z'] * $value['cailiao_kdxsz']), '4');
                    }
                }
                $yuanjian_name = $value['sort'] == 1 ? "实木、转印木门面板" : "强化木门面板";
                $temp['yuanjian_name'] = $yuanjian_name;
                $temp['liaohao'] = $value['liaohao'];
                $temp['liaohao_guige'] = $value['guige'];
                $temp['liaohao_pinming'] = $value['pingming'];
                $temp['yuanjian_level'] = "门扇级" . '(' . $type . ')';
                array_push($muzhiMenshan, $temp);
            }
        }
        return $muzhiMenshan;
    }

    public function getMenShanMf($order, $type)
    {
        $menShanMf = array();
        $dept = $order['zhizaobm'];
        $product_type = $order['door_style'];
        $dang_ci = $order['dang_ci'];
        $menshan_type = $order['menshan'];
        $menshan_hs = $order['huase'];
        $chuanghua = $order['chuanghua'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = 'sheet_name,sort,guige_cd,yongliao_k_m,yongliao_l_m,yongliao_k_z,yongliao_l_z,cailiao_cd,
        liaohao,pingming,guige,sheet_name,sort,guige_kdxsm,cailiao_kdxsm,guige_kdxsz,cailiao_kdxsz,cailiao_l_m';
        //处理木门门扇面板用料规则
        if ($type == '母门') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 3  and sort in(1,2,5) and dept_name like '%$dept%' and product_type like '%$product_type%'  and dangci = '$dang_ci' and menshan_type like '%$menshan_type%' and menshan_hs like '%$menshan_hs%'  and chuanghua like '%$chuanghua%' and $height between guige_start and guige_end ";
        } elseif ($type == '子门') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 3  and sort in(3,4,5) and dept_name like '%$dept%' and product_type like '%$product_type%'  and dangci = '$dang_ci' and menshan_type like '%$menshan_type%' and menshan_hs like '%$menshan_hs%'  and chuanghua like '%$chuanghua%' and $height between guige_start and guige_end ";
        }

        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                if ($type == '母门') {
                    if ($value['sort'] == 1 || $value['sort'] == 2) {
                        if (!empty($value['guige_cd'])) {
                            #2100/材料长度/系数
                            $temp['yongliang'] = round($value['guige_cd'] / $value['cailiao_cd'] / $value['cailiao_kdxsm'], '4');
                        } else {
                            #QPA=（规格长度+用料长度（母门锁方））/材料长度/12
                            $suofang = round(($height + $value['yongliao_l_m']) / $value['cailiao_cd'] / $value['cailiao_kdxsm'], '4');
                            $jiaofang = round(($height + $value['yongliao_k_m']) / $value['cailiao_cd'] / $value['cailiao_kdxsm'], '4');
                            $temp['yongliang'] = ($value['sort'] == 1) ? $jiaofang : $suofang;
                        }
                    } elseif ($value['sort'] == 5) {
                        #QPA=（规格宽度*0.7+用料长度）/材料长度/12
                        $temp['yongliang'] = round(($width * $value['guige_kdxsm'] + $value['yongliao_l_m']) / $value['cailiao_l_m'] / $value['cailiao_kdxsm'], '4');
                    }
                }
                if ($type == '子门') {
                    if ($value['sort'] == 3 || $value['sort'] == 4) {
                        if (!empty($value['guige_cd'])) {
                            #2100/材料长度/系数
                            $temp['yongliang'] = round($value['guige_cd'] / $value['cailiao_cd'] / $value['cailiao_kdxsm'], '4');
                        } else {
                            #QPA=（规格长度+用料长度（子门铰方））/材料长度/系数
                            $jiaofang = round(($height + $value['yongliao_k_z']) / $value['cailiao_cd'] / $value['cailiao_kdxsm'], '4');
                            $suofang = round(($height + $value['yongliao_l_z']) / $value['cailiao_cd'] / $value['cailiao_kdxsm'], '4');
                            $temp['yongliang'] = $value['sort'] == 3 ? $jiaofang : $suofang;
                        }
                    } elseif ($value['sort'] == 5) {
                        #QPA=（规格宽度*0.7+用料长度）/材料长度
                        $temp['yongliang'] = round(($width * $value['guige_kdxsm'] + $value['yongliao_k_z']) / $value['cailiao_l_m'] / $value['cailiao_kdxsm'], '4');
                    }
                }
                $sheet_name = $value['sheet_name'];
                switch ($value['sort']) {
                    case 1:
                        $yuanjian_name = '木门木方长边骨架(母门铰方)';
                        break;
                    case 2:
                        $yuanjian_name = '木门木方长边骨架(母门锁方)';
                        break;
                    case 3:
                        $yuanjian_name = '木门木方长边骨架(子门铰方)';
                        break;
                    case 4:
                        $yuanjian_name = '木门木方长边骨架(子门铰方)';
                        break;
                    case 5:
                        $yuanjian_name = '木门木方短边骨架';
                        break;
                }
                $temp['yuanjian_name'] = $yuanjian_name;
                $temp['liaohao'] = $value['liaohao'];
                $temp['liaohao_guige'] = $value['guige'];
                $temp['liaohao_pinming'] = $value['pingming'];
                $temp['yuanjian_level'] = "门扇级" . '(' . $type . ')';
                array_push($menShanMf, $temp);
            }
        }
        return $menShanMf;
    }

    public function getMenShanXl($order, $type)
    {
        $menShanXl = array();
        $dept = $order['zhizaobm'];
        $product_type = $order['door_style'];
        $dang_ci = $order['dang_ci'];
        $menshan_type = $order['menshan'];
        $menshan_hs = $order['huase'];
        $chuanghua = $order['chuanghua'];
        $menshan_yq = $order['menshancz'];
        $tianchong = $order[''];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = 'tianchong,product_type,guige_cd,guige_k_m,yongliao_l_m,yongliao_k_m,cailiao_l_m,cailiao_k_m,guige_l_z,guige_k_z,yongliao_l_z, yongliao_k_z,cailiao_l_z,cailiao_k_z,
        liaohao,pingming,guige,sheet_name,sort,guige_kdxsm,cailiao_kdxsm,guige_kdxsz,cailiao_kdxsz';
        //处理木门门扇面板用料规则
        $sql = " select $colum from bom_mumen_menshan_rule where type = 4 and dept_name like '%$dept%' and product_type like '%$product_type%'  and dangci = '$dang_ci' and menshan_type like '%$menshan_type%' and menshan_hs like '%$menshan_hs%'  and chuanghua like '%$chuanghua%' and menshan_yq like '%$menshan_yq%' and tianchong like '%$tianchong%' and $height between guige_start and guige_end ";
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                if ($type == '母门') {
                    if ($value['sort'] == 1) {
                        if (!empty($value['guige_k_m'])) {
                            #QPA=（规格长度+用料长度）*74/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * 74 / $value['cailiao_l_m'] / $value['cailiao_k_m'], '4');
                        } else {
                            #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）*0.7*2/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * ($width * $value['guige_kdxsm'] + $value['yongliao_k_m']) * $value['cailiao_kdxsm'] / $value['cailiao_l_m'] / $value['cailiao_k_m'], '4');
                        }
                    } elseif ($value['sort'] == 2) {
                        if (!empty($value['guige_k_m'])) {
                            #QPA=（规格长度+用料长度）*74*5.52/1000000
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * 74 * 5.52 / 1000000, '4');
                        } else {
                            #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）*0.7*2.25/1000000
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * ($width * $value['guige_kdxsm'] + $value['yongliao_k_m']) * 0.7 * 2.25 / 1000000, '4');
                        }
                    } elseif ($value['sort'] == 3) {
                        if (!empty($value['guige_k_m'])) {
                            #QPA=（规格长度+用料长度）*74/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * 74 / $value['cailiao_l_m'] / $value['cailiao_k_m'], '4');
                        } else {
                            #QPA=（规格长度+用料长度）*（规格宽度*0.7+用料宽度）/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_m']) * ($width * $value['guige_kdxsm'] + $value['yongliao_k_m']) / $value['cailiao_l_m'] / $value['cailiao_k_m'], '4');
                        }
                    }
                }
                if ($type == '子门') {
                    if ($value['sort'] == 1) {
                        if (!empty($value['guige_k_z'])) {
                            #QPA=（规格长度+用料长度）*74/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * 74 / $value['cailiao_l_z'] / $value['cailiao_k_z'], '4');
                        } else {
                            #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）*0.7*2/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * ($width * $value['guige_kdxsz'] + $value['yongliao_k_z']) * $value['cailiao_kdxsz'] / $value['cailiao_l_z'] / $value['cailiao_k_z'], '4');
                        }
                    } elseif ($value['sort'] == 2) {
                        if (!empty($value['guige_k_z'])) {
                            #QPA=（规格长度+用料长度）*74*5.52/1000000
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * 74 * 5.52 / 1000000, '4');
                        } else {
                            #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）*0.7*2.25/1000000
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * ($width * $value['cailiao_kdxsm'] + $value['yongliao_k_z']) * 0.7 * 2.25 / 1000000, '4');
                        }
                    } elseif ($value['sort'] == 3) {
                        if (!empty($value['guige_k_z'])) {
                            #QPA=（规格长度+用料长度）*74/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * 74 / $value['cailiao_l_z'] / $value['cailiao_k_z'], '4');
                        } else {
                            #QPA=（规格长度+用料长度）*（规格宽度*0.5+用料宽度）/材料长度/材料宽度
                            $temp['yongliang'] = round(($height + $value['yongliao_l_z']) * ($width * $value['cailiao_kdxsm'] + $value['yongliao_k_z']) / $value['cailiao_l_z'] / $value['cailiao_k_z'], '4');
                        }
                    }
                }
                $temp['yuanjian_name'] = $value['sheet_name'] . '(' . $value['tianchong'] . ')';
                $temp['liaohao'] = $value['liaohao'];
                $temp['liaohao_guige'] = $value['guige'];
                $temp['liaohao_pinming'] = $value['pingming'];
                $temp['yuanjian_level'] = "门扇级" . '(' . $type . ')';
                array_push($menShanXl, $temp);
            }
        }
        return $menShanXl;
    }

    public function MuMenXt($order, $type)
    {
        $mumenxt = array();
        $dept = $order['zhizaobm'];
        $dingdan_type = $order['order_type'];
        $product_type = $order['door_style'];
        $menlei_jg = $order['menkuangcz'];
        $xiantiao_type = $order['jiaolian'];
        $xiantiao_ys = $order['dkcailiao'];
        $xiantiao_jg = $order['biaojian'];
        $biaomian_fs = $order['biaomcl'];
        $biaomian_hw = $order['biao_pai'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        $colum = 'product_type,liaohao,pingming,guige,sheet_name,sort,qpa';
        if ($type == '侧边') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 5 and dept_name like '%$dept%' and product_type like '%$product_type%' and sort = 1 and menlei_jg like '%$menlei_jg%' and xiantiao_type like '%$xiantiao_type%' and xiantiao_ys like '%$xiantiao_ys%' and xiantiao_jg like '%$xiantiao_jg%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and  $height between guige_start and guige_end ";
            $res = Db::query($sql, 1);
            if (!empty($res)) {
                foreach ($res as $key => $value) {
                    $temp = array();
                    $temp['yongliang'] = $value['qpa'];
                    $temp['yuanjian_name'] = '木门侧边线条';
                    $temp['liaohao'] = $value['liaohao'];
                    $temp['liaohao_guige'] = $value['guige'];
                    $temp['liaohao_pinming'] = $value['pingming'];
                    $temp['yuanjian_level'] = $type . "线条级";
                    array_push($mumenxt, $temp);
                }
            }
        } elseif ($type == '上边') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 5 and dept_name like '%$dept%' and product_type like '%$product_type%' and sort = 2 and menlei_jg like '%$menlei_jg%' and xiantiao_type like '%$xiantiao_type%' and xiantiao_ys like '%$xiantiao_ys%' and xiantiao_jg like '%$xiantiao_jg%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and  $width between guige_start and guige_end ";
            $res = Db::query($sql, 1);
            if (!empty($res)) {
                foreach ($res as $key => $value) {
                    $temp = array();
                    $temp['yongliang'] = $value['qpa'];
                    $temp['yuanjian_name'] = '木门上边线条';
                    $temp['liaohao'] = $value['liaohao'];
                    $temp['liaohao_guige'] = $value['guige'];
                    $temp['liaohao_pinming'] = $value['pingming'];
                    $temp['yuanjian_level'] = $type . "线条级";
                    array_push($mumenxt, $temp);
                }
            }
        } elseif ($type == '下边') {
            $sql = " select $colum from bom_mumen_menshan_rule where type = 5 and dept_name like '%$dept%' and dingdan_type like '%$dingdan_type%' and  product_type like '%$product_type%' and sort = 3 and menlei_jg like '%$menlei_jg%' and xiantiao_type like '%$xiantiao_type%' and xiantiao_ys like '%$xiantiao_ys%' and xiantiao_jg like '%$xiantiao_jg%' and biaomian_fs like '%$biaomian_fs%' and biaomian_hw like '%$biaomian_hw%' and  $width between guige_start and guige_end ";
            $res = Db::query($sql, 1);
            if (!empty($res)) {
                foreach ($res as $key => $value) {
                    $temp = array();
                    $temp['yongliang'] = $value['qpa'];
                    $temp['yuanjian_name'] = '木门下边线条';
                    $temp['liaohao'] = $value['liaohao'];
                    $temp['liaohao_guige'] = $value['guige'];
                    $temp['liaohao_pinming'] = $value['pingming'];
                    $temp['yuanjian_level'] = $type . "线条级";
                    array_push($mumenxt, $temp);
                }
            }
        }
        return $mumenxt;
    }

    public function getNaihcLiaohaoInfo($orderInfo)
    {

        $orderType = $this->getNhcOrderType($orderInfo['order_type']);
        $naihcCodeService = new NaihcCodeService();
        $yuanjianList = $naihcCodeService->getNaihcYuanJianList($orderInfo);  //获取耐火窗元件列表
        switch ($orderType) {
            case '成品':
                $chengpinlh = $naihcCodeService->getNaihcMaterialCode('成品', $orderInfo); //获取耐火窗成品料号
                $chengpinXiajie = $this->findXiajie($yuanjianList, '成品级');//成品下阶
                DB::startTrans();
                $orderProductMd5 = $orderInfo['order_product_md5'];
                //料号位数不足35位，则errorCode==1
                $hasErrorCode = (strpos($chengpinlh['liaohao'], '$') !== false || strlen($chengpinlh['liaohao']) != 35) ? 1 : 0;
                $userName = $GLOBALS['uname'];
                $nowTime = date('Y-m-d H:i:s');
                $dingdanhao = $orderInfo['oeb01'];
                $xiangci = $orderInfo['oeb03'];
                if (!empty($chengpinlh)) {     //如果有成品则产生成品下阶
                    $sql = "insert into bom_chengpin_liaohao(id,liaohao,pinming,guige,order_product_md5,has_error_code,created_at,created_by,dingdanh,xiangci) 
              values(bom_chengpin_liaohao_seq.nextval,'" . $chengpinlh['liaohao'] . "','" . $chengpinlh['liaohao_pinming'] . "','" . $chengpinlh['liaohao_guige'] . "','$orderProductMd5',$hasErrorCode,to_date('$nowTime','yyyy-mm-dd hh24:mi:ss'),'$userName','$dingdanhao','$xiangci') ";
                    M()->execute($sql);

                    //如果有半品窗框、半品扇框则加入成品下阶
                    $banpin_ck = $naihcCodeService->getNaihcMaterialCode('半品窗框', $orderInfo);
                    $banpin_ck['child'] = array();
                    array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框横框', ''));
                    array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框竖框', ''));
                    array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框横档', ''));
                    array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框立柱', ''));
                    array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '转换框横框', ''));
                    array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '转换框竖框', ''));
                    array_push($chengpinXiajie, $banpin_ck);      //窗框加入成品下阶

                    $banpin_sk = $naihcCodeService->getNaihcMaterialCode('半品扇框', $orderInfo);
                    $banpin_sk['child'] = array();
                    array_push($banpin_sk['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '', '扇框横框'));
                    array_push($banpin_sk['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '', '扇框竖框'));

                    array_push($chengpinXiajie, $banpin_sk);
                    $this->insertXiajie($chengpinXiajie, 0, $chengpinlh);//插入成品下阶
                }
                //半品窗框下阶
                $banpin_ck = $naihcCodeService->getNaihcMaterialCode('半品窗框', $orderInfo);
                $banpin_ck_xiajie = array();
                $banpin_ck['child'] = array();
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框横框', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框竖框', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框横档', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框立柱', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '转换框横框', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '转换框竖框', ''));
                two_array_merge($banpin_ck_xiajie, $this->findXiajie($yuanjianList, '半品窗框级'));
                $this->insertBanpinLiaohao($banpin_ck, $banpin_ck_xiajie, []);

                //半品扇框下阶
                $banpin_sk = $naihcCodeService->getNaihcMaterialCode('半品扇框', $orderInfo);
                $banpin_sk_xiajie = array();
                $banpin_sk['child'] = array();
                array_push($banpin_sk['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '', '扇框横框'));
                array_push($banpin_sk['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '', '扇框竖框'));

                two_array_merge($banpin_sk_xiajie, $this->findXiajie($yuanjianList, '半品扇框级'));
                $this->insertBanpinLiaohao($banpin_sk, $banpin_sk_xiajie, []);

                DB::commit();
                $chengpinlhID = M()->query("select bom_chengpin_liaohao_seq.currval as id from dual", true);
                $chengpinlhID = $chengpinlhID[0]['id'];
                $chengpinlh['id'] = $chengpinlhID;
                $column = getColumnName('bom_banpin_liaohao');
                $banpinlh = M()->query("select $column from bom_banpin_liaohao where chengpin_liaohao_id=$chengpinlhID ", true);
                $liaohaoInfo = [
                    'chengpinlh' => $chengpinlh,
                    'banpinlh' => $banpinlh
                ];
                return $liaohaoInfo;
                break;
            case '窗框':
                $chengpinlh = $naihcCodeService->getNaihcMaterialCode('成品', $orderInfo);
                $banpin_ck = $naihcCodeService->getNaihcMaterialCode('半品窗框', $orderInfo);
                $banpin_ck_xiajie = array();
                $banpin_ck['child'] = array();
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框横框', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框竖框', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框横档', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '窗框立柱', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '转换框横框', ''));
                array_push($banpin_ck['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '转换框竖框', ''));
                two_array_merge($banpin_ck_xiajie, $this->findXiajie($yuanjianList, '半品窗框级'));
                $banpinlh = $this->insertBanpinLiaohao($banpin_ck, $banpin_ck_xiajie, []);
                return [
                    'chengpinlh' => $chengpinlh,
                    'banpinlh' => $banpinlh,
                ];
                break;
            case '扇框':
                $chengpinlh = $naihcCodeService->getNaihcMaterialCode('成品', $orderInfo);
                $banpin_sk = $naihcCodeService->getNaihcMaterialCode('半品扇框', $orderInfo);
                $banpin_sk_xiajie = array();
                $banpin_sk['child'] = array();
                array_push($banpin_sk['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '', '扇框横框'));
                array_push($banpin_sk['child'], $naihcCodeService->getNaihcMaterialCode('耐火窗单框', $orderInfo, '', '扇框竖框'));
                two_array_merge($banpin_sk_xiajie, $this->findXiajie($yuanjianList, '半品扇框级'));
                $banpinlh = $this->insertBanpinLiaohao($banpin_sk, $banpin_sk_xiajie, []);
                return [
                    'chengpinlh' => $chengpinlh,
                    'banpinlh' => $banpinlh
                ];
                break;
        }
    }

    public function getNhcOrderType($orderType)
    {
        if (strpos($orderType, '成品') !== false) {
            return '成品';
        } elseif (strpos($orderType, '窗框') !== false) {
            return '窗框';
        } elseif (strpos($orderType, '扇框') !== false) {
            return '扇框';
        } elseif (strpos($orderType, '单框') !== false) {
            return '单框';
        } else {
            return '成品';
        }
    }

    public function getGlassXiaJie($order)
    {
        $boli = array();
        $dept = $order['zhizaobm'];
        $menkuang = $order['menkuang'];
        $dangci = $order['dang_ci'];
        $chuanghua = $order['chuanghua'];
        $kaixiang = $order['kaixiang'];
        $menshan = $order['menshan'];
        $mkhoudu = $order['mkhoudu'];
        $huase = $order['huase'];
        $order_db = $order['order_db'];
            $mskaikong = $order['mskaikong'];
        $guigecArr = explode('*', $order['guige']);
        if (empty($guigecArr[1])) {
            $guigecArr = explode('×', $order['guige']);
        }
        $height = $guigecArr[0];
        $width = $guigecArr[1];
        #玻璃规则
        $colum = 'height_rule,width_rule,boli_houdu,qpa1';
        $kaixiangGlass = strpos($kaixiang, '内') !== false ? '内开' : '外开';
        $sql = " select $colum from bom_boli_rule where dept_name like '%$dept%' and menkuang like '%$menkuang%' and dangci like '%$dangci%' and chuanghua like '%$chuanghua%' and kaixiang like '%$kaixiangGlass%' and menshan like '%$menshan%' and kuanghou like '%$mkhoudu%' and $height BETWEEN height_start and height_end";
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                $temp['yongliang'] = $value['qpa1'];
                $temp['yuanjian_name'] = '玻璃';
                $heightR =$value['height_rule'];
                $height1 = $height + eval("return $heightR;");  #字符串表达式需要转换计算
                $widthR = $value['width_rule'];
                $width1 = $width + eval("return $widthR;");   #字符串表达式需要转换计算
                $temp['liaohao'] = $this->getGlassLiaoHao($chuanghua, $height1, $width1, $value['boli_houdu'], $order_db);
                $temp['liaohao_guige'] = $height1 . '*' . $width1 . '*' . $value['boli_houdu'];
                $temp['liaohao_pinming'] = $this->getGlassPinMing($chuanghua, $order_db);
                $temp['yuanjian_level'] = "成品级";
                array_push($boli, $temp);
            }
        }
        #四开玻璃
        $colum = 'ch_xiao,ch_da,boli_houdu,qpa1,qpa2';
        $sikaiGuige = $height . '×' . $width;
        $sql = " select $colum from bom_boli_rule where dept_name like '%$dept%' and menkuang like '%$menkuang%' and menshan like '%$menshan%' and chuanghua like '%$chuanghua%' and xinghao like '%$sikaiGuige%'";
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                $temp['yongliang'] = $value['qpa1'];
                $temp['yuanjian_name'] = '四开玻璃(小窗花)';
                $temp['liaohao_pinming'] = $this->getGlassPinMing($chuanghua, $order_db);
                $temp['yuanjian_level'] = "成品级";
                $chArr = explode('×', $value['ch_xiao']);
                $ch_height = $chArr[0];
                $ch_width = $chArr[1];
                $temp['liaohao'] = $this->getGlassLiaoHao($chuanghua, $ch_height, $ch_width, $value['boli_houdu'], $order_db);
                $temp['liaohao_guige'] = $ch_height . '*' . $ch_width . '*' . $value['boli_houdu'];
                array_push($boli, $temp);  #小窗花料号
                #产生大窗花料号
                $chArr = explode('×', $value['ch_da']);
                $ch_height = $chArr[0];
                $ch_width = $chArr[1];
                $temp['liaohao'] = $this->getGlassLiaoHao($chuanghua, $ch_height, $ch_width, $value['boli_houdu'], $order_db);
                $temp['yongliang'] = $value['qpa2'];
                $temp['yuanjian_name'] = '四开玻璃(大窗花)';
                $temp['liaohao_guige'] = $ch_height . '*' . $ch_width . '*' . $value['boli_houdu'];
                array_push($boli, $temp);  #大窗花料号
            }
        }

        #门花玻璃
        if (strpos($chuanghua, '无') !== false || empty($chuanghua)) {
            $chuanghua1 = '无副窗';
        } else {
            $chuanghua1 = '带副窗';
        }
        $colum = 'boli_height,boli_width_m,boli_width_z,boli_width_mz,boli_width_zz,boli_houdu,qpa1,boli_type,xishu1,xishu2';
        $sql = " select $colum from bom_boli_rule where dept_name like '%$dept%' and huase like '%$huase%' and menshan like '%$menshan%' and kaixiang like '%$kaixiang%' and chuanghua like '%$chuanghua1%'  and $height BETWEEN height_start and height_end and $width BETWEEN width_start and width_end";
        $res = Db::query($sql, 1);
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $temp = array();
                $temp['yongliang'] = $value['qpa1'];
                $height1 = $value['boli_height'];
                $temp['yuanjian_level'] = "成品级";
                $temp['liaohao_pinming'] = $this->getGlassPinMing($chuanghua, $order_db);
                if (strpos($menshan, '对开') !== false) {
                    #对开门
                    if ($value['xishu1'] > 0) {
                        $width1 = $value['boli_width_m'];
                    } else {
                        $width1 = round($width / 2, 4) + $value['xishu1'];
                    }
                    $temp['liaohao'] = $this->getGlassLiaoHao('钢化', $height1, $width1, $value['boli_houdu'], $order_db);
                    $temp['yuanjian_name'] = '门花玻璃(母门)';
                    $temp['liaohao_guige'] = $height1 . '*' . $width1 . '*' . $value['boli_houdu'];
                    array_push($boli, $temp);
                    $temp['yuanjian_name'] = '门花玻璃(子门)';
                    array_push($boli, $temp);
                } else {
                    #四开门
                    $width1 = round($width / 4, 4) + $value['xishu1'];
                    $width2 = round($width / 4, 4) + $value['xishu2'];
                    $temp['liaohao'] = $this->getGlassLiaoHao('钢化', $height1, $width1, $value['boli_houdu'], $order_db);
                    $temp['yuanjian_name'] = '门花玻璃(母门)';
                    $temp['liaohao_guige'] = $height1 . '*' . $width1 . '*' . $value['boli_houdu'];
                    array_push($boli, $temp);  #母门 料号
                    $temp['yuanjian_name'] = '门花玻璃(子门)';  # 子门料号
                    array_push($boli, $temp);
                    #处理母子门 子子门
                    $temp['liaohao'] = $this->getGlassLiaoHao('钢化', $height1, $width2, $value['boli_houdu'], $order_db);
                    $temp['yuanjian_name'] = '门花玻璃(母子门)';
                    $temp['liaohao_guige'] = $height1 . '*' . $width2 . '*' . $value['boli_houdu'];
                    array_push($boli, $temp);
                    $temp['yuanjian_name'] = '门花玻璃(子子门)';
                    array_push($boli, $temp);
                }
            }
        }
        #观察孔规则
        $colum = 'qpa1,qpa2,liaohao,pinming,guige';
        $sql = "select $colum from bom_boli_rule where dept_name like '%$dept%' and dangci like '%$dangci%' and menshan like '%$menshan%' and kaixiang like '%$mskaikong%'";
        $observHole = Db::query($sql, 1);
        if (!empty($observHole)) {
            foreach ($observHole as $k => $v) {
                $temp = array();
                $temp['yuanjian_name'] = '观察孔';
                $temp['yongliang'] = $value['qpa1'];
                $temp['yuanjian_level'] = "成品级";
                $temp['liaohao_pinming'] = $v['pinming'];
                $temp['liaohao'] = $v['liaohao'];
                $temp['liaohao_guige'] = $v['guige'];
                if (strpos($menshan, '对开门') !== false) {
                    $temp['yuanjian_name'] = '观察孔(母门)';
                    array_push($boli, $temp);
                    $temp['yuanjian_name'] = '观察孔(子门)';
                    $temp['yongliang'] = $value['qpa2'];
                    array_push($boli, $temp);
                } else {
                    array_push($boli, $temp);
                }
            }
        }
        return $boli;
    }

    public function getGlassLiaoHao($glassType, $glassHeight, $glassWidth, $glassHoudu, $order_db = '')
    {
        $fixed = 'KPPAQ';
        if (strpos($glassType, '钢化') !== false) {
            $fixed .= 'GHBL';
        } else {
            if ($order_db == 'M8') {
                $fixed .= 'FHBL';
            } else {
                $fixed .= 'PTBL';
            }
        }
        $fixed .= str_pad($glassHeight, 4, '0', STR_PAD_LEFT);
        $fixed .= str_pad($glassWidth, 4, '0', STR_PAD_LEFT);
        $fixed .= str_pad($glassHoudu * 10, 3, '0', STR_PAD_LEFT);
        return $fixed;
    }

    public function getGlassPinMing($glassType, $order_db)
    {
        if (strpos($glassType, '钢化') != false) {
            $pinming = '钢化玻璃';
        } else {
            if ($order_db == 'M8') {
                $pinming = '防火玻璃';
            } else {
                $pinming = '普通玻璃';
            }
        }
        return $pinming;
    }
}
