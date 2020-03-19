<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'Basic.php';

class User extends CI_Basic_Api_Controller {

	const ROLE = 'user';

	public function index()
	{
		load_info([
			'routes'=>[
				'/user/profile/',
			],
		]);
	}

	public function profile()
	{
		master_crud([
			'table'=>'login',
			'id'=>$this->login->login_id,
			'select'=>[
				'login_id', 'username',	'email', 'name', 'avatar', 'role',
			],
			'validations'=>[
				['name', 'Name', 'required|alpha_numeric_spaces'],
				['email', 'Email', 'required|valid_email'],
			],
			'file_uploads'=>[
				['name'=>'avatar', 'types'=>'jpg|jpeg|png|bmp']
			],
			'before_update'=>function($id, &$data, $existing) {
				// Password Change
				$password = 'password';
				if (get_instance()->input->post($password) || empty($existing->{$password})) {
					if (run_validation([
						[$password, 'Password', 'required'],
						['passconf', 'Password Confirmation', "matches[$password]"]
					])) {
						$data[$password] = $_POST[$password];
						if(control_password_update($data, $password)) {
							$data['otp'] = NULL;
						}
					}
				}
				return TRUE;
			}
		]);
	}

}
