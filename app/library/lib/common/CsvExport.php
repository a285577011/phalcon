<?php
namespace lib\common;
class CsvExport
{
	public static function csvOutput($filename, $info)
	{
		ob_clean();
		header("Content-Type:text/csv; charset=utf-8");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Pragma: public");
		header("Expires: 0");
		
		//判断浏览器，输出双字节文件名不乱码
		$filename .= '.csv';
		$encoded_filename = urlencode($filename);
		$encoded_filename = str_replace("+", "%20", $encoded_filename);

		$ua = $_SERVER["HTTP_USER_AGENT"];
		if(preg_match("/MSIE/", $ua))
		{
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		}
		else if(preg_match("/Firefox/", $ua))
		{
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		}
		else
		{
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		$file = fopen("php://output", "a");
		//fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
		foreach($info as $v=>$list){
			for($i=0;$i<count($list);$i++){
				$content[$v][]=@iconv("utf-8", "gb2312", $list[$i]);
			}
			fputcsv($file, $content[$v]);
		}
		fclose($file);
		exit;
	}
	/**
	 * 使用foutcsv导出
	 *
	 * @param $tableName 文件名
	 * @param $head 内容标题
	 * @param $data 数据内容  为二维数组
	 */
	public static function outcsv($tableName, $head, $data)
	{
		header('Content-Disposition: attachment;filename=' . $tableName . '.csv');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'a');
		foreach($head as $list)
			$title[]=@iconv("utf-8", "gb2312", $list);
		fputcsv($fp, $title);
		foreach($data as $v=>$list){
			for($i=0;$i<count($list);$i++){
				$content[$v][]=@iconv("utf-8", "gb2312", $list[$i]);
			}
			fputcsv($fp, $content[$v]);
		}
	}
}
