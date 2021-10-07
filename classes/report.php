<?php
/**
 * @copyright (c) 2014 aircheng
 * @file report.php
 * @brief 导出excel类库
 * @author dabao
 * @date 2014/11/28 22:09:43
 * @version 1.0.0

 * @update 4.6
 * @date 2016/9/15 23:30:28
 * @author nswe
 * @content 重构了写入方式和方法
 */
class report
{
	//文件名
	private $fileName = 'user';

	//数据内容
	private $_data    = "";

	//构造函数
	public function __construct($fileName = '')
	{
		$this->setFileName($fileName);
	}

	//设置要导出的文件名
	public function setFileName($fileName)
	{
		$this->fileName = $fileName;
	}

	/**
	 * @brief 写入标题操作
	 * @param $data array 一维数组
	 */
	public function setTitle($data = array())
	{
		array_walk($data,function(&$val,$key)
		{
			$val = "<th style='text-align:center;background-color:green;color:#fff;font-size:12px;vnd.ms-excel.numberformat:@'>".$val."</th>";
		});
		$this->_data = "<tr>".join($data)."</tr>" . $this->_data;
	}

	/**
	 * @brief 写入内容操作，每次存入一行
	 * @param $data array  数据
	 */
	public function setData($data = array())
	{
		array_walk($data,function(&$val,$key)
		{
			$dataType = is_numeric($val) && strlen($val) >= 10 ? "vnd.ms-excel.numberformat:@" : "";
			$val = "<td style='text-align:center;font-size:12px;".$dataType."'>".$val."</td>";
		});
		$this->_data .= "<tr>".join($data)."</tr>";
	}

	/**
	 * @brief 写入结尾操作
	 * @param $data array 一维数组
	 */
	public function setTail($data = array())
	{
		array_walk($data,function(&$val,$key)
		{
			$val = "<th style='text-align:center;background-color:orange;color:#fff;font-size:12px;vnd.ms-excel.numberformat:@'>".$val."</th>";
		});
		$this->_data = $this->_data . "<tr>".join($data)."</tr>";
	}

	//开始下载
	public function toDownload($data = '')
	{
		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename='.$this->fileName.'_'.date('Y-m-d').'.xls');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$result = $data ? $data : "<table border='1'>".$this->_data."</table>";
echo <<< OEF
<html>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<body>
	{$result}
	</body>
</html>
OEF;
	}
}