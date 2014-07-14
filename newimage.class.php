<?php 

class newimage
{
	//final path for image's pre size, mid size and full size 
	public $prepath;
	public $midpath;
	public $fullpath;
	public $dlogopath;
	//break's switch
	public $break;
	//image's source sizes
	public $height;
	public $width;
	//new name, it's the similar as pid
	public $newname;
	//dealer id, it's check dealer folder.
	protected $u_id;
	//count of image
	protected $count;
	//array of images
	public $images;
	public $midpathflag;
	// pathes Массив с путями для записи фото, array('frontimg' => (object)array('width' => '241', 'height' => '310')));
	// имя свойства может быть любым, обычно используется наименование относительного размера картинки, 
	// например midpath путь к средней картинке. 
	public $pathes;
	//абсолютный путь к папке с сайтом example: /var/www/iphone/
	public $sitepath;
	//абсолютный путь к директории временных файлов
	public $tmppath;
	protected $db;

	function __construct($u_id, $newname, $pathes, $sitepath, $tmppath)
	{	
		if(!is_numeric($u_id)) return false;
		$this->u_id = $u_id;
		foreach ($pathes as $k => $v) 
			$this->pathes->$k = array_merge($v, array('sizes' => $v['width'].'x'.$v['height']));
		
		$this->sitepath = $sitepath;
		$this->tmppath = $tmppath;
		$this->newname = $newname;
		$this->count = 0;
		$this->db = new PDO('mysql:host=localhost;dbname=iphone;charset=utf8', 'root', 'pass');
		$this->db->query("SET NAMES utf8");
	}

	//add no photo image.
	public function emptyimage()
	{
		$this->images[0]->prepath = "/img/p/pre/no_image.gif";
		$this->images[0]->midpath = "/img/p/mid/no_image.gif";
		$this->images[0]->fullpath = "/img/p/full/no_image.gif";
	}
	//add image to this pid
	//exist if it's update exist image.
	public function addimage($urlpath, $exist = null)
	{
		$this->urlgetimage($urlpath);
		if ($exist) $this->count = $exist;
		if(!$this->break)
		{
			$this->getSize($this->processpath);
			$this->execute();
			$this->count++;
		}
	}

	//get image 
	public function urlgetimage($urlpath)
	{
		if(!file_put_contents($this->tmppath."temp.jpg", @file_get_contents($urlpath)))
		{
			print_r("image missed");
			$this->break = 1;
		} 		
		$this->processpath = $this->tmppath."temp.jpg";
	}

	//it use the identify method with ImageMagick app
	function getSize($path)
	{
		exec ("identify ".$path, $output, $status);
		preg_match("/([0-9]{2,4})[x]([0-9]{2,4})/", $output[0], $matches);
		$this->images[$this->count]->width = $matches[1];
		$this->images[$this->count]->height = $matches[2];
	}

	//проверяет, существует ли директория для записи файлов.
	protected function checkfolders()
	{
		foreach ($this->pathes as $k => $item) 
			if (!is_dir($this->sitepath.$item['path'])) mkdir($this->sitepath.$item['path']);
	}

	protected function execute()
	{
		$this->checkfolders();
		foreach ($this->pathes as $k => $item) {
			if  ($this->images[$this->count]->width > $item['width'] || $this->images[$this->count]->height > $item['height']) 
			{
				exec ("convert -resize ".$item['sizes']." ".$this->processpath." ".$this->sitepath.$item['path'].$this->count.".jpg", $output, $status);
				$this->getSize($this->sitepath.$item['path']."_".$this->count.".jpg");
				$this->midpathflag = 1;
			}	
			if ($this->images[$this->count]->width < $item['width'] || $this->images[$this->count]->height < $item['height']) 
			{
				$this->midpathflag == 1 ? exec("convert -bordercolor White -border 200x200 -gravity Center -crop ".$item['sizes']."+0+0 ".$this->sitepath.$item['path'].$this->count.".jpg"." ".$this->sitepath.$item['path'].$this->count.".jpg"):
				exec("convert -bordercolor White -border 500x500 -gravity Center -crop ".$item['sizes']."+0+0 ".$this->processpath." ".$this->sitepath.$item['path'].$this->count.".jpg");
			}
			$this->count++;
		}
	}

	public function rename($imagePath, $id)
	{
		$count = 0;
		foreach ($this->pathes as $k => $item) {
			$imageQty = $this->getImageCount($this->sitepath.$item['path']);
			
			 foreach($imageQty as $item) {
			 	//print_r($this->sitepath.$item['path'].$x.".jpg<br>");
			 	$product = preg_match("/product/", $item) ? 'product/' : '';
			 	if (!is_dir($imagePath.$product)) mkdir($imagePath.$product);
			 	rename($item, $imagePath.$product.$count.".jpg");
			 	$this->images[$count] = $imagePath.$product.$count.".jpg";
			 	$count++;
			}
			$update = $this->db->prepare("UPDATE products SET p_images = ? WHERE p_id = ? AND u_id = ?");
			$update->execute(array(serialize($this->images), $id, '0'));
		}
	}

	//получает количество картинок продукта
	public static function getImageCount($path) {	
		return glob($path."*.jpg");
	}

	public static function getImages($pid) {
		$db = new PDO('mysql:host=localhost;dbname=iphone;charset=utf8', 'root', 'pass');
		$db->query("SET NAMES utf8");
		$select = $db->prepare("SELECT `p_images` FROM products WHERE p_id = ?");
		$select->execute(array($pid));
		$select = $select->fetch(PDO::FETCH_OBJ);

		return unserialize($select->p_images);
	}
	public function load()
	{
		$update = $this->db->prepare("UPDATE products SET p_img = ? WHERE p_id = ? AND u_id = ?");
		$update->execute(array(serialize($this->images), $this->newname, $this->u_id));
	}

	public function loadtotpl()
	{
		$select = $this->db->prepare("SELECT `p_img` FROM products WHERE p_id = ?");
		$select->execute(array($this->newname));
		$select = $select->fetch(PDO::FETCH_OBJ);
		$this->images = unserialize($select->p_img);
		
	}

}