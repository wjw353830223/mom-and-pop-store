<?php 
namespace Lib;
use Lib\Http;
use think\Config;
use think\Exception;
/**
 * 聚合短信发送类
 */
class Sms
{
	
	/**
     * 短信发送url
     * @var string
     */
	protected $send_url = 'http://v.juhe.cn/sms/send';

	/**
     * 短信发送相关配置
     * @var array
     */
	protected $config = [];

	public function __construct($config = []){
		$this->config['key'] = Config::get('sms.key');
		$this->config['tpl_id'] = Config::get('sms.tpl_id');
	}

	public function send($mobile,$code,$wite_time = 5){
		if (empty($mobile) || !is_mobile($mobile)) {
			throw new Exception('invalid mobile or code');
		}

		$code = empty($code) ? random_string(6,1) : $code;

		$this->config['mobile'] = $mobile;
		$this->config['tpl_value'] = '#code#='.$code.'&#hour#='.$wite_time;

		$content = Http::ihttp_post($this->send_url,$this->config);
		
		if (empty($content)) {
			throw new Exception('sms failed');
		}

		$content = json_decode($content['content'],true);
		if ($content['error_code'] !== 0) {
			throw new Exception('sms failed');
		}else{
			return true;
		}
	}
}