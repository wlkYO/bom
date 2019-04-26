<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/20
 * Time: 16:03
 */
namespace app\api\controller;

use app\api\service\LiaohaoService;
use think\Db;
class DownloadExcel extends Liaohao {
    public $hang ;
    public $style = array();
    public $strCsv ;
    public $zhuliaohao;
    public function downloadExcel($searchString='',$item='',$sdate='',$edate='',$zhizaobm=''){
//        ini_set('max_execution_time', 5000);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', "-1");
        if (empty($searchString)){
            //csv格式输出
            if (empty($zhizaobm)){
                return array('resultcode'=>-1,'resultmsg'=>'制造部门还未选择!');
            }
            if (empty($sdate)){
                $sdate = date('Y-m-d');
            }
            if (empty($edate)){
                $edate = date('Y-m-d');
            }
            if (strtotime($sdate)>strtotime($edate)){
                return array('resultcode'=>-1,'resultmsg'=>'开始时间不能大于结束时间!');
            }
            //订单号查询
            $sql = "select oeb01,oeb03,menkuang,menshan,dkcailiao,huase from oeb_file where (oeb01 like '%M9%'or oeb01 like '%M8%' or oeb01 like '%M5%') and jh_sh_date BETWEEN to_date
                  ('$sdate 00:00:00','yyyy-MM-dd hh24:mi:ss') AND  to_date('$edate 23:59:59','yyyy-MM-dd hh24:mi:ss') 
                  and OPERATE_PLAN=(select GYS_CODE from SELL_GYS where GYS_NAME='$zhizaobm')";
            $data=DB::query($sql,true);
            if (!count($data)){
                return array('resultcode'=>-1,'resultmsg'=>'未查询到当天的订单号或工单号!');
            }
            $this->strCsv = '工单号,项次,序号,主件料号,料件编号,品名,规格,单位用量'."\n";
            //循环订单号数据
            //填芯规则没有查询到则提示：补全填芯规则
            $noTianxin = array();
            $liaohaoService = new LiaohaoService();
            foreach ($data as $val){
                $searchString = $val['oeb01'];
                $item = $val['oeb03'];
                if (strpos($val['oeb01'],'M8') !== false) {
                    $oebColumn = getColumnName('oeb_file');
                    $sql = "select $oebColumn from oeb_file where oeb01='$searchString' and oeb03='$item'";
                    $orderInfo = DB::query($sql,true);
                    if (in_array($orderInfo[0]['operate_plan'],['DS9','DS10'])) {
                        $tianxinInfo = $liaohaoService->getTianXinMenshanQH($orderInfo[0],'母门');
                    } else {
                        $tianxinInfo = $liaohaoService->getTianXinMenshan($orderInfo[0],'母门');
                    }
                    if (empty($tianxinInfo)) {
                        array_push($noTianxin,['dingdanh'=>$val['oeb01'],'xiangci'=>$val['oeb03'],'menkuang'=>$val['menkuang'],'menshan'=>$val['menshan'],
                            'dkcailiao'=>$val['dkcailiao'],'huase'=>$val['huase']]);
                    }
                }
                $data = parent::getLiaohaoByOrder($searchString,$item,1);

                $dataCsv = $data['data']['liaohao_info'];
                //序号重置
                $this->hang = 1;
                $this->dataCsv($dataCsv,$searchString,$item);
                $this->strCsv .= "\n\n";
            }
            if (!empty($noTianxin)) {
                $str = "以下订单号和项次未匹配到填芯用料规则:<br/>";
                foreach ($noTianxin as $key => $val) {
                    $str .= '订单号： '.$val['dingdanh'].' 项次：'.$val['xiangci'].'<br/>部分匹配属性：<br/>门框：'
                        .$val['menkuang'].' 门扇：'.$val['menshan'].' 底框材料：'.$val['dkcailiao'].' 花色：'.$val['huase']."<br/>";
                }
                echo $str;
                return;
            }

            //csv输出
            $this->strCsv = iconv('utf-8','gbk//TRANSLIT',$this->strCsv);
            $filename = "$edate 料号输出.csv";
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=".$filename);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');
            echo $this->strCsv;exit();
            return array('resultcode'=>-1,'resultmsg'=>'订单号为空!');
        }
        //2018-06-25 开放防火门、钢质门（2018-09-14：M5表示钢质门）
        $doorType = substr($searchString,0,2);
        if (in_array($doorType,['M8','M9','M5','M7','M4'])) {//本地测试M5
            $data = parent::getLiaohaoByOrder($searchString,$item);
            if (empty($data)){
                return array('resultcode'=>-1,'resultmsg'=>'订单号没有相对应的料号信息!');
            } 
        } else {
            return null;
        }
        //2018-05-31 仅输出钢质门
//        if (substr($searchString,0,2) == 'M9') {
//            $data = parent::getLiaohaoByOrder($searchString,$item);
//            if (empty($data)){
//                return array('resultcode'=>-1,'resultmsg'=>'订单号没有相对应的料号信息!');
//            }
//        } else {
//            return null;
//        }
//        echo json_encode($data['data']['liaohao_info']['child'][20]);
//        var_dump($data['data']['liaohao_info']['child'][21]);
//        echo json_encode($data);
//        return;
        vendor("PHPExcel.Classes.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $titleExcel = $data['data']['order_info_download'];
        $dataExcel = $data['data']['liaohao_info'];

        //excel标题头
        $this->titleExcel($objPHPExcel,$titleExcel);
        $this->dataExcel($objPHPExcel,$dataExcel);
        $this->excelStyle($objPHPExcel,$titleExcel);
    }

    /**
     * 订单头信息
     * @param $objPHPExcel
     * @param $title
     * @return mixed
     */
    public function titleExcel($objPHPExcel,$title){
        //Excel列下标顺序
        $currentColumn = 'B';
        for ($i = 1; $i <= 50; $i++)
        {
            $a[] = $currentColumn++;
        }
        //头信息
        $title = getBomExcelTitle($title);
        $titleArr = $title['title'];
        $titleData = $title['body'];
        /*$titleArr = array(
            '订单信息','订单号','项次','前板厚','后板厚','门框材质','门扇材质',
            '档次','门框','框厚','底框材料','门扇','规格','开向','铰链','花色','表面方式','表面要求','窗花','猫眼','标牌',
            '主锁','锁芯','副锁','锁把信息','标件','包装品牌','包装方式','其他特殊要求','体积','数量','订单类别'
        );
        //订单数据相关信息
        $titleData = array(
            $title['oeb01'],$title['oeb03'],$title['qmenbhd'],$title['hmenbhd'],$title['menkuangcz'],$title['menshancz'],
            $title['dang_ci'],$title['menkuang'],$title['mkhoudu'],$title['dkcailiao'],$title['menshan'],$title['guige'],
            $title['kaixiang'],$title['jiaolian'],$title['huase'],$title['biaomcl'],$title['biaomiantsyq'],$title['chuanghua'],
            $title['maoyan'],$title['biao_pai'],$title['suoju'],$title['suoxin'],$title['tiandscshuo'],$title['suoba_info'],$title['biaojian'],
            $title['baozhpack'],$title['baozhuangfs'],$title['remark'],$title['unitcube'],$title['oeb12'],$title['order_type']
        );*/
        //设置头excel值
        foreach($titleArr  as $key=>$val){
            $objPHPExcel->setActiveSheetIndex()->setCellValue($a[$key].'1',$val);
        }
        //设置订单数据excel值
        foreach ($titleData as $key=>$val){
            $objPHPExcel->setActiveSheetIndex()->setCellValue($a[$key+1].'2',$val);
        }
        //订单信息合并项
        $titleMarge = 'B1:B2';
        $objPHPExcel->getActiveSheet()->mergeCells($titleMarge);
        //行数开始
        $this->hang = 3;
        return $objPHPExcel;
    }

    /**处理数据
     * @param $objPHPExcel
     * @param $dataExcel
     */
    public function dataExcel($objPHPExcel,$dataExcel){
        $yuanjian_level = empty($dataExcel['chengpinlh']['yuanjian_level'])?$dataExcel['child'][0]['yuanjian_level']:$dataExcel['chengpinlh']['yuanjian_level'];
        $liaohao = empty($dataExcel['chengpinlh']['liaohao'])?'':$dataExcel['chengpinlh']['liaohao'];
        $liaohao_guige = empty($dataExcel['chengpinlh']['liaohao_guige'])?'':$dataExcel['chengpinlh']['liaohao_guige'];
        $liaohao_pinming = empty($dataExcel['chengpinlh']['liaohao_pinming'])?'':$dataExcel['chengpinlh']['liaohao_pinming'];
        $this->headSet($objPHPExcel,$yuanjian_level,$liaohao,$liaohao_guige,$liaohao_pinming);
        $this->xmhead($objPHPExcel);
        $this->dataForeach($dataExcel,$objPHPExcel);
    }

    /**
     * 处理child中的数据
     * @param $dataExcel
     * @param $objPHPExcel
     */
    public function dataForeach($dataExcel,$objPHPExcel){
        //设置品级头 ,当hang小于10时,跳过成品级的头设置
        if ( $this->hang>=10){
            $yuanjian = $dataExcel['yuanjian_name'];
            $liaohao = $dataExcel['liaohao'];
            $liaohao_guige = $dataExcel['liaohao_guige'];
            $liaohao_pinming = $dataExcel['liaohao_pinming'];
            $this->headSet($objPHPExcel,$yuanjian,$liaohao,$liaohao_guige,$liaohao_pinming);
            $this->xmhead($objPHPExcel);
            $start_hang = 'B'.$this->hang;
        }
        foreach ($dataExcel['child'] as $key=>$val){
            //列数据
            $yuanjian = $val['yuanjian_name'];
            $liaohao = $val['liaohao'];
            $pinming = $val['liaohao_pinming'];
            $guige = $val['liaohao_guige'];
            $yongliang = $val['yongliang'];
            $level = $val['yuanjian_level'];
            //设置行数据
            $this->xmDataSet($objPHPExcel,$key,$yuanjian,$liaohao,$pinming,$guige,$yongliang,$level);
            if (count($val['child'])){
                $other[] = $val;
            }
        }
        //获取需要设置的边框坐标
        $end_hang = $this->hang;
        if (empty($start_hang)){
            $start_hang = 'B6';
        }
        $this->style[] = $start_hang.':H'.$end_hang;
        $this->hangAdd();
        //数据中多品级设置
        if (count($other)){
            foreach ($other as $val){
                $this->dataForeach($val,$objPHPExcel);
            }
        }
    }

    /**
     * 设置品级头
     * @param $objPHPExcel
     * @param $yuanjian
     * @param $liaohao
     * @param $liaohao_guige
     * @param $liaohao_pinming
     * @return mixed
     */
    public function headSet($objPHPExcel,$yuanjian,$liaohao,$liaohao_guige,$liaohao_pinming){
        $this->hangAdd();
        $objPHPExcel->getActiveSheet()->getStyle('A'.$this->hang)->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C'.$this->hang,$yuanjian.'料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D'.$this->hang,$yuanjian.'品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E'.$this->hang,$yuanjian.'规格');
        //加粗字体
        $objPHPExcel->getActiveSheet()->getStyle( 'A'.$this->hang.':H'.$this->hang)->applyFromArray(
            array(
                'font'    => array (
                    'bold'      => true
                )
            )
        );
        $this->hangAdd();
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C'.$this->hang,$liaohao);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D'.$this->hang,$liaohao_pinming);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E'.$this->hang,$liaohao_guige);
        return $objPHPExcel;
    }

    /**
     * 元件项次
     * @param $objPHPExcel
     * @return mixed
     */
    public function xmhead($objPHPExcel){
        $this->hangAdd();
        $objPHPExcel->getActiveSheet()->getStyle('A'.$this->hang)->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B'.$this->hang,'元件项次');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C'.$this->hang,'元件名称');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D'.$this->hang,'元件料号');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E'.$this->hang,'元件品名');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F'.$this->hang,'元件规格');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G'.$this->hang,'QPA用量');
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H'.$this->hang,'层级');
        //加粗字体
        $objPHPExcel->getActiveSheet()->getStyle( 'A'.$this->hang.':H'.$this->hang)->applyFromArray(
            array(
                'font'    => array (
                    'bold'      => true
                )
            )
        );
        return $objPHPExcel;
    }

    /**
     * 设置行数据
     * @param $objPHPExcel
     * @param $key
     * @param $yuanjian
     * @param $liaohao
     * @param $pinming
     * @param $guige
     * @param $yongliang
     * @param $level
     * @return mixed
     */
    public function xmDataSet($objPHPExcel,$key,$yuanjian,$liaohao,$pinming,$guige,$yongliang,$level){
        $this->hangAdd();
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B'.$this->hang,($key+1));
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C'.$this->hang,$yuanjian);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D'.$this->hang,$liaohao);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E'.$this->hang,$pinming);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F'.$this->hang,$guige);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G'.$this->hang,$yongliang);
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H'.$this->hang,$level);
        return $objPHPExcel;
    }
    /**
     * excel样式
     * @param $objPHPExcel
     * @param $titleExcel
     */
    public function excelStyle($objPHPExcel,$titleExcel){
        $styleArray = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                ),
            )
        );
        foreach ($this->style as $val){
            $objPHPExcel->getActiveSheet()->getStyle($val)->applyFromArray($styleArray);
        }
        $objPHPExcel->setactivesheetindex(0);
        $objPHPExcel->getActiveSheet()->setTitle($titleExcel['oeb01']);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('B1:AE2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->setActiveSheetIndex()->getStyle('B1:AE2')->getFill()->getStartColor()->setARGB("0099CCFF");  //浅蓝色
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'A')->setWidth(1);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'b')->setWidth(10);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'C')->setWidth(20);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'D')->setWidth(20);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'E')->setWidth(20);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'F')->setWidth(20);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'G')->setWidth(20);
        $objPHPExcel->setActiveSheetIndex()->getColumnDimension( 'H')->setWidth(20);
        $fileName = $titleExcel['oeb01']."订单料号";
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$fileName.xls");
        header('Cache-Control: max-age=0');
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter->save('php://output'); //文件通过浏览器下载
        return;
    }


    // CSV部分
    /**处理数据
     * @param $dataCsv
     */
    public function dataCsv($dataCsv,$gdh,$xc){
        $liaohao = empty($dataCsv['chengpinlh']['liaohao'])?'':$dataCsv['chengpinlh']['liaohao'];
        $liaohao_guige = empty($dataCsv['chengpinlh']['liaohao_guige'])?'':$dataCsv['chengpinlh']['liaohao_guige'];
        $liaohao_pinming = empty($dataCsv['chengpinlh']['liaohao_pinming'])?'':$dataCsv['chengpinlh']['liaohao_pinming'];
        $this->strCsv .= "$gdh,$xc,0,$liaohao,===,===,===,="."\n".",,0,$liaohao,$liaohao,$liaohao_pinming,$liaohao_guige,合计"."\n";
        $this->dataForeachCsv($dataCsv,$liaohao);
    }

    /**
     * 处理child中的数据
     * @param $dataCsv
     */
    public function dataForeachCsv($dataCsv,$zhuliaohao){
        //设置数据料号数据
        if (empty($dataCsv['liaohao'])){
            $this->zhuliaohao = $zhuliaohao;
        }else{
            $this->zhuliaohao = $dataCsv['liaohao'];
        }
        if (!empty($dataCsv['child'])) {
            foreach ($dataCsv['child'] as $key=>$val){
                //列数据
                $liaohao = $val['liaohao'];
                $pinming = $val['liaohao_pinming'];
                $guige = $val['liaohao_guige'];
                $yongliang = $val['yongliang'];
                //设置行数据
                $this->strCsv .= ",,$this->hang,$this->zhuliaohao,$liaohao,$pinming,$guige,$yongliang"."\n";
                if (count($val['child'])){
                    $other[] = $val;
                }
            }
        }
        $this->hangAdd();
        //数据中多品级设置
        if (count($other)){
            foreach ($other as $val){
                $this->dataForeachCsv($val,$zhuliaohao);
            }
        }
    }
    /**
     * hang加1
     */
    public function hangAdd(){
        $this->hang += 1;
    }
}