<?php

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

add_action( 'admin_menu', 'zpt_init_calenso_admin' );

// Main funktion
function zpt_calenso_widget_function() {
	do_action( 'zpt_calenso_widget_hook' );
	global $wpdb;

	$zpt_mn_tray = $booking_details = null;

	// Calenso WP Plugin Auth0 Access Token
	$access_token = '';
	$apiBaseUrl   = 'https://my.calenso.com/api/v1';
	$audience     = 'https://my.calenso.com/api/v1';

	// check if user want to edit booking, then load booking data
	if ( isset( $_GET['action'], $_GET['booking_id'] ) && $_GET['action'] === 'edit' ) {

		$booking_id = sanitize_text_field( $_GET['booking_id'] );

		$booking_data = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE . " WHERE id = '" . esc_sql( $booking_id ) . "' LIMIT 1", ARRAY_A )[0];
		if ( isset( $booking_data ) ) {
			$booking_details            = $booking_data;
			$booking_details['details'] = json_decode( $booking_data['function'], true );
		}
	}

	if ( isset( $_POST['already_account'] ) || isset( $booking_details ) ) {
		$step = '2';
	} else {
		$step = '1';
	}

	?>
	<script>
		function errcheck() {
			if (errchecke == 1) {
				var e = document.getElementById('errc');
				//var p = document.getElementById('borred');
				e.classList.remove('zpt-hidden');
				//p.classList.add('calenso-redborder-error');
			} else {
				console.log('No');
			}
		}
	</script>
	<?php

	if ( isset( $_POST['check_account'] ) ) {
		if ( trim( $_POST['booking_name'] ) != '' ) {

			$_SESSION['zpt_calenso_tray'] = array(
				'event' => array(),
				'store' => array(),
				'user'  => array(),
			);

			/* WidgetConf */
			$api_url = esc_url_raw( $apiBaseUrl . '/partners/information' );
			$data    = json_encode(
				array(
					'booking_name' => sanitize_text_field( $_POST['booking_name'] ),
					'token_type'   => 'public_widget',
				)
			);

			$user_data_result = zpt_curl_connect_request( $api_url, null, null, $data );
			// echo 'All: <pre>',print_r($user_data_result,1),'</pre>';

			if ( isset( $user_data_result->errors[0]->code ) == '404' ) {
				?>
				<script type="text/javascript">
					var errchecke = 1;
				</script>
				<?php
				$step        = '2';
				$zpt_mn_tray = $user_data_result->errors[0]->message;
			} else {

				?>
				<script type="text/javascript">
					var errchecke = 0;
				</script>
				<?php
				$_SESSION['partner_id']   = sanitize_text_field( $user_data_result->id );
				$_SESSION['partner_uuid'] = sanitize_text_field( $user_data_result->uuid );
				$_SESSION['workers']      = $user_data_result->workers;
				$_SESSION['token']        = $user_data_result->token;
				// echo 'Token: <pre>' . $_SESSION['token'] . '</pre>';

				$data = json_encode(
					array(
						'status' => array(
							'value'    => 1,
							'operator' => '=',
						),
					)
				);

				$event_data_result = zpt_curl_connect_request( $apiBaseUrl . '/events/filter', $_SESSION['token'], sanitize_text_field( $_SESSION['partner_uuid'] ), $data );
				// echo 'Events: <pre>',print_r($event_data_result,1),'</pre>';

				if ( ! empty( $event_data_result ) && is_object( $event_data_result ) && property_exists( $event_data_result, '0' ) ) {
					$event_data_result[0]->id    = esc_sql( sanitize_text_field( $event_data_result[0]->id ) );
					$event_data_result[0]->title = esc_sql( sanitize_text_field( $event_data_result[0]->title ) );
					$check_already_event         = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . ZPT_CALENSO_EVENT_TABLE . " WHERE event_id = '" . $event_data_result[0]->id . "' AND name = '" . $event_data_result[0]->title . "'" );
					if ( $check_already_event ) {
						$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . ZPT_CALENSO_EVENT_TABLE );
					}
					$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_EVENT_TABLE . " (`event_id`, `name`) VALUES ('" . $event_data_result[0]->id . "', '" . $event_data_result[0]->title . "')" );
					$_SESSION['zpt_calenso_tray']['event'] = $event_data_result;
				}

				// SEND REQUEST FOR STORE AND SAVE INTO DB
				$store_api_url     = $apiBaseUrl . '/stores/filter';
				$data              = json_encode(
					array(
						'partner_id' => array(
							'value'    => sanitize_text_field( $_SESSION['partner_id'] ),
							'operator' => '=',
						),
					)
				);
				$store_data_result = zpt_curl_connect_request( $store_api_url, $_SESSION['token'], sanitize_text_field( $_SESSION['partner_uuid'] ), $data );
				// echo '<pre>',print_r($store_data_result,1),'</pre>';

				if ( ! empty( $store_data_result ) ) {
					$service_array = array();
					$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . ZPT_CALENSO_STORE_TABLE );
					foreach ( $store_data_result as $store ) {
						// SEND REQUEST FOR SERVICES AND SAVE INTO DB
						$data                = json_encode(
							array(
								'partner_id' => sanitize_text_field( $_SESSION['partner_id'] ),
								'store_id'   => $store->id,
							)
						);
						$services_api_url    = $apiBaseUrl . '/appointment_services/appointment_services_by_store/';
						$service_data_result = zpt_curl_connect_request( $services_api_url, $_SESSION['token'], sanitize_text_field( $_SESSION['partner_uuid'] ), $data );
						// echo '<pre>',print_r($service_data_result,1),'</pre>';

						if ( ! empty( $service_data_result ) ) {
							$count = 0;
							foreach ( $service_data_result as $service_data_result_ ) {
								$service_array[ $service_data_result_->id ] = ( $service_data_result_->name );
								++$count;
							}
						}

						if ( ! isset( $check_already_store ) ) {
							$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_STORE_TABLE . " (`store_id`, `name`) VALUES ('" . esc_sql( sanitize_text_field( $store->id ) ) . "', '" . esc_sql( sanitize_text_field( $store->name ) ) . "')" );
							$_SESSION['zpt_calenso_tray']['store'][] = array(
								'id'   => $store->id,
								'name' => $store->name,
							);
						}
					}
					$service_id = json_encode( $service_array );
				}
				if ( ! empty( $user_data_result->supported_widget_languages ) ) {
					$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE );
					foreach ( $user_data_result->supported_widget_languages as $language ) {
						$language           = esc_sql( sanitize_text_field( $language ) );
						$check_already_lang = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " WHERE name = '$language'" );
						if ( ! $check_already_lang ) {
							if ( $language == 'en' ) {
								$attribute = $language . '_US';
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`, `attribute`, `label`) VALUES ('$language', '$attribute', 'Englisch')" );
							} elseif ( $language == 'de' ) {
								$attribute = $language . '_CH';
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`, `attribute`, `label`) VALUES ('$language', '$attribute', 'Deutsch')" );
							} elseif ( $language == 'fr' ) {
								$attribute = $language . '_CH';
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`, `attribute`, `label`) VALUES ('$language', '$attribute', 'Französisch')" );
							} elseif ( $language == 'it' ) {
								$attribute = $language . '_CH';
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`, `attribute`, `label`) VALUES ('$language', '$attribute', 'Italienisch')" );
							} elseif ( $language == 'nl' ) {
								$attribute = $language . '_NL';
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`, `attribute`, `label`) VALUES ('$language', '$attribute', 'Niederländisch')" );
							} elseif ( $language == 'fi' ) {
								$attribute = $language . '_FI';
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`, `attribute`, `label`) VALUES ('$language', '$attribute', 'Finnisch')" );
							} else {
								$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE . " (`name`) VALUES ('$language')" );
							}
						}
					}
				}

				$_SESSION['zpt_calenso_tray']['user']['partner_id']   = $user_data_result->id;
				$_SESSION['zpt_calenso_tray']['user']['partner_uuid'] = $user_data_result->uuid;
				$_SESSION['zpt_calenso_tray']['user']['partner_name'] = $user_data_result->name;
				$_SESSION['zpt_calenso_tray']['user']['booking_name'] = $user_data_result->booking_name;
				$_SESSION['zpt_calenso_tray']['user']['service_id']   = $service_id;
				$_SESSION['zpt_calenso_tray']['user']['language_id']  = $user_data_result->language_id;

				if ( isset( $_SESSION['zpt_calenso_tray']['user']['partner_id'] ) ) {
					$step = '3';
				} else {
					$step = '2';
				}
			}
		}
	}

	if ( isset( $_POST['genrate_shortcode'] ) ) {
		if ( isset( $_POST['user_name'], $_POST['zpt_sc_dats'] ) ) {

			$zpt_sc_dats = sanitize_text_field( stripslashes( $_POST['zpt_sc_dats'] ) );

			// check if user want to edit booking
			if ( isset( $_GET['action'], $_GET['booking_id'] ) && $_GET['action'] === 'edit' ) {

				$booking_id = sanitize_text_field( $_GET['booking_id'] );

				$short_code = '[calenso_booking id="' . $booking_id . '"]';

				$save_into_db = $wpdb->update(
					$wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE,
					array(
						'title'     => sanitize_text_field( $_POST['user_name'] ),
						'shortcode' => $short_code,
						'function'  => sanitize_text_field( $zpt_sc_dats ),
					),
					array(
						'id' => $booking_id,
					)
				);

			} else {

				// $get_id     = $wpdb->get_row( "SHOW TABLE STATUS LIKE '" . $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE . "'" );
				// $short_code = '[calenso_booking id="' . $get_id->Auto_increment . '"]';

				// $save_into_db = $wpdb->insert(
				// $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE,
				// array(
				// 'title'     => sanitize_text_field( $_POST['user_name'] ),
				// 'shortcode' => $short_code,
				// 'function'  => sanitize_text_field( $zpt_sc_dats ),
				// )
				// );

				// check else User creating new shortcode.
				$save_into_db = $wpdb->insert(
					$wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE,
					array(
						'title'     => sanitize_text_field( $_POST['user_name'] ),
						'shortcode' => '',
						'function'  => sanitize_text_field( $zpt_sc_dats ),
					)
				);
				if ( $save_into_db !== false ) {
					// Get the auto-incremented ID.
					$auto_increment_id = $wpdb->insert_id;
					// Construct the shortcode with the auto-incremented ID
					$short_code = '[calenso_booking id="' . $auto_increment_id . '"]';
					// Update the 'shortcode' field with the generated shortcode
					$wpdb->update(
						$wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE,
						array( 'shortcode' => $short_code ),
						array( 'id' => $auto_increment_id )
					);

					update_option( 'ZPT_LAST_USED_TITLE', $_POST['user_name'] );

					$step = '4';
				} else {
					echo '<script>alert("' . zpt_calenso__( 'Error_Text_Problem_Has_Occured' ) . '");</script>';
					$step = '3';
				}
			}
			if ( $save_into_db !== false ) {

				update_option( 'ZPT_LAST_USED_TITLE', $_POST['user_name'] );

				$step = '4';
			} else {
				echo '<script>alert("' . zpt_calenso__( 'Error_Text_Problem_Has_Occured' ) . '");</script>';
				$step = '3';
			}
		}
	}

	if ( $step == '2' ) {
		$last_used = get_option( 'ZPT_LAST_USED_TITLE' );
		?>
		<!--Page 2
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400i,500,600,700" rel="stylesheet">-->
		<form method="post" class="zpt-form">
			<div class="zpt-col-xs-12 zpt-box">
				<div class="zpt-col-xs-12 zpt-pad-0">
					<div class="zpt-col-xs-12 zpt-pad-0">
						<!--Header-->
						<h3 class="zpt-pad-l-18 zpt-mar-t-20 Wordpress-Ein calenso-font-color paleft"><?php echo zpt_calenso__( 'Header_Title' ); ?></h3>
						<p class="zpt-pad-l-18 calenso-sub paleft"><?php echo zpt_calenso__( 'Header_Subtitle' ); ?></p>
					</div><br><br>
					<br />
					<br />​
					<hr />​​​​​​​​​​​​​​​​​​​<br />
					<!--Stepper-->​​​​​​​​​​​​​​​​​​​​​​​​
					<div class="stepper-wrapper">
						<div class="stepper-item completed">
							<div class="step-counter">
								<div class="check"></div>
							</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_1_Introduction' ); ?></div>
						</div>
						<div class="stepper-item active">
							<div class="step-counter">2</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_2_Booking_Name' ); ?></div>
						</div>
						<div class="stepper-item">
							<div class="step-counter">3</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_3_Configuration' ); ?></div>
						</div>
						<div class="stepper-item">
							<div class="step-counter">4</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_4_Integration_Code' ); ?></div>
						</div>
					</div>
					<br>
					<hr />​​​​​​​​​​​​​​​​​​​<br />
					<div class="<?php isset( $zpt_mn_tray ) ? ' zpt-has-error' : ''; ?>">
						<!--Guide Text-->
						<div class="paleft zpt-pad-r-30">
							<p class="calenso-font-color calenso-sub"><?php echo zpt_calenso__( 'Page_2_Text_Booking_Link_Description' ); ?><br><br><a class="purple" target="blank" href="https://dashboard.calenso.com/app/settings"><?php echo zpt_calenso__( 'Page_2_Text_Link_To_Booking_Link_Location' ); ?></a><br><br><?php echo zpt_calenso__( 'Page_2_Text_Booking_Link_Instructions' ); ?></p>
						</div><br>
						<!--Buchungsname Einfügen-->
						<div class="paleft zpt-pad-r-30 flexo">
							<p class="bor">https://book.calenso.com/</p>
							<div id="borred">
								<input type="text" class="clean" placeholder="<?php echo zpt_calenso__( 'Page_2_Input_Booking_Link' ); ?>"<?php echo isset( $booking_details['title'] ) ? ' value="' . esc_attr( $booking_details['title'] ) . '"' : ' value="' . $last_used . '"'; ?> name="booking_name" required="required" autofocus>
							</div>
						</div>
						<div class="zpt-pad-l-30 zpt-pad-r-30">
							<p id="errc" class="calenso-red-error zpt-hidden"><?php echo zpt_calenso__( 'Page_2_Text_Error_Message_Booking_Link_Wrong' ); ?></p>
						</div>
						<script>
							errcheck();
						</script>
						<?php isset( $zpt_mn_tray ) ? '<span class="zpt-error">' . $zpt_mn_tray . '</span>' : ''; ?><br>
						<!--Weiter Knopf-->
						<div class="text-right zpt-pad-r-30">
							<button type="submit" class="calenso-button" name="check_account"><?php echo zpt_calenso__( 'Page_2_Button_Continue' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</form>
		<?php
	} elseif ( $step == '3' ) {

		$get_store    = $_SESSION['zpt_calenso_tray']['store'];
		$get_events   = $_SESSION['zpt_calenso_tray']['event'];
		$get_language = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE );
		$user_name    = $_SESSION['zpt_calenso_tray']['user']['booking_name'];
		$get_service  = $_SESSION['zpt_calenso_tray']['user'];
		$get_workers  = $_SESSION['workers'];
		?>
		<!--Page 3
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400i,500,600,700" rel="stylesheet">-->
		<form method="post" class="zpt-form">
			<input type="hidden" name="user_name" value="<?php echo $user_name; ?>">
			<div class="zpt-col-xs-12 zpt-box">
				<div class="zpt-col-xs-12 zpt-pad-0">
					<div class="zpt-col-xs-12 zpt-pad-0 zpt-form-group">
						<!--Header-->
						<h3 class="zpt-pad-l-18 zpt-mar-t-20 Wordpress-Ein calenso-font-color paleft"><?php echo zpt_calenso__( 'Header_Title' ); ?></h3>
						<p class="zpt-pad-l-18 calenso-sub paleft"><?php echo zpt_calenso__( 'Header_Subtitle' ); ?></p>
					</div><br><br>
					<!--Stepper-->
					<br />
					<br />​
					<hr />​​​​​​​​​​​​​​​​​​​<br />​​​​​
					<div class="stepper-wrapper">
						<div class="stepper-item completed">
							<div class="step-counter">
								<div class="check"></div>
							</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_1_Introduction' ); ?></div>
						</div>
						<div class="stepper-item completed">
							<div class="step-counter">
								<div class="check"></div>
							</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_2_Booking_Name' ); ?></div>
						</div>
						<div class="stepper-item active">
							<div class="step-counter">3</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_3_Configuration' ); ?></div>
						</div>
						<div class="stepper-item">
							<div class="step-counter">4</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_4_Integration_Code' ); ?></div>
						</div>
					</div>
					<br>
					<hr />​​​​​​​​​​​​​​​​​​​<br /><br />​​​​​
					<div class="zpt-col-sm-11 zpt-col-xs-12 zpt-pad-0">
						<!--Integrationsart Auswahl-->
						<table>
							<tr>
								<td class="paleft paright">
									<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Integration_Type_Selector' ); ?></h3>
								</td>
								<td class="calenso-selector calenso-sub">
									<select id="pls" class="selector calenso-font-color">
										<option value="iframe"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['iam'] == 'iframe' ) ? ' selected' : ''; ?>>
											<p><?php echo zpt_calenso__( 'Page_3_Selection_iFrame' ); ?></p>
										</option>
										<option value="webcomponent"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['iam'] == 'webcomponent' ) ? ' selected' : ''; ?>>
											<p><?php echo zpt_calenso__( 'Page_3_Selection_Web_Component' ); ?></p>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<td></td>
								<td>
									<div class="ses">
										<p class="calenso-subber"><a class="purple" target="blank" href="https://calenso.freshdesk.com/a/solutions/articles/80000874787?lang=de"><?php echo zpt_calenso__( 'Page_3_Text_Support_Article_Part_1' ); ?></a><?php echo zpt_calenso__( 'Page_3_Text_Support_Article_Part_2' ); ?></p>
									</div>
								</td>
							</tr>
						</table><br><br>
						<div class="zpt-col-xs-12 zpt-pad-0">
							<!--Buchungstyp Auswahl-->
							<table>
								<tr>
									<td class="paleft paright">
										<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Booking_Type_Selector' ); ?></h3>
									</td>
									<td class="calenso-selector calenso-sub">
										<select class="selector calenso-font-color zpt-booking-type" name="type">
											<option value="appointment"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['type'] == 'appointment' ) ? ' selected' : ''; ?>><?php echo zpt_calenso__( 'Page_3_Selection_Appointment' ); ?></option>
											<option value="event"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['type'] == 'event' ) ? ' selected' : ''; ?>><?php echo zpt_calenso__( 'Page_3_Text_Group_Appointment_Selector' ); ?></option>
										</select>
									</td>
								</tr>
							</table><br><br>
						</div>
						<div class="zpt-col-xs-12 zpt-pad-0">
							<!--Filiale Auswahl-->
							<div class="filiale
							<?php
							if ( isset( $booking_details['details']['type'] ) && $booking_details['details']['type'] == 'event' ) {
								echo ' zpt-hidden';}
							?>
							">
								<table>
									<tr>
										<td class="paleft paright">
											<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Branch_Selector' ); ?></h3>
										</td>
										<td class="calenso-selector calenso-sub">
											<select class="selector calenso-font-color" name="store">
												<option value="0">-</option>
												<?php
												if ( ! empty( $get_store ) ) {
													foreach ( $get_store as $get_store_ ) {
														?>
														<option value="<?php echo $get_store_['id']; ?>"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['store_id'] == $get_store_['id'] ) ? ' selected' : ''; ?>><?php echo $get_store_['name']; ?></option>
														<?php
													}
												}
												?>
											</select>
										</td>
									</tr>
								</table><br><br></div>
							<div class="dienstleistung
							<?php
							if ( isset( $booking_details['details']['type'] ) && $booking_details['details']['type'] == 'event' ) {
								echo ' zpt-hidden';}
							?>
							">
								<!--Dienstleistung Auswahl-->
								<table>
									<tr>
										<td class="paleft paright">
											<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Service_Selector' ); ?></h3>
										</td>
										<td class="calenso-selector calenso-sub">
											<select class="selector" name="service">
												<option value="0">-</option>
												<?php
												if ( ! empty( $get_service ) ) {
													$serv = json_decode( $get_service['service_id'], true );
													foreach ( $serv as $serv_id => $serv_ ) {
														?>
														<option value="<?php echo $serv_id; ?>"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['service'] == $serv_id ) ? ' selected' : ''; ?>><?php echo $serv_; ?></option>
														<?php
													}
												}
												?>
											</select>
										</td>
										</td>
									</tr>
								</table><br><br></div>
							<div class="zpt-event-data
							<?php
							if ( isset( $booking_details['details']['type'] ) ) {
								if ( $booking_details['details']['type'] == 'appointment' ) {
									echo ' zpt-hidden';
								}
							} else {
								echo ' zpt-hidden';}
							?>
							">
								<!--Gruppentermin Auswahl falls Gruppentermin ausgewählt-->
								<table>
									<tr>
										<td class="paleft paright">
											<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Group_Appointment_Selector' ); ?></h3>
										</td>
										<td class="calenso-selector calenso-sub">
											<select class="selector" name="zpt_event">
												<option value="0">-</option>
												<?php
												if ( isset( $get_events[0] ) && ! empty( $get_events[0] ) ) {
													foreach ( $get_events as $get_event_ ) {
														?>
														<option value="<?php echo $get_event_->id; ?>"<?php echo ( isset( $booking_details['details']['iam'] ) && $booking_details['details']['event'] == $get_event->id ) ? ' selected' : ''; ?>><?php echo $get_event_->title; ?></option>
														<?php
													}
												} else {
													echo '<option value="" disabled selected>' . zpt_calenso__( 'Page_3_Text_Group_Appointment_Error_Not_Found' ) . '</option>';
												}
												?>
											</select>
										</td>
									</tr>
								</table><br><br>
							</div>
							<!--Mitarbeiter auswahl-->
							<div class="mitarbeiter
							<?php
							if ( isset( $booking_details['details']['type'] ) && $booking_details['details']['type'] == 'event' ) {
								echo ' zpt-hidden';}
							?>
							">
								<table>
									<tr>
										<td class="paleft paright">
											<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Employee_Selector' ); ?></h3>
										</td>
										<td class="calenso-selector calenso-sub">
											<?php
											// $workers = json_decode($get_workers, true);
											// echo "<pre>" . $workers . "</pre>";
											?>
											<select name="worker" class="selector">
												<option value="0">-</option>
												<?php

												if ( isset( $get_workers[0] ) && ! empty( $get_workers[0] ) ) {

													foreach ( $get_workers as $worker ) {
														?>
														<option value="<?php echo $worker->id; ?>"<?php echo ( isset( $booking_details['details']['worker'] ) && $booking_details['details']['worker'] == $worker->id ) ? ' selected' : ''; ?>><?php echo $worker->resource_name; ?></option>
														<?php
													}
												} else {
													echo '<option value="" disabled selected>No worker found</option>';
												}
												?>
											</select>
										</td>
									</tr>
								</table><br><br>
							</div>
							<!--Sprachen Auswahl-->
							<table>
								<tr>
									<td class="paleft paright">
										<h3 class="calenso-font calenso-font-color calenso-size"><?php echo zpt_calenso__( 'Page_3_Text_Language_Selector' ); ?></h3>
									</td>
									<td class="calenso-selector calenso-sub">
										<select class="selector" name="language">
											<?php
											if ( ! empty( $get_language ) ) {
												foreach ( $get_language as $get_language_ ) {
													?>
													<option value="<?php echo $get_language_->attribute; ?>"<?php echo ( isset( $booking_details['details']['lang'] ) && $booking_details['details']['lang'] == $get_language_->attribute ) ? ' selected' : ''; ?>><?php echo $get_language_->label; ?></option>
													<?php
												}
											}
											?>
										</select>
									</td>
								</tr>
							</table><br><br><br>
						</div>
					</div>
					<textarea class="hidden" name="zpt_sc_dats"><?php echo ( isset( $booking_details['details'] ) && $booking_details['details'] ) ? json_encode( $booking_details['details'] ) : ''; ?></textarea>
					<!--Shortcode generieren Knopf-->
					<div class="text-right zpt-pad-r-22">
					<?php
					if ( isset( $booking_details ) ) {
						?>
							<button type="submit" class="calenso-button" name="genrate_shortcode"><?php echo zpt_calenso__( 'Page_3_Button_Update_Shortcode' ); ?></button>
						<?php } else { ?>
							<button type="submit" class="calenso-button" name="genrate_shortcode"><?php echo zpt_calenso__( 'Page_3_Button_Create_Shortcode' ); ?></button>
						<?php } ?>
					</div>
				</div>
			</div>
		</form>
		<script>
			if (window.jQuery) {
				//console.log("jquery admin step 3");
			}


			//Gruppentermin zeigen falls ausgewählt
			/*jQuery(document).on('change', '.zpt-booking-type', function (e) {

			//console.log("'change', '.zpt-booking-type'");
			
			switch (jQuery(this).val()) {
				case 'appointment':
					jQuery('.zpt-event-data').addClass('zpt-hidden');
					jQuery('.dienstleistung').removeClass('zpt-hidden');
					jQuery('.filiale').removeClass('zpt-hidden');
					break;
				case 'event':
					jQuery('.zpt-event-data').removeClass('zpt-hidden');
					jQuery('.dienstleistung').addClass('zpt-hidden');
					jQuery('.filiale').addClass('zpt-hidden');
					break;
			}
			});
			

			/*jQuery(document).on('change', 'input[name="technology"]', function (e) {
				switch (jQuery(this).val()) {
					case 'iframe':
						jQuery('span[for="iframe"]').removeClass('zpt-hidden');
						jQuery('span[for="webcomponent"]').addClass('zpt-hidden');
						break;
					case 'webcomponent':
						jQuery('span[for="webcomponent"]').removeClass('zpt-hidden');
						jQuery('span[for="iframe"]').addClass('zpt-hidden');
						break;
				}
			});*/

			//Variablen zuweisen für Funktion Shortcode generieren
			jQuery(document).on('click', 'button[name="genrate_shortcode"]', function() {
				var user_val = jQuery('input[name="user_name"]').val();
				var e = document.getElementById("pls");
				var radio_val = e.value;
				var type_val = jQuery('select[name="type"]').val();
				var event_id = jQuery('select[name="zpt_event"]').val();
				var worker_id = jQuery('select[name="worker"]').val();
				var store_val = jQuery('select[name="store"]').val();
				var service_val = jQuery('select[name="service"]').val();
				var language_val = jQuery('select[name="language"]').val();

				if (event_id == 0) {
					event_id = "NULL"
				}

				if (store_val == 0) {
					store_val = ""
				}

				if (service_val == 0) {
					service_val = ""
				}

				if (worker_id == 0) {
					worker_id = ""
				}

				if (language_val == null) {
					language_val = "de_CH"
				}

				if (radio_val == 'iframe' && type_val == 'appointment') {
					var zpt_jsc = {
						iam: 'iframe',
						partner: user_val,
						type: type_val,
						store_id: store_val,
						service: service_val,
						worker: worker_id,
						lang: language_val,
					};
					jQuery('textarea[name="zpt_sc_dats"]').val(JSON.stringify(zpt_jsc));
				} else if (radio_val == 'webcomponent' && type_val == 'appointment') {

					var zpt_jsc = {
						iam: 'webcomponent',
						partner: user_val,
						type: type_val,
						store_id: store_val,
						service: service_val,
						worker: worker_id,
						lang: language_val,
					};
					jQuery('textarea[name="zpt_sc_dats"]').val(JSON.stringify(zpt_jsc));
				} else if (radio_val == 'iframe' && type_val == 'event') {

					var zpt_jsc = {
						iam: 'iframe',
						partner: user_val,
						type: type_val,
						event: event_id,
						worker: worker_id,
						service: service_val,
						lang: language_val,
					};
					jQuery('textarea[name="zpt_sc_dats"]').val(JSON.stringify(zpt_jsc));
				} else if (radio_val == 'webcomponent' && type_val == 'event') {

					var zpt_jsc = {
						iam: 'webcomponent',
						partner: user_val,
						type: type_val,
						event: event_id,
						worker: worker_id,
						service: service_val,
						lang: language_val,
					};
					jQuery('textarea[name="zpt_sc_dats"]').val(JSON.stringify(zpt_jsc));
				} else {
					alert("<?php echo zpt_calenso__( 'Error_Text_Missing_Data' ); ?>");
					e.preventDefault();
				}
			});

			/*
			jQuery(document).on('change', 'input[name="advance_option"]', function() {
				var check = jQuery('input[name="advance_option"]:checked');
				if (check.length > 0) {
					jQuery(".advance_option_div").slideDown();
				} else {
					jQuery(".advance_option_div").slideUp();
				}
			});*/
		</script>

		<?php
	} elseif ( $step == '4' ) {
		?>
		<!--Page 4
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400i,500,600,700" rel="stylesheet">-->
		<div class="zpt-form zpt-box">
			<!--header-->
			<h3 class="zpt-pad-l-18 zpt-mar-t-20 Wordpress-Ein calenso-font-color paleft"><?php echo zpt_calenso__( 'Header_Title' ); ?></h3>
			<p class="zpt-pad-l-18 calenso-sub paleft"><?php echo zpt_calenso__( 'Header_Subtitle' ); ?></p>
			<hr />​​​​​​​​​​​​​​​​​​​<br />
			<!--Stepper-->​​​​​
			<div class="stepper-wrapper">
				<div class="stepper-item completed">
					<div class="step-counter">
						<div class="check"></div>
					</div>
					<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_1_Introduction' ); ?></div>
				</div>
				<div class="stepper-item completed">
					<div class="step-counter">
						<div class="check"></div>
					</div>
					<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_2_Booking_Name' ); ?></div>
				</div>
				<div class="stepper-item completed">
					<div class="step-counter">
						<div class="check"></div>
					</div>
					<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_3_Configuration' ); ?></div>
				</div>
				<div class="stepper-item active">
					<div class="step-counter">4</div>
					<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_4_Integration_Code' ); ?></div>
				</div>
			</div>
			<br />​
			<hr />​​​​​​​​​​​​​​​​​​​<br />
			<!--Guide Text-->
			<p class="zpt-pad-l-20 zpt-pad-r-20 calenso-font-color calenso-sub"><?php echo zpt_calenso__( 'Page_4_Text_Shortcode_Instructions' ); ?></p>
			<div class="zpt-pad-l-20 zpt-pad-r-20">
				<!--Shortcode display-->
				<div class="">
					<textarea class="calenso-textarea" rows="1" readonly><?php echo $short_code; ?></textarea></div><br><br>
				<!--Back Knopf-->
				<div class="text-right">
					<?php
					/*
					$current_page_url = admin_url( 'admin.php' );
					$current_page_url = add_query_arg( 'page', 'calenso_wordpress_widget', $current_page_url );
					echo esc_url( $current_page_url );
					*/
					?>
					<button onclick="window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=calenso_wordpress_widget' ) ); ?>'" class="calenso-button">
						<?php echo zpt_calenso__( 'Page_4_Button_Finalize_Setup' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	} else {
		// First Plugin Page.
		?>
		<!--Page 1
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400i,500,600,700" rel="stylesheet">-->
		<form method="post" class="zpt-form">
			<div class="zpt-col-xs-12 zpt-box">
				<div class="zpt-col-xs-12 zpt-pad-0">
					<!--Header-->
					<h3 class="zpt-pad-l-18 zpt-mar-t-20 Wordpress-Ein calenso-font-color paleft"><?php echo zpt_calenso__( 'Header_Title' ); ?></h3>
					<p class="zpt-pad-l-18 calenso-sub paleft"><?php echo zpt_calenso__( 'Header_Subtitle' ); ?></p>
					<hr />​​​​​​​​​​​​​​​​​​​<br />
					<!--Stepper-->​​​​​
					<div class="stepper-wrapper">
						<div class="stepper-item active">
							<div class="step-counter">1</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_1_Introduction' ); ?></div>
						</div>
						<div class="stepper-item">
							<div class="step-counter">2</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_2_Booking_Name' ); ?></div>
						</div>
						<div class="stepper-item">
							<div class="step-counter">3</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_3_Configuration' ); ?></div>
						</div>
						<div class="stepper-item">
							<div class="step-counter">4</div>
							<div class="step-name calenso-font-color"><?php echo zpt_calenso__( 'Stepper_Step_4_Integration_Code' ); ?></div>
						</div>
					</div><br />
					<hr />​​​​​​​​​​​​​​​​​​​
					<div class="text-center">
						<div class="zpt-inner-desc">
							<!--Bild-->
							<img src="<?php echo ZPT_CALENSO_DIR . 'assets/img/login_illu1.svg'; ?>" class="zpt-image" alt=""><br>
							<!--Text 1-->
							<p class="zpt-pg calenso-font-color calenso-sub">
								<?php echo zpt_calenso__( 'Page_1_Text_Calenso_Description_Part_1' ); ?>
							</p><br>
							<!--Text 2-->
							<p class="zpt-pg calenso-font-color calenso-sub">
								<?php echo zpt_calenso__( 'Page_1_Text_Calenso_Description_Part_2' ); ?>
							</p>
						</div>
						<!--Knopf 1-->
						<button type="submit" class="calenso-btn-start zpt-mar-b-10 zpt-mar-l-10 zpt-mar-r-10" name="already_account"><?php echo zpt_calenso__( 'Page_1_Button_Calenso_Account_Exists' ); ?></button>
						<!--Knopf 2-->
						<a target="_blank" href="https://calenso.com/registrierung">
							<button type="button" class="calenso-btn-start zpt-mar-l-10 zpt-mar-r-10"><?php echo zpt_calenso__( 'Page_1_Button_Create_New_Calenso_Account' ); ?></button>
						</a>
					</div>
				</div>
			</div>
			<?php zpt_calenso_sidebar_html(); ?>
		</form>
		<?php
	}
}

// Shortcode ID generierung.
function zpt_calenso_widget_shortcode_function() {
	do_action( 'zpt_calenso_widget_shortcode_hook' );

	$zptListTable = new ZPT_Calenso_Booking_Table();
	$zptListTable->prepare_items();
	$delete_nonce = wp_create_nonce( 'zpt_bulk_delete_booking' );
	?>
	<div class="wrap">
		<div id="icon-users" class="icon32"><br /></div>
		<h2 class="zpt-open"><?php echo zpt_calenso__( 'Universal_Title' ); ?></h2>
		<form id="calenso-booking-filter" method="post" class="zpt-form">
			<input type="hidden" name="zpt_bulk_wpnonce" value="<?php echo $delete_nonce; ?>" />
			<?php if ( isset( $_REQUEST['page'] ) ) { ?>
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
			<?php } ?>
			<?php echo $zptListTable->display(); ?>
		</form>
	</div>
	<?php
}

