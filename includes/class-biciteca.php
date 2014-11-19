<?php

if ( ! defined( 'ABSPATH' ) ) exit;


class biciteca {

	private static $_instance = null;


	public function __construct ($file = '', $version = '0.1.0' ) {
		$this->_version = $version;
		$this->_token = 'biciteca';

		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this , 'install') );
		
		$account_sid = get_option('wpt_twilio_sid'); 
		$auth_token = get_option('wpt_twilio_auth_token'); 
		$this->client = new Services_Twilio($account_sid, $auth_token);

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1);
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'typicons_enqueue_styles' ), 10, 1 );

		add_action('admin_menu', array( $this, 'add_pages') );


		$this->pt_member = $this->register_post_type('member', 'Members', 'Member', 'Biciteca members');
		$this->pt_station = $this->register_post_type('station', 'Station', 'Station', 'Biciteca stations');

		$columns = array(
            'id'            => 'ID',
            'name'          => 'Name',
            'membership_type' => 'Type of Membership',
            'membership_id' => 'Primary phone number',
            'address'       => 'Address'
        );

		if (is_admin() ){
			$this->admin = new biciteca_Admin_API();
			$this->admin_member_table = new biciteca_List_Table($this->pt_member->post_type, $columns, 'member', 'members' );
		}

		$this->sms = new biciteca_SMS_API();
		
	}


	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '' ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new biciteca_Post_Type( $post_type, $plural, $single, $description );

		return $post_type;
	}


	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()


	public function enqueue_scripts () {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()


	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()


	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	public function typicons_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-typicons', esc_url( $this->assets_url ) . 'font/typicons.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-typicons' );
	} // End typicons_enqueue_styles ()


	public function add_pages(){
		add_menu_page(__('Biciteca Admin','biciteca'), __('Biciteca','biciteca'), 'manage_options', 'biciteca-plugin', array( $this, 'biciteca_page') );
		
		add_submenu_page('biciteca-plugin', __('Registration','biciteca'), __('Registration','biciteca'), 'manage_options', 'biciteca-registration', array( $this, 'registration_landing_page') );
		add_submenu_page('biciteca-plugin', __('Register New Member','biciteca'), __('Register New Member','biciteca'), 'manage_options', 'biciteca-add-member', array( $this, 'add_member_page') );
		add_submenu_page('biciteca-plugin', __('Admin','biciteca'), __('Admin','biciteca'), 'manage_options', 'biciteca-admin', array( $this, 'admin_landing_page') );
		
		add_submenu_page('biciteca-admin', __('Biciteca Add Station','biciteca'), __('Biciteca Add Station','biciteca'), 'manage_options', 'biciteca-add-station', array( $this, 'add_station_page') );
		add_submenu_page('biciteca-admin', __('Biciteca Change Lock Codes','biciteca'), __('Biciteca Change Lock Codes','biciteca'), 'manage_options', 'biciteca-admin-manage-station', array( $this, 'manage_station_page') );
		add_submenu_page('biciteca-registration', __('Edit Member Details','biciteca'), __('Edit Member Details','biciteca'), 'manage_options', 'biciteca-edit-member', array( $this, 'edit_member_page') );
	}

	public function biciteca_page(){
		$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
			$html .= '<h2>' . __('Biciteca Plugin', 'biciteca') . '</h2>';
			$html .= '<p>This plugin was created for the Desert Riderz and the North Shore Biciteca.</p>';
		$html .= '</div>';
		echo $html;
	}
	
	public function registration_landing_page(){
		$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
			$html .= '<h2>' . __('Biciteca Registration', 'biciteca');
			$html .= '<a class="add-new-h2" href="?page=biciteca-add-member" />' . esc_attr( __( 'Add Member' , 'biciteca' ) ) . '</a>' . "\n";
			$html .= '</h2>';
		$html .= '</div>';
		echo $html;

		$this->admin_member_table->display();
	}

	public function admin_landing_page(){
		$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
			$html .= '<h2>' . __('Biciteca Administrator Dashboard', 'biciteca') . '</h2>';
			$html .= '<div class="panel half">';
			$html .= '<h2>' . __('Stations', 'biciteca');
			$html .= '<a class="add-new-h2" href="?page=biciteca-add-station" /><span class="typcn typcn-plus"></span></a>' . "\n";
			$html .= '</h2>';
			if ($_GET['send_sms']){
				$message = $this->client->account->messages->create(array(
    				'To' => "+254703535226", 
					'From' => "+17605314114", 
					'Body' => "yet another test! From Admin",  
				));
				$html .= "Sent message {$message->sid}";
			}
			$data = get_posts(array('post_type' => 'station'));
			
			foreach ( $data as $row):
				$station_status = $this->get_station_status($row->ID);
				$html .= '<div class="station">';
				$html .= '<div class="details">'; 
				$html .= '<span class="typcn typcn-media-record status-marker ' . $station_status['color'] . '"></span><h3 class="header">' . $row->post_title . '</h3>';
				$html .= '<span class="status ' . $station_status['color'] . '">' . $station_status['next_reset'] . '</span>';
				$html .= '</div>';
				$html .= '<a href="?page=biciteca-admin-manage-station&id='. $row->ID .'" />';
					$html .= '<span class="typcn typcn-cog-outline large"><span>';
				$html .= '</a>' . "\n";
				$html .= '</div>';
				//$html .= '<a class="button-primary" href="?page=biciteca-admin-generate-lock-codes&id='. $row->ID .'" />' . esc_attr( __( 'Generate Lock Codes' , 'biciteca' ) ) . '</a>' . "\n";
			endforeach;
			$html .= '</div>';
		$html .= '</div>';
		echo $html;
	}

	public function get_station_status( $station_id ){
		$station_status = [];
		if ( isset(get_post_meta($station_id, 'last_reset')[0])  && isset(get_post_meta($station_id, 'next_reset')[0])){
			$last_reset = new DateTime(get_post_meta($station_id, 'last_reset')[0]);
			$next_reset = new DateTime(get_post_meta($station_id, 'next_reset')[0]);
			$today = new DateTime();

			if ( $last_reset->diff($today, true)->format('%a') > 0 ){
				$station_status['last_reset'] = $last_reset->diff($today, true)->format('Last reset was done %a days ago.');
			}elseif ( $last_reset->diff($today, true)->format('%a') == 0 ) {
				$station_status['last_reset'] = 'Last reset was today.';
			}

			if ( $today->diff($next_reset, true)->format('%a') <= 0 ){
				$station_status['next_reset'] = 'Please reset the lock codes.';
				$station_status['color'] = 'red';
			}elseif ( $today->diff($next_reset, true)->format('%a') < 2 ) {
				$station_status['next_reset'] = $today->diff($next_reset, true)->format('Next reset is in %a days.');
				$station_status['color'] = 'yellow';
			}else {
				$station_status['next_reset'] = $today->diff($next_reset, true)->format('Next reset is in %a days.');
				$station_status['color'] = 'green';
			}
			return $station_status;
		}
	}

	public function biciteca_redirect($location, $status) {
   		return $location;
	}

	public function manage_station_page(){
		$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
		$html .= '<h2>' . __('Biciteca Manage Station', 'biciteca') . '</h2>';
		$html .= '<div class="panel half">';
			if ($_GET['id']){
				$station = get_post($_GET['id']);

				if ($_POST['reset']){
					for($i = 0; $i <=12; $i++){
						update_post_meta($station->ID, 'new_lockcode_' . $i, rand(1000, 9999));
					}
					update_post_meta($station->ID, 'last_reset', date("F j, Y, g:i a"));
					update_post_meta($station->ID, 'next_reset', date("F j, Y, g:i a", strtotime("+7 day", time())));
					delete_post_meta($station->ID, 'locks_are_updated');
					
				}

				if ($_POST['locks_are_updated']){
					for($i = 0; $i <=12; $i++){
						update_post_meta($station->ID, 'lockcode_' . $i, get_post_meta($station->ID, 'new_lockcode_'.$i)[0]);
						delete_post_meta($station->ID, 'new_lockcode_'.$i);
					}
					update_post_meta($station->ID, 'locks_are_updated', date("F j, Y, g:i a"));
					
				}

				$station_status = $this->get_station_status($station->ID);

				$html .= '<div class="details">'; 
					$html .= '<span class="typcn typcn-media-record status-marker ' . $station_status['color'] . '"></span><h2 class="header">' . $station->post_title . '</h2>';
					$html .= '<span class="status ' . $station_status['color'] . '">' . $station_status['last_reset'] . ' ' . $station_status['next_reset'] .'</span>';
				$html .= '</div>';
				$html .= '<form class="ordered" method="post" action="">' . "\n";
					$html .= '<input id="reset" type="hidden" name="reset" value="true">';
					$html .= '<input name="submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Click to reset lock codes' , 'biciteca' ) ) . '" />' . "\n";
				$html .= '</form>';

				if ( !isset( get_post_meta($station->ID, 'locks_are_updated')[0] ) ){
					$html .= '<form class="ordered" method="post" action="">' . "\n";
						$html .= '<input id="locks_are_updated" type="hidden" name="locks_are_updated" value="true">';
						$html .= '<input name="submit" type="submit" class="button-primary" value="Are the locks updated?" />' . "\n";
					$html .= '</form>';
					$html .= '<table class="form-table">';
						$html .= '<tbody>';
						$html .= '<tr><th>Lock #</th><th>Current Codes</th><th>New Codes</th></tr>';
							for($i = 1; $i <=12; $i++){
								$html .= '<tr>';
									$html .= '<td>';
									$html .= $i;
									$html .= '</td>';
									$html .= '<td>';
									$html .= get_post_meta($station->ID, 'lockcode_'.$i)[0];
									$html .= '</td>';
									$html .= '<td>';
									$html .= get_post_meta($station->ID, 'new_lockcode_'.$i)[0];
									$html .= '</td>';
								$html .= '</tr>';
							}
						$html .= '</tbody>';
					$html .= '</table>';
				}else{
					$html .= '<table class="form-table">';
						$html .= '<tbody>';
						$html .= '<tr><th>Lock #</th><th>Codes</th></tr>';
							for($i = 1; $i <=12; $i++){
								$html .= '<tr>';
									$html .= '<td>';
									$html .= $i;
									$html .= '</td>';
									$html .= '<td>';
									$html .= get_post_meta($station->ID, 'lockcode_'.$i)[0];
									$html .= '</td>';
								$html .= '</tr>';
							}
						$html .= '</tbody>';
					$html .= '</table>';
				}
				
			}else {
				$html .= '<p>You are on the wrong path. Find your way.</p>';
			}
		$html .= '</div>';
		$html .= '</div>';
		echo $html;
	}

	public function add_station_page(){
		$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
		$html .= '<h2>' . __('Biciteca Add Station', 'biciteca') . '</h2>';
		$station = false;
		if ($_POST){
			$new_station = array(
				'post_title' => $_POST['title'],
				'post_type' => $this->pt_station->post_type,
				'post_status' => 'publish'
				);
			$station_id = wp_insert_post( $new_station );
			$station = get_post($station_id);

			for($i = 0; $i <=12; $i++){
				update_post_meta($station->ID, 'new_lockcode_' . $i, rand(1000, 9999));
			}
			update_post_meta($station->ID, 'last_reset', date("F j, Y, g:i a"));
			update_post_meta($station->ID, 'next_reset', date("F j, Y, g:i a", strtotime("+7 day", time())));
			$html .= '<p> Station ' . $_POST['title'] . ' was added successfully!</p>';
		}
			$html .= '<form method="post" action="">' . "\n";
				$html .= '<table class="form-table">';
					$html .= '<tr>';
						$html .= '<td>';
							$html .= $this->admin->display_field(array('id'=>'title', 'type'=>'text', 'description'=>'New station\'s name', 'placeholder'=> 'Higher Station'), $station, false);
						$html .= '</td>';
					$html .= '</tr>';
					$html .= '<tr>';
						$html .= '<td>';
							$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save' , 'biciteca' ) ) . '" />' . "\n";
						$html .= '</td>';
					$html .= '</tr>';
				$html .= '</table>';
			$html .= '</form>';
		$html .= '</div>';
		echo $html;
	}

	public function add_member_page(){
		if ($_POST){
			$new_member = array(
				'post_title' => $_POST['title'],
				'post_type' => $this->pt_member->post_type,
				'post_status' => 'publish'
				);
			$member_id = wp_insert_post( $new_member );
			foreach( $_POST as $key => $value){
				if($key != 'title' and $key != 'post_type' and $key != 'Submit' ){
					add_post_meta($member_id, $key, $value);
				}
			}
			$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
				$html .= '<h2>' . __('Biciteca Add Member', 'biciteca') . '</h2>';
				$html .= '<p>' . $_POST['title'] . ' was added successfully!</p>';
			$html .= '</div>';
			echo $html;
		} else {
			$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
				$html .= '<h2>' . __('Biciteca Add Member', 'biciteca') . '</h2>';
				$html .= '<h4>' . __('New member details', 'biciteca') . '</h4>';
				$html .= '<form method="post" action="">' . "\n";
					$html .= '<table class="form-table">';
						$html .= '<tbody>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'membership_type', 'type'=>'radio', 'options'=>array('individual'=>'Individual Membership', 'family'=>'Family Membership') ), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'title', 'type'=>'text', 'description'=>'New member\'s name', 'placeholder'=> 'John Doe'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'address', 'type'=>'text', 'description'=>'Address', 'placeholder'=> 'Knowhere'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'phone_number', 'type'=>'number', 'description'=>'Phone #'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'phone_number_1', 'type'=>'number', 'description'=>'Other Phone #'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'phone_number_2', 'type'=>'number', 'description'=>'Other Phone #'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'phone_number_3', 'type'=>'number', 'description'=>'Other Phone #'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'opt_language', 'type'=>'radio', 'options'=>array('EN'=>'English', 'ES'=>'Spanish') ), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'opt_have_sms', 'type'=>'checkbox', 'description'=>'Does not have SMS capabilities'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'membership_id', 'type'=>'number', 'description'=>'Membership ID'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'last_payment_date', 'type'=>'text', 'description'=>'Date of most recent payment'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'amount_paid', 'type'=>'number', 'description'=>'Amount paid'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'start_date', 'type'=>'text', 'description'=>'Start Date'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'end_date', 'type'=>'text', 'description'=>'End Date'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= $this->admin->display_field(array('id'=>'next_payment_date', 'type'=>'text', 'description'=>'Next payment date'), false, false);
								$html .= '</td>';
							$html .= '</tr>';
							$html .= '<tr>';
								$html .= '<td>';
									$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save' , 'biciteca' ) ) . '" />' . "\n";
								$html .= '</td>';
							$html .= '</tr>';
						$html .= '</tbody>';
					$html .= '</table>';
				$html .= '</form>';
			$html .= '</div>';
			echo $html;
		}
	}

	public function edit_member_page(){
		$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
		$html .= '<h2>' . __('Biciteca Edit Member', 'biciteca') . '</h2>';
		if ($_GET['id']){
			$member = get_post($_GET['id']);
			if ($_POST){
				if ( $member->post_title != $_POST['title'] ){
					$member->post_title = $_POST['title'];
					wp_update_post( $member );
				}

				foreach( $_POST as $key => $value){
					if($key != 'title' and $key != 'post_type' and $key != 'Submit' ){
						$prev_value = get_post_meta( $member->ID, $key, true );
						if ( $prev_value != $value ){
							update_post_meta($member->ID, $key, $value, $prev_value);
						}
					}
				}
				$html .= '<p>' . $_POST['title'] . ' was edited successfully!</p>';
			}
			$html .= '<form method="post" action="">' . "\n";
				$html .= '<table class="form-table">';
					$html .= '<tbody>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'membership_type', 'type'=>'radio', 'options'=>array('individual'=>'Individual Membership', 'family'=>'Family Membership') ), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'title', 'type'=>'text', 'description'=>'New member\'s name', 'placeholder'=> 'John Doe'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'address', 'type'=>'text', 'description'=>'Address', 'placeholder'=> 'Knowhere'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'phone_number', 'type'=>'number', 'description'=>'Phone #'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'phone_number_1', 'type'=>'number', 'description'=>'Other Phone #'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'phone_number_2', 'type'=>'number', 'description'=>'Other Phone #'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'phone_number_3', 'type'=>'number', 'description'=>'Other Phone #'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'opt_language', 'type'=>'radio', 'options'=>array('EN'=>'English', 'ES'=>'Spanish') ), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'opt_have_sms', 'type'=>'checkbox', 'description'=>'Does not have SMS capabilities'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'membership_id', 'type'=>'number', 'description'=>'Membership ID'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'last_payment_date', 'type'=>'text', 'description'=>'Date of most recent payment'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'amount_paid', 'type'=>'number', 'description'=>'Amount paid'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'start_date', 'type'=>'text', 'description'=>'Start Date'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'end_date', 'type'=>'text', 'description'=>'End Date'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= $this->admin->display_field(array('id'=>'next_payment_date', 'type'=>'text', 'description'=>'Next payment date'), $member, false);
							$html .= '</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td>';
								$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Edits' , 'biciteca' ) ) . '" />' . "\n";
							$html .= '</td>';
						$html .= '</tr>';
					$html .= '</tbody>';
				$html .= '</table>';
			$html .= '</form>';
			
		}else{
			$html .= '<p>Error!</p>';
		}
		$html .= '</div>';
		echo $html;
	}



	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

}
?>