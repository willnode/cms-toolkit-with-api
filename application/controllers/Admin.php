<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'User.php';

class Admin extends User {

	const ROLE = 'admin';

	public function index()
	{
		load_info([
			'routes'=>[
				'/admin/user/',
				'/admin/profile/',
			],
		]);
	}

	public function user($id=NULL)
	{
		master_crud([
			'table' => 'login',
			'id' => $id,
			'select' => [
				'login_id', 'username',	'email', 'name', 'avatar', 'otp',
			],
			'filter' => ['role' => 'user'],
			'validations' => [
				['name', 'Name', 'required|alpha_numeric_spaces'],
				['email', 'Email', 'required|valid_email'],
				['username', 'Username', 'required|min_length[3]|alpha_numeric'],
			],
			'file_uploads' => [
				['name' => 'avatar', 'types' => 'jpg|jpeg|png|bmp']
			],
			'after_update' => function($id, $data, $is_created) {
				// OTP Check
				$otps = get_post_updates(['otp_invoke', 'otp_revoke']);
				if (!empty($otps)) {
					if (isset($otps['otp_invoke'])) {
						$otp = (version_compare(PHP_VERSION, '7.0.0') >= 0 ? 'random_int' : 'mt_rand')(111111, 999999);
					} else if (isset($otps['otp_revoke'])) {
						$otp = NULL;
					}
					get_instance()->db->update('login', ['otp' => $otp], ['login_id' => $id]);
				}
				return TRUE;
			}
		]);
	}
}
