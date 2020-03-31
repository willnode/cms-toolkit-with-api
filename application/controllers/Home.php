<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'Basic.php';

class Home extends CI_Basic_Api_Controller {

	public function index()
	{
		load_info([
			'routes'=>[
				'/login/',
				'/forgot/',
				'/admin/',
				'/user/'
			],
		]);
	}

	public function login()
	{
		if (!empty($this->login)) {
			unset($this->login->password);
			load_ok([
				'login'=>$this->login
			]);
		} else {
			load_401('Wrong Authentication', 'guest');
		}
	}

	public function forgot()
	{
		if (REQUEST_METHOD === POST) {
			if (run_validation([
				['email', 'Email', 'required|valid_email'],
			])) {
				$login = get_values_at('login', $_POST['email'], 'load_404', 'email');
				if (empty($_POST['pin'])) {
					$otp = generate_pin();
					get_instance()->db->update('login', ['otp' => $otp], ['login_id' => $login->login_id]);
					// TODO: Send email to reset page
					load_ok('PIN has been generated');
				} else {
					if ($login->otp !== $_POST['pin']) {
						load_404('PIN is incorrect');
					}
					else if (empty($_POST['password'])) {
						load_ok('PIN is correct');
					}
					else {
						$data = get_post_updates(['password'], ['otp' => null]);
						control_password_update($data);
						get_instance()->db->update('login', $data, ['login_id' => $login->login_id]);
						load_ok('Password updated');
					}
				}
			}
		}
		load_405();
	}

	public function load_404()
	{
		load_404();
	}

	public function hash($password)
	{
		// Built-in hash helper
		echo password_hash($password, PASSWORD_DEFAULT);
	}
}
