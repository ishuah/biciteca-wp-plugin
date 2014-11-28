<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class biciteca_Data_Logger {
	
	public function __construct(){
		$this->post_type = 'log';
	}

	public function write_log($userId, $userNumber, $stationId, $action){
		$logId = wp_insert_post( array(
			'post_title' => $userNumber . ' ' . $action,
			'post_type' => $this->post_type,
			'post_status' => 'publish'
			));
		
		add_post_meta($logId, 'userId', $userId);
		add_post_meta($logId, 'userNumber', $userNumber);
		add_post_meta($logId, 'stationId', $stationId);
		add_post_meta($logId, 'action', $action);
		add_post_meta($logId, 'timestamp',date("F j, Y, g:i a"));
	}

	public function read_logs($filter = 'all', $targetId){
		$query = array( 
			'post_type' => $this->post_type
			);
		$logs = [];
		if ( $filter == 'userId' ){
			$query['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key' => 'userId',
					'value' => $targetId,
					'compare' => '='
					)
				);
		} elseif ( $filter == 'userNumber' ) {
			$query['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key' => 'userNumber',
					'value' => $targetId,
					'compare' => '='
					)
				);
		} elseif ( $filter == 'stationId' ) {
			$query['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key' => 'stationId',
					'value' => $targetId,
					'compare' => '='
					)
				);
		}
		$log_posts = get_posts($query);
		
		foreach ($log_posts as $post){
			$logs[] = array(
				'userId' => get_post_meta($post->ID, 'userId')[0],
				'userNumber' => get_post_meta($post->ID, 'userNumber')[0],
				'stationId' => get_post_meta($post->ID, 'stationId')[0],
				'action' => get_post_meta($post->ID, 'action')[0],
				'timestamp' => get_post_meta($post->ID, 'timestamp')[0]
				);
		}
		return $logs;

	}
}
?>