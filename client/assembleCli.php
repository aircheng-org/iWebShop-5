<?php
/**
 * @brief 拼团计划任务 取消订单， 删除报名，减掉人数。
 *        让操作系统在间隔时间执行次脚本
 * @date 2019/8/16 22:13:09
 * @author wenjie
 */
$iweb = dirname(__FILE__)."/../lib/iweb.php";
$config = dirname(__FILE__)."/../config/config.php";
require($iweb);

class assembleCli extends IApplication
{
    //清理过期的时间（分钟）
    private static $timeStep = 100;

    //执行入口
    public function execRequest()
    {
        //需要统计的拼团组队列
        $commanderIdArray = [];

        $activeDB = new IModel('assemble_active');
        $the_time = self::$timeStep;

        //查询超时未付款的订单和拼团组ID
        $activeData = $activeDB->query("is_pay = 0 and timestampdiff(minute,create_time,NOW()) >= {$the_time}  ","assemble_commander_id,order_no");
        foreach($activeData as $kay => $val)
        {
            //取消订单
            $order_no = $val['order_no'];
            $this->orderCancel($order_no);

            //删除拼团报名
            $activeDB->del("order_no = '".$order_no."'");

            //压入数组
            $commanderIdArray[] = $val['assemble_commander_id'];
        }

        //重新计算报名人数
        $commanderDB = new IModel('assemble_commander');
        foreach($commanderIdArray as $id)
        {
            $commanderDB->lock = 'for update';
            $numData = $activeDB->getObj('assemble_commander_id = '.$id,'count(*) as num');
            $commanderDB->lock = '';

            $commanderDB->setData(['member_nums' => $numData['num']]);
            $commanderDB->update($id);
        }
    }

    //取消订单
    private function orderCancel($order_no)
    {
        $orderModel = new IModel('order');
        $resultData = $orderModel->getObj("order_no = '".$order_no."'","id,order_no") ;

        if($resultData)
        {
            $order_id = $resultData['id'];

            //生成订单日志
            $tb_order_log = new IModel('order_log');

            //订单自动作废
            $action = '作废';
            $note   = '订单【'.$order_no.'】未付款超时作废';

            //订单重置取消
            Order_class::resetOrderProp($order_id);

            $logObj = new log('db');
            $logObj->write('operation',array("系统自动","订单更新为作废",'订单号：'.$order_no));

            $tb_order_log->setData(array(
                'order_id' => $order_id,
                'user'     => "系统自动",
                'action'   => $action,
                'result'   => '成功',
                'note'     => $note,
                'addtime'  => ITime::getDateTime(),
            ));
            $tb_order_log->add();
        }
    }
}

$configData = include($config);
$configData['basePath'] = dirname(__FILE__).'/../';
IWeb::createApp("assembleCli", $configData)->run();