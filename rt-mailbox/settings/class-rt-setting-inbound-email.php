<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 12/9/14
 * Time: 5:10 PM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Zend\Mail\Storage\Imap as ImapStorage;

if ( ! class_exists( 'RT_Setting_Inbound_Email' ) ) {

	/**
	 * Class RT_Setting_Inbound_Email
	 */
	class RT_Setting_Inbound_Email {

		/**
		 * @var string
		 */
		var $user_id = '';
		/**
		 * @var null
		 */
		var $oauth2 = null;
		/**
		 * @var null
		 */
		var $client = null;

		/**
		 * @var $base_url - url for page
		 */
		var $base_url;

		function __construct( $base_url ) {
			$this->base_url = $base_url;
			add_action( 'init', array( $this, 'save_replay_by_email' ) );
		}


		/**
		 * @param       $field
		 * @param       $value
		 * @param array $modules
		 * @param bool  $newflag
		 */
		public function rt_reply_by_email_view( $field, $value, $modules, $newflag = true ) {
			global $rt_mail_settings, $rt_imap_server_model;

			$imap_servers = $rt_imap_server_model->get_all_servers();

			$server_types = array();
			if ( ! empty( $imap_servers ) ){
				$server_types['imap'] = 'IMAP';
			}
			$server_types = apply_filters( 'rt_mailbox_server_type', $server_types );

			if ( empty( $imap_servers ) ){
				echo '<div id="error_handle" class=""><p>'.__( 'Please set Imap Servers detail on ' ).'<a href="' . esc_url( admin_url( 'admin.php?page='.Rt_Mailbox::$page_name.'&tab=imap' ) ) . '">IMAP </a>  Page </p></div>';
				return;
			} else {
				if ( $newflag ) { ?>
						<legend><a class="button" id="rtmailbox_add_personal_email" href="#"><?php _e( 'Add Email' ); ?></a></legend>
						<div class="rtmailbox-hide-row" id="rtmailbox_email_acc_type_container">
							<div class="rtmailbox-severtype-container" >
								<input type="hidden" name="module_to_register" name="module_to_register" value="<?php echo $modules; ?>" />
								<select id="rtmailbox_select_email_acc_type">
									<option value=""><?php _e( 'Select Server Connection Type' ); ?></option>
									<?php foreach ( $server_types as $key => $value ) {?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
									<?php } ?>
								</select>
							</div>
						<?php if ( $imap_servers ) { ?>
							<div class="rtmailbox-hide-row" id="rtmailbox_add_imap_acc_form" autocomplete="off">
								<select  name="rtmailbox_imap_server" id="rtmailbox_imap_server">
									<option value=""><?php _e( 'Select Mail Server' ); ?></option>
									<?php foreach ( $imap_servers as $server ) { ?>
										<option value="<?php echo esc_attr( $server->id ); ?>"><?php echo esc_html( $server->server_name ); ?></option>
									<?php } ?>
								</select>
								<div id="rtmailbox_add_imap_acc_fields">
								</div>
								<input id="rtmailbox_add_imap" name="rtmailbox_add_imap_email" class="button button-primary" type="submit" value="save">
							</div>
						<?php } ?>
						</div>
					<?php
				}
			}
			?>
			<div class="mail_list" >
				<h3 class="title">Mail List</h3><?php
				$rCount = 0;
				$is_empty_mailbox_check = true;
				$google_acs = $rt_mail_settings->get_user_google_ac( array( 'module' => $modules ) );
				if ( isset( $google_acs ) && ! empty( $google_acs ) ){
				foreach ( $google_acs as $ac ){
					$rCount ++;
					$ac->email_data = unserialize( $ac->email_data );
					$email          = filter_var( $ac->email_data['email'], FILTER_SANITIZE_EMAIL );
					$email_type     = $ac->type;
					$imap_server    = $ac->imap_server;
					$mail_folders   = ( isset( $ac->email_data['mail_folders'] ) ) ? $ac->email_data['mail_folders'] : '';
					$mail_folders   = array_filter( explode( ',', $mail_folders ) );
					$inbox_folder   = ( isset( $ac->email_data['inbox_folder'] ) ) ? $ac->email_data['inbox_folder'] : '';
					$token = $ac->outh_token;
					$is_empty_mailbox_check = false;
					if ( isset( $ac->email_data['picture'] ) ){
						$img          = filter_var( $ac->email_data['picture'], FILTER_VALIDATE_URL );
						$personMarkup = "<img src='$img?sz=96'>";
					} else {
						$personMarkup = get_avatar( $email, 96 );
					}

					$all_folders = null;
					$login_successful = true;

					try {
						$hdZendEmail = new Rt_Zend_Mail();
						if ( $hdZendEmail->try_imap_login( $email, $token, $email_type, $imap_server ) ) {
							$storage     = new ImapStorage( $hdZendEmail->imap );
							$all_folders = $storage->getFolders();
						} else {
							$login_successful = false;
						}
					} catch ( Exception $e ) {
						echo '<p class="description">' . esc_html( $e->getMessage() ) . '</p>';
					} ?>
					<div>
						<div>
							<input type="hidden" name='mail_ac[]' value="<?php echo esc_attr( $email ); ?>"/>
							<strong><?php if ( isset( $ac->email_data['name'] ) ) { echo $ac->email_data['name']; } ?> <br/><a href='mailto:<?php echo $email ?>'><?php echo $email ?></a></strong>
							<div class="rtmailbox-maillist-action">
								<?php if ( $login_successful ) { ?>
									<a class="button rtMailbox-hide-mail-folders mailbox_show_hide" href="#"><?php echo __( 'Show' ); ?></a>
								<?php } ?>
								<a class='button remove-google-ac' href='<?php echo esc_url( $_SERVER['REQUEST_URI'] . '&rtmailbox_submit_enable_reply_by_email=save&email=' . $email . '&module_to_register=' . $modules ); ?>'><?php echo __( 'Remove A/C' ); ?></a>
							</div>
						</div>
						<?php if ( $login_successful ) { ?>
							<table class="rtmailbox-hide-row">
								<tr valign="top" >
									<td class="long">
										<label><strong><?php _e( 'Mail Folders to read' ); ?></strong></label><br/>
										<label>
											<?php _e( 'Inbox Folder' ); ?>
											<select data-email-id="<?php echo esc_attr( $ac->id ); ?>" class="mailbox-inbox-folder" name="inbox_folder[<?php echo esc_attr( $email ); ?>]" data-prev-value="<?php echo esc_attr( $inbox_folder ); ?>">
												<option value=""><?php _e( 'Choose Inbox Folder' ); ?></option>
												<?php if ( ! is_null( $all_folders ) ) { ?>
													<?php $hdZendEmail->render_folders_dropdown( $all_folders, $value = $inbox_folder ); ?>
												<?php } ?>
											</select>
										</label>
										<p class="description"><?php _e( 'Choosing an Inbox Folder is mandatory in order to parse the emails from Mailbox.' ) ?></p>
										<?php if ( ! is_null( $all_folders ) ) { ?>
											<div id="mail_folder_container">
												<?php $hdZendEmail->render_folders_checkbox( $all_folders, $element_name = 'mail_folders[' . esc_attr( $email ) . ']', $values = $mail_folders, $data_str = 'data-email-id=' . $ac->id, $inbox_folder ); ?>
											</div>
										<?php } else { ?>
											<p class="description"><?php _e( 'No Folders found.' ); ?></p>
										<?php } ?>
									</td>
								</tr>
							</table>
						<?php } else {
							echo '<p class="long"><strong>'.__( ' Please remove account and enter correct credential or enable IMAP in your mailbox.' ). '</strong></p>';
						}?>
					</div>
				<?php
				} ?>
					<script>
						jQuery(document).ready(function ($) {
							$(document).on('change', 'select.mailbox-inbox-folder', function (e) {
								e.preventDefault()
								inbox = $(this).val();
								prev_value = $(this).data('prev-value');
								$(this).data('prev-value', inbox);
								var email_id = $(this).data('email-id');
								$('input[data-email-id="' + email_id + '"][value="' + inbox + '"]').attr('disabled', 'disabled');
								$('input[data-email-id="' + email_id + '"][value="' + inbox + '"]').attr('checked', false);
								$('input[data-email-id="' + email_id + '"][value="' + inbox + '"]').prop('checked', false);
								$('input[data-email-id="' + email_id + '"][value="' + prev_value + '"]').removeAttr('disabled');
							});
						});
					</script>
				<?php } ?>
				<?php
				if ( $is_empty_mailbox_check ){
					?>
					<p>You have no mailbox setup please setup one.</p>
				<?php
				}
				?>
			</div>
			<input class="button button-primary" name="rtmailbox_submit_enable_reply_by_email" type="submit" value="save">
			<?php
			do_action( 'rt_mailbox_reply_by_email_view' );
		}

		public function save_replay_by_email() {
			global $rt_mail_settings;
			$module = '';
			if ( isset( $_POST['module_to_register'] ) && ! empty( $_POST['module_to_register'] ) ){
				$module = $_POST['module_to_register'];
			}
			if ( isset( $_REQUEST['rtmailbox_submit_enable_reply_by_email'] ) && 'save' == $_REQUEST['rtmailbox_submit_enable_reply_by_email'] ) {
				if ( isset( $_POST['mail_ac'] ) ) {
					foreach ( $_POST['mail_ac'] as $mail_ac ) {
						if ( ! is_email( $mail_ac ) ){
							continue;
						}
						if ( isset( $_POST['imap_password'] ) ) {
							$token = rt_encrypt_decrypt( $_POST['imap_password'] );
						} else {
							$token = null;
						}
						if ( isset( $_POST['imap_server'] ) ) {
							$imap_server = $_POST['imap_server'];
						} else {
							$imap_server = null;
						}
						$email_ac   = $rt_mail_settings->get_email_acc( $mail_ac, $module );
						$email_data = null;
						if ( isset( $_POST['mail_folders'] ) && ! empty( $_POST['mail_folders'] ) && is_array( $_POST['mail_folders'] ) && ! empty( $email_ac ) ) {
							$email_data                 = maybe_unserialize( $email_ac->email_data );
							$email_data['mail_folders'] = implode( ',', $_POST['mail_folders'][ $mail_ac ] );
						}
						if ( isset( $_POST['inbox_folder'] ) && ! empty( $_POST['inbox_folder'] ) && ! empty( $email_ac ) ) {
							if ( is_null( $email_data ) ) {
								$email_data = maybe_unserialize( $email_ac->email_data );
							}
							$email_data['inbox_folder'] = $_POST['inbox_folder'][ $mail_ac ];
						}
						$rt_mail_settings->update_mail_acl( $mail_ac, $token, maybe_serialize( $email_data ), $imap_server );
					}
				}
				if ( isset( $_REQUEST['email'] ) && is_email( $_REQUEST['email'] ) ) {
					global $rt_mail_accounts_model, $rt_mail_crons;
					$module = $rt_mail_accounts_model->get_mail_account( array( 'email' => $_REQUEST['email'] ) );
					$rt_mail_settings->delete_user_google_ac( $_REQUEST['email'], $module );
					$tmp = $module[0];
					$rt_mail_crons->deregister_cron_for_module( $tmp->module );
					return;
				}
				if ( isset( $_REQUEST['rtmailbox_add_imap_email'] ) ) {
					if ( isset( $_POST['rtmailbox_imap_user_email'] ) && ! empty( $_POST['rtmailbox_imap_user_email'] ) && isset( $_POST['rtmailbox_imap_user_pwd'] ) && ! empty( $_POST['rtmailbox_imap_user_pwd'] ) && isset( $_POST['rtmailbox_imap_server'] ) && ! empty( $_POST['rtmailbox_imap_server'] ) ) {
						$password    = $_POST['rtmailbox_imap_user_pwd'];
						$email       = $_POST['rtmailbox_imap_user_email'];
						$email_data  = array(
							'email' => $email,
						);
						$imap_server = $_POST['rtmailbox_imap_server'];
						$rt_mail_settings->add_user_google_ac( rt_encrypt_decrypt( $password ), $email, maybe_serialize( $email_data ), $this->user_id, 'imap', $imap_server, $module );
					}
				}
			}
		}

	}

}
