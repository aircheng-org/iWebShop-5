<?php
/**
  * @notice 重要！不要修改此文件，如果要增加API，可以通过扩展的方式:比如修改模板目录views下的config.php等
  * 具体参考API开发文档， http://www.aircheng.com/down
 */
return array(

    //取商品数据
    'getGoodsInfo' => array(
        'query' => array(
            'name'   => 'goods as go',
            'where'  => 'go.id = #id# and go.is_del = 0',
            'fields' => 'go.name,go.id as goods_id,go.img,go.sell_price,go.point,go.weight,go.store_nums,go.exp,go.goods_no,0 as product_id,go.seller_id,go.is_delivery_fee,go.is_del,go.active_id,go.promo,go.type',
            'type'   => 'row',
        )
    ),

    //取货品数据
    'getProductInfo' => array(
        'query' => array(
            'name'   => 'goods as go,products as pro',
            'where'  => 'pro.id = #id# and pro.goods_id = go.id and go.is_del = 0',
            'fields' => 'pro.sell_price,pro.weight,pro.id as product_id,pro.spec_array,pro.goods_id,pro.store_nums,pro.products_no as goods_no,go.name,go.point,go.exp,go.img,go.seller_id,go.is_delivery_fee,go.is_del,go.active_id,go.promo,go.type',
            'type'   => 'row',
        )
    ),

    //取文章置顶列表
    'getArtList' => array(
        'query' => array(
            'name' => 'article',
            'where' => 'visibility = 1 and top = 1',
            'order' => 'sort ASC',
            'fields'=> 'title,id,style,color,create_time',
            'limit' => '10'
        )
    ),

    //团购列表
    'getRegimentList' => array(
        'query' => array(
            'name'  => 'regiment as r',
            'join'  => 'left join goods as go on r.goods_id = go.id',
            'where' => 'r.is_close = 0 and NOW() between r.start_time and r.end_time and go.is_del = 0',
            'order' => 'r.sort asc',
            'fields'=> 'r.*',
        )
    ),

    //往期团购列表
    'getEverRegimentList' => array(
        'query' => array(
            'name' => 'regiment',
            'where' => 'is_close = 0 and NOW() > end_time',
            'order' => 'sort asc',
            'limit' => 5,
        )
    ),

    //椐据ID团购
    'getRegimentRowById' => array(
        'query' => array(
            'name'  => 'regiment',
            'where' => 'id = #id# and NOW() between start_time and end_time and is_close = 0',
            'type'  => 'row',
        )
    ),

    //限时抢购列表
    'getPromotionList'=> array(
        'query' => array(
            'name' => 'promotion as p',
            'join' => 'left join goods as go on go.id = p.condition',
            'fields'=>'go.sell_price,p.end_time,go.img as img,p.name as name,p.award_value as award_value,go.id as goods_id,p.id as p_id,p.start_time',
            'where'=>'p.type = 1 and p.is_close = 0 and go.is_del = 0 and NOW() between start_time and end_time AND go.id is not null',
            'order'=>'p.sort asc',
            'limit'=>'10'
        )
    ),

    //根据ID限时抢购
    'getPromotionRowById'=> array(
        'query' => array(
            'name'  => 'promotion',
            'fields'=> 'award_value,end_time,user_group,`condition`',
            'where' => 'type = 1 and `id` = #id# and NOW() between start_time and end_time and is_close = 0',
            'type'  => 'row',
        )
    ),

    //新品列表
    'getCommendNew' => array(
        'query' => array(
            'name' => 'commend_goods as co',
            'join' => 'left join goods as go on co.goods_id = go.id',
            'where' => 'co.commend_id = 1 and go.is_del = 0 AND go.id is not null',
            'fields' => 'go.img,go.sell_price,go.name,go.id,go.market_price',
            'limit'=>'10',
            'order'=>'sort asc'
        )
    ),

    //特价商品列表
    'getCommendPrice' => array(
        'query' => array(
            'name' => 'commend_goods as co',
            'join' => 'left join goods as go on co.goods_id = go.id',
            'where' => 'co.commend_id = 2 and go.is_del = 0 AND go.id is not null',
            'fields' => 'go.img,go.sell_price,go.name,go.id,go.market_price',
            'limit'=>'10',
            'order'=>'sort asc'
        )
    ),

    //热卖商品列表
    'getCommendHot' => array(
        'query' => array(
            'name' => 'commend_goods as co',
            'join' => 'left join goods as go on co.goods_id = go.id',
            'where' => 'co.commend_id = 3 and go.is_del = 0 AND go.id is not null',
            'fields' => 'go.img,go.sell_price,go.name,go.id,go.market_price',
            'limit'=>'10',
            'order'=>'sort asc'
        )
    ),

    //推荐商品列表
    'getCommendRecom' => array(
        'query' => array(
            'name' => 'commend_goods as co',
            'join' => 'left join goods as go on co.goods_id = go.id',
            'where' => 'co.commend_id = 4 and go.is_del = 0 AND go.id is not null',
            'fields' => 'go.img,go.sell_price,go.name,go.id,go.market_price',
            'limit'=>'10',
            'order'=>'sort asc'
        )
    ),

    //已配送的订单
    'getOrderDistributed' => array(
        'query' => array(
            'name' => 'order',
            'where' => 'distribution_status = 1 and if_del = 0',
            'limit' => '10',
            'order' => 'id desc'
        )
    ),

    //根据品牌热卖商品列表
    'getCommendHotBrand'   => array(
        'query' => array(
            'name' => 'commend_goods as co',
            'join' => 'left join goods as go on co.goods_id = go.id',
            'where' => 'co.commend_id = 3 and go.is_del = 0 AND go.id is not null and go.brand_id = #brandid#',
            'fields' => 'go.img,go.sell_price,go.name,go.id',
            'limit'=>'10',
            'order'=>'sort asc'
        )
    ),

    //导航列表
    'getGuideList'=>array(
        'file' => 'other.php','class' => 'APIOther'
    ),

    //公告列表
    'getAnnouncementList'=>array(
        'query'=>array('name'=>'announcement','order'=>'id desc','page' => IReq::get('page') ? IReq::get('page') : 1)
    ),

    //所有关键字列表
    'getKeywordAllList'=>array(
        'query'=>array('name'=>'keyword','order'=>'`order` asc','page' => IReq::get('page') ? IReq::get('page') : 1)
    ),

    //获取热门关键词列表
    'getKeywordList'=>array(
        'query'=>array(
            'name'  => 'keyword',
            'where' => ' hot = 1',
            'order' => '`order` asc',
            'limit' => 5,
        )
    ),

    //查找关键字
    'getKeywordByWord'=>array(
        'query'=>array(
            'name'=>'keyword',
            'where'=>'word like "%#word#%" and word != "#word#"',
            'limit'=>10
        )
    ),

    //根据商品分类ID获取分类名称
    'getCategoryExtendNameByCategoryid'=>array(
        'query'=>array(
            'name'  => 'category_extend as ce',
            'join'  => 'left join category as cd on cd.id = ce.category_id',
            'where' => 'ce.goods_id = #id#',
            'order' => 'cd.id asc',
        )
    ),

    //根据商品分类取得商品列表
    'getCategoryExtendList'=>array(
        'query'=>array(
            'name'  => 'category_extend as ca',
            'join'  => 'left join goods as go on go.id = ca.goods_id',
            'where' => 'ca.category_id in(#categroy_id#) and go.is_del = 0',
            'order' => 'go.sort asc',
            'fields'=> 'go.id,go.name,go.img,go.sell_price,go.market_price',
            'limit' => 10,
        )
    ),

    //根据分类取销量排名列表
    'getCategoryExtendListByCategoryid'=>array(
        'query'=>array(
            'name'  => 'goods as go',
            'join'  => 'left join category_extend as ca on ca.goods_id = go.id',
            'where' => 'ca.category_id in (#categroy_id#) and go.is_del = 0',
            'fields'=> 'distinct go.id,go.name,go.img,go.sell_price',
            'order' => 'sale desc',
            'limit' => 10,
        )
    ),

    //所有一级分类根据visibility值
    'getCategoryListTopByVis'=>array(
        'query'=>array(
            'name'  => 'category',
            'where' => ' parent_id = 0 and visibility = 1 ',
            'order' => ' sort asc',
            'limit' => 20,
        )
    ),

    //所有一级分类
    'getCategoryListTop'=>array(
        'query'=>array(
            'name'  => 'category',
            'where' => ' parent_id = 0',
            'order' => ' sort asc',
            'limit' => 20,
        )
    ),

    //根据一级分类输出二级分类列表根据visibility值
    'getCategoryByParentidByVis'=>array(
        'query'=>array(
            'name'  => 'category',
            'where' => ' parent_id = #parent_id# and visibility = 1 ',
            'order' => ' sort asc',
            'limit' => 10,
        )
    ),

    //根据一级分类输出二级分类列表
    'getCategoryByParentid'=>array(
        'query'=>array(
            'name'  => 'category',
            'where' => ' parent_id = #parent_id#',
            'order' => ' sort asc',
            'limit' => 10,
        )
    ),

    //所有品牌列表
    'getBrandList'=>array(
        'query'=>array(
            'name'  => 'brand',
            'order' => ' sort asc',
            'limit' => 10,
        )
    ),

    //所有品牌列表
    'getListByBrand'=>array(
        'query'=>array(
            'name'  => 'brand',
            'order' => 'sort asc',
            'where' => 'name like "%#name#%"',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有品牌分类
    'getListByBrandCategory'=>array(
        'query'=>array(
            'name'  => 'brand_category',
            'order' => 'id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //取得品牌详情
    'getBrandInfo'=>array(
        'file' => 'brand.php','class' => 'APIBrand'
    ),

    //取得商户详情
    'getSellerInfo'=>array(
        'file' => 'seller.php','class' => 'APISeller'
    ),

    //取得VIP商户列表
    'getVipSellerList'=>array(
        'query'=>array(
            'name'  => 'seller',
            'order' => ' sort asc ',
            'limit' => 10,
            'where' => 'is_del = 0 and is_vip = 1 and is_lock = 0',
        )
    ),

    //取得商户列表
    'getSellerList'=>array(
    	'query' => array(
    		'name' => 'seller',
    		'where'=> 'is_del = 0 and is_lock = 0',
    		'order'=> 'sort asc',
    		'page' => IReq::get('page') ? IReq::get('page') : 1,
    	)
    ),

    //取得回收站商户列表
    'getRecycleSellerList'=>array(
        'query' => array(
            'name' => 'seller',
            'where'=> 'is_del = 1',
            'order'=> 'id desc',
            'page' => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //取得所有正常状态商户
    'getSellerListAll'=>array(
        'query'=>array(
            'name'  => 'seller',
            'where' => 'is_del = 0',
        )
    ),

    //最新评论列表
    'getCommentList'=>array(
        'query'=>array(
            'name'  => 'comment as co',
            'join'  => 'left join goods as go on co.goods_id = go.id',
            'where' => ' co.status = 1 AND go.id is not null',
            'fields'=> 'go.img as img,go.name as name,co.point,co.contents,co.goods_id',
            'order' => ' co.id desc',
            'limit' => 10,
        )
    ),

    //帮助中心底部列表
    'getHelpCategoryFoot'=>array(
        'query'=>array(
            'name'  => 'help_category',
            'where' => ' position_foot = 1',
            'order' => 'sort ASC',
            'limit' => 5,
        )
    ),

    //帮助中心左侧列表
    'getHelpCategoryLeft'=>array(
        'query'=>array(
            'name'  => 'help_category',
            'where' => ' position_left = 1',
            'order' => 'sort ASC',
            'limit' => 5,
        )
    ),

    //取帮助中心列表
    'getHelpListByCatidAll'=>array(
        'query'=>array(
            'name'  => 'help',
            'where' => ' cat_id =  #cat_id# ',
            'order' => 'sort ASC',
            'limit' => 5,
        )
    ),

    //获取文章所有分类
    'getArticleCategoryListAll'=>array(
        'query'=>array(
            'name'  => 'article_category',
            'order' => 'path asc',
        )
    ),

    //文章分类
    'getArticleCategoryList'=>array(
        'query'=>array(
            'name'  => 'article_category',
            'where' => ' issys = 0 ',
            'order' => 'sort ASC',
            'limit' => 10,
        )
    ),

    //文章详情
    'getArticleCategoryInfo'=>array(
        'file' => 'article.php','class' => 'APIArticle'
    ),

    //文章列表
    'getArticleList' => array(
        'query'=>array(
            'name'  => 'article',
            'order' => 'id desc',
            'where' => 'visibility = 1',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //根据分类读列表
    'getArticleListByCatid' => array(
        'file' => 'article.php','class' => 'APIArticle'
    ),

    //公告列表
    'getNoticeList' => array(
    	'query' => array(
    		'name' => 'announcement',
    		'order'=> 'id desc',
    		'page' => IReq::get('page') ? IReq::get('page') : 1,
    	)
    ),

    //品牌分类
    'getBrandCategory'=>array(
        'query'=>array(
            'name'  => 'brand_category',
            'order' => 'id desc',
        )
    ),

    //查找相关分类
    'getBrandListWhere'=>array(
        'query'=>array(
            'name'  => 'brand',
            'where' => 'FIND_IN_SET(#id#,category_ids)',
            'order' => ' sort asc',
        )
    ),

    //根据品牌销量排名列表
    'getGoodsListBrandSum'=>array(
        'query'=>array(
            'name'   => 'goods',
            'fields' => 'id,name,img,sell_price',
            'where'  => 'is_del = 0 and brand_id = #brandid#',
            'order'  => 'sale desc',
            'limit'  => 10,
        )
    ),

    //根据商家设置排名列表
    'getGoodsListBySellerid'=>array(
        'query'=>array(
            'name'   => 'goods',
            'fields' => 'id,name,img,sell_price,sale',
            'where'  => "seller_id = #seller_id# and is_del = 0",
            'order'  => 'sort asc',
            'limit'  => 10,
        )
    ),

    //帮助中心列表
    'getHelpList' => array(
    	'query' => array(
    		'name' => 'help as h',
    		'join' => 'left join help_category as hcat on h.cat_id = hcat.id',
    		'order'=> 'sort asc',
    		'fields' => 'h.*,hcat.name as cat_name',
    		'page' => IReq::get('page') ? IReq::get('page') : 1,
    	)
    ),

    //根据分类取帮助中心列表
    'getHelpListByCatId' => array(
        'file' => 'help.php','class' => 'APIHelp'
    ),

    //根据分类取推荐商品
    'getCategoryExtendByCommendid'=>array(
        'query'=>array(
            'name'  => 'category_extend as ca',
            'join'  => 'left join goods as go on ca.goods_id = go.id left join commend_goods as co on co.goods_id = go.id',
            'where' => 'ca.category_id in (#childId#) and co.commend_id = 4 and go.is_del = 0',
            'fields'=> 'DISTINCT go.id,go.img,go.sell_price,go.name,go.market_price,go.description',
            'order' => 'go.sort asc',
            'limit' => 6,
        )
    ),

    //根据商品分类获取品牌
    'getCategoryExtendByBrandid'=>array(
        'query'=>array(
            'name'  => 'category_extend as ca',
            'join'  => 'left join goods as go on ca.goods_id = go.id left join brand as b on b.id = go.brand_id',
            'where' => 'ca.category_id in ( #childId# ) and go.is_del = 0 and go.brand_id != 0',
            'fields'=> 'DISTINCT b.id,b.name',
            'limit' => 10,
        )
    ),

    //帮助中心内容
    'getHelpInfo'=>array(
        'query'=>array(
            'name'  => 'help',
            'where' => 'id = #id#',
            'type'  => 'row',
        )
    ),

    //查找商品
    'getGoodsCategoryExtend'=>array(
        'query'=>array(
            'name'  => 'goods as go',
            'join'  => 'left join category_extend as ca on go.id = ca.goods_id left join category as c on c.id = ca.category_id',
            'where' => 'go.is_del = 0 and go.name like "%#word#%" or FIND_IN_SET("#word#",search_words)',
            'fields'=> 'c.name,c.id,count(*) as num',
            'group' => 'ca.category_id',
            'limit' => 20
        )
    ),

    //用户中心-账户预存款
    'getUcenterAccoutLog' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-我的建议
    'getUcenterSuggestion' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-商品讨论
    'getUcenterConsult' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-商品评价
    'getUcenterEvaluation' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-收藏夹
    'getUcenterFavoriteByCatid'=>array(
        'query'=>array(
            'name'=>'favorite as f,category as c ',
            'where'=>'f.user_id = '.IWeb::$app->getController()->user['user_id'].' and f.cat_id = c.id ',
            'fields'=> 'count(*) as num,c.name,c.id ',
            'group'=> 'cat_id',
        )
    ),

    //用户中心-个人资料
    'getMemberInfo' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-个人主页统计
    'getMemberTongJi' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-优惠券统计
    'getPropTongJi' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-订单列表
    'getOrderListByUserid'=>array(
        'query'=>array(
            'name'  => 'order',
            'where' => ' user_id = '.IWeb::$app->getController()->user['user_id'].' and if_del = 0 ',
            'order'=> 'id desc',
            'limit' => 6,
        )
    ),

    //用户中心-感兴趣的商品
    'getGoodsByCommendgoods'=>array(
        'query'=>array(
            'name'  => 'goods',
            'where' => 'is_del = 0',
            'order' => 'grade desc',
            'limit' => 12,
        )
    ),

    //用户中心-积分列表
    'getUcenterPointLog' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-优惠券列表
    'getTicketList'=>array(
        'query'=>array(
            'name'  => 'ticket',
            'where' => 'point > 0 and NOW() BETWEEN start_time and end_time',
            'limit' => 20,
        )
    ),

    //用户中心-信息列表
    'getUcenterMessageList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-订单列表
    'getOrderList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-订单中商品列表
    'getOrderGoodsListByGoodsid'=>array(
        'query'=>array(
            'name'  => 'order_goods as og',
            'join'  => 'left join goods as go on og.goods_id = go.id',
            'where' => 'order_id = #order_id# ',
            'fields'=> 'og.*,go.point,go.type',
        )
    ),

    //用户中心-我的优惠券
    'getPropList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-退款记录
    'getRefundmentDocList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-换货记录
    'getExchangeDocList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-维修记录
    'getFixDocList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //用户中心-提现记录
    'getWithdrawList' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //快捷登录
    'getOauthList' => array(
    	'file' => 'other.php','class' => 'APIOther'
    ),

    //查看大图相册
    'getGoodsPhotoRelationList'=>array(
        'query'=>array(
            'name'  => 'goods_photo_relation AS a ',
            'join'  => "left join goods_photo AS b ON a.photo_id = b.id",
            'where' => "a.goods_id = #id# ",
            'fields'=> "a.*,b.img",
        )
    ),

    //地区列表
    'getAreasListTop'=>array(
        'query'=>array(
            'name'  => 'areas',
            'where' => "parent_id =0 ",
        )
    ),

    //支付列表
    'getPaymentList'=>array(
        'file'  => 'other.php','class' => 'APIOther',
    ),

    //充值支付列表
    'getPaymentListByOnline'=>array(
        'file'  => 'other.php','class' => 'APIOther',
    ),

    //根据分类读品牌
    'getBrandListByGoodsCategoryId' => array(
        'file' => 'brand.php','class' => 'APIBrand'
    ),

    //获取促销规则
    'getProrule' => array(
        'file' => 'other.php','class' => 'APIOther'
    ),

    //获取有效配送方式
    'getDeliveryList' => array(
        'query' => array(
            'name' => 'delivery',
            'where'=> 'is_delete = 0 and status = 1',
            'order'=> 'sort asc',
        )
    ),

    //获取文章关联的商品
    'getArticleGoods' => array(
        'query' => array(
            'name'   => 'relation as r',
            'join'   => 'left join goods as go on r.goods_id = go.id',
            'where'  => 'r.article_id in (#article_id#) and go.id is not null',
            'fields' => 'go.id as goods_id,go.img,go.name,go.sell_price',
        )
    ),

    //获取全部特价商品活动
    'getSaleList' => array(
        'file' => 'goods.php','class' => 'APIGoods'
    ),

    //获取商家信息
    'getSaleRow' => array(
        'file' => 'goods.php','class' => 'APIGoods'
    ),

    //获取商品所有回收站数据
    'getGoodsRecycleList' => array(
        'query'=>array(
            'name'    => 'goods as go',
            'order'   => 'go.sort asc',
            'where'   => 'go.is_del = 1',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取订单所有回收站数据
    'getOrderRecycleList' => array(
        'query'=>array(
            'name'    => 'order as o',
            'join'    => 'left join delivery as d on o.distribution = d.id left join payment as p on o.pay_type = p.id left join user as u on u.id = o.user_id',
            'fields'  => 'o.id as oid,d.name as dname,p.name as pname,o.order_no,o.accept_name,o.pay_status,o.pay_type,o.distribution_status,u.username,o.create_time,o.status,o.type,o.goods_type',
            'where'   => 'if_del = 1',
            'page'    => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //用户中心-订单列表
    'getOrderListWithArea' => array(
        'file' => 'order.php','class' => 'APIOrder'
    ),

    //获取退款单列表
    'getListByOrderRefundment' => array(
        'query'=>array(
            'name'   => 'refundment_doc as c',
            'join'   => 'left join user as u on u.id = c.user_id',
            'fields' => 'c.*,u.username',
            'where'  => 'c.if_del = 0 and c.pay_status in(1,2)',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'dispose_time desc',
        )
    ),

    //获取退款单回收站列表
    'getListByOrderRefundmentRecycle' => array(
        'query'=>array(
            'name'    => 'refundment_doc as c',
            'join'    => 'left join user as u on u.id = c.user_id',
            'fields'  => 'c.*,u.username',
            'where'   => 'c.if_del = 1 and c.pay_status in (1,2)',
            'page'    => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'dispose_time desc',
        )
    ),

    //取得所有模型
    'getListByModel'=>array(
        'query'=>array(
            'name'  => 'model',
            'order' => 'id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //前台首页获取幻灯片数据
    'getBannerList' => array(
        'file' => 'other.php','class' => 'APIOther'
    ),

    //获取后台自定义的幻灯片数据
    'getBannerConf' => array(
        'file' => 'other.php','class' => 'APIOther'
    ),

    //获取商品评论数据
    'getListByGoods' => array(
        'file' => 'comment.php','class' => 'APIComment'
    ),

    //获取所有讨论数据
    'getListByDiscussion' => array(
        'query'=>array(
            'name'   => 'discussion as d',
            'fields' => "d.id,d.time,u.id as userid,u.username,goods.id as goods_id,goods.name as goods_name",
            'join'   => "left join goods as goods on d.goods_id = goods.id left join user as u on d.user_id = u.id",
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取供应商所有咨询数据
    'getSellerListByRefer' => array(
        'query'=>array(
            'name'   => 'refer as re',
            'fields' => "se.seller_name,a.admin_name,u.username,re.*,go.name",
            'join'   => "left join goods as go on go.id = re.goods_id left join user as u on u.id = re.user_id left join admin as a on a.id = re.admin_id left join seller as se on se.id = re.seller_id",
            'where'  => "go.seller_id = ". IWeb::$app->getController()->seller['seller_id'],
            'order'  => 're.id desc',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //用户中心-收藏夹信息
    'getFavorite' => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //获取默认广告数据
    'getAdRow' => array(
        'file' => 'other.php','class' => 'APIOther'
    ),

    //获取发票信息列表
    "getInvoiceListByUserId" => array(
        'file' => 'ucenter.php','class' => 'APIUcenter'
    ),

    //获取发票单条信息
    "getInvoiceRowById" => array(
        'query' => array(
            'name'  => 'invoice',
            'where' => 'id = #id#',
            'type'  => 'row',
        )
    ),

    //获取商城系统的某个子分类或兄弟分类
    "catTree" => array(
        'file' => 'goods.php','class' => 'APIGoods'
    ),

    //获取店内分类的某个子分类或兄弟分类
    "catTreeSeller" => array(
        'file' => 'seller.php','class' => 'APISeller'
    ),

    //根据一级分类输出二级分类商家店内列表
    'getSellerCategoryByParentid'=>array(
        'query'=>array(
            'name'  => 'category_seller',
            'where' => ' parent_id = #parent_id# ',
            'order' => ' sort asc',
            'limit' => 10,
        )
    ),

    //某个商家店内商品分类
    'getSellerCategoryList'=>array(
        'query'=>array(
            'name'  =>'category_seller',
            'order' =>'sort asc',
            'where' => 'seller_id=#seller_id#'
        )
    ),

    //根据商家店内商品分类取得商品列表
    'getSellerCategoryExtendList'=>array(
        'query'=>array(
            'name'  => 'category_extend_seller as ca',
            'join'  => 'left join goods as go on go.id = ca.goods_id',
            'where' => 'ca.category_id in(#categroy_id#) and go.is_del = 0',
            'order' => 'go.sort asc',
            'fields'=> 'go.id,go.name,go.img,go.sell_price,go.market_price,go.sale',
            'limit' => 10,
        )
    ),

    //所有一级商家店内分类
    'getSellerCategoryListTop'=>array(
        'query'=>array(
            'name'  => 'category_seller',
            'where' => ' seller_id = #seller_id# and parent_id = 0 ',
            'order' => ' sort asc',
            'limit' => 20,
        )
    ),

    //积分商品列表
    'getCostPointList' => array(
        'query' => array(
            'name'  => 'cost_point as c',
            'join'  => 'left join goods as go on c.goods_id = go.id',
            'where' => 'c.is_close = 0 and go.is_del = 0',
            'order' => 'c.sort asc',
            'fields'=> 'c.*,go.img,go.sell_price,go.name as goods_name',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //积分商品
    'getCostPointRowById'=> array(
        'query' => array(
            'name'  => 'cost_point',
            'fields'=> 'point,goods_id,is_close,seller_id,user_group',
            'where' => '`id` = #id# and is_close = 0',
            'type'  => 'row',
        )
    ),

    //热卖积分商品列表
    'getCostPointHot' => array(
        'query' => array(
            'name'   => 'cost_point as c',
            'join'   => 'left join goods as go on c.goods_id = go.id',
            'where'  => 'c.is_close = 0 and go.is_del = 0',
            'fields' => 'c.*,go.img,go.sell_price,go.name as goods_name',
            'limit'  => '10',
            'order'  => 'go.sale desc'
        )
    ),

    //用户登录
    'userLogin' => array(
        'file' => 'ucenter.php','class' => 'ServiceUcenter'
    ),

    //选择自提点省份
    'getTakeselfProvince' => array(
        'query' => array(
            'name'   => 'takeself as ts',
            'join'   => 'left join areas as a on a.area_id = ts.province',
            'fields' => 'a.*',
            'order'  =>  'ts.sort asc',
            'group'  => 'ts.province',
        )
    ),

    //获取所有会员消息数据
    'getListByMessage' => array(
        'query'=>array(
            'name'  => 'message',
            'fields' => '*',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取会员消息单条消息
    'getUserMessageRowById'=> array(
        'query' => array(
            'name'  => 'message',
            'where' => 'id = #id#',
            'type'  => 'row',
        )
    ),

    //获取所有商户消息
    'getListBySellerMessage' => array(
        'query'=>array(
            'name'  => 'seller_message',
            'fields' => '*',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取商户消息单条消息
    'getSellerMessageRowById'=> array(
        'query' => array(
            'name'  => 'seller_message',
            'where' => 'id = #id#',
            'type'  => 'row',
        )
    ),

    //获取咨询数据单条消息
    'getReferRowById'=> array(
        'query' => array(
            'name'   => 'refer as r',
            'join'   => 'left join goods as goods on r.goods_id = goods.id left join user as u on r.user_id = u.id left join admin as admin on r.admin_id = admin.id left join seller as se on se.id = r.seller_id',
            'fields' => 'se.seller_name,r.*,u.username,goods.name as goods_name,goods.id as goods_id,admin.admin_name',
            'where' => 'r.id = #id#',
            'type'  => 'row',
        )
    ),

    //取得所有模型
    'getModelListAll'=>array(
        'query'=>array(
            'name'  => 'model',
        )
    ),

    //取得所有规格
    'getListBySpec'=>array(
        'query'=>array(
            'name'  => 'spec',
            'where' => "is_del = #is_del# and seller_id = ".(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'order' => 'sort asc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //取得所有规格
    'getListBySpecPhoto'=>array(
        'query'=>array(
            'name'  => 'spec_photo',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //取得所有规格
    'getSpecListAll'=>array(
        'query'=>array(
            'name'  => 'spec',
            'where' => ' seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0).' and is_del = 0 ',
            'order' => 'sort asc',
        )
    ),

    //取得所有商品品牌
    'getBrandListAllOnce'=>array(
        'query'=>array(
            'name'  => 'brand',
        )
    ),

    //取得所有用户组
    'getUserGroupListAll'=>array(
        'query'=>array(
            'name'  => 'user_group',
        )
    ),

    //获取快速导航
    'getQuickNavigaAll'=> array(
        'query' => array(
            'name'  => 'quick_naviga',
            'where' => 'admin_id = '.(isset(IWeb::$app->getController()->admin['admin_id']) ? IWeb::$app->getController()->admin['admin_id'] : 0). ' and is_del = 0',
        )
    ),

    //获取快速导航
    'getListByQuickNaviga'=> array(
        'query' => array(
            'name'  => 'quick_naviga',
            'where' => 'admin_id = '.(isset(IWeb::$app->getController()->admin['admin_id']) ? IWeb::$app->getController()->admin['admin_id'] : 0). ' and is_del = #is_del#',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取积分兑换活动
    'getListByCostPoint'=>array(
        'query'=>array(
            'name'  => 'cost_point as co',
            'join'  => 'left join goods as go on co.goods_id = go.id',
            'fields'=> 'co.id as cid,co.is_close,co.goods_id,co.sort,co.name as cname,go.name,go.id',
            'where' => 'co.seller_id = 0 and go.is_del = 0 AND go.id is not null',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取供应商促销活动列表
    'getSellerListRule'=>array(
        'query'=>array(
            'name'  => 'promotion',
            'where' => "type = 0 and seller_id = ".IWeb::$app->getController()->seller['seller_id'],
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取促销活动列表
    'getListRule'=>array(
        'query'=>array(
            'name'  => 'promotion',
            'where' => "type in (0,5,6,7) and seller_id = ".(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取限时抢购列表
    'getListSpeed'=>array(
        'query'=>array(
            'name'  => 'promotion',
            'where' => "type =1",
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取特价列表
    'getListSale'=>array(
        'query'=>array(
            'name'  => 'promotion',
            'where' => "award_type = 7 and seller_id = 0",
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取团购
    'getListByRegiment'=>array(
        'query'=>array(
            'name'  => 'regiment',
            'order' => 'sort asc,id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取供应商团购
    'getSellerListByRegiment'=>array(
        'query'=>array(
            'name'  => 'regiment',
            'where' => 'seller_id = '.IWeb::$app->getController()->seller['seller_id'],
            'order' => 'sort asc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取代金卷列表
    'getListByTicket'=>array(
        'query'=>array(
            'name'  => 'ticket',
            'order' => 'id desc',
            'where' => 'seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取道具列表
    'getListByProp'=>array(
        'query'=>array(
            'name'  => 'prop',
            'where' => 'type = 0 and `condition` = #ticket_id# '.(isset(IWeb::$app->getController()->seller['seller_id']) ? ' and seller_id ='.IWeb::$app->getController()->seller['seller_id'] : ''),
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有道具列表
    'getPropListAll'=>array(
        'query' => array(
            'name'  => 'prop',
            'where' => 'id = #id#',
        )
    ),

    //取得所有供应商货款结算申请
    'getListBySellerBill'=>array(
        'query' => array(
            'name'  => 'bill',
            'where' => "seller_id = ".IWeb::$app->getController()->seller['seller_id'],
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取特价分类信息
    'getCategorySale'=>array(
        'query' => array(
            'name'  => 'category',
            'where' => 'id in (#id#)',
        )
    ),

    //获取特价单品信息
    'getGoodSale'=>array(
        'query' => array(
            'name'  => 'goods',
            'where' => 'id in (#goods_id#)',
            'fields'=> 'id as goods_id,name'
        )
    ),

    //获取提现申请列表
    'getListByWithdraw'=>array(
        'query'=>array(
            'name'  => 'withdraw as w',
            'join'  => 'left join member as m on w.user_id = m.user_id left join user as u on u.id = w.user_id',
            'fields'=> 'w.*,u.username,m.balance',
            'where' => "w.status in (#status#)",
            'order' => "id desc",
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取营销短信列表
    'getListByMarketing'=>array(
        'query'=>array(
            'name'  => 'marketing_sms',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取到货通知列表
    'getListByNotify'=>array(
        'query'=>array(
            'name'  => 'notify_registry as notify',
            'join'  => 'left join goods as goods on notify.goods_id = goods.id left join user as u on notify.user_id = u.id',
            'fields'=> 'notify.*,u.username,goods.name as goods_name,goods.store_nums',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取邮件订阅列表
    'getListByRegistry'=>array(
        'query'=>array(
            'name'  => 'email_registry',
            'order' => 'id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取营销短信单条消息
    'getMarketingRowById'=> array(
        'query' => array(
            'name'  => 'marketing_sms',
            'where' => 'id = #id#',
            'type'  => 'row',
        )
    ),

    //获取收款单列表
    'getListByCollection'=>array(
        'query'=>array(
            'name'  => 'collection_doc as c',
            'join'  => 'left join order as o on c.order_id = o.id left join user as u on u.id = c.user_id left join payment as p on c.payment_id = p.id',
            'fields'=> 'o.order_no,c.amount,u.username,p.name,c.id,c.pay_status,c.time',
            'where' => 'c.if_del = 0',
            'order' => 'c.id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取收款单列表
    'getRecycleListByCollection'=>array(
        'query'=>array(
            'name'  => 'collection_doc as c',
            'join'  => 'left join order as o on c.order_id = o.id left join user as u on u.id = c.user_id left join payment as p on c.payment_id = p.id',
            'fields'=> 'o.order_no,c.amount,u.username,p.name,c.id,c.pay_status,c.time',
            'where' => 'c.if_del = 1',
            'order' => 'c.id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //取得管理员详情
    'getAdminInfo'=>array(
        'query' => array(
            'name'  => 'admin',
            'where' => "id= #admin_id# and is_del = 0",
            'type'  => 'row',
        )
    ),

    //获取发货单列表
    'getListByDeliveryDoc'=>array(
        'query'=>array(
            'name'  => 'delivery_doc as c',
            'join'  => 'left join order as o on c.order_id = o.id left join user as m on m.id = c.user_id left join freight_company as fr on c.freight_id = fr.id',
            'fields'=> 'o.order_no,c.name,c.delivery_code,fr.freight_name,c.id,c.time,c.freight,m.username',
            'where' => 'c.if_del = #if_del#',
            'order' => 'o.id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //根据delivery_id获取商品信息
    'getOrderGoodsRowByDeliveryId'=> array(
        'query' => array(
            'name'  => 'order_goods',
            'where' => 'delivery_id = #id#',
        )
    ),

    //根据订单id获取商品信息
    'getOrderGoodsRowByOrderId'=> array(
        'query' => array(
            'name'  => 'order_goods',
            'where' => 'order_id in (#id#)',
        )
    ),

    //根据供应商订单id获取商品信息
    'getSellerOrderGoodsRowByOrderId'=> array(
        'query' => array(
            'name'  => 'order_goods',
            'where' => 'order_id in (#id#) and seller_id = '.IWeb::$app->getController()->seller['seller_id'],
        )
    ),

    //根据id获取商品信息
    'getOrderGoodsRowById'=> array(
        'query' => array(
            'name'  => 'order_goods',
            'where' => 'id in (#id#)',
        )
    ),

    //根据订单id获取已支付金额
    'getAmountRowByOrderId'=> array(
        'query' => array(
            'name'  => 'collection_doc',
            'where' => 'order_id = #order_id# and if_del = 0',
            'fields'=> 'amount',
            'type'  => 'row'
        )
    ),

    //根据订单id获取收款单据
    'getCollectionDocByOrderId'=> array(
        'query' => array(
            'name'  => 'collection_doc as c',
            'join'  => 'left join payment as p on c.payment_id = p.id',
            'where' => 'c.order_id = #order_id#',
            'fields'=> 'c.*,p.name',
        )
    ),

    //根据订单id获取退款单据
    'getRefundmentDocByOrderId'=> array(
        'query' => array(
            'name'  => 'refundment_doc',
            'where' => 'order_id = #order_id#',
        )
    ),

    //获取退款单申请列表
    'getRefundmentList'=> array(
        'query'=>array(
            'name'  => 'refundment_doc',
            'where' => 'if_del = 0 and pay_status not in(1,2)'.(isset(IWeb::$app->getController()->seller['seller_id']) ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
            'order' => 'dispose_time desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //根据商品ID获取品牌数据
    'getBrandByGoodsId'=> array(
        'query' => array(
            'name'  => 'brand as b',
            'join'  => 'left join goods as go on go.brand_id = b.id',
            'where' => 'go.id = #id#',
            'fields'=>'b.name'
        )
    ),

    //获取全部快递单打印公司
    'getExpresswaybill'=> array(
        'query' => array(
            'name'  => 'expresswaybill',
            'where' => 'seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
        )
    ),

    //获取已启用的快递单打印公司
    'getExpresswaybillIsOpen'=> array(
        'query' => array(
            'name'  => 'expresswaybill',
            'where' => 'is_open = 1 and seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
        )
    ),

    //根据ID获取快递单打印公司信息
    'getExpresswaybillById'=> array(
        'query' => array(
            'name'  => 'expresswaybill as e',
            'join'  => 'left join freight_company as fc on fc.freight_type = e.freight_type',
            'fields'=> 'e.*,fc.id as freight_id',
            'where' => 'e.id = #id#'.(isset(IWeb::$app->getController()->admin['admin_id']) ? '' : ' and e.seller_id = '.IWeb::$app->getController()->seller['seller_id']),
            'type'  => 'row',
        )
    ),

    //获取支付方式数据
    'getPayment'=> array(
        'query' => array(
            'name'  => 'payment',
            'where' => 'status = 0',
        )
    ),

    //根据订单ID获取发货记录
    'getDeliveryDocByOrderId'=> array(
        'query' => array(
            'name'  => 'delivery_doc as c',
            'join'  => 'left join delivery as p on c.delivery_type = p.id',
            'where' => 'c.order_id = #order_id# and c.if_del = 0',
            'fields'=>'c.*,p.name as pname'
        )
    ),

    //获取配送方式
    'getDelivery'=> array(
        'query' => array(
            'name'  => 'delivery',
            'where' => 'is_delete = 0',
        )
    ),

    //根据ID获取配送方式
    'getDeliveryById'=> array(
        'query' => array(
            'name'  => 'delivery',
            'where' => 'id = #distribution#',
        )
    ),

    //获取物流公司
    'getFreightCompany'=> array(
        'query' => array(
            'name'  => 'freight_company',
            'where' => 'is_del = 0',
            'order' => 'sort asc',
        )
    ),

    //根据ID获取物流公司
    'getFreightCompanyById'=> array(
        'query' => array(
            'name'  => 'freight_company',
            'where' => 'id = #freight_id#',
        )
    ),

    //根据订单ID获取订单日志
    'getOrderLogByOrderId'=> array(
        'query' => array(
            'name'  => 'order_log as ol',
            'where' => 'ol.order_id = #order_id#',
        )
    ),

    //获取所有发货信息
    'getShipInfoList' => array(
        'query'=>array(
            'name'  => 'merch_ship_info',
            'where' => "is_del = #is_del# and seller_id = ".(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'order' => 'id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    'getShipInfoRowById' => array(
    	'query' => array(
    		'name'  => 'merch_ship_info',
    		'type'  => 'row',
    		'where' => 'id = #id# and is_del = 0'
    	)
    ),

    //获取所有广告位
    'getAdPositionRowById'=> array(
        'query' => array(
            'name'  => 'ad_position',
            'where' => 'id = #id#',
            'type'  => 'row'
        )
    ),

    //获取所有广告列表
    'getAdList' => array(
        'query'=>array(
            'name'  => 'ad_manage as ad',
            'join'  => 'left join ad_position as adp on ad.position_id = adp.id',
            'fields'=> 'adp.name as adp_name,ad.*',
            'order' => 'ad.order ASC',
            'where' => 'ad.seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有广告位列表
    'getAdPositionList' => array(
        'query'=>array(
            'name'  => 'ad_position',
            'where' => 'seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有文章列表
    'getAllArticleList' => array(
        'query'=>array(
            'name'  => 'article as ar',
            'join'  => 'left join article_category as ac on ac.id = ar.category_id',
            'fields'=> 'ar.id,ar.title,ar.create_time,ar.top,ar.style,ar.color,ar.sort,ar.visibility,ac.name',
            'order' => 'ar.sort asc,ar.id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有帮助分类列表
    'getHelpCategoryAll'=>array(
        'query'=>array(
            'name'  => 'help_category',
            'order' => 'sort ASC,id DESC',
        )
    ),

    //获取所有帮助分类信息
    'getHelpCategoryInfo'=>array(
        'query'=>array(
            'name'  => 'help_category',
            'where' => 'id = #id#',
            'type'  => 'row',
        )
    ),

    //获取所有搜索统计列表
    'getSearchList'=>array(
        'query'=>array(
            'name'  => 'search',
            'order' => 'num desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有管理员
    'getListByAdmin'=>array(
        'query'=>array(
            'name'  => 'admin as a',
            'join'  => 'left join admin_role as b on a.role_id = b.id',
            'fields'=> 'a.*,b.name as role_name',
            'where' => 'a.is_del = #is_del#',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有角色
    'getAreaAll'=>array(
        'query'=>array(
            'name'   => 'areas',
            'where'  => 'parent_id = #parent_id#',
            'order'  => 'sort asc',
        )
    ),

    //系统-商家数量
    'getSellerCount'=>array(
        'query'=>array(
            'name'  =>'seller',
            'fields'=>'count(*) as amount',
            'where' =>'is_lock = #is_lock# and is_del = 0',
            'type'  =>'amount'
        )
    ),

    //系统-销售总额
    'getOrderAmount'=>array(
        'query'=>array(
            'name'  =>'order',
            'fields'=>'sum(order_amount) as amount',
            'where' =>'`pay_status` = 1',
            'type'  =>'amount'
        )
    ),

    //系统-注册用户
    'getCountRegUser'=>array(
        'query'=>array(
            'name'  =>'member',
            'fields'=>'count(*) as countNums',
            'where' => 'status in (1,3)',
            'type'  =>'countNums'
        )
    ),

    //系统-品牌数量
    'getBrandCount'=>array(
        'query'=>array(
            'name'  =>'brand',
            'fields'=>'count(id) as countNums',
            'type'  =>'countNums'
        )
    ),

    //系统-订单数量
    'getCountOrder'=>array(
        'query'=>array(
            'name'  =>'order',
            'fields'=>'count(id) as countNums',
            'where' =>'if_del = 0',
            'type'  =>'countNums'
        )
    ),

    //系统-库存预警
    'getGoodsWarning'=>array(
        'query'=>array(
            'name'  =>'goods',
            'fields'=>'count(id) as countNums',
            'where' =>'is_del = 0 and store_nums < #store_num_warning#',
            'type'  =>'countNums'
        )
    ),

    //系统-待回复建议
    'suggestionWaitCount'=>array(
        'query'=>array(
            'name'  =>'suggestion',
            'fields'=>'count(*) as countNums',
            'where' =>'re_time is null',
            'type'  =>'countNums'
        )
    ),

    //系统-付款未发货订单
    'orderWaitCount'=>array(
        'query'=>array(
            'name'  =>'order',
            'fields'=>'count(id) as countNums',
            'where' =>'distribution_status = 0 and pay_status = 1 and if_del = 0 and status in (1,2)'.(isset(IWeb::$app->getController()->seller['seller_id']) && IWeb::$app->getController()->seller['seller_id'] > 0 ? " and seller_id = ".IWeb::$app->getController()->seller['seller_id'] : ""),
            'type'  =>'countNums'
        )
    ),

    //系统-待审提现申请
    'withdrawWaitCount' => array(
        'query'=>array(
            'name'  =>'withdraw',
            'fields'=>'count(id) as countNums',
            'where' =>'status = 0',
            'type'  =>'countNums'
        )
    ),

    //系统-待审商品
    'goodsWaitCount'=>array(
        'query'=>array(
            'name'  =>'goods',
            'fields'=>'count(id) as countNums',
            'where' =>'is_del = 3',
            'type'  =>'countNums'
        )
    ),

    //系统-最新10条等待处理订单
    'getNewsOrderList'=>array(
        'query'=>array(
            'name'  =>'order as o',
            'join'  =>'left join delivery as d on o.distribution = d.id left join payment as p on o.pay_type = p.id left join user as u on u.id = o.user_id',
            'fields'=>'o.id as oid,d.name as dname,p.name as pname,o.order_no,o.accept_name,o.pay_status,o.distribution_status,u.username,o.create_time,o.status,o.order_amount',
            'where' =>'o.status < 3 and if_del = 0',
            'order' =>'o.id desc',
            'limit' =>10
        )
    ),

    //获取所有配送方式
    'getListByDelivery'=>array(
        'query'=>array(
            'name'  => 'delivery',
            'where' => 'is_delete = #is_delete#',
            'order' => 'sort',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取供应商所有配送方式
    'getListBySellerDelivery'=>array(
        'query'=>array(
            'name'  => 'delivery',
            'where' => 'is_delete = 0 and status = 1',
            'order' => 'sort',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有物流公司
    'getListByFreightCompany'=>array(
        'query'=>array(
            'name'  => 'freight_company',
            'where' => 'is_del = #is_del#',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取所有快捷登录
    'getOauthListAll'=>array(
        'query'=>array(
            'name'  => 'oauth',
        )
    ),

    //获取后台所有支付方式
    'getPaymentAll'=> array(
        'query' => array(
            'name'  => 'payment',
            'where' => 'id > 0',
        )
    ),

    //获取所有权限资源
    'getRightListAll'=> array(
        'query' => array(
            'name'  => 'right',
            'where' => 'is_del = #is_del#',
        )
    ),

    //获取后台所有角色
    'getAdminRoleListAll'=>array(
        'query'=>array(
            'name'  => 'admin_role',
            'where' => 'is_del = #is_del#',
        )
    ),

    //获取所有自提点列表
    'getListByTakeself' => array(
        'query'=>array(
            'name'  => 'takeself as o',
            'join'  => 'left join areas as d on o.area = d.area_id',
            'fields'=> 'o.id,o.name, o.sort,o.province,o.city,o.area,o.address,o.phone,o.mobile,d.area_name',
            'order' => 'sort',
            'where' => 'seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) ? IWeb::$app->getController()->seller['seller_id'] : 0),
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取供应商物流配送配置信息
    'getSellerDeliveryExtendRowById'=>array(
        'query'=>array(
            'name'  =>'delivery_extend',
            'where' =>'seller_id = '.IWeb::$app->getController()->seller['seller_id'].' and delivery_id = #delivery_id#',
            'type'  =>'row'
        )
    ),

    //根据ID获取订单
    'getOrderRowById'=>array(
        'query'=>array(
            'name'  =>'order',
            'where' =>'id = #order_id#',
            'type'  => 'row',
        )
    ),

	//商品数量
    'goodsCount' => array(
    	'query' => array(
	    	'name' => 'goods',
	    	'fields'=> 'count(*) as num',
	    	'where' => 'is_del in (0,2,3)'.(isset(IWeb::$app->getController()->seller['seller_id']) && IWeb::$app->getController()->seller['seller_id'] > 0 ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
	    	'type'  => 'num',
    	)
    ),

    //退款申请数量
    'refundsCount' => array(
    	'query' => array(
	    	'name' => 'refundment_doc',
	    	'fields'=> 'count(*) as num',
	    	'where' => 'pay_status in (0,4) and if_del = 0 '.(isset(IWeb::$app->getController()->seller['seller_id']) && IWeb::$app->getController()->seller['seller_id'] > 0 ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
	    	'type'  => 'num',
    	)
    ),

    //待回复评论
    'commentWaitCount' => array(
    	'query' => array(
	    	'name' => 'comment',
	    	'fields'=> 'count(*) as num',
	    	'where' => 'status = 1 and recomment_time = 0 '.(isset(IWeb::$app->getController()->seller['seller_id']) && IWeb::$app->getController()->seller['seller_id'] > 0 ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
	    	'type'  => 'num',
    	)
    ),

    //待回复咨询
    'referWaitCount' => array(
    	'query' => array(
	    	'name' => 'refer as re',
	    	'join' => 'left join goods as go on go.id = re.goods_id',
	    	'fields'=> 'count(*) as num',
	    	'where' => 're.status = 0 and go.seller_id = '.(isset(IWeb::$app->getController()->seller['seller_id']) && IWeb::$app->getController()->seller['seller_id'] > 0 ? IWeb::$app->getController()->seller['seller_id'] : 0),
	    	'type'  => 'num',
    	)
    ),

    //评论数
    'commentCount' => array(
    	'query' => array(
	    	'name' => 'comment',
	    	'fields'=> 'count(*) as num',
	    	'where' => 'status = 1 '.(isset(IWeb::$app->getController()->seller['seller_id']) && IWeb::$app->getController()->seller['seller_id'] > 0 ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
	    	'type'  => 'num',
    	)
    ),

    //获取单品手续费列表
    'getGoodsRateList' => array(
        'query' => array(
            'name' => 'goods_rate as gr',
            'join' => 'left join goods as go on gr.goods_id = go.id',
            'fields' => 'gr.goods_id,gr.goods_rate,go.name',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取分类手续费列表
    'getCategoryRateList' => array(
        'query' => array(
            'name' => 'category_rate as r',
            'join' => 'left join category as c on r.category_id = c.id',
            'fields' => 'r.category_id,r.category_rate,c.name',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //根据订单id获取商品手续费信息
    'getOrderGoodsServicefeeRowByOrderId' => array(
        'query' => array(
            'name' => 'order_goods_servicefee as ogs',
            'join' => 'left join order_goods as og on ogs.order_goods_id = og.id',
            'fields' => 'ogs.type,ogs.rate,ogs.discount,ogs.amount,og.goods_id,og.real_price,og.goods_nums,og.goods_array',
            'where' => 'ogs.order_id = #id#',
            'order' => 'ogs.id asc',
        )
    ),

    //根据订单ID获取虚拟商品验证码信息
    'getOrderCodeByOrderId' => array(
        'query' => array(
            'name' => 'order_code_relation',
            'where'=> 'order_id = #id#',
        )
    ),

    //根据订单ID获取虚拟商品下载信息
    'getOrderDownloadByOrderId' => array(
        'query' => array(
            'name'   => 'order_download_relation as odr',
            'join'   => 'left join goods_extend_download as ged on odr.goods_id = ged.goods_id',
            'where'  => 'odr.order_id = #id#',
            'fields' => 'odr.*,ged.url,ged.end_time,ged.limit_num',
        )
    ),

    //根据消费码获取信息
    'getCodeInfo' => array(
        'file' => 'order.php','class' => 'APIOrder'
    ),

    //系统-商家货款待结算订单数
    'billWaitCount'=>array(
        'query'=>array(
            'name'  =>'order',
            'fields'=>'count(*) as countNums',
            'where' =>'status in (5,7) and pay_type > 0 and pay_status = 1 and is_checkout = 0 and seller_id > 0 and TO_DAYS(NOW()) - TO_DAYS(completion_time) >= '.intval(IWeb::$app->getController()->_siteConfig->low_bill),
            'type'  =>'countNums'
        )
    ),

    //系统-换货申请
    'exchangeWaitCount'=>array(
        'query'=>array(
            'name'  =>'exchange_doc',
            'fields'=>'count(*) as countNums',
            'where' =>'status in (0,4)',
            'type'  =>'countNums'
        )
    ),

    //系统-维修申请
    'fixWaitCount'=>array(
        'query'=>array(
            'name'  =>'fix_doc',
            'fields'=>'count(*) as countNums',
            'where' =>'status in (0,4)',
            'type'  =>'countNums'
        )
    ),

    //系统-团购活动申请
    'regimentWaitCount'=>array(
        'query'=>array(
            'name'  =>'regiment',
            'fields'=>'count(*) as countNums',
            'where' =>'is_close = 2',
            'type'  =>'countNums'
        )
    ),

    //换货单申请
    'getExchangeList' => array(
        'query'=>array(
            'name'  => 'exchange_doc',
            'where' => 'if_del = 0 and status not in (1,2)'.(isset(IWeb::$app->getController()->seller['seller_id']) ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
            'order' => 'id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
            'order' => 'id desc',
        )
    ),

    //维修单申请
    'getFixList' => array(
        'query'=>array(
            'name'  => 'fix_doc',
            'where' => 'if_del = 0 and status not in (1,2)'.(isset(IWeb::$app->getController()->seller['seller_id']) ? ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'] : ''),
            'order' => 'id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'id desc',
        )
    ),

    //获取换货单列表
    'getListByOrderExchange' => array(
        'query'=>array(
            'name'   => 'exchange_doc as c',
            'join'   => 'left join user as u on u.id = c.user_id',
            'fields' => 'c.*,u.username',
            'where'  => 'c.if_del = 0 and c.status in(1,2)',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'dispose_time desc',
        )
    ),

    //获取换货单回收站列表
    'getListByOrderExchangeRecycle' => array(
        'query'=>array(
            'name'    => 'exchange_doc as c',
            'join'    => 'left join user as u on u.id = c.user_id',
            'fields'  => 'c.*,u.username',
            'where'   => 'c.if_del = 1 and c.status in(1,2)',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'dispose_time desc',
        )
    ),

    //获取维修单列表
    'getListByOrderFix' => array(
        'query'=>array(
            'name'   => 'fix_doc as c',
            'join'   => 'left join user as u on u.id = c.user_id',
            'fields' => 'c.*,u.username',
            'where'  => 'c.if_del = 0 and c.status in(1,2)',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'dispose_time desc',
        )
    ),

    //获取换维修回收站列表
    'getListByOrderFixRecycle' => array(
        'query'=>array(
            'name'   => 'fix_doc as c',
            'join'   => 'left join user as u on u.id = c.user_id',
            'fields' => 'c.*,u.username',
            'where'  => 'c.if_del = 1 and c.status in(1,2)',
            'page'   => IReq::get('page') ? IReq::get('page') : 1,
            'order'  => 'dispose_time desc',
        )
    ),

    //根据订单id获取换货单据
    'getExchangeDocByOrderId'=> array(
        'query' => array(
            'name'  => 'exchange_doc',
            'where' => 'order_id = #order_id#',
        )
    ),

    //根据订单id获取维修单据
    'getFixDocByOrderId'=> array(
        'query' => array(
            'name'  => 'fix_doc',
            'where' => 'order_id = #order_id#',
        )
    ),

    //根据自提码获取信息
    'getTakeselfInfo' => array(
        'file' => 'order.php','class' => 'APIOrder'
    ),

    //根据订单ID获取时间类商品信息
    'getOrderPreorderByOrderId' => array(
        'query' => array(
            'name'   => 'order_extend_preorder',
            'where'  => 'order_id = #id#',
        )
    ),

    //拼团列表
    'getAssembleList' => array(
        'query' => array(
            'name'  => 'assemble as r',
            'join'  => 'left join goods as go on r.goods_id = go.id',
            'where' => 'r.is_close = 0  and go.is_del = 0',
            'order' => 'r.sort asc',
            'fields'=> 'r.*',
            'limit' => '10',
        )
    ),

    //获取全部拼团
    'getListByAssemble'=>array(
        'query'=>array(
            'name'  => 'assemble',
            'order' => 'sort asc,id desc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //椐据ID拼团
    'getAssembleRowById' => array(
        'query' => array(
            'name'  => 'assemble',
            'where' => 'id = #id# and is_close = 0',
            'type'  => 'row',
        )
    ),

    //获取商家拼团
    'getSellerListByAssemble'=>array(
        'query'=>array(
            'name'  => 'assemble',
            'where' => 'seller_id = '.IWeb::$app->getController()->seller['seller_id'],
            'order' => 'sort asc',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //根据订单号获取拼团组信息
    'getAssembleCommanderByOrderNo'=>array(
        'query'=>array(
            'name'  => 'assemble_active as aa',
            'join'  => 'left join assemble_commander as ac on aa.assemble_commander_id = ac.id',
            'where' => 'aa.order_no = "#order_no#"',
            'fields'=> 'aa.is_pay,ac.*',
            'type'  => 'row',
        )
    ),

    //获取专题列表
    'getTopicList'=>array(
        'query'=>array(
            'name'  => 'topic',
            'page'  => IReq::get('page') ? IReq::get('page') : 1,
        )
    ),

    //获取专题列表
    'getTopicRow'=>array(
        'query'=>array(
            'name'  => 'topic',
            'type'  => 'row',
            'where' => 'id = #id#',
        )
    ),

    //获取充值活动
    'getPromotionByOnline'=> array(
        'query' => array(
            'name'  => 'promotion',
            'where' => 'type = 6 and NOW() between start_time and end_time and is_close = 0',
            'order' => 'sort asc',
        )
    ),

    //统计商家订单总额
    'getSellerSellAmount'=> array(
        'file' => 'seller.php','class' => 'APISeller'
    ),

    //可提现订单数量
    'getSellerOrderNumCheckout'=> array(
        'file' => 'seller.php','class' => 'APISeller'
    ),

    //累计提现额
    'getSellerBillPay'=> array(
        'query' => array(
            'name'  => 'bill',
            'where' => 'seller_id = #seller_id#',
            'fields'=> 'SUM(`amount`) as amount',
            'type'  => 'amount',
        )
    ),

    //获取某个商家可以领取优惠券列表
    'getFreeTicketList'=>array(
        'file' => 'other.php','class' => 'APIOther'
    ),

	//获取合并支付订单
	'getBatchOrder' => ['file' => 'order.php','class' => 'APIOrder'],
);