<?php
namespace Lib;
include EXTEND_PATH . 'Lib/phpqrcode.php';
/**
 * 二维码生成类
 */
class Qrcode
{
	protected $config = [
		//二维码内容
		'content' => '',
		// 纠错级别：L、M、Q、H  
		'level' => 'L',
		//点大小：1到10  
		'size' => '10',
		//二维码周围边框空白区域间距值
		'margin' => '3',
		//logo文件路径
		'logo_file' => false,
		//是否保存图片,在logo文件不为false时忽略此参数
		'is_save' => false,
		//二维码保存路径
		'save_path' => './qrcode/' 
	];

	/**
     * 架构方法 设置参数
     * @access public
     * @param  array $config 配置参数
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 使用 $this->name 获取配置
     * @access public
     * @param  string $name 配置名称
     * @return mixed    配置值
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * 设置验证码配置
     * @access public
     * @param  string $name  配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 检查配置
     * @access public
     * @param  string $name 配置名称
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 创建二维码
     * @access public
     * @return file
     */
	public function build()
	{
		//不允许创建无内容二维码
		if (empty($this->config['content'])) {
			return false;
		}

		if (isset($this->config['logo_file']) && !empty($this->config['logo_file'])) {
			return $this->logo_qr();
		}else{
			return $this->normal_qr();
		}
	}

	/**
	* 创建无logo二维码
	* @access protected
	* @return file
	*/
	protected function normal_qr(){
		@ob_end_clean();
		
		if ($this->config['is_save']) {
			$file_name = $this->get_name();
			$file_path = $this->config['save_path'] .  $file_name;
			\QRcode::png($this->config['content'], $file_path, $this->config['level'], $this->config['size'],$this->config['margin']);
			return $file_name;
		}else{
			return \QRcode::png($this->config['content'], false, $this->config['level'], $this->config['size'],$this->config['margin']);
		}
		
	}

	/**
	* 创建有logo二维码
	* @access protected
	* @return string
	*/
	protected function logo_qr(){
		if (!is_file($this->config['logo_file'])) {
			return false;
		}
		$file_name = $this->get_name();
		$file_path = $this->config['save_path'] .  $file_name;
		\QRcode::png($this->config['content'], $file_path, $this->config['level'], $this->config['size'],$this->config['margin']);

		$logo = imagecreatefromstring(file_get_contents($this->config['logo_file']));
        $qrcode = imagecreatefromstring(file_get_contents($file_path));
        $qr_width = imagesx($qrcode);//二维码图片宽度   
        $qr_height = imagesy($qrcode);//二维码图片高度   
        $logo_width = imagesx($logo);//logo图片宽度   
        $logo_height = imagesy($logo);//logo图片高度   
        $logo_qr_width = $qr_width / 5;   
        $scale = $logo_width/$logo_qr_width;   
        $logo_qr_height = $logo_height/$scale;   
        $from_width = ($qr_width - $logo_qr_width) / 2;   
        //重新组合图片并调整大小   
        imagecopyresampled($qrcode, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);   
        //保存生成的二维码
        imagepng($qrcode, $file_path);   
        return $file_name;
	}

	/**
	* 生成二维码名称
	*/
	protected function get_name(){
		$file_name = uniqid() . '.png';
		return $file_name;
	}
}