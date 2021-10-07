<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file ucenter.php
 * @brief 用户中心api方法
 * @author chendeshan
 * @date 2018/4/13 7:46:55
 * @version 5.1
 */
class APIUcenter
{
    ///用户中心-账户预存款
    public function getUcenterAccoutLog($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('account_log');
        $query->where = "user_id = ".$userid;
        $query->order = 'id desc';
        $query->page  = $page;
        return $query;
    }
    //用户中心-我的建议
    public function getUcenterSuggestion($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('suggestion');
        $query->where="user_id = ".$userid;
        $query->page  = $page;
        $query->order = 'id desc';
        return $query;
    }
    //用户中心-商品讨论
    public function getUcenterConsult($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('refer as r');
        $query->join   = "join goods as go on r.goods_id = go.id ";
        $query->where  = "r.user_id =". $userid;
        $query->fields = "time,name,question,status,answer,admin_id,go.id as gid,reply_time";
        $query->page   = $page;
        $query->order = 'r.id desc';
        return $query;
    }
    //用户中心-商品评价
    public function getUcenterEvaluation($status = '',$userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $status = IFilter::act($status,'int');
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $where  = "c.user_id = {$userid}";
        $where .= ($status === '') ? "" : " and c.status = {$status}";

        $query = new IQuery('comment as c');
        $query->join   = "left join order_goods as og on og.id = c.order_goods_id";
        $query->fields = "c.*,og.goods_array,og.img";
        $query->where  = $where;
        $query->page   = $page;
        $query->order  = 'c.id desc';
        $result        = $query->find();

        foreach($result as $key => $val)
        {
            $goodsArray = JSON::decode($val['goods_array']);
            $result[$key]['name']  = $goodsArray['name'];
            $result[$key]['value'] = $goodsArray['value'];
        }
        return $query->setData($result);
    }

    //用户中心-用户信息
    public function getMemberInfo($userid = '')
    {
        $userid = $userid ? $userid : IWeb::$app->getController()->user['user_id'];
        $userid = IFilter::act($userid,'int');
        $tb_member = new IModel('member as m,user as u');
        $info = $tb_member->getObj("m.user_id = u.id and m.user_id=".$userid);
        if($info && $info['group_id'])
        {
            $userGroup = new IModel('user_group');
            $groupRow  = $userGroup->getObj('id = '.$info['group_id']);
            $info['group_name'] = $groupRow ? $groupRow['group_name'] : "";
        }
        else
        {
        	$info['group_name'] = "";
        }
        return $info;
    }
    //用户中心-个人主页统计
    public function getMemberTongJi($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $result = array();

        $query = new IQuery('order');
        $query->fields = "count(id) as num";
        $query->where  = "user_id = ".$userid." and if_del = 0";
        $info = $query->find();
        $result['num'] = $info[0]['num'];

        $query->fields = "sum(order_amount) as amount";
        $query->where  = "user_id = ".$userid." and status = 5 and if_del = 0";
        $info = $query->find();
        $result['amount'] = $info[0]['amount'];

        return $result;
    }
    //用户中心-优惠券统计
    public function getPropTongJi()
    {
        $user_id     = IWeb::$app->getController()->user['user_id'];
        $member_info = Api::run('getMemberInfo',$user_id);
        $propIds     = trim($member_info['prop'],',');
        $propIds     = $propIds ? $propIds : 0;

        $query  = new IQuery('prop');
        $query->fields = "count(id) as prop_num";
        $query->where  = "id in (".$propIds.") and type = 0";
        $info = $query->find();
        return $info[0];
    }
    //用户中心-积分列表
    public function getUcenterPointLog($userid = '')
    {
        $userid     = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page       = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query      = new IQuery('point_log');
        $query->where  = "user_id = ".$userid;
        $query->page   = $page;
        $query->order  = "id desc";
        return $query;
    }
    //用户中心-信息列表
    public function getUcenterMessageList()
    {
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

        $msgObj = new Mess(IWeb::$app->getController()->user['user_id']);
        $msgIds = $msgObj->getAllMsgIds();
        $msgIds = $msgIds ? $msgIds : 0;

        $query  = new IQuery('message');
        $query->where= "id in(".$msgIds.")";
        $query->order= "id desc";
        $query->page = $page;
        $query->msg  = $msgObj;
        return $query;
    }
    //用户中心-订单列表
    public function getOrderList($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('order');
        $query->where = "user_id =".$userid." and if_del= 0";
        $query->order = "id desc";
        $query->page  = $page;
        return $query;
    }
    //用户中心-我的优惠券
    public function getPropList()
    {
        $page        = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $user_id     = IWeb::$app->getController()->user['user_id'];
        $member_info = Api::run('getMemberInfo',$user_id);
        $propIds     = trim($member_info['prop'],',');
        $propIds     = $propIds ? $propIds : 0;

        $query = new IQuery('prop');
        $query->where = "id in(".$propIds.") and is_send = 1 and type = 0";
		$query->order = 'end_time desc';
        $query->page  = $page;
        return $query;
    }

    //用户中心-退款记录
    public function getRefundmentDocList($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('refundment_doc');
        $query->where = "user_id = ".$userid." and if_del = 0";
        $query->order = "id desc";
        $query->page  = $page;
        return $query;
    }
    //用户中心-提现记录
    public function getWithdrawList($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('withdraw');
        $query->where = "user_id = ".$userid;
        $query->order = "id desc";
        $query->page  = $page;
        return $query;
    }

    //[收藏夹]获取收藏夹数据
    public function getFavorite($cat = '')
    {
        //获取收藏夹信息
        $userid = IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $cat_id = IFilter::act($cat,'int');

        $favoriteObj = new IQuery("favorite as f");
        $favoriteObj->join  = "left join goods as go on go.id = f.goods_id";
        $favoriteObj->fields= " f.*,go.name,go.id as goods_id,go.img,go.store_nums,go.sell_price,go.market_price";

        $where = 'user_id = '.$userid;
        $where.= $cat_id ? ' and cat_id = '.$cat_id : "";

        $favoriteObj->where = $where;
        $favoriteObj->page  = $page;
        return $favoriteObj;
    }

    //[发票管理]获取发票列表
    public function getInvoiceListByUserId($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        if($userid)
        {
            $model = new IModel('invoice');
            $result = $model->query('user_id = '.$userid);
        }
        else
        {
            $result = ISafe::get('invoice');
            $result = $result ? array($result) : array();
        }
        return $result;
    }

    //用户中心-维修记录
    public function getFixDocList($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('fix_doc');
        $query->where = "user_id = ".$userid." and if_del = 0";
        $query->order = "id desc";
        $query->page  = $page;
        return $query;
    }

    //用户中心-换货记录
    public function getExchangeDocList($userid = '')
    {
        $userid = $userid ? IFilter::act($userid,'int') : IWeb::$app->getController()->user['user_id'];
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query  = new IQuery('exchange_doc');
        $query->where = "user_id = ".$userid." and if_del = 0";
        $query->order = "id desc";
        $query->page  = $page;
        return $query;
    }
}

/**
 * @brief 外部服务用户中心类
 * @notice 部分需要用户身份的接口要有userToken令牌进行访问
 */
class ServiceUcenter
{
    /**
     * @brief 用户登录接口
     */
    public function userLogin()
    {
        $userRow = IWeb::$app->getController()->user;
        plugin::trigger("userLoginCallback",$userRow);
    }
}