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

        $this->load->model('v1/central');
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

            $this->response($response, 200);
            
        } else {

            $response = $this->central->calls_get($this->request->body['id'], $this->request->body['manager'], $this->request->body['department'], $this->request->body['level']);
            $this->response($response, 200);
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

            $date = date('Y-m-d H:i', now());
            $login = $this->central->login($username, $password, $date);

            if ($login['status'] === TRUE) {
                //login valid

                $response = array(
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
                );

                $this->response($response, 200);
                    
            } else {
                
                //login failed
                $this->response($login, 200);
            }
        }
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