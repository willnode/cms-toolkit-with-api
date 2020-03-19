<?php
defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * Basic wrapper for ALL login roles shared functionality
 */
class CI_Basic_Api_Controller extends CI_Controller {

	/**
	 * To be overridden, role name applied
	 */
	const ROLE = NULL;

	/**
	 * At construct, do additional check if user logged in (and using proper role)
	 */
	public function __construct() {

		parent::__construct();

		// Always set CORS
		set_cors_headers();

		// respond to preflights
		if (REQUEST_METHOD == OPTIONS) {

			// return only the headers and not the content
			exit;
		}

		// Get authentication
		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];
			$login = $this->db->where('username', $username)->or_where('email', $username)->get('login')->row();
			if (!empty($login)) {
				if (password_verify($password, $login->password) || $login->otp === $password) {
					$this->login = $login;
					$this->current_id = $this->login->login_id;
				}
			}
		}

		// Role Room Precheck
		if (static::ROLE) {
			if (empty($this->login) OR $this->login->role !== static::ROLE) {
				load_401('Wrong Authentication', static::ROLE);
			}
		}
	}

	public function logout()
	{
		load_401('Logged out', static::ROLE ?: 'guest');
	}
}