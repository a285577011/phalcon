<?php
namespace core;
use Phalcon\Http\Request\file;
class UploadFile {

	private $savePath='';
	protected $preid;
	function __construct($preid)
	{
		$this->savePath=\core\Config::item('avatar_upload_path');
		$this->preid = $preid;
		
	}
	
	/**
	 * 文件上传
	 *
	 * @param string $fieldname 文件名
	 * @return string 上传的信息
	 */
	public function UploadFileImg($files)
	{
		 $data=array();
		 $allowedtypes = array('image/pjpeg','image/gif','image/jpg','image/jpeg','image/png');
		 $maxsize = 1024*1024;
		 $filename = date("Ymd").mt_rand(100,999).date("His").mt_rand(0,9);
		 $fileend = explode('.', $files->getName());
		 $count = count($fileend);
		 $filename = $filename . '.' . $fileend[$count-1];
		 if(strpos($files->getRealType(),'image') === false)
		 {
		 	$data = array('flag' =>false,'error' =>'请上传正常的图片类型');
		 	return $data;
		 }
		 if (!in_array($files->getType(), $allowedtypes))
		 {
		 	$data = array('flag' =>false,'error' =>'文件类型错误');
		 	return $data;
		 } 
		if($files->getSize() > $maxsize)
		 {
		 	$data = array('flag' =>false,'error' =>'文件尺寸过大');
		 	return $data;
		 }
		 if($files->moveTo($this->savePath.$filename))
		 {
		 	$data = array('flag' =>true,'error' =>'文件上传成功','apath'=>$filename);
		 }
		 else
		 {
		 	$data = array('flag' =>false,'error' =>$files-> getError());
		 }
		 return $data;
	}
	
}

?>