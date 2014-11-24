<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

class biciteca_SMS_API {

	private static $sms_responses = array();

	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('init', array($this, 'add_endpoint'), 0);

		$this->sms_responses = array(
		'EN' => array(
			'EXPIRED_MEMBERSHIP' => 'I\'m sorry, you cannot check out a Biciteca bike at this time. You do not have a membership for this month. Please contact Desert Riderz at 760-625-6274 about renewing your monthly membership.',
			'BIKES_AVAILABLE' => ' bikes are currently available at station ',
			'NON_USER' => 'Hi, this number is not registered on the bike system. Please contact Desert Riderz at 760-625-6274 about starting your monthly membership.',
			'INVALID_STATION' => 'The station code you sent is not valid, please try again. Thank you.',
			'TAKEN_BIKE' => 'The bike you have requested is not available, please try another one. Thank you.',
			'CHECKOUT_BIKE' => 'Your lock code is %d. You have 2 hours to enjoy your Biciteca bike.',
			'CHECKIN_BIKE' => 'Your lock code is %d. Thank you for returning your Biciteca bike safely. We hope you enjoyed Biciteca, come back soon!',
			'CANNOT_CHECKOUT_BIKE' => 'I\'m sorry, you cannot check out a Biciteca bike at this time as you already have a bike checked-out.',
			'CANNOT_CHECKIN_BIKE' => 'I\'m sorry, you cannot check in a Biciteca bike, you do not have a bike checked-out.',
			'UNAVAILABLE_SLOT' => 'The requested slot is not vacant, please try another one. Thank you.'
			),
		'ES' => array(
			'EXPIRED_MEMBERSHIP' => 'Lo lamento, usted no puede usar una bicicleta en este momento. Usted no ha pagado su membresía para este mes. Por favor contacte a Desert Riderz a 760-625-6274 para renovar su membresía mensual.',
			'BIKES_AVAILABLE' => ' bicicletas están disponibles en la estación ',
			'INVALID_STATION' => 'El código de estación que usted envió no es válida, por favor intente de nuevo. Gracias.',
			'TAKEN_BIKE' => 'La moto que ha solicitado no está disponible, por favor, pruebe otra. Gracias.',
			'CHECKOUT_BIKE' => 'El código para abrir el candado es %d. Usted tiene dos horas para disfrutar la bicicleta de Biciteca.',
			'CHECKIN_BIKE' => 'Su código de candado es %d. Gracias por devolver la bicicleta a la estación de Biciteca con seguridad. Esperamos que haya disfrutado los servicios de Biciteca, vuelva pronto!',
			'CANNOT_CHECKOUT_BIKE' => 'Lo lamento, no puede usar una bicicleta de Biciteca en este momento porque usted ya ha sacado una bicicleta.',
			'CANNOT_CHECKIN_BIKE' => 'Lo siento, no se puede comprobar en una bicicleta Biciteca, usted no tiene una bicicleta desprotegido.',
			'UNAVAILABLE_SLOT' => 'La solicitud de franja horaria no es vacante, por favor pruebe otra. Gracias.'
			)
		);
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
 		if ($_POST['From'] == '' || $_POST['Body'] == '')
 			exit;

 		$text = explode(" ", strtolower($_POST['Body']));
 		if (sizeof($text) < 2)
 			$this->send_response('Invalid query format.');

 		$member_query = array(
 			'post_type' => 'member',
 			'meta_query' => array(
 					'relation' => 'OR',
 					array(
 						'key' => 'phone_number',
 						'value' => $_POST['From'],
 						'compare' => 'LIKE'
 						),
 					array(
 						'key' => 'phone_number_1',
 						'value' => $_POST['From'],
 						'compare' => 'LIKE'
 						),
 					array(
 						'key' => 'phone_number_2',
 						'value' => $_POST['From'],
 						'compare' => 'LIKE'
 						),
 					array(
 						'key' => 'phone_number_3',
 						'value' => $_POST['From'],
 						'compare' => 'LIKE'
 						)
 				)
 			);

 		$members = query_posts($member_query);

 		if ($members){
 			$lang = (get_post_meta($members[0]->ID, 'opt_language')[0] == 'ES' ? 'ES' : 'EN');
 			if($this->membership_is_valid($members[0]->ID)){
 				$station = query_posts(array(
 						'post_type'=>'station', 
 						'meta_query' => array(
 							'relation' => 'AND',
 							array(
 								'key' => 'station_code',
 								'value' => $text[1],
 								'compare' => 'LIKE'
 								)
 							)
 						))[0];

 				if(!$station){
 					$this->send_response($this->sms_responses[$lang]['INVALID_STATION']);
 					exit;
 				}

 				if ($text[0] == 'checkin'){
 					$valid = false;
 					for($i = 1; $i <=12; $i++){
 						$lock_status = get_post_meta($station->ID, 'slot_taken_' . $i);
 						if ($lock_status[0] == $_POST['From']) {
 							$valid = true;
 							break;
 						}
 					}
 					if($valid){
 						$lock_status = get_post_meta($station->ID, 'slot_taken_' . $text[2]);
	 					if ($lock_status[0]){
	 						$lock_code = get_post_meta($station->ID, 'lockcode_' . $text[2]);
	 						$this->send_response(sprintf($this->sms_responses[$lang]['CHECKIN_BIKE'], $lock_code[0]));
	 						delete_post_meta($station->ID, 'slot_taken_' . $text[2]);
	 					} else {
	 						$this->send_response($this->sms_responses[$lang]['UNAVAILABLE_SLOT']);
	 					}
 					} else {
 						$this->send_response($this->sms_responses[$lang]['CANNOT_CHECKIN_BIKE']);
 					}
 					

 				} elseif ($text[0] == 'checkout') {	
 					for($i = 1; $i <=12; $i++){
 						$lock_status = get_post_meta($station->ID, 'slot_taken_' . $i);
 						if ($lock_status[0] == $_POST['From']) {
 							$this->send_response($this->sms_responses[$lang]['CANNOT_CHECKOUT_BIKE']);
 							exit;
 						}
 					}		

 					$lock_status = get_post_meta($station->ID, 'slot_taken_' . $text[2]);
 					if ($lock_status[0]){
 						$this->send_response($this->sms_responses[$lang]['TAKEN_BIKE']);
 					} else {
 						$lock_code = get_post_meta($station->ID, 'lockcode_' . $text[2]);
 						$this->send_response(sprintf($this->sms_responses[$lang]['CHECKOUT_BIKE'], $lock_code[0]));
 						update_post_meta($station->ID, 'slot_taken_' . $text[2], $_POST['From']);
 					}
 				
 				} elseif ($text[0] == 'check'){
 					$count = 0;
 					for($i = 1; $i <=12; $i++){
 						$lock_status = get_post_meta($station->ID, 'slot_taken_' . $i);
 						if (is_null($lock_status[0])){
 							$count++;
 						}elseif ($lock_status[0] == $_POST['From']) {
 							$this->send_response($this->sms_responses[$lang]['CANNOT_CHECKOUT_BIKE']);
 							exit;
 						}
 					}
 					$this->send_response($count . $this->sms_responses[$lang]['BIKES_AVAILABLE'] . $text[1]);
 				}
 			} else {
 				$this->send_response($this->sms_responses[$lang]['EXPIRED_MEMBERSHIP']);
 			}
 		}else {
 			$this->send_response($this->sms_responses['EN']['NON_USER']);
 		}
 		
 	}

 	protected function send_response($msg){
 		header('content-type: text/xml; charset=utf-8');
 		$response = '<Response>';
    	$response .= '<Message>' . $msg . '</Message>';
    	$response .= '</Response>';
    	echo $response;
 	}

 	protected function membership_is_valid($memberId){
 		$today = new DateTime();
 		$expiry = new DateTime(get_post_meta($memberId, 'end_date')[0]);
		if ($today->diff($expiry)->format('%R%a') >= 0){
			return true;
		}
			return false;
 	}
}
?>