<?php

require_once('phpmailer/PHPMailerAutoload.php');

$apiKey = '9084de3d533812184a53480e583e6fd8-us5'; // Your MailChimp API Key
$listId = '69350e983d'; // Your MailChimp List ID

$toemails = array();

$toemails[] = array(
				'email' => 'pantiosam@hotmail.com', // Email Address
				'name' => 'Panificadora Tío Sam' // Name
			);

// Form Processing Messages
$message_success = 'Hemos recibido tu correo <strong>correctamente</strong>. Te enviaremos una respuesta lo más pronto posible, gracias.';

// Add this only if you use reCaptcha with your Contact Forms
$recaptcha_secret = ''; // Your reCaptcha Secret

$mail = new PHPMailer();

// If you intend you use SMTP, add your SMTP Code after this Line


if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if( $_POST['quick-contact-form-email'] != '' ) {

		$name = $_POST['quick-contact-form-name'];
		$email = $_POST['quick-contact-form-email'];
		$subscribe_email = $email;
		$message = $_POST['quick-contact-form-message'];

		$subject = 'Nuevo mensaje de formato de contacto';

		$botcheck = $_POST['quick-contact-form-botcheck'];

		if( isset( $_GET['list'] ) AND $_GET['list'] != '' ) {
			$listId = $_GET['list'];
		}

		if( $botcheck == '' ) {

			$mail->SetFrom( $email , $name );
			$mail->AddReplyTo( $email , $name );
			foreach( $toemails as $toemail ) {
				$mail->AddAddress( $toemail['email'] , $toemail['name'] );
			}
			$mail->Subject = $subject;

			$name = isset($name) ? "Name: $name<br><br>" : '';
			$email = isset($email) ? "Email: $email<br><br>" : '';
			$message = isset($message) ? "Message: $message<br><br>" : '';

			$referrer = $_SERVER['HTTP_REFERER'] ? '<br><br><br>Este formato fue enviado de: ' . $_SERVER['HTTP_REFERER'] : '';

			$body = "$name $email $message $referrer";

			// Runs only when reCaptcha is present in the Contact Form
			if( isset( $_POST['g-recaptcha-response'] ) ) {
				$recaptcha_response = $_POST['g-recaptcha-response'];
				$response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $recaptcha_response );

				$g_response = json_decode( $response );

				if ( $g_response->success !== true ) {
					echo '{ "alert": "error", "message": "Captcha not Validated! Please Try Again." }';
					die;
				}
			}

			$mail->MsgHTML( $body );
			$sendEmail = $mail->Send();

			if( $sendEmail == true ):

				$datacenter = explode( '-', $apiKey );
				$submit_url = "https://" . $datacenter[1] . ".api.mailchimp.com/3.0/lists/" . $listId . "/members/" ;

				$data = array(
					'email_address' => $subscribe_email,
					'status' => 'subscribed'
				);

				if( !empty( $merge_vars ) ) { $data['merge_fields'] = $merge_vars; }

				$payload = json_encode($data);

				$auth = base64_encode( 'user:' . $apiKey );

				$header   = array();
				$header[] = 'Content-type: application/json; charset=utf-8';
				$header[] = 'Authorization: Basic ' . $auth;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $submit_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

				$result = curl_exec($ch);
				curl_close($ch);
				$data = json_decode($result);

				echo '{ "alert": "success", "message": "' . $message_success . '" }';
			else:
				echo '{ "alert": "error", "message": "Email <strong>no enviado</strong> debido a un error inesperado. Intentalo de nuevo más tarde.<br /><br /><strong>Razón:</strong><br />' . $mail->ErrorInfo . '" }';
			endif;
		} else {
			echo '{ "alert": "error", "message": "Bot <strong>Detectado</strong>.! Limpia Botster.!" }';
		}
	} else {
		echo '{ "alert": "error", "message": "Por favor <strong>llena</strong> todos los campos y vuelvelo a intentar." }';
	}
} else {
	echo '{ "alert": "error", "message": "Un <strong>error inesperado</strong> ha ocurrido. Intentalo de nuevo más tarde." }';
}

?>
