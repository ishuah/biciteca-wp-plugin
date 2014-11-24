jQuery( document ).ready( function ( $ ) {
	if (window.location.href.indexOf("?page=biciteca-edit-member") > -1 || window.location.href.indexOf("?page=biciteca-add-member") > -1 ){

		if ($('#membership_type_family').is(':checked')){
			//fix this
		}else{
			$('#phone_number_1').parent().hide();
			$('#phone_number_2').parent().hide();
			$('#phone_number_3').parent().hide();
		}

		$('#membership_type_family').change(
			function(){
				if($('#membership_type_family').is(':checked')){
					$('#phone_number_1').parent().show();
					$('#phone_number_2').parent().show();
					$('#phone_number_3').parent().show();
				}
			}
			);

		$('#membership_type_individual').change(
			function(){
				if($('#membership_type_individual').is(':checked')){
					$('#phone_number_1').parent().hide();
					$('#phone_number_2').parent().hide();
					$('#phone_number_3').parent().hide();
				}
			}
			);

		$('#last_payment_date').datepicker();
		$('#start_date').datepicker();
		$('#end_date').datepicker();
		$('#next_payment_date').datepicker();

	}
});