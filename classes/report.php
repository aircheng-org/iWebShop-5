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

 * @update 5.13
 * @date 14:44 2022/9/23
 * @author nswe
 * @content 用phpexcel重写,生成原生的excel
 */
class report
{
	//文件名
	private $fileName = 'user';

	//数据内容
	private $_data    = "";

	//phpexcel对象
	private $phpexcel = null;

	//字符串类型的列数
	public $dataStringCols = [];

	//构造函数
	public function __construct($fileName = '')
	{
		$this->setFileName($fileName);
		$this->phpexcel = new PHPExcel();
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
	public function setTitle($data = [])
	{
		foreach($data as $indexNum => $col)
		{
			$this->phpexcel->getActiveSheet()->setCellValueByColumnAndRow($indexNum,1,$col);
		}
	}

	/**
	 * @brief 写入内容操作，每次存入一行
	 * @param $data array  数据
	 */
	public function setData($data = array())
	{
		$lastNum = $this->phpexcel->getActiveSheet(0)->getHighestRow()+1;
		foreach($data as $indexNum => $col)
		{
			if($this->dataStringCols && in_array($indexNum,$this->dataStringCols))
			{
				$this->phpexcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($indexNum,$lastNum,$col);
			}
			else
			{
				$this->phpexcel->getActiveSheet()->setCellValueByColumnAndRow($indexNum,$lastNum,$col);
			}
		}
	}

	/**
	 * @brief 写入结尾操作
	 * @param $data array 一维数组
	 */
	public function setTail($data = array())
	{
		$lastNum = $this->phpexcel->getActiveSheet(0)->getHighestRow()+1;
		foreach($data as $indexNum => $col)
		{
			$this->phpexcel->getActiveSheet()->setCellValueByColumnAndRow($indexNum,$lastNum,$col);
		}
	}

	//开始下载
	public function toDownload($data = '')
	{
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename='.$this->fileName.'_'.date('Y-m-d').'.xlsx');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$phpexcel = new PHPExcel_Writer_Excel2007($this->phpexcel);
		$phpexcel->save('php://output');
	}
}