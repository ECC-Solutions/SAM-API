<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Example
 *
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array.
 *
 * @package	CodeIgniter
 * @subpackage	Rest Server
 * @category	Controller
 * @author	Phil Sturgeon
 * @link	http://philsturgeon.co.uk/code/
 */
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/v1/REST_Controller.php';

class i extends REST_Controller {

    protected $methods = array(
        'login_post' => array('level' => 101, 'limit' => 1),
        'employees_post' => array('level' => 10, 'limit' => 1),
        'test_get' => array('level' => 1, 'limit' => 1)
    );

    function user_post() {
        //$this->some_model->updateUser( $this->get('id') );
        $message = array('id' => $this->get('id'), 'name' => $this->post('name'), 'email' => $this->post('email'), 'message' => 'ADDED!');

        $this->response($message, 200); // 200 being the HTTP response code
    }

    function user_delete() {
        //$this->some_model->deletesomething( $this->get('id') );
        $message = array('id' => $this->get('id'), 'message' => 'DELETED!');

        $this->response($message, 200); // 200 being the HTTP response code
    }

    function users_get() {
        //$users = $this->some_model->getSomething( $this->get('limit') );
        $users = array(
            array('id' => 1, 'name' => 'Some Guy', 'email' => 'example1@example.com'),
            array('id' => 2, 'name' => 'Person Face', 'email' => 'example2@example.com'),
            3 => array('id' => 3, 'name' => 'Scotty', 'email' => 'example3@example.com', 'fact' => array('hobbies' => array('fartings', 'bikes'))),
        );

        if ($users) {
            $this->response($users, 404); // 200 being the HTTP response code
        } else {
            $this->response(array('error' => 'Couldn\'t find any users!'), 404);
        }
    }

    function employees_post() {

        $this->load->model('v1/central');
        $this->load->library('form_validation');

        $password = md5('@ecc_123');
        //$photo = $this->post('photo');
        //validation rules
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('email', 'email', 'valid_email|is_unique[ecc_employees_emp.email_emp]');
        $this->form_validation->set_rules('firstName', 'first name', 'required');
        $this->form_validation->set_rules('lastName', 'last name', 'required');
        $this->form_validation->set_rules('mobile', 'mobile', 'required|numeric');
        $this->form_validation->set_rules('department', 'Department', 'required|numeric');
        $this->form_validation->set_rules('homePhone', 'Department', 'numeric');

        if ($this->form_validation->run() === FALSE) {

            $firstName = $this->form_validation->error('firstName') ? $this->form_validation->error('firstName') : $this->post('firstName');
            $lastName = $this->form_validation->error('lastName') ? $this->form_validation->error('lastName') : $this->post('lastName');
            $mobile = $this->form_validation->error('mobile') ? $this->form_validation->error('mobile') : $this->post('mobile');
            $mobile2 = $this->form_validation->error('mobile2') ? $this->form_validation->error('mobile2') : $this->post('mobile2');
            $homePhone = $this->form_validation->error('homePhone') ? $this->form_validation->error('homePhone') : $this->post('homePhone');
            $homeAddress = $this->form_validation->error('homeAddress') ? $this->form_validation->error('homeAddress') : $this->post('homeAddress');
            $department = $this->form_validation->error('department') ? $this->form_validation->error('department') : $this->post('department');
            $email = $this->form_validation->error('email') ? $this->form_validation->error('email') : $this->post('email');
            $position = $this->form_validation->error('position') ? $this->form_validation->error('position') : $this->post('position');

            $response = array(
                'error' => TRUE,
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'mobile' => $mobile,
                'mobile2' => $mobile2,
                'homePhone' => $homePhone,
                'homeAddress' => $homeAddress,
                'department' => $department,
                'email' => $email,
                'position' => $position
            );
            $this->response($response, 200);
        } else {

            $response = $this->central->employee_post($this->post('firstName'), $this->post('lastName'), $this->post('mobile'), $this->post('mobile2'), $this->post('homePhone'), $this->post('homeAddress'), $this->post('department'), $this->post('position'), $this->post('email'), $password);

            //generate key
            // Build a new key
            $key = self::_generate_key();
            //exit(var_dump($response));
            // If no key level provided, give them a rubbish one
            $level = $this->post('level') ? $this->post('level') : 1;
            $type = $this->post('type') ? $this->post('type') : 'web';
            $ignore_limits = $this->post('ignore_limits') ? $this->post('ignore_limits') : 1;

            //insert to database
            //self::_insert_key($key, array('level' => $level, 'ignore_limits' => $ignore_limits, 'userid' => $response['id'], 'type' => $type));

            $this->response($response, 200);
        }
    }

    function calls_post() {

        $this->load->model('api/central');
        $this->load->library('form_validation');

        //this resource receiving data as application/json
        //$_POST have to be hacked in order to validate it correctly
        //hacking the $_POST
        $_POST['id'] = $this->request->body['id'];
        $_POST['manager'] = $this->request->body['manager'];
        $_POST['department'] = $this->request->body['department'];
        $_POST['level'] = $this->request->body['level'];

        //validation rules
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('id', 'id', 'required|numeric');
        $this->form_validation->set_rules('manager', 'manager', 'required|numeric');
        $this->form_validation->set_rules('department', 'department', 'required');
        $this->form_validation->set_rules('level', 'level', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {

            $employeeId = $this->form_validation->error('id') ? $this->form_validation->error('id') : $this->post('id');
            $manager = $this->form_validation->error('manager') ? $this->form_validation->error('manager') : $this->post('manager');
            $department = $this->form_validation->error('department') ? $this->form_validation->error('department') : $this->post('department');
            $level = $this->form_validation->error('level') ? $this->form_validation->error('level') : $this->post('level');

            $response = array(
                'status' => FALSE,
                'error' => 'We don\'t have data to display, Minimum information required to process the request is missing',
                'authenticated' => TRUE,
                'id' => $employeeId,
                'manager' => $manager,
                'department' => $department,
                'level' => $level
            );

            $this->response(json_encode($response), 200);
        } else {

            $response = $this->central->calls_get($this->request->body['id'], $this->request->body['manager'], $this->request->body['department'], $this->request->body['level']);
            $this->response(json_encode($response), 200);
        }
    }

    function login_post() {

        //this resource receiving data as application/json
        //$_POST have to be hacked in order to validate it correctly
        //hacking the $_POST
        $_POST['username'] = $this->request->body['username'];
        $_POST['password'] = $this->request->body['password'];
        $_POST['access_token'] = $this->request->body['access_token'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $access_token = $_POST['access_token'];

        if (empty($username) || empty($password)) {

            $response = array(
                'status' => FALSE,
                'authenticated' => FALSE,
                'error' => 'Kindly ensure to fullfil all required information in order to log you in'
            );
            $this->response($response, 200);
        } else {

            $this->load->model('v1/central');
            $password = md5($password);

            $refer = 'SAM web client';
            $token_type = 'Bearer';
            $date = date('Y-m-d H:i', now());
            $login = $this->central->login($username, $password, $date);

            if ($login['authenticated'] === TRUE) {
                //login valid
                $access_token_validation = self::_check_token($access_token, $login['employeeid']);
                if ($access_token_validation) {

                    $response = array(
                        'status' => FALSE,
                        'authenticated' => TRUE,
                        'error' => 'Your authentication is still valid'
                    );
                    $this->response($response, 200);
                } else {

                    $genToken = self::_generate_token();
                    //pass access_token & level & manager flag in order to define token scope
                    $tokenInfo = self::_insert_token($genToken['access_token'], $login['level'], $login['is_manager'], array('userId_token' => $login['employeeid'], 'expire_token' => 3600, 'refer_token' => $refer, 'refresh_token' => $genToken['refresh_token'], 'type_token' => $token_type));
                    $scopes = self::_scopes($tokenInfo['scopes_token']);

                    $token = array(
                        'access_token' => $genToken['access_token'],
                        'refresh_token' => $genToken['refresh_token'],
                        'token_type' => $token_type,
                        'expires_in' => $tokenInfo['dateCreated_token'],
                        'scopes' => $scopes
                    );

                    $access_token = array(
                        'status' => TRUE,
                        'authenticated' => TRUE,
                        'error' => 'logged in successfully',
                        'id' => $login['employeeid'],
                        'username' => $login['loggedin_user'],
                        'is_manager' => $login['is_manager'],
                        'level' => $login['level'], //level: admin:1, moderator:2, user:3, //master details username: master, password: #@dm!n
                        'department' => $login['department'],
                        'force_password' => $login['force_password'],
                        'name' => $login['name'],
                        'position' => $login['position'],
                        'avatar' => $login['avatar'],
                        'token' => $token
                    );

                    $this->response($access_token, 200);
                }
            } else {
                //login failed
                $this->response($login, 200);
            }
        }
    }

    private function _generate_token() {

        $this->load->helper('security');

        do {
            //creating access token
            $salt = sha1(uniqid(mt_rand(), true));
            $token = substr($salt, 0, 40);
        }
        //Already in the DB? Fail, Try again.
        while (self::_token_exists($token));

        //creating the refresh token
        $refreshSalt = sha1(uniqid(mt_rand(), true));
        $refresh_token = substr($refreshSalt, 0, 40);

        $data = array(
            'access_token' => $token,
            'refresh_token' => $refresh_token
        );
        return $data;
    }

    private function _token_exists($token) {
        return $this->db->where('tokenId_token', $token)->count_all_results('ecc_tokens_token') > 0;
    }

    private function _insert_token($token, $level, $manager, $data) {

        $data['tokenId_token'] = $token;
        $data['dateCreated_token'] = function_exists('now') ? now() : time();

        //define scopes
        if ($level === '1') {
            //if it is administration level
            //define scopes: comma seperated scopes
            $scopes = "calls:full,departments:full,employees:full,companies:full,stages:full,accounts:full,resources:full,products:full,reports:full";
        }
        $data['scopes_token'] = $scopes;

        $this->db->set($data)->insert('ecc_tokens_token');
        return $data;
    }

    private function _check_token($token, $user) {

        $now = now();
        return $this->db->where("tokenId_token = '" . $token . "' && dateCreated_token+3600" . "> '" . $now . "' && userId_token = " . $user . " && valid_token = 1")->count_all_results('ecc_tokens_token') > 0;
    }

    private function _generate_key() {
        $this->load->helper('security');

        do {
            $salt = do_hash(time() . mt_rand());
            $new_key = substr($salt, 0, config_item('rest_key_length'));
        }

        // Already in the DB? Fail. Try again
        while (self::_key_exists($new_key));

        return $new_key;
    }

    private function _scopes($scope) {

        //split scopes into array
        $parts = explode(',', $scope);
        $arr = array();

        foreach ($parts as $val) {
            list($key, $value) = explode(':', $val);
            $arr[$key] = $value;
        }


        return $arr;
    }

    // --------------------------------------------------------------------



    private function _key_exists($key) {
        return $this->db->where('key', $key)->count_all_results(config_item('rest_keys_table')) > 0;
    }

    private function _insert_key($key, $data) {

        $data['key'] = $key;
        $data['date_created'] = function_exists('now') ? now() : time();

        return $this->db->set($data)->insert(config_item('rest_keys_table'));
    }

}