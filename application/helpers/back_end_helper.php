<?php

function generate_pin () {
	return (version_compare(PHP_VERSION, '7.0.0') >= 0 ? 'random_int' : 'mt_rand')(111111, 999999);
}

function set_cors_headers() {
	$frontUrl =  get_instance()->config->item('front_url');
	if ($frontUrl) {
		header('Access-Control-Allow-Origin: '.$frontUrl);
		header('Access-Control-Allow-Headers: X-Requested-With, Authorization');
		header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE');
		header('Access-Control-Max-Age: 86400');
		header('Vary: Origin');
	}
}

/**
 * CodeIgniter Form validation for short, dirty, quick config
 */
function run_validation($config = []) {
	$ci = &get_instance();
	if (REQUEST_METHOD !== POST) load_error('Action require POST method');
	$ci->load->library('form_validation');
	foreach ($config as $conf) {
		if (count($conf) >= 3 AND $conf[2]) {
			$ci->form_validation->set_rules($conf[0], $conf[1], $conf[2]);
		}
	}
	if ( $ci->form_validation->run()) {
		return true;
	}
	load_json([
		'status'=>'Error',
		'message' => $ci->form_validation->error_string(' ', ' '),
		'validations'=>$ci->form_validation->error_array()
	]);
	exit;
}

/**
 * Quick way to get POST values in assosicate array
 */
function get_post_updates($vars = [], $default = []) {
	$ci = &get_instance();
	$updates = $default;
	foreach ($vars as $var) {
		if (($val = $ci->input->post($var)) !== null) {
			$updates[$var] = $val;
		}
	}
	return $updates;
}

/**
 * Handle file removal easily
 */
function control_file_delete($folder, $existing_value = '')
{
	$existing_file = "./uploads/$folder/$existing_value";
	if (is_file($existing_file)) {
		unlink($existing_file);
	}
}

/**
 * Handle file upload on POST, and also delete existing file in previous data (so no orphan files)
 */
function control_file_upload(&$updates, $name, $folder, $existing_row = NULL, $types = '*', $required = FALSE, $custom_file_name = NULL)
{
	$ci = &get_instance();
    if (isset($_FILES[$name]) AND is_uploaded_file($_FILES[$name]['tmp_name'])) {
        if (!is_dir("./uploads/$folder/")) {
            mkdir("./uploads/$folder/", 0777, true);
		}
        $ci->upload->initialize([
            'upload_path' => "./uploads/$folder/",
			'allowed_types' => $types,
			'file_name' => !empty($custom_file_name) ? $custom_file_name : NULL,
		]);
        if ($ci->upload->do_upload($name)) {
			$updates[$name] = $ci->upload->file_name;
			if (!empty($existing_row->{$name}))
				control_file_delete($folder, $existing_row->{$name});
			return TRUE;
		} else {
			load_error($ci->upload->display_errors('', "\n"));
		}
    } elseif ($ci->input->post($name.'_delete') && !$required) {
		$updates[$name] = '';
		if (!empty($existing_row->{$name}))
			control_file_delete($folder, $existing_row->{$name});
		return TRUE;
	}
	if ($required && empty($existing_row->{$name}))
		load_error("File $name is required");
	return TRUE;
}

/**
 * Modify POST data in assoc array to hash the PASSWORD field
 */
function control_password_update(&$updates, $field = 'password') {
	if (!empty($updates[$field])) {
		$updates[$field] = password_hash($updates[$field], PASSWORD_BCRYPT);
		return TRUE;
	}
	return FALSE;
}

/**
 * Allow custom DB error handling
 */
function catch_db_error() {
	get_instance()->db->db_debug = FALSE;
	error_reporting(0);
}

/**
 * Check if last DB query throws some error
 */
function check_db_error() {
	if (!empty(get_instance()->db->error()['message'])) {
		load_error(get_instance()->db->error()['message']);
	}
	return FALSE;
}

function get_default_values($table, $field_key = NULL, $select = '*') {
	if ($field_key === NULL)
		$field_key = $table.'_id';
	$fields = get_instance()->db->list_fields($table);
	$values = [];
	foreach ($fields as $f) {
		$values[$f] = $f === $field_key ? 0 : '';
	}
	if (is_array( $select )) {
		foreach ($values as $key => $value) {
			if (!in_array($key, $select)) {
				unset($values[$key]);
			}
		}
	}
	return (object)$values;
}

function get_values_at($table, $id, $not_found_handler = '', $field_key = NULL, $select = '*') {
	if ($field_key === NULL)
		$field_key = $table.'_id';
	$data = get_instance()->db->select($select)->get_where($table, [$field_key => $id])->row();
	if (empty($data) && is_callable($not_found_handler))
		$not_found_handler();
	return $data;
}

/**
 * Update or insert depending on ID, and update that's id to LAST_INSERT_ID
 * or Show the error if it fails
 */
function insert_or_update($table, &$data, &$id, $id_column = NULL) {
	catch_db_error();
	if ($id == 0) { // ID 0 means we do insert
		get_instance()->db->insert($table, $data);
		if (check_db_error()) {
			return FALSE;
		}
		$id = get_instance()->db->insert_id();
	} else {
		$id_column = $id_column ?: $table."_id";
		get_instance()->db->limit(1)->update($table, $data, [$id_column => $id]);
		if (check_db_error()) {
			return FALSE;
		}
	}
	return TRUE;
}

/**
 * Like load_404, but for 204 (PUT's OK)
 */
function load_204($msg = NULL) {
	set_status_header(204);
	if ($msg) {
		load_error($msg);
	}
	exit;
}

function load_404($msg = 'Not Found') {
	set_status_header(404);
	if ($msg) {
		load_error($msg);
	}
	exit(4);
}

/**
 * Like load_404, but for 401
 */
function load_401($msg = NULL, $realm = NULL) {
	set_status_header(401);
	if ($realm) {
		header('WWW-Authenticate: Basic realm="'.$realm.'"');
	}
	if ($msg) {
		load_error($msg);
	}
	exit;
}

/**
 * Like load_404, but for 405
 */
function load_405($msg = NULL) {
	set_status_header(405);
	if ($msg) {
		load_error($msg);
	}
	exit;
}

/**
 * Return JSON of PHP data
 */
function load_json($data) {
	header('Content-Type: application/json');
	echo json_encode($data);
}

/**
 * Return JSON of PHP data
 */
function load_info($data = []) {
	$data['status'] = 'Info';
	load_json($data);
	exit;
}

/**
 * Return JSON of PHP data
 */
function load_ok($data = []) {
	$data['status'] = 'OK';
	load_json($data);
	exit;
}

/**
 * Return JSON of PHP data
 */
function load_error($message) {
	load_json([
		'status'=>'Error',
		'message' => $message
	]);
	exit;
}

/**
 * The general setup to send email with single call
 */

function send_email_for_real($attr) {
	$scheme = isset($attr['scheme']) ? $attr['scheme'] : 'default';
	// required attributes
	$from = $attr['from'];
	$sender = $attr['sender'];
	$to = $attr['to'];
	$subject = $attr['subject'];
	$body = $attr['body'];

	// Action
	$this->load->config('email');
	$this->load->library('email', $config['email'][$scheme]);
	$this->email->from($from, $sender);
	$this->email->to($to);
	$this->email->subject($subject);
	$this->email->message($body);
	if($this->email->send()) {
		return TRUE;
	} else {
		load_error($this->email->print_debugger());
	}
}


/**
 * The Generic Database Model that's fully compatible with Bootstrap-Table AJAX
 */
function ajax_table_driver($table, $filter = [], $searchable_columns = [], $select = '*') {
	$ci = &get_instance();
	$cursor = $ci->db->select($select)->from($table)->where($filter);
	$totalNotFiltered = $cursor->count_all_results('', FALSE);
	$search = $ci->input->get('search');
	$limit = $ci->input->get('limit');
	$offset = $ci->input->get('offset');
	if ($search && count($searchable_columns) > 0) {
		$cursor->group_start();
		foreach ($searchable_columns as $col) {
			$cursor->or_like($col, $search);
		}
		$cursor->group_end();
		$cursor->offset($ci->input->get('offset'));
		$total = $cursor->count_all_results('', FALSE);
	} else {
		$total = $totalNotFiltered;
	}

	if ($limit) $cursor->limit($limit);
	if ($offset) $cursor->offset($offset);

	return [
		'total' => $total,
		'totalNotFiltered' => $totalNotFiltered,
		'rows' => $cursor->get()->result()
	];
}


/**
 * The Master CRUD is heavily opinionated yet configurable REST logic covers most CRUD needs
 * To make it work for GET you
 * 		NEED: ['table', 'id'],
 * 		OPTIONALLY: ['select', 'filter', 'searchable'],
 * 		FINETUNING: ['field_key', 'method', 'joins']
 * To make it work for POST you
 * 		NEED: ['validations', 'file_uploads'],
 * 		OPTIONALLY: ['updatables', 'message_ok', 'filter']
 * 		FINETUNING: ['before_update', 'after_update']
 */
function master_crud($attr) {
	// Two things only required: Table name and ID
	$table = $attr['table'];
	$row_id = $attr['id'];
	$field_key = isset($attr['field_key']) ? $attr['field_key'] : $table.'_id';
	$method =  isset($attr['method']) ? $attr['method'] : REQUEST_METHOD;
	$select = isset($attr['select']) ? $attr['select'] : '*';
	$filter = isset($attr['filter']) ? $attr['filter'] : [];
	if ($row_id === NULL) {
		if ($method === GET) {
			$searchable = isset($attr['searchable']) ? $attr['searchable'] : [];
			// Traverse table
			isset($attr['joins']) AND is_callable($attr['joins']) AND $attr['joins']();
			load_ok(ajax_table_driver($table, $filter, $searchable, $select));
		}
		if ($method === POST) {
			// Fallback, same stuff
			$row_id = 0;
		}
	}
	if ($row_id !== NULL) {
		if (!is_numeric($row_id)) {
			load_404();
		}
		$row_id = (int)$row_id;
		if ($method === GET) {
			if ($row_id === 0) {
				// Get default table values
				load_ok([
					'data' => get_default_values($table, $field_key, $select)
				]);
			} else {
				// Get row resource
				get_instance()->db->where($filter);
				load_ok([
					'data' => get_values_at($table, $row_id, 'load_404', $field_key, $select)
				]);
			}
		} else if ($method === POST || $method === PUT) {
			$validations = isset($attr['validations']) ? $attr['validations'] : [];
			$files = isset($attr['file_uploads']) ? $attr['file_uploads'] : [];
			$updatables = isset($attr['updatables']) ? $attr['updatables'] : (
				// Auto infer from validations + file_uploads
				array_merge(
					array_map(function($x){return $x[0];}, $validations),
					array_map(function($x){return is_string($x) ? $x : $x['name'];}, $files)
				)
			);
			get_instance()->db->where($filter);
			$message_ok = isset($attr['message_ok']) ? $attr['message_ok'] : "Successfully Saved";
			$existing = get_values_at($table, $row_id, '', $field_key);
			$default_values = isset($attr['default_values']) ? $attr['default_values'] : $filter;
			$before_update = isset($attr['before_update']) ? $attr['before_update'] : NULL;
			$after_update = isset($attr['after_update']) ? $attr['after_update'] : NULL;
			$row_create_flag = $row_id === 0;
			$ok = TRUE;
			$ok AND ($ok = run_validation($validations));
			$ok AND ($data = get_post_updates($updatables, $default_values));
			foreach ($files as $file) {
				if ($ok) {
					$file_name = is_string($file) ? $file : $file['name'];
					$file_folder = isset($file['folder']) ? $file['folder'] : $file_name;
					$file_types = isset($file['types']) ? $file['types'] : '*';
					$file_required = isset($file['required']) ? $file['required'] : FALSE;
					$file_custom_name = (isset($file['custom_filename']) AND is_callable($file['custom_filename']) AND isset($_FILES[$file_name]['name'])) ? $file['custom_filename']($row_id, $_FILES[$file_name]['name'], $existing) : NULL;
					$ok = control_file_upload($data, $file_name, $file_folder, $existing, $file_types, $file_required, $file_custom_name);
				}
			}
			$ok AND is_callable($before_update) AND ($ok = $before_update($row_id, $data, $existing));
			$ok AND insert_or_update($table, $data, $row_id, $field_key);
			$ok AND is_callable($after_update) AND ($ok = $after_update($row_id, $data, $row_create_flag));
			$ok AND load_ok(['message' => $message_ok ]);
			load_error('Unknown error');
		} else if ($method === DELETE) {
			if ($this->db->delete($table, [$field_key => $row_id])) {
				load_ok('Deleted successfully');
			} else {
				load_404();
			}
		}
	}
}