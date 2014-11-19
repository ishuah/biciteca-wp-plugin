<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

class biciteca_SMS_API {

	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('init', array($this, 'add_endpoint'), 0);
	}

	public function add_query_vars($vars){
		$vars[] = '__twilio';
		$vars[] = 'incoming';
		$vars[] = 'body';
		return $vars;
 	}

 	public function add_endpoint(){
 		add_rewrite_rule('^__twilio/incoming/?', 'index.php?__twilio=1&incoming=1', 'top');
 	}

 	public function sniff_requests(){
 		global $wp;
 		if ( isset($wp->query_vars['__twilio']) ){
 			$this->handle_request();
 			exit;
 		}
 	}

 	protected function handle_request(){
 		global $wp;
 		
 		header('content-type: text/plain; charset=utf-8');
 		$this->send_response('Hello '. $_POST['From'] . ' thanks for ' . $_POST['Body']);
 	}

 	protected function send_response($msg){
 		header('content-type: text/xml; charset=utf-8');
 		$response = '<Response>';
    	$response .= '<Message>' . $msg . '</Message>';
    	$response .= '</Response>';
    	echo $response;
 	}
}
?>