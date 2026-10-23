<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>银联云闪付</title>
    <style>
	* {
		margin: 0;
		padding: 0;
		box-sizing: border-box;
		font-family: 'Microsoft YaHei', sans-serif;
	}

	body {
		background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
		display: flex;
		justify-content: center;
		align-items: center;
		min-height: 100vh;
		padding: 20px;
	}

	.payment-container {
		width: 520px;
		padding: 40px 30px;
		background-color: #fff;
		border-radius: 20px;
		box-shadow: 0 10px 30px rgba(0,0,0,0.12);
		border: 1px solid rgba(0,0,0,0.05);
		text-align: center;
		position: relative;
		overflow: hidden;
	}

	.unionpay-logo {
		width: 100%;
		height: 50px;
		background: linear-gradient(90deg, #0066cc, #0099ff);
		border-radius: 8px;
		margin-bottom: 36px;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 4px 12px rgba(0,0,0,0.08);
	}

	.logo-text {
		color: #fff;
		font-size: 20px;
		font-weight: bold;
		text-shadow: 0 1px 2px rgba(0,0,0,0.1);
	}

	.qr-code-area {
		width: 260px;
		height: 260px;
		background-color: #f5f7fa;
		border: 2px dashed #0066cc;
		border-radius: 16px;
		margin: 0 auto 32px;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		box-shadow: 0 4px 12px rgba(0,0,0,0.05);
	}

	.qr-code {
		width: 100%;
		height: 100%;
		background-color: #fff;
	}

	.payment-tip {
		color: #333;
		font-size: 16px;
		margin-bottom: 30px;
		font-weight: 500;
	}

	.btn-pay {
		width: 100%;
		height: 50px;
		background-color: #0066cc;
		color: #fff;
		border: none;
		border-radius: 8px;
		font-size: 18px;
		cursor: pointer;
		transition: all 0.3s ease;
		box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	}

	.btn-pay:hover {
		background-color: #004080;
		transform: translateY(-1px);
		box-shadow: 0 4px 12px rgba(0,0,0,0.15);
	}

	.btn-pay:active {
		transform: translateY(0);
	}

	.order-info {
		margin-top: 24px;
		padding-top: 16px;
		border-top: 1px solid #e8e8e8;
		font-size: 14px;
		color: #666;
	}

	.order-info span {
		margin: 0 8px;
	}
    </style>
    <script src="https://code.jquery.com/1.13.3/jquery.min.js" integrity="sha256-sw0iNNXmOJbQhYFuC9OF2kOlD5KQKe1y5lfBn4C9Sjg=" crossorigin="anonymous"></script>
</head>
<body>
<div class="payment-container">
	<div class="unionpay-logo">
		<span class="logo-text">银联云闪付</span>
	</div>

	<div class="qr-code-area">
		<div class="qr-code" id="qrCode">
			<img src="<?php echo $sendData['code_img'];?>" width="256px" />
		</div>
	</div>

	<p class="payment-tip">请使用云闪付APP扫码完成支付</p>

	<button class="btn-pay" onclick="window.location.href='<?php echo $sendData['url'];?>';">我已经支付</button>

	<div class="order-info">
		<span>订单号：<?php echo $sendData['order_no'];?></span>
		<span>金额：¥<?php echo $sendData['amount'];?></span>
	</div>
</div>
<script>
function orderCheck()
{
	jQuery.getJSON("<?php echo $sendData['orderCheckUrl'];?>",function(content)
	{
		//支付成功跳转走
		if(content.result == 1)
		{
			window.location.href="<?php echo $sendData['successUrl'];?>";
		}
	})
}
setInterval(orderCheck,3000);
</script>
</body>
</html>
