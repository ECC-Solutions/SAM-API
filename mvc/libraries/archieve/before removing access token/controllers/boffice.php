<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class boffice extends CI_Controller {

    public $id;
    public $level;
    public $manager;
    public $department;
    public $logged_username;
    public $name;
    public $position;
    public $avatar;
    public $forcePassword;
    public $status;
    public $getCallNotification;
    
    function __construct() {
        parent::__construct();
        //helper
        $this->load->helper('date');
        //$this->load->helper('text');
        // check logged in & credentials
        $this->is_logged_in();
        $this->credentials();
        $this->calls_notification();
    }

    function initial() {
        //$this->load->view('boffice/js/jquery-1.8.2.min.js');
        $this->load->view('boffice/js/jquery-1.9.2-ui.js');
        $this->load->view('boffice/js/jquery.gritter.min.js');
        $this->load->view('boffice/js/pusher.min.js');
        $this->load->view('boffice/js/PusherNotifier.js');
        //$this->load->view('boffice/js/jquery.mousewheel.min.js');
        //$this->load->view('boffice/js/jquery.mCustomScrollbar.min.js');
        $this->load->view('boffice/js/jquery.validate.js');
    }

    function app() {
        $data['loggedIn'] = $this->id;
        $this->load->view('boffice/js/initial.js', $data);
    }

    function index() {

        if ($this->ForcePassword == '1') {
            redirect('boffice/password');
        }
        $this->load->library('Sanitizer');
        $this->load->model('common');
        $data['access'] = $this->status; //true or false
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['avatar'] = $this->avatar;
        $data['title'] = 'Welcome ';
        $data['action'] = 'New Employee';
        $data['type'] = 'calls';
        $data['id'] = $this->uri->segment(4);
        $data['method'] = $this->router->fetch_method();
        $data['level'] = $level = $this->level;
        $data['notification'] = $this->getCallNotification;
        $data['department'] = $department = $this->department;
        $data['manager'] = $manager = $this->manager;
        $data['loggedIn'] = $this->id;
        $data['substr'] = new Sanitizer($string, $count);
        $data['EmployeeMeta'] = new Sanitizer($EmpId);

        //notifications
        $data['Notices'] = $this->common->notifications();
        //pullPusher
        $data['pullCount'] = $this->common->pusher($this->id);
        //current employee
        $data['Emp'] = $this->common->getEmployee($this->id);
        //get the calls
        //$data['getCallStatus'] = $this->common->getCalls($manager, $level, $department, $this->id);

        $this->load->view('boffice/header', $data);
        $this->load->view('boffice/dataTable');
        $this->load->view('boffice/footer');
    }

    function password() {
        $data['id'] = $this->id;
        $this->load->view('boffice/password', $data);
    }

    function changePassword() {
        $password = md5($this->input->post('password'));
        $password2 = md5($this->input->post('password2'));

        if ($password == $password2) {
            $update = array('password_emp' => $password, 'force_password_emp' => '0', 'status_emp' => '1');
            $this->db->where('id_emp', $this->id);
            $this->db->update('ecc_employees_emp', $update);
            //redirect('boffice/index', $data);
        } else {
            //redirect('boffice/password', $data);
        }
    }

    function passwordConfirmation() {
        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('id_emp', $this->id);
        $data['getStatus'] = $this->db->get();

        $this->load->view('boffice/passwordConfirmation', $data);
    }

    function insert() {
        $data['access'] = $this->status;
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['avatar'] = $this->avatar;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['manager'] = $this->manager;
        $data['level'] = $this->level;
        $data['method'] = $this->router->fetch_method();
        if ($data['type'] == 'employees') {

            if ($this->level == 1) {
                $this->db->select('*');
                $this->db->from('ecc_department_dep');
                $this->db->where('id_dep != 1');
                $data['getDepartment'] = $this->db->get();
            } else {
                $this->db->select('*');
                $this->db->from('ecc_department_dep');
                $this->db->where('name_dep', $this->department);
                $data['getDepartment'] = $this->db->get();
            }
        } elseif ($data['type'] == 'companies') {
            //insert draft row
            $draft = array('status_co' => 'draft');
            $this->db->insert('ecc_company_co', $draft);
            $data['draftId'] = $this->db->insert_id();

            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->where('status_co', 'active');
            $data['companyDataList'] = $this->db->get();

            $this->db->SELECT('*');
            $this->db->FROM('ecc_companyCategory_cocat');
            $data['company_type'] = $this->db->get();
        } elseif ($data['type'] == 'departments') {

        } elseif ($data['type'] == 'products') {

        } elseif ($data['type'] == 'accounts') {
            $this->load->helper('date');
            //insert drafted row
            $draft = array('status_acc' => 'draft');
            $this->db->insert('ecc_accounts_acc', $draft);
            $data['draftId'] = $this->db->insert_id();

            $this->db->select('*');
            $this->db->from('ecc_products_prod');
            $data['get_products'] = $this->db->get();

            $department = $this->department;

            if ($this->level == '1') {

                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->join('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
                $this->db->join('ecc_department_dep', 'iddep_memper = id_dep ', 'inner');
                //$this->db->where('name_dep', $department);
                $this->db->where('level_emp != 1');
                $this->db->group_by('id_emp');
                $data['get_employees'] = $this->db->get();
            } elseif ($this->level == '3' && $this->manager == true) {

                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->join('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
                $this->db->join('ecc_department_dep', 'iddep_memper = id_dep ', 'inner');
                $this->db->where('name_dep', $department);
                $this->db->where('level_emp != 1');
                $this->db->group_by('id_emp');
                $data['get_employees'] = $this->db->get();
            }

            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->WHERE('status_co', 'active');
            $data['get_company'] = $this->db->get();
        } elseif ($data['type'] == 'calls') {

            if ($data['level'] == 1 || ($data['level'] == 3 && $data['manager'] == true)) {

                $draft = array('status_call' => 'draft');
                $this->db->insert('ecc_calls_call', $draft);
                $data['draftId'] = $this->db->insert_id();

                $this->db->select('*');
                $this->db->from('ecc_company_co');
                $this->db->where('status_co', 'active');
                $data['get_companies'] = $this->db->get();
            } elseif ($data['level'] == 3 && ($data['manager'] !== true || !isset($data['manager']))) {

                $draft = array('status_call' => 'draft');
                $this->db->insert('ecc_calls_call', $draft);
                $data['draftId'] = $this->db->insert_id();

                $this->db->select('*');
                $this->db->from('ecc_company_co');
                $this->db->join('ecc_accounts_acc', 'id_co = idco_acc', 'inner');
                $this->db->where('idemp_acc', $this->id);
                $this->db->where('status_co', 'active');
                $data['get_companies'] = $this->db->get();
            }
        } elseif ($data['type'] == 'departments') {
            //insert draft row
            $draft = array('status_dep' => 'draft');
            $this->db->insert('ecc_department_dep', $draft);
            $data['draftId'] = $this->db->insert_id();
        } elseif ($data['type'] == 'managers') {
            $this->db->select('*');
            $this->db->from('ecc_department_dep');
            $data['getDepartment'] = $this->db->get();
        } elseif ($data['type'] == 'notifications') {
            $this->db->select('*');
            $this->db->from('ecc_notificationType_ntype');
            $data['getType'] = $this->db->get();
        }

        $this->load->view('boffice/insert', $data);
    }

    //register new call
    function companyContact() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $this->load->view('boffice/companyContactForm', $data);
    }

    function addCompanyContactFromCall() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $this->load->view('boffice/addCompanyContactFromCall', $data);
    }

    function getCallContact() {
        //when adding new contact to call we redirect to this page to show how many of employees in the contact list
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $this->db->select('*');
        $this->db->from('ecc_call_cmeta');
        $this->db->JOIN('ecc_companyContact_cocontact', 'metaValue_cmeta = id_cocontact', 'inner');
        $this->db->where('idcall_cmeta', $data['draftId']);
        $this->db->where('metaKey_cmeta', 'contact');
        $data['getContact'] = $this->db->get();

        $this->load->view('boffice/getCallContact', $data);
    }

    function GetAccountOwner() {
        //when choosing company we select the employee related to this company regarding the selected drafted ip
        $data['manager'] = $this->manager;
        $data['level'] = $this->level;
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['loggedIn'] = $this->id;
        $data['loggedInName'] = $this->name;
        if ($data['level'] == 1) {

            $this->db->select('*');
            $this->db->from('ecc_employees_emp');
            $this->db->join('ecc_accounts_acc', 'id_emp = idemp_acc', 'inner');
            $this->db->where('idco_acc', $data['companyId']);
            $data['GetEmployee'] = $this->db->get();
        } elseif ($data['level'] == 3 && $data['manager'] == true) {

            $this->db->select('*');
            $this->db->from('ecc_employees_emp');
            $this->db->join('ecc_accounts_acc', 'id_emp = idemp_acc', 'inner');
            $this->db->join('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
            $this->db->join('ecc_department_dep', 'iddep_memper = id_dep', 'inner');
            $this->db->where('idco_acc', $data['companyId']);
            $this->db->where('name_dep', $this->department);
            $data['GetEmployee'] = $this->db->get();
        } elseif ($data['level'] == 3) {

            $this->db->select('*');
            $this->db->from('ecc_employees_emp');
            $this->db->join('ecc_accounts_acc', 'id_emp = idemp_acc', 'inner');
            $this->db->where('idco_acc', $data['companyId']);
            $this->db->where('id_emp', $this->id);
            $data['GetEmployee'] = $this->db->get();
        }

        $this->load->view('boffice/GetEmployee', $data);
    }

    function GetContactList() {
        //when choose call type: call, we direct to get contact related to choosed company
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $this->db->select('*');
        $this->db->from('ecc_companyContact_cocontact');
        $this->db->where('idco_cocontact', $data['companyId']);
        $data['GetContact'] = $this->db->get();

        $this->load->view('boffice/GetContact', $data);
    }

    function callFiles() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['employeeId'] = $this->uri->segment(5);

        $this->db->select('*');
        $this->db->from('ecc_files_file');
        $this->db->join('ecc_fileCallsRelations_fcr', 'id_file = idfile_fcr', 'left');
        $where = "grade_file = 'a' || (idemp_fcr = '$data[employeeId]' && idco_fcr = '$data[companyId]')";
        $this->db->where($where);
        $this->db->group_by('id_file');
        $data['getFiles'] = $this->db->get();


        $this->load->view('boffice/callFiles', $data);
    }

    function uploadFiles() {

        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['employeeId'] = $this->uri->segment(5);

        $this->load->view('boffice/uploadFiles', $data);
    }

    /*     * **end register call*** */

    function addCompanyContact() {
        $data['draftId'] = $this->uri->segment(3);
        $this->load->view('boffice/addCompanyContact', $data);
    }

    function addCompanyBranch() {
        $data['draftId'] = $this->uri->segment(3);
        $this->load->view('boffice/addCompanyBranch', $data);
    }

    function GetCompanyContact() {
        $data['draftId'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_companyContact_cocontact');
        $this->db->where('idco_cocontact', $data['draftId']);
        $data['getContact'] = $this->db->get();
        $this->load->view('boffice/GetCompanyContact', $data);
    }

    function GetCompanyBranch() {
        $data['draftId'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_companyBranch_cobr');
        $this->db->where('idco_cobr', $data['draftId']);
        $data['getBranch'] = $this->db->get();
        $this->load->view('boffice/GetCompanyBranch', $data);
    }

    //this was canceled

    function insertCallContact() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $contact = $this->input->post('employee');

        $insert = array(
            'idcall_cmeta' => $data['draftId'],
            'metaKey_cmeta' => 'contact',
            'metaValue_cmeta' => $contact
        );

        $this->db->insert('ecc_call_cmeta', $insert);
    }

    function insertCallFile() {
        $id = $this->uri->segment(3);
        $this->load->model('upload_model');
        $file = $this->upload_model->do_upload_call_attachment();
        $attached = $file['file_name'];
        $fileType = $file['file_type'];
        $fileSize = $file['file_size'];

        $insert = array(
            'idcall_cmeta' => $id,
            'metaKey_cmeta' => 'file',
            'metaValue_cmeta' => $attached,
            'mimeType_cmeta' => $fileType,
            'fileSize_cmeta' => $fileSize
        );

        $this->db->insert('ecc_call_cmeta', $insert);
    }

    function getCallFile() {
        $id = $this->uri->segment(3);

        $this->db->select('*');
        $this->db->from('ecc_call_cmeta');
        $this->db->where('idcall_cmeta', $id);
        $this->db->where('metaKey_cmeta', 'file');
        $data['getFiles'] = $this->db->get();

        $this->load->view('boffice/getFiles.php', $data);
    }

    function insertCall() {
        $this->load->library('Sanitizer');
        $id = $this->uri->segment(4);
        $company = $this->uri->segment(3);
        $employee = $this->input->post('employee');
        $PreReason = str_replace('<br />', '\n', $this->input->post('reason'));
        $type = $this->input->post('type');
        $status = $this->input->post('status');

        $SanReason = new Sanitizer($string);
        $reason = $SanReason->sanitize($PreReason);

        //date
        $format = 'DATE_W3C';
        $time = time();
        //$date = standard_date($format, $time);
        $date = date('Y-m-d H:i:s', now());
        //set inDate
        if ($this->input->post('in') == true) {
            $inDate = date('Y-m-d', strtotime(str_replace('-', '/', $this->input->post('in'))));
        } else {
            $inDate = '';
        };

        //set DueDate
        if ($this->input->post('dueDate') == true) {
            $dueDate = date('Y-m-d', strtotime($this->input->post('dueDate')));
            $inDate = $dueDate;
        } else {
            $dueDate = date('Y-m-d H:i:s', now());
        };

        //set Feedback
        if ($this->input->post('feedback') == 'Available only if the call were closed') {
            $feedback = '';
        } else {
            $PreFeedback = $this->input->post('feedback');

            $SanFeed = new Sanitizer($string);
            $feedback = $SanFeed->sanitize($PreFeedback);

            $insertFeedback = array(
                'idcall_feed' => $id,
                'idemp_feed' => $employee,
                'idco_feed' => $company,
                'feedback_feed' => $feedback,
                'date_feed' => $date
            );

            $this->db->insert('ecc_feedback_feed', $insertFeedback);
        };


        $insert = array(
            'id_call' => $id,
            'idemp_call' => $employee,
            'idco_call' => $company,
            'callStatus_call' => $status,
            'callType_call' => $type,
            'accept_call' => '0',
            'inDate_call' => $inDate,
            'dueDate_call' => $dueDate,
            'status_call' => 'active',
            'date_call' => $date
        );

        $insertMeta = array(
            array(
                'idcall_cmeta' => $id,
                'metaKey_cmeta' => 'inDate',
                'metaValue_cmeta' => $inDate
            ), array(
                'idcall_cmeta' => $id,
                'metaKey_cmeta' => 'reason',
                'metaValue_cmeta' => $reason
            )
        );

        $this->db->delete('ecc_calls_call', array('status_call' => 'draft'));

        $this->db->insert('ecc_calls_call', $insert);
        $this->db->insert_batch('ecc_call_cmeta', $insertMeta);

        //update employee profile
        $this->db->where('id_emp', $employee);
        $this->db->set('callsCount_emp', 'callsCount_emp+1', false);
        $this->db->update('ecc_employees_emp');



        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('id_emp', $employee);
        $getEmp = $this->db->get();

        foreach ($getEmp->result() as $empRow) {

            //get employee
            $this->db->SELECT('*');
            $this->db->FROM('ecc_employee_empmeta');
            $this->db->WHERE('idemp_empmeta', $empRow->id_emp);
            $meta = $this->db->GET();

            $thisMeta = array();
            foreach ($meta->result() as $row2) {
                $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
            }//meta

            $MetaEmp = array(
                'first_name' => $thisMeta['first_name'],
                'last_name' => $thisMeta['last_name'],
                'position' => $thisMeta['position'],
                'avatar' => $thisMeta['avatar'],
                'mobile' => $thisMeta['mobile']
            );
            $EmpName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->where('id_co', $company);
            $getCo = $this->db->get();

            foreach ($getCo->result() as $coRow) {
                $coName = $coRow->name_co;
            }

            $push = array(
                'objectid_push' => $id,
                'affectedEmp_push' => $empRow->id_emp,
                'creatorEmp_push' => $this->id,
                'affectedNameSpace_push' => $EmpName,
                'creatorNameSpace_push' => $this->name,
                'type_push' => 'calls',
                'name_push' => 'You have to make a call to <b>' . $coName . '</b>',
                'date_push' => date('Y-m-d H:i:s', now()),
                'status_push' => '1'
            );
            $this->db->insert('ecc_pusher_push', $push);

            //push notifications
            $this->db->select('*');
            $this->db->from('ecc_pusher_push');
            $this->db->where('status_push', '1');
            $this->db->where('affectedEmp_push', $empRow->id_emp);
            $getCount = $this->db->get();

            $PushNotification = array(
                'count' => $getCount->num_rows(),
                'name' => 'You have to make a call to ' . $coName
            );
            $this->load->library('pusher');
            $this->pusher->trigger('notifications-' . $empRow->id_emp, 'notifications', $PushNotification);
        };

        $this->db->_error_message();
    }

    function callFiles2nd() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['employeeId'] = $this->uri->segment(5);

        $this->db->select('*');
        $this->db->from('ecc_files_file');
        $this->db->join('ecc_fileCallsRelations_fcr', 'id_file = idfile_fcr', 'inner');
        $where = "grade_file = 'a' || (idemp_fcr = '$data[employeeId]' && idco_fcr = '$data[companyId]')";
        $this->db->where($where);
        $this->db->group_by('id_file');
        $data['getFiles'] = $this->db->get();


        $this->load->view('boffice/callFiles', $data);
    }

    function callFilesAfterSelected() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['employeeId'] = $this->uri->segment(5);

        $this->db->select('*');
        $this->db->from('ecc_files_file');
        $this->db->join('ecc_fileCallsRelations_fcr', 'id_file = idfile_fcr', 'left');
        $where = "(idemp_fcr = '$data[employeeId]' && idco_fcr = '$data[companyId]') || grade_file = 'a'";
        $this->db->where($where);
        $this->db->group_by('id_file');
        $data['getFiles'] = $this->db->get();

        $this->load->view('boffice/callFilesAfterSelected', $data);
    }

    function insertAttachedFile() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['employeeId'] = $this->uri->segment(5);


        foreach ($this->input->post('file') as $checked) {
            $files[$checked] = $checked;
            $insert = array();
            foreach ($files as $file) {
                $insert[$file] = $file;
                $insert = array(
                    'idfile_fcr' => $insert[$file],
                    'idemp_fcr' => $data['employeeId'],
                    'idco_fcr' => $data['companyId'],
                    'idcall_fcr' => $data['draftId']
                );
            };

            $this->db->insert('ecc_fileCallsRelations_fcr', $insert);
        };
    }

    function insertFileCall() {
        $data['companyId'] = $this->uri->segment(3);
        $data['draftId'] = $this->uri->segment(4);
        $data['employeeId'] = $this->uri->segment(5);

        $this->load->model('upload_model');
        $name = $this->input->post('name');
        $userfile = $this->upload_model->upload_file($name);
        $file = $userfile['file_name'];
        $type = $userfile['file_type'];
        $size = $userfile['file_size'];
        $grade = 'b';
        //date
        $format = 'DATE_W3C';
        $time = time();
        $date = date('Y-m-d H:i:s', now());

        $insert = array('name_file' => $name, 'file_file' => $file, 'date_file' => $date, 'mimeType_file' => $type, 'fileSize_file' => $size, 'grade_file' => $grade);
        $this->db->insert('ecc_files_file', $insert);
        $fileId = $this->db->insert_id();

        $insertRelation = array('idcall_fcr' => $data['draftId'], 'idfile_fcr' => $fileId, 'idemp_fcr' => $data['employeeId'], 'idco_fcr' => $data['companyId']);
        $this->db->insert('ecc_fileCallsRelations_fcr', $insertRelation);
    }

    function attachedFiles() {
        $data['draftId'] = $this->uri->segment(3);

        $this->db->select('*');
        $this->db->from('ecc_fileCallsRelations_fcr');
        $this->db->join('ecc_files_file', 'idfile_fcr = id_file', 'inner');
        $this->db->where('idcall_fcr', $data['draftId']);
        $data['getFiles'] = $this->db->get();

        $this->load->view('boffice/attachedFiles', $data);
    }

    function editCall() {
        $data['call'] = $this->uri->segment(3);
        $this->load->view('boffice/editCall', $data);
    }

    function CloseCall() {
        $call = $this->uri->segment(3);
        $feedback = $this->input->post('feedback');

        $update = array('metaValue_cmeta' => $feedback);
        $this->db->where('idcall_cmeta', $call);
        $this->db->where('metaKey_cmeta', 'feedback');
        $this->db->update('ecc_call_cmeta', $update);

        $close = array('callStatus_call' => 'done');
        $this->db->where('id_call', $call);
        $this->db->update('ecc_calls_call', $close);
    }

    //end register call
    //insert file
    function insertfile() {
        $this->load->library('Sanitizer');
        $this->load->model('upload_model');
        $PrName = $this->input->post('name');
        $userfile = $this->upload_model->upload_file($name);
        $file = $userfile['file_name'];
        $type = $userfile['file_type'];
        $size = $userfile['file_size'];
        $grade = 'a';

        $SanName = new Sanitizer($string);
        $name = $SanName->sanitize($PrName);


        //date
        $format = 'DATE_W3C';
        $time = time();
        $date = standard_date($format, $time);
        $date = date('Y-m-d', now());
        $insert = array('name_file' => $name, 'file_file' => $file, 'date_file' => $date, 'mimeType_file' => $type, 'fileSize_file' => $size, 'grade_file' => $grade);
        $this->db->insert('ecc_files_file', $insert);

        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('level_emp != 1');
        $getEmp = $this->db->get();

        foreach ($getEmp->result() as $empRow) {

            //get employee
            $this->db->SELECT('*');
            $this->db->FROM('ecc_employee_empmeta');
            $this->db->WHERE('idemp_empmeta', $empRow->id_emp);
            $meta = $this->db->GET();

            $thisMeta = array();
            foreach ($meta->result() as $row2) {
                $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
            }//meta

            $MetaEmp = array(
                'first_name' => $thisMeta['first_name'],
                'last_name' => $thisMeta['last_name'],
                'position' => $thisMeta['position'],
                'avatar' => $thisMeta['avatar'],
                'mobile' => $thisMeta['mobile']
            );
            $EmpName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

            $push = array(
                'objectid_push' => $id,
                'affectedEmp_push' => $empRow->id_emp,
                'creatorEmp_push' => $this->id,
                'affectedNameSpace_push' => $EmpName,
                'creatorNameSpace_push' => $this->name,
                'type_push' => 'products',
                'name_push' => $name . ' just added to ECC Business documents',
                'date_push' => date('Y-m-d H:i:s', now()),
                'status_push' => '1'
            );
            $this->db->insert('ecc_pusher_push', $push);
        };
    }

    function insertNtofication() {
        $this->load->library('Sanitizer');
        $PrSubject = $this->input->post('subject');
        $PrMessage = $this->input->post('message');
        $type = $this->input->post('type');
        $dueDate = date('Y-m-d', strtotime(str_replace('-', '/', $this->input->post('dueDate'))));
        $department = $this->department;


        $SanSubject = new Sanitizer($string);
        $SanMessage = new Sanitizer($string);

        $subject = $SanSubject->sanitize($PrSubject);
        $message = $SanMessage->sanitize($PrMessage);

        $insert = array('subject_notif' => $subject, 'department_notif' => $department, 'message_notif' => $message, 'active_notif' => '1', 'dueDate_notif' => $dueDate, 'inDate_notif' => date('Y-m-d H:i:s', now()), 'public_notif' => '1', 'idntype_notif' => $type);

        $this->db->insert('ecc_notifications_notif', $insert);
    }

    function insertcompany() {
        $this->load->library('Sanitizer');
        $this->load->model('upload_model');
        //form validation
        $this->load->library('form_validation'); //load the validation library
        $this->form_validation->set_error_delimiters('<div class="clearfix"><span class="notification undone">', '</span></div>');
        $this->form_validation->set_rules('name', 'name', 'required');

        if ($this->form_validation->run() == FALSE) {
            echo "at least the name of the company must be provided";
        } else {

            $type = $this->input->post('type');
            $PrName = $this->input->post('name');
            $PrArName = $this->input->post('arName');
            $PreAddress = $this->input->post('address');

            $SanName = new Sanitizer($string);
            $SanAddress = new Sanitizer($string);
            $SanName = new Sanitizer($string);

            $address = $SanAddress->sanitize($PreAddress);
            $arName = $SanName->sanitize($PrArName);
            $name = $SanName->sanitize($PrName);

            //$data['uri_encoded'] = new Sanitizer($title);

            /*
              $address = str_replace('"',"'", $address);
              $address = str_replace('/',"-", $address);
              $address = str_replace('\n',"-", $address);
              $address = trim($address);

              $address = str_replace(array("\r\n", "\r"), "\n", $address);
              $address = explode("\n", $address);
              $new_lines = array();

              foreach ($address as $i => $address) {
              if(!empty($address))
              $new_lines[] = trim($address);
              }
              $address = implode($new_lines);


             */

            $website = $this->input->post('website');
            $id = $this->uri->segment(3);
            $userfile = $this->upload_model->do_upload_company_logo($name);

            if ($this->upload->display_errors() == true) {
                $logo = '';
            } else {
                $logo = $userfile['file_name'];
            }


            $insertCompany = array(
                'id_co' => $id,
                'name_co' => $name,
                'arName_co' => $arName,
                'idcocat_co' => $type,
                'status_co' => 'active'
            );
            //delete drafting data
            $this->db->DELETE('ecc_company_co', array('status_co' => 'draft'));
            //insert company basic data
            $this->db->INSERT('ecc_company_co', $insertCompany);
            $insertCompanyMeta = array(
                array(
                    'idco_cometa' => $id,
                    'metaKey_cometa' => 'address',
                    'metaValue_cometa' => $address
                ), array(
                    'idco_cometa' => $id,
                    'metaKey_cometa' => 'website',
                    'metaValue_cometa' => $website
                ), array(
                    'idco_cometa' => $id,
                    'metaKey_cometa' => 'logo',
                    'metaValue_cometa' => $logo
                ), array(
                    'idco_cometa' => $id,
                    'metaKey_cometa' => 'registeredDate',
                    'metaValue_cometa' => date('Y-m-d H:i:s', now())
                ), array(
                    'idco_cometa' => $id,
                    'metaKey_cometa' => 'creatorId',
                    'metaValue_cometa' => $this->id
                ), array(
                    'idco_cometa' => $id,
                    'metaKey_cometa' => 'creatorNameSpace',
                    'metaValue_cometa' => $this->name
                )
            );
            $this->db->INSERT_BATCH('ecc_companyMeta_cometa', $insertCompanyMeta);

            $this->db->where('id_cocat', $type);
            $this->db->set('count_cocat', 'count_cocat+1', FALSE);
            $this->db->update('ecc_companyCategory_cocat');

            //assign account if regular user who is the creator
            if ($this->level == '3' && $this->manager != true) {

                $assign = array(
                    'idemp_acc' => $this->id,
                    'idco_acc' => $id,
                    'registeredDate_acc' => date('Y-m-d H:i:s', now()),
                    'status_acc' => '1'
                );
                $this->db->insert('ecc_accounts_acc', $assign);
                $AccId = $this->db->insert_id();

                $this->db->where('id_co', $id);
                $this->db->set('accountCount_co', 'accountCount_co+1', false);
                $this->db->update('ecc_company_co');


                //push notification
                $push = array(
                    'objectid_push' => $AccId,
                    'affectedEmp_push' => $this->id,
                    'creatorEmp_push' => $this->id,
                    'affectedNameSpace_push' => $this->name,
                    'creatorNameSpace_push' => $this->name,
                    'type_push' => 'accounts',
                    'name_push' => $name . ' just assigned for you as an account manager, good luck for you! we are waiting to sign the contract with them :)',
                    'date_push' => date('Y-m-d H:i:s', now()),
                    'status_push' => '1'
                );
                $this->db->insert('ecc_pusher_push', $push);
            }

            //push notification to all managers
            $this->db->select('*');
            $this->db->from('ecc_managers_man');
            $this->db->join('ecc_employees_emp', 'idemp_man = id_emp', 'inner');
            $AllManagers = $this->db->get();

            if ($AllManagers->num_rows() >= '1') {

                foreach ($AllManagers->result() as $pushManager) {

                    //get employee
                    $this->db->SELECT('*');
                    $this->db->FROM('ecc_employee_empmeta');
                    $this->db->WHERE('idemp_empmeta', $pushManager->id_emp);
                    $meta = $this->db->GET();

                    $thisMeta = array();
                    foreach ($meta->result() as $row2) {
                        $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
                    }//meta

                    $MetaEmp = array(
                        'first_name' => $thisMeta['first_name'],
                        'last_name' => $thisMeta['last_name'],
                        'position' => $thisMeta['position'],
                        'avatar' => $thisMeta['avatar'],
                        'mobile' => $thisMeta['mobile']
                    );
                    $ManagerName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

                    if ($this->manager == true || $this->level == '1') {
                        $PushName = $this->name . ' just added <b>' . $name . '</b> as a new company';
                    } else {
                        $PushName = $this->name . ' just added <b>' . $name . '</b> as a new company, He is responsible for it as an account manager';
                    }
                    $push = array(
                        'objectid_push' => $id,
                        'affectedEmp_push' => $pushManager->idemp_man,
                        'creatorEmp_push' => $this->id,
                        'affectedNameSpace_push' => $ManagerName,
                        'creatorNameSpace_push' => $this->name,
                        'type_push' => 'companies',
                        'name_push' => $PushName,
                        'date_push' => date('Y-m-d H:i:s', now()),
                        'status_push' => '1'
                    );
                    $this->db->insert('ecc_pusher_push', $push);

                    //push notifications
                    $this->db->select('*');
                    $this->db->from('ecc_pusher_push');
                    $this->db->where('status_push', '1');
                    $this->db->where('affectedEmp_push', $pushManager->idemp_man);
                    $getCount = $this->db->get();

                    $PushNotification = array(
                        'count' => $getCount->num_rows(),
                        'name' => $PushName
                    );
                    $this->load->library('pusher');
                    $this->pusher->trigger('notifications-' . $pushManager->idemp_man, 'notifications', $PushNotification);
                }
            }
        }
    }

    function insertAccount() {
        $employee = $this->input->post('employee');
        $company = $this->input->post('company');
        $id = $this->uri->segment(3);
        //date
        $format = 'DATE_W3C';
        $time = time();
        //$date = standard_date($format, $time);
        $date = date('Y-m-d', now());
        $insert = array(
            'id_acc' => $id,
            'idemp_acc' => $employee,
            'idco_acc' => $company,
            'registeredDate_acc' => $date,
            'status_acc' => 'active'
        );

        //delete drafting data
        $this->db->DELETE('ecc_accounts_acc', array('status_acc' => 'draft'));
        $this->db->INSERT('ecc_accounts_acc', $insert);

        //account manager
        $this->db->where('id_emp', $employee);
        $this->db->set('companyCount_emp', 'companyCount_emp+1', FALSE);
        $this->db->update('ecc_employees_emp');

        //company Accounts
        $this->db->where('id_co', $company);
        $this->db->set('accountCount_co', 'accountCount_co+1', FALSE);
        $this->db->update('ecc_company_co');

        //get employee
        $this->db->SELECT('*');
        $this->db->FROM('ecc_employee_empmeta');
        $this->db->WHERE('idemp_empmeta', $employee);
        $meta = $this->db->GET();

        $thisMeta = array();
        foreach ($meta->result() as $row2) {
            $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
        }//meta

        $MetaEmp = array(
            'first_name' => $thisMeta['first_name'],
            'last_name' => $thisMeta['last_name'],
            'position' => $thisMeta['position'],
            'avatar' => $thisMeta['avatar'],
            'mobile' => $thisMeta['mobile']
        );
        $accOwnerName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

        //get employee
        $this->db->SELECT('*');
        $this->db->FROM('ecc_company_co');
        $this->db->WHERE('id_co', $company);
        $CoName = $this->db->GET();

        foreach ($CoName->result() as $coRow) {
            $coName = $coRow->name_co;
        };

        $push = array(
            'objectid_push' => $id,
            'affectedEmp_push' => $employee,
            'creatorEmp_push' => $this->id,
            'affectedNameSpace_push' => $accOwnerName,
            'creatorNameSpace_push' => $this->name,
            'type_push' => 'accounts',
            'name_push' => '<b>' . $coName . '</b> just assigned for you as an account manager, good luck for you! we are waiting to sign the contract with them :)',
            'date_push' => date('Y-m-d H:i:s', now()),
            'status_push' => '1'
        );
        $this->db->insert('ecc_pusher_push', $push);
        //push notifications
        $this->db->select('*');
        $this->db->from('ecc_pusher_push');
        $this->db->where('status_push', '1');
        $this->db->where('affectedEmp_push', $employee);
        $getCount = $this->db->get();

        $PushNotification = array(
            'count' => $getCount->num_rows(),
            'name' => '<b>' . $coName . '</b> just assigned for you as an account manager, good luck for you! we are waiting to sign the contract with them :)'
        );
        $this->load->library('pusher');
        $this->pusher->trigger('notifications-' . $employee, 'notifications', $PushNotification);
    }

    function insertEmployee() {
        $this->load->library('Sanitizer');
        //catch the form variables
        $this->load->model('upload_model');
        //form validation
        $this->load->library('form_validation'); //load the validation library
        $this->form_validation->set_error_delimiters('<div class="clearfix"><span class="notification undone">', '</span></div>');
        $this->form_validation->set_rules('first_name', 'first_name', 'required');

        if ($this->form_validation->run() == FALSE) {
            echo 'error';
        } else {

            $email = $this->input->post('email');
            $first_name = $this->input->post('first_name');
            $last_name = $this->input->post('last_name');
            $name = $first_name . " " . $last_name;
            $mobile = $this->input->post('mobile');
            $mobile2 = $this->input->post('mobile2');
            $home_phone = $this->input->post('home_phone');
            $PrAddress = $this->input->post('address');
            $position = $this->input->post('position');
            $department = $this->input->post('department');
            $userfile = $this->upload_model->do_upload_employee_photo($name);
            $avatar = $userfile['file_name'];
            $password = md5('@ecc_123');
            //date
            $format = 'RFC3339';
            $time = time();
            $date = standard_date($format, $time);




            //description
            $SanAddress = new Sanitizer($string);
            $address = $SanAddress->sanitize($PrAddress);


            $insertEmployee = array(
                'email_emp' => $email,
                'password_emp' => $password,
                'status_emp' => '0',
                'regsiteredDate_emp' => $date,
                'level_emp' => '3',
                'force_password_emp' => '1'
            );

            $this->db->insert('ecc_employees_emp', $insertEmployee);
            $id = $this->db->insert_id();
            $insertEmployeeMeta = array(
                array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'first_name',
                    'metaValue_empmeta' => $first_name
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'last_name',
                    'metaValue_empmeta' => $last_name
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'avatar',
                    'metaValue_empmeta' => $avatar
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'position',
                    'metaValue_empmeta' => $position
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'mobile',
                    'metaValue_empmeta' => $mobile
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'mobile2',
                    'metaValue_empmeta' => $mobile2
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'home_phone',
                    'metaValue_empmeta' => $home_phone
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'address',
                    'metaValue_empmeta' => $address
                )
            );
            $this->db->insert_batch('ecc_employee_empmeta', $insertEmployeeMeta);

            $insertDepartment = array('iddep_memper' => $department, 'idemp_memper' => $id);
            $this->db->insert('ecc_mempership_memper', $insertDepartment);

            $this->db->where('id_dep', $department);
            $this->db->set('count_dep', 'count_dep+1', false);
            $this->db->update('ecc_department_dep');
            $errorMessage = $this->upload->display_errors();

            $dateTime = new DateTime();

            //push notification
            $push = array(
                'objectid_push' => $id,
                'affectedEmp_push' => $id,
                'creatorEmp_push' => $this->id,
                'affectedNameSpace_push' => $name,
                'creatorNameSpace_push' => $this->name,
                'type_push' => 'employees',
                'name_push' => 'Hi <b>' . $name . '</b>, Welcome to S.A.M Sys.! you are now able to create a highly job experience ',
                'date_push' => date('Y-m-d H:i:s', now()),
                'status_push' => '1'
            );
            $this->db->insert('ecc_pusher_push', $push);

            //push notification to department Manager
            $this->db->select('*');
            $this->db->from('ecc_managers_man');
            $this->db->join('ecc_employees_emp', 'idemp_man = id_emp', 'inner');
            $this->db->where('iddep_man', $department);
            $DepManager = $this->db->get();

            if ($DepManager->num_rows() == '1') {

                foreach ($DepManager->result() as $depMan) {

                    $theMan = $depMan->id_emp;
                    //get employee
                    $this->db->SELECT('*');
                    $this->db->FROM('ecc_employee_empmeta');
                    $this->db->WHERE('idemp_empmeta', $theMan);
                    $meta = $this->db->GET();

                    $thisMeta = array();
                    foreach ($meta->result() as $row2) {
                        $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
                    }//meta

                    $MetaEmp = array(
                        'first_name' => $thisMeta['first_name'],
                        'last_name' => $thisMeta['last_name'],
                        'position' => $thisMeta['position'],
                        'avatar' => $thisMeta['avatar'],
                        'mobile' => $thisMeta['mobile']
                    );
                    $DepManagerName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];
                };

                //push notification
                $push = array(
                    'objectid_push' => $id,
                    'affectedEmp_push' => $theMan,
                    'creatorEmp_push' => $this->id,
                    'affectedNameSpace_push' => $DepManagerName,
                    'creatorNameSpace_push' => $this->name,
                    'type_push' => 'employees',
                    'name_push' => '<b>' . $name . '</b> a <b>' . $position . '</b> just added to your department, you are now able to assign calls for him',
                    'date_push' => date('Y-m-d H:i:s', now()),
                    'status_push' => '1'
                );
                $this->db->insert('ecc_pusher_push', $push);
            }
        }
    }

    function insertDepartment() {
        $name = $this->input->post('name');
        $insertDep = array('name_dep' => $name, 'status_dep' => 1);
        $this->db->INSERT('ecc_department_dep', $insertDep);
    }

    function insertProducts() {
        $this->load->library('Sanitizer');

        $PrName = $this->input->post('name');
        //description
        $PrDescription = $this->input->post('description');


        $SanName = new Sanitizer($string);
        $name = $SanName->sanitize($PrName);

        $SanDesc = new Sanitizer($string);
        $description = $SanDesc->sanitize($PrDescription);


        $insertProd = array('name_prod' => $name);
        $this->db->INSERT('ecc_products_prod', $insertProd);
        $id = $this->db->insert_id();

        $insertMeta = array('idprod_prodmeta' => $id, 'metaKey_prodmeta' => 'description', 'metaValue_prodmeta' => $description);
        $this->db->INSERT('ecc_product_prodmeta', $insertMeta);

        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('level_emp != 1');
        $getEmp = $this->db->get();

        foreach ($getEmp->result() as $empRow) {

            //get employee
            $this->db->SELECT('*');
            $this->db->FROM('ecc_employee_empmeta');
            $this->db->WHERE('idemp_empmeta', $empRow->id_emp);
            $meta = $this->db->GET();

            $thisMeta = array();
            foreach ($meta->result() as $row2) {
                $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
            }//meta

            $MetaEmp = array(
                'first_name' => $thisMeta['first_name'],
                'last_name' => $thisMeta['last_name'],
                'position' => $thisMeta['position'],
                'avatar' => $thisMeta['avatar'],
                'mobile' => $thisMeta['mobile']
            );
            $EmpName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

            $push = array(
                'objectid_push' => $id,
                'affectedEmp_push' => $empRow->id_emp,
                'creatorEmp_push' => $this->id,
                'affectedNameSpace_push' => $EmpName,
                'creatorNameSpace_push' => $this->name,
                'type_push' => 'products',
                'name_push' => $name . ' just added to ECC products, Enjoy buying it :)',
                'date_push' => date('Y-m-d H:i:s', now()),
                'status_push' => '1'
            );
            $this->db->insert('ecc_pusher_push', $push);

            //push notifications
            $this->db->select('*');
            $this->db->from('ecc_pusher_push');
            $this->db->where('status_push', '1');
            $this->db->where('affectedEmp_push', $empRow->id_emp);
            $getCount = $this->db->get();

            $PushNotification = array(
                'count' => $getCount->num_rows(),
                'name' => $name . ' just added to ECC products, Enjoy buying it :)',
            );
            $this->load->library('pusher');
            $this->pusher->trigger('notifications-' . $empRow->id_emp, 'notifications', $PushNotification);
        };
    }

    function insertAccountProduct() {
        $productId = $this->input->post('product');
        $draftId = $this->uri->segment(3);

        $insert = array('idacc_accprod' => $draftId, 'idprod_accprod' => $productId);
        $this->db->insert('ecc_accounts_products_accprod', $insert);
    }

    function AccountProducts() {
        $draftId = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_accounts_products_accprod');
        $this->db->join('ecc_products_prod', 'idprod_accprod = id_prod', 'INNER');
        $this->db->where('idacc_accprod', $draftId);
        $data['get_products'] = $this->db->get();

        $this->load->view('boffice/account_products', $data);
    }

    function test() {
        //echo show_error('message' [, int $status_code= 500 ] );
        $this->load->library('Sanitizer');
        $GetEmployeeMeta = new Sanitizer($EmpId);
        $EmployeeMeta = $GetEmployeeMeta->EmployeeMeta(4);
        print_r($EmployeeMeta);
    }

    function DataCall() {
        $this->load->library('Sanitizer');
        $this->load->model('common');
        $this->load->helper('text');
        $data['access'] = $this->status; //true or false
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['avatar'] = $this->avatar;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['id'] = $this->uri->segment(4);
        $data['method'] = $this->router->fetch_method();
        $data['level'] = $this->level;
        //$data['notification'] =$this->getCallNotification;
        $data['manager'] = $this->manager;
        $data['department'] = $this->department;
        $data['loggedInId'] = $this->id;
        $data['substr'] = new Sanitizer();
        $data['EmployeeMeta'] = new Sanitizer();

        if ($data['manager'] == true && $data['level'] == '3') {
            $data['getCallStatus'] = $this->common->dataCallsManager($data['department']);
        } elseif ($data['level'] == '1') {
            $data['getCallStatus'] = $this->common->dataCallsAdmin();
        } elseif ($data['level'] == '3') {
            $data['getCallStatus'] = $this->common->dataCallsReguler($this->id);
        }
        $this->load->view('boffice/dataTable/calls', $data);
    }

    function dataTable() {
        $this->load->library('Sanitizer');
        $this->load->model('common');
        $this->load->helper('text');
        $data['access'] = $this->status; //true or false
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['avatar'] = $this->avatar;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['id'] = $this->uri->segment(4);
        $data['method'] = $this->router->fetch_method();
        $data['level'] = $level = $this->level;
        //$data['notification'] =$this->getCallNotification;
        $data['manager'] = $manager = $this->manager;
        $data['department'] = $department = $this->department;
        $data['loggedInId'] = $loggedId = $this->id;
        $data['substr'] = new Sanitizer();
        $data['EmployeeMeta'] = new Sanitizer();
        $arg = $this->uri->segment(4);

        if ($data['type'] == 'employees') {

            if ($data['level'] == 1) {
                $this->db->SELECT('*');
                $this->db->FROM('ecc_employees_emp');
                $this->db->where('level_emp != 1');
                $this->db->GROUP_BY('id_emp');
                $this->db->ORDER_BY('id_emp', DESC);
                $data['get_employees'] = $this->db->GET();
            } elseif ($data['level'] == 3) {
                $this->db->SELECT('*');
                $this->db->FROM('ecc_employees_emp');
                $this->db->JOIN('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
                $this->db->JOIN('ecc_department_dep', 'iddep_memper = id_dep', 'inner');
                $this->db->JOIN('ecc_managers_man', 'iddep_man = id_dep', 'left');
                //$this->db->where('status_emp', '1');
                $this->db->where('name_dep', $this->department);
                $this->db->where('level_emp != 1');
                $this->db->GROUP_BY('id_emp');
                $this->db->ORDER_BY('id_emp', DESC);
                $data['get_employees'] = $this->db->GET();
            }
            $this->load->view('boffice/dataTable/employees', $data);
        } elseif ($data['type'] == 'companies') {

            $data['category'] = $this->common->companyCategories();
            $data['get_companies'] = $this->common->getCompanies($level, $loggedId, $manager, $arg);
            $this->load->view('boffice/dataTable/companies', $data);
        } elseif ($data['type'] == 'products') {

            $this->db->SELECT('*');
            $this->db->FROM('ecc_products_prod');
            $this->db->order_by('name_prod', 'ASC');
            $data['get_products'] = $this->db->GET();
            $this->load->view('boffice/dataTable/products', $data);
        } elseif ($data['type'] == 'accounts') {
            if ($data['level'] == 1 || ($data['level'] == 3 && $data['manager'] == true)) {
                $this->db->SELECT('*');
                $this->db->FROM('ecc_company_co');
                $this->db->JOIN('ecc_companyMeta_cometa', 'idco_cometa = id_co', 'INNER');
                $this->db->WHERE('status_co', 'active');
                $this->db->WHERE('idcocat_co != 4');
                $this->db->GROUP_BY('id_co');
                $data['get_companies'] = $this->db->get();
            } elseif ($data['level'] == 3 && $data['manager'] == false) {
                $this->db->SELECT('*');
                $this->db->FROM('ecc_company_co');
                $this->db->JOIN('ecc_companyMeta_cometa', 'idco_cometa = id_co', 'INNER');
                $this->db->JOIN('ecc_accounts_acc', 'id_co = idco_acc', 'inner');
                $this->db->WHERE('idemp_acc', $this->id);
                $this->db->WHERE('status_co', 'active');
                $this->db->GROUP_BY('id_co');
                $data['get_companies'] = $this->db->get();
            }
            $this->load->view('boffice/dataTable/accounts', $data);
        } elseif ($data['type'] == 'calls') {
            //get the calls
            $data['getCallStatus'] = $this->common->getCurrentCalls($manager, $level, $department, $this->id);
            //$data['getCallStatus'] = $this->common->dataCalls($level, $loggedId, $manager, $department);
            $this->load->view('boffice/dataTable/calls', $data);
        } elseif ($data['type'] == 'departments') {
            if ($data['level'] == 1) {
                $this->db->SELECT('*');
                $this->db->FROM('ecc_department_dep');
                $this->db->join('ecc_managers_man', 'id_dep = iddep_man', 'left');
                $this->db->where('id_dep != 1');
                $this->db->order_by('name_dep', 'ASC');
                $data['get_departments'] = $this->db->get();
            } elseif ($data['level'] == 3) {
                $this->db->SELECT('*');
                $this->db->FROM('ecc_department_dep');
                $this->db->join('ecc_managers_man', 'id_dep = iddep_man', 'inner');
                $this->db->join('ecc_employees_emp', 'idemp_man = id_emp', 'inner');
                $this->db->where('name_dep', $this->department);
                $this->db->order_by('name_dep', 'ASC');
                $data['get_departments'] = $this->db->get();
            }
            $this->load->view('boffice/dataTable/departments', $data);
        } elseif ($data['type'] == 'managers') {

            if ($data['level'] == 1) {
                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->join('ecc_managers_man', 'id_emp = idemp_man', 'inner');
                $this->db->join('ecc_department_dep', 'iddep_man = id_dep');
                $this->db->where('status_dep', '1');
                $data['getManagers'] = $this->db->get();
            } elseif ($data['level'] == 3) {
                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->join('ecc_managers_man', 'id_emp = idemp_man', 'inner');
                $this->db->join('ecc_department_dep', 'iddep_man = id_dep');
                //$this->db->where('name_dep', $this->department);
                $this->db->where('status_dep', '1');
                $data['getManagers'] = $this->db->get();
            }
        } elseif ($data['type'] == 'resources') {

            if ($this->level == '1') {
                $this->db->select('*');
                $this->db->from('ecc_files_file');
                $this->db->order_by('name_file', 'ASC');
                $data['getFiles'] = $this->db->get();
            } else {
                $this->db->select('*');
                $this->db->from('ecc_files_file');
                $this->db->where('grade_file', 'a');
                $this->db->order_by('name_file', 'ASC');
                $data['getFiles'] = $this->db->get();
            }
            $this->load->view('boffice/dataTable/resources', $data);
        } elseif ($data['type'] == 'reports') {

            if ($data['level'] == 1) {

                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->where('status_emp', '1');
                $this->db->where('level_emp != 1');
                $data['getEmployee'] = $this->db->get();
            } elseif ($data['level'] == 3 && $data['manager'] == true) {

                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->join('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
                $this->db->join('ecc_department_dep', 'iddep_memper = id_dep', 'inner');
                $this->db->where('name_dep', $this->department);
                $this->db->where('status_emp', '1');
                $this->db->where('level_emp != 1');
                $data['getEmployee'] = $this->db->get();
            } elseif ($data['level'] == 3) {

                $this->db->select('*');
                $this->db->from('ecc_employees_emp');
                $this->db->where('id_emp', $this->id);
                $this->db->where('status_emp', '1');
                $this->db->where('level_emp != 1');
                $data['getEmployee'] = $this->db->get();
            }
            $this->load->view('boffice/dataTable/reports', $data);
        } elseif ($data['type'] == 'notifications') {

            if ($data['level'] == '1') {
                $this->db->select('*');
                $this->db->from('ecc_notifications_notif');
                $this->db->order_by('subject_notif', 'ASC');
                $data['notifications'] = $this->db->get();
            } else {
                $this->db->select('*');
                $this->db->from('ecc_notifications_notif');
                $this->db->where('department_notif', $data['department']);
                $this->db->order_by('subject_notif', 'ASC');
                $data['notifications'] = $this->db->get();
            }
            $this->load->view('boffice/dataTable/notifications', $data);
        }
        //$this->load->view('boffice/dataTable', $data);
    }

    function insert_agent() {

        $name = $this->input->post('name');
        $email = $this->input->post('email');
        $phone = $this->input->post('phone');
        $mobile = $this->input->post('mobile');
        $fax = $this->input->post('fax');
        $position = $this->input->post('position');
        $behavior = $this->input->post('behavior');
        $companyid = $this->uri->segment(3);

        $insertAgent = array(
            'name_cocontact' => $name,
            'email_cocontact' => $email,
            'phone_cocontact' => $phone,
            'mobile_cocontact' => $mobile,
            'fax_cocontact' => $fax,
            'position_cocontact' => $position,
            'brief_cocontact' => $behavior,
            'idco_cocontact' => $companyid,
        );
        $this->db->insert('ecc_companyContact_cocontact', $insertAgent);
    }

    function insert_branch() {

        $name = $this->input->post('name');
        $address = $this->input->post('address');
        $phone = $this->input->post('phone');
        $fax = $this->input->post('fax');
        $companyid = $this->uri->segment(3);

        $insertBranch = array(
            'name_cobr' => $name,
            'address_cobr' => $address,
            'phone_cobr' => $phone,
            'fax_cobr' => $fax,
            'idco_cobr' => $companyid
        );
        $this->db->insert('ecc_companyBranch_cobr', $insertBranch);
    }

    function preview_contact() {
        $id = $this->uri->segment(3);
        $this->db->SELECT('*');
        $this->db->FROM('ecc_companyContact_cocontact');
        $this->db->WHERE('idco_cocontact', $id);
        $data['companyContact'] = $this->db->get();

        $this->load->view('boffice/preview_contacts', $data);
    }

    function preview_branch() {
        $id = $this->uri->segment(3);
        $this->db->SELECT('*');
        $this->db->FROM('ecc_companyBranch_cobr');
        $this->db->WHERE('idco_cobr', $id);
        $data['companyBranch'] = $this->db->get();
        $this->load->view('boffice/preview_branches', $data);
    }

    function selectManagers() {
        $data['department'] = $this->uri->segment(3);

        $this->db->select('*');
        $this->db->from('ecc_mempership_memper');
        $this->db->join('ecc_employees_emp', 'idemp_memper = id_emp', 'inner');
        $this->db->where('iddep_memper', $data['department']);
        $data['getManagers'] = $this->db->get();

        $this->load->view('boffice/selectManagers', $data);
    }

    function insertManagers() {
        $department = $this->input->post('department');
        $manager = $this->input->post('manager');

        $insert = array('iddep_man' => $department, 'idemp_man' => $manager);
        $this->db->insert('ecc_managers_man', $insert);
    }

    function reports() {
        $data['employee'] = $this->uri->segment(3);

        $this->db->select('*');
        $this->db->from('ecc_calls_call');
        $this->db->join('ecc_employees_emp', 'idemp_call = id_emp', 'inner');
        $this->db->where('status_call', 'active');
        $this->db->where('id_emp', $data['employee']);
        $data['checkCalls'] = $this->db->get();
        $this->load->view('boffice/reports', $data);
    }

    function report() {
        //load our new PHPExcel library
        $this->load->library('excel');
        $this->load->library('Sanitizer');

        $EmployeeMeta = new Sanitizer($EmpId);
        $employee = $this->uri->segment(3);
        $from = date('Y-m-d', strtotime($this->input->post('FromDate')));
        $to = date('Y-m-d', strtotime($this->input->post('endDate')));

        $this->db->select('*');
        $this->db->from('ecc_calls_call');
        //$this->db->join('ecc_employees_emp', 'idemp_call = id_emp', 'inner');
        $this->db->join('ecc_company_co', 'idco_call = id_co', 'inner');
        $this->db->where('status_call', 'active');
        $this->db->where('callStatus_call', 'done');
        $this->db->where('idemp_call', $employee);
        $this->db->where('inDate_call >=', $from);
        $this->db->where('inDate_call <=', $to);
        $getCalls = $this->db->get();
        $cell = 9;
        $meta = $EmployeeMeta->EmployeeMeta($employee);

        $callNumber = 1;
        foreach ($getCalls->result() as $row) {

            $cell = $cell + 4;

            $this->db->select('*');
            $this->db->from('ecc_companyCategory_cocat');
            $getCat = $this->db->get();

            $this->db->select('*');
            $this->db->from('ecc_feedback_feed');
            $this->db->where('idcall_feed', $row->id_call);
            $getFeed = $this->db->get();

            //call meta
            $this->db->SELECT('*');
            $this->db->FROM('ecc_call_cmeta');
            $this->db->WHERE('idcall_cmeta', $row->id_call);
            $metaCall = $this->db->GET();

            $thisMetaCall = array();
            foreach ($metaCall->result() as $row2) {
                $thisMetaCall[$row2->metaKey_cmeta] = $row2->metaValue_cmeta;
            }; //meta

            $MetaCall = array(
                'reason' => $thisMetaCall['reason'],
                'contact' => $thisMetaCall['contact'],
            );

            //call meta
            $this->db->SELECT('*');
            $this->db->FROM('ecc_call_cmeta');
            $this->db->WHERE('idcall_cmeta', $row->id_call);
            $this->db->WHERE('metaKey_cmeta', 'contact');
            $metaContact = $this->db->GET();

            $name = $meta['first_name'] . " " . $meta['last_name'];

            $position = $meta['position'];

            $totalCalls = $getCalls->num_rows();
            $company = $row->name_co;
            $date = $row->date_call;

            $doneDate = $row->inDate_call;

            $objective = $MetaCall['reason'];
            //$feedback = $MetaCall['feedback'];
            $status = $row->callStatus_call;

            //$logo = imagecreatefromjpeg(base_url() . "assets/bofficr/images/ecc_report_logo.png");
            $author = $this->name;
            //date
            $format = 'DATE_RFC1036';
            $time = time();
            $creationDate = standard_date($format, $time);

            //activate worksheet number 1
            $this->excel->setActiveSheetIndex(0);

            //name the worksheet
            $this->excel->getActiveSheet()->setTitle($name . ' Calls Report');

            $this->excel->getActiveSheet()->setCellValue('A1', $name);
            $this->excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
            $this->excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('A1:G1');

            $this->excel->getActiveSheet()->setCellValue('A2', $position);
            $this->excel->getActiveSheet()->getStyle('A2')->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('A2:G2');

            $this->excel->getActiveSheet()->setCellValue('A4', 'Total Calls');
            $this->excel->getActiveSheet()->getStyle('A4')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('A4')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('A4:C4');

            $this->excel->getActiveSheet()->setCellValue('E4', 'From');
            $this->excel->getActiveSheet()->getStyle('E4')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('E4')->getFont()->setBold(true);

            $this->excel->getActiveSheet()->setCellValue('H4', 'To');
            $this->excel->getActiveSheet()->getStyle('H4')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('H4')->getFont()->setBold(true);

            //$this->excel->getActiveSheet()->setCellValue('A5', 'Companies');
            $this->excel->getActiveSheet()->getStyle('A5')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('A5')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('A5:C5');

            $this->excel->getActiveSheet()->setCellValue('A7', 'Company to call');
            $this->excel->getActiveSheet()->getStyle('A7')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('A7:D11');

            $this->excel->getActiveSheet()->setCellValue('E7', 'Created in');
            $this->excel->getActiveSheet()->getStyle('E7')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('E7')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('E7:G11');

            $this->excel->getActiveSheet()->setCellValue('H7', 'Done in date');
            $this->excel->getActiveSheet()->getStyle('H7')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('H7')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('H7:I11');

            $this->excel->getActiveSheet()->setCellValue('J7', 'Call objective');
            $this->excel->getActiveSheet()->getStyle('J7')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('J7')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('J7:K11');

            $this->excel->getActiveSheet()->setCellValue('D4', $totalCalls);
            $this->excel->getActiveSheet()->getStyle('D4')->getFont()->setSize(12);
            //$this->excel->getActiveSheet()->mergeCells('C4:D4');

            $this->excel->getActiveSheet()->setCellValue('F4', $from);
            $this->excel->getActiveSheet()->getStyle('F4')->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('F4:G4');

            $this->excel->getActiveSheet()->setCellValue('I4', $to);
            $this->excel->getActiveSheet()->getStyle('I4')->getFont()->setSize(12);

            $this->excel->getActiveSheet()->getStyle('A7:V11')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF555555');
            $this->excel->getActiveSheet()->getStyle('A7:V11')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $this->excel->getActiveSheet()->getStyle('A7:V11')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $this->excel->getActiveSheet()->getStyle('A7:V8')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $this->excel->getActiveSheet()->getStyle('A7:V11')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);

            /*

              $this->excel->getActiveSheet()->setCellValue('B6', '0');
              $this->excel->getActiveSheet()->setCellValue('D6', '0');
              $this->excel->getActiveSheet()->setCellValue('F6', '0');
              $this->excel->getActiveSheet()->setCellValue('H6', '0');
              $this->excel->getActiveSheet()->setCellValue('J6', '0');

              $catCell = 1;
              foreach($getCat->result() as $rowCat){


              if($catCell == 1){
              $this->excel->getActiveSheet()->setCellValue('A6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('A6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }elseif($catCell == 2){
              $cat{$catCell} = 1;
              $this->excel->getActiveSheet()->setCellValue('C6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('C6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }elseif($catCell == 3){
              $cat{$catCell} = 1;
              $this->excel->getActiveSheet()->setCellValue('E6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('E6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }elseif($catCell == 4){
              $cat{$catCell} = 1;
              $this->excel->getActiveSheet()->setCellValue('G6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('G6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }elseif($catCell == 5){
              $cat{$catCell} = 1;
              $this->excel->getActiveSheet()->setCellValue('I6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('I6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }elseif($catCell == 6){
              $cat{$catCell} = 1;
              $this->excel->getActiveSheet()->setCellValue('K6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('K6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }elseif($catCell == 7){
              $this->excel->getActiveSheet()->setCellValue('M6', $rowCat->name_cocat);
              $this->excel->getActiveSheet()->getStyle('M6')->getFont()->setSize(12);
              $catCell = $catCell+1;
              }
              };
              $callCat = 1;
              $callCat2 = 1;
              if($row->idcocat_co == 1){

              $this->excel->getActiveSheet()->setCellValue('B6', $callCat);
              $callCat++;
              }elseif($row->idcocat_co == 2){
              $this->excel->getActiveSheet()->setCellValue('D6', +1);

              }elseif($row->idcocat_co == 3){
              $this->excel->getActiveSheet()->setCellValue('F6', +1);
              }elseif($row->idcocat_co == 4){
              $this->excel->getActiveSheet()->setCellValue('H6', +1);
              $callCat2++;
              };
             */

            //echo $row->idcocat_co ."<br />";

            $this->excel->getActiveSheet()->getStyle('A' . $cell . ':V' . $cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFdcdcdc');
            $this->excel->getActiveSheet()->getStyle('A' . $cell . ':V' . $cell)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

            $this->excel->getActiveSheet()->setCellValue('A' . $cell, $callNumber);
            $this->excel->getActiveSheet()->getStyle('A' . $cell)->getFont()->setSize(20);
            $this->excel->getActiveSheet()->getStyle('A' . $cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $this->excel->getActiveSheet()->getStyle('A' . $cell)->getFont()->setBold(true);
            $this->excel->getActiveSheet()->getStyle('B' . ($cell + 1))->getFont()->setBold(true);
            $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);

            $this->excel->getActiveSheet()->setCellValue('B' . $cell, $company);
            $this->excel->getActiveSheet()->getStyle('B' . $cell)->getFont()->setSize(14);
            $this->excel->getActiveSheet()->mergeCells('B' . $cell . ':D' . $cell);
            $this->excel->getActiveSheet()->getStyle('B' . $cell)->getFont()->setBold(true);

            $this->excel->getActiveSheet()->getRowDimension($cell)->setRowHeight(40);
            $this->excel->getActiveSheet()->getStyle('A' . $cell . ':V' . $cell)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $this->excel->getActiveSheet()->setCellValue('E' . $cell, $date);
            $this->excel->getActiveSheet()->getStyle('E' . $cell)->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('E' . $cell . ':F' . $cell);

            $this->excel->getActiveSheet()->setCellValue('H' . $cell, $doneDate);
            $this->excel->getActiveSheet()->getStyle('H' . $cell)->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('H' . $cell . ':I' . $cell);

            $this->excel->getActiveSheet()->setCellValue('J' . $cell, $objective);
            $this->excel->getActiveSheet()->getStyle('J' . $cell)->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('J' . $cell . ':V' . $cell);

            $this->excel->getActiveSheet()->setCellValue('B' . ($cell + 1), 'Feedbacks');
            $this->excel->getActiveSheet()->getStyle('B' . ($cell + 1))->getFont()->setSize(14);
            $this->excel->getActiveSheet()->getStyle('B' . ($cell + 1))->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('B' . ($cell + 1) . ':V' . ($cell + 1));
            $this->excel->getActiveSheet()->getRowDimension($cell + 1)->setRowHeight(30);
            $this->excel->getActiveSheet()->getStyle('B' . ($cell + 1) . ':V' . ($cell + 1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $this->excel->getActiveSheet()->getStyle('B' . ($cell + 1) . ':V' . ($cell + 1))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFf0f0f0');
            $this->excel->getActiveSheet()->getStyle('B' . ($cell + 1) . ':V' . ($cell + 1))->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

            $this->excel->getActiveSheet()->mergeCells('J7:V8');
            $this->excel->getActiveSheet()->mergeCells('C7:D7');
            $this->excel->getActiveSheet()->mergeCells('C8:D8');
            $this->excel->getActiveSheet()->mergeCells('A1:V1');
            $this->excel->getActiveSheet()->mergeCells('A2:V2');
            $this->excel->getActiveSheet()->mergeCells('A3:V3');
            $this->excel->getActiveSheet()->mergeCells('A6:V6');
            //$this->excel->getActiveSheet()->mergeCells('A9:V11');
            $cellFeed = $cell + 2;
            if ($getFeed->num_rows() == 0) {
                $this->excel->getActiveSheet()->setCellValue('B' . $cellFeed, 'No Feedbacks avalable');
                $this->excel->getActiveSheet()->mergeCells('B' . $cellFeed . ':V' . $cellFeed);
                $this->excel->getActiveSheet()->getRowDimension($cellFeed)->setRowHeight(30);
                $this->excel->getActiveSheet()->getStyle('B' . $cellFeed . ':V' . $cellFeed)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $this->excel->getActiveSheet()->getStyle('B' . $cellFeed . ':V' . $cellFeed)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFf0f0f0');
                $this->excel->getActiveSheet()->getStyle('B' . $cellFeed . ':V' . $cellFeed)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            } else {

                foreach ($getFeed->result() as $rowFeed) {
                    $metaFeed = $EmployeeMeta->EmployeeMeta($rowFeed->idemp_feed);
                    $this->excel->getActiveSheet()->getRowDimension($cellFeed)->setRowHeight(25);
                    $this->excel->getActiveSheet()->getStyle('B' . $cellFeed . ':V' . $cellFeed)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $this->excel->getActiveSheet()->setCellValue('B' . $cellFeed, $metaFeed['first_name'] . " " . $metaFeed['last_name']);
                    $this->excel->getActiveSheet()->getStyle('B' . $cellFeed)->getFont()->setSize(12);
                    $this->excel->getActiveSheet()->mergeCells('B' . $cellFeed . ':D' . $cellFeed);

                    $this->excel->getActiveSheet()->setCellValue('E' . $cellFeed, '(' . $rowFeed->date_feed . ') ' . $rowFeed->feedback_feed);
                    $this->excel->getActiveSheet()->getStyle('E' . $cellFeed)->getFont()->setSize(12);
                    $this->excel->getActiveSheet()->mergeCells('E' . $cellFeed . ':V' . $cellFeed);
                    $this->excel->getActiveSheet()->getStyle('B' . $cellFeed . ':V' . $cellFeed)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFf0f0f0');
                    $this->excel->getActiveSheet()->getStyle('B' . $cellFeed . ':V' . $cellFeed)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $cellFeed++;
                }
            }



            $this->excel->getActiveSheet()->setCellValue('B' . ($cellFeed + 1), 'In Contact List');
            $this->excel->getActiveSheet()->getStyle('B' . ($cellFeed + 1))->getFont()->setSize(14);
            $this->excel->getActiveSheet()->getStyle('B' . ($cellFeed + 1))->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('B' . ($cellFeed + 1) . ':V' . ($cellFeed + 1));


            $this->excel->getActiveSheet()->getRowDimension($cellFeed + 1)->setRowHeight(30);
            $this->excel->getActiveSheet()->getStyle('B' . ($cellFeed + 1) . ':V' . ($cellFeed + 1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $this->excel->getActiveSheet()->getStyle('B' . ($cellFeed + 1) . ':V' . ($cellFeed + 1))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFf0f0f0');
            $this->excel->getActiveSheet()->getStyle('B' . ($cellFeed + 1) . ':V' . ($cellFeed + 1))->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);


            //$i = 1;
            $contactList = array();
            $cellContact = $cellFeed;

            if ($metaContact->num_rows() == '0') {

                $this->excel->getActiveSheet()->setCellValue('B' . ($cellContact + 2), 'No contact asigned to this call');
                $this->excel->getActiveSheet()->mergeCells('B' . ($cellContact + 2) . ':V' . ($cellContact + 2));
            } else {

                foreach ($metaContact->result() as $rowContact) {

                    $this->db->select('*');
                    $this->db->from('ecc_companyContact_cocontact');
                    $this->db->where('id_cocontact', $rowContact->metaValue_cmeta);
                    $contactName = $this->db->get();

                    foreach ($contactName->result() as $rowContactName) {

                        $this->excel->getActiveSheet()->getRowDimension(($cellContact + 2))->setRowHeight(25);

                        $this->excel->getActiveSheet()->getStyle('B' . ($cellContact + 2) . ':V' . ($cellContact + 2))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                        $this->excel->getActiveSheet()->setCellValue('B' . ($cellContact + 2), $rowContactName->name_cocontact);
                        $this->excel->getActiveSheet()->setCellValue('E' . ($cellContact + 2), $rowContactName->email_cocontact);
                        $this->excel->getActiveSheet()->setCellValue('J' . ($cellContact + 2), '(' . $rowContactName->mobile_cocontact . ')');
                        $this->excel->getActiveSheet()->getStyle('J' . ($cellContact + 2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        $this->excel->getActiveSheet()->getStyle('B' . ($cellContact + 2))->getFont()->setSize(12);

                        $this->excel->getActiveSheet()->mergeCells('B' . ($cellContact + 2) . ':D' . ($cellContact + 2));
                        $this->excel->getActiveSheet()->mergeCells('E' . ($cellContact + 2) . ':I' . ($cellContact + 2));
                        $this->excel->getActiveSheet()->mergeCells('J' . ($cellContact + 2) . ':V' . ($cellContact + 2));
                        $this->excel->getActiveSheet()->getStyle('B' . ($cellContact + 2) . ':V' . ($cellContact + 2))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFf0f0f0');
                        $this->excel->getActiveSheet()->getStyle('B' . ($cellContact + 2) . ':V' . ($cellContact + 2))->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    }

                    $i++;
                    $cellContact++;
                }
            }

            $cell = $cellContact;
            //set aligment to center for that merged cell (A1 to D1)
            $this->excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $this->excel->getActiveSheet()->getStyle('D4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);



            $filename = $name . " (from " . $from . ' to ' . $to . ').xls'; //save our workbook as this file name
            header('Content-Type: application/vnd.ms-excel'); //mime type
            header('Content-Disposition: attachment;filename="' . $filename . '"'); //tell browser what's the file name
            header('Cache-Control: max-age=0'); //no cache
            //save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
            //if you want to save it as .XLSX Excel 2007 format
            $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
            //force user to download the Excel file without writing it to server's HD
            $callNumber++;
        }//foreach

        /*
          $this->excel->getActiveSheet()->setCellValue('A'.($cell+3), 'this report was generated by the ECC Sales Quality System in ' . $creationDate .', by the request of '. $author);
          $this->excel->getActiveSheet()->getStyle('A'.($cell+3))->getFont()->setSize(12);
          $this->excel->getActiveSheet()->mergeCells('A'.($cell+3).':Q'.($cell+3));
         */

        $objWriter->save('php://output');
    }

    function companiesReport() {
        $this->load->library('excel');
        $this->load->library('Sanitizer');
        $this->load->model('companies_report');
        //$EmployeeMeta = new Sanitizer($EmpId);
//		$from = date('Y-m-d', strtotime($this->uri->segment(3)));
//		$to = date('Y-m-d', strtotime($this->uri->segment(4)));
        $from = date('Y-m-d', strtotime($this->input->post('from')));
        $to = date('Y-m-d', strtotime($this->input->post('to')));

        $getResults = $this->companies_report->getCompanies($from, $to);

        $cell = 14;
        $i = 0;
        foreach ($getResults as $row) {
            $cell + 2;
            //activate worksheet number 1
            $this->excel->setActiveSheetIndex(0);

            //name the worksheet
            $this->excel->getActiveSheet()->setTitle('Prospect Companies');

            $this->excel->getActiveSheet()->setCellValue('A1', 'Prospect Companies');
            $this->excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
            $this->excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('A1:V1');

            $this->excel->getActiveSheet()->setCellValue('A4', 'Total');
            $this->excel->getActiveSheet()->getStyle('A4')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('A4')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('A4:C5');
            $this->excel->getActiveSheet()->mergeCells('D4:D5');

            $this->excel->getActiveSheet()->setCellValue('E4', 'From');
            $this->excel->getActiveSheet()->getStyle('E4')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('E4')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('E4:E5');

            $this->excel->getActiveSheet()->setCellValue('H4', 'To');
            $this->excel->getActiveSheet()->getStyle('H4')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('H4')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('H4:H5');

            $this->excel->getActiveSheet()->mergeCells('A2:V3');
            $this->excel->getActiveSheet()->mergeCells('A6:V7');
            $this->excel->getActiveSheet()->mergeCells('A13:V14');

            $this->excel->getActiveSheet()->setCellValue('D4', $getResults);
            $this->excel->getActiveSheet()->getStyle('D4')->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('D4:D5');

            $this->excel->getActiveSheet()->setCellValue('F4', $from);
            $this->excel->getActiveSheet()->getStyle('F4')->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('F4:G5');

            $this->excel->getActiveSheet()->setCellValue('I4', $to);
            $this->excel->getActiveSheet()->getStyle('I4')->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('I4:J5');

            $this->excel->getActiveSheet()->setCellValue('B8', 'Created In');
            $this->excel->getActiveSheet()->getStyle('B8')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('B8')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('B8:D12');

            $this->excel->getActiveSheet()->setCellValue('E8', 'Company Name');
            $this->excel->getActiveSheet()->getStyle('E8')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('E8')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('E8:G12');

            $this->excel->getActiveSheet()->setCellValue('H8', 'By');
            $this->excel->getActiveSheet()->getStyle('H8')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('H8')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('H8:I12');

            $this->excel->getActiveSheet()->setCellValue('J8', 'Accounts Owner');
            $this->excel->getActiveSheet()->getStyle('J8')->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('J8')->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('J8:V12');

            $this->excel->getActiveSheet()->getStyle('A8:V12')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF555555');
            $this->excel->getActiveSheet()->getStyle('A8:V12')->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $this->excel->getActiveSheet()->getStyle('A8:V12')->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $this->excel->getActiveSheet()->getStyle('A8:V8')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $this->excel->getActiveSheet()->getStyle('A7:V11')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);

            $this->excel->getActiveSheet()->getRowDimension($cell)->setRowHeight(30);
            $this->excel->getActiveSheet()->getStyle('A' . $cell . ':V' . $cell)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $this->excel->getActiveSheet()->getStyle('A' . $cell . ':V' . $cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFf0f0f0');
            $this->excel->getActiveSheet()->getStyle('A' . $cell . ':V' . $cell)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

            $this->excel->getActiveSheet()->setCellValue('A' . $cell, $i);
            $this->excel->getActiveSheet()->getStyle('A' . $cell)->getFont()->setSize(16);
            $this->excel->getActiveSheet()->getStyle('A' . $cell)->getFont()->setBold(true);

            $this->excel->getActiveSheet()->setCellValue('B' . $cell, $row->name_co);
            $this->excel->getActiveSheet()->getStyle('B' . $cell)->getFont()->setSize(14);
            $this->excel->getActiveSheet()->getStyle('B' . $cell)->getFont()->setBold(true);
            $this->excel->getActiveSheet()->mergeCells('B' . $cell . ':D' . $cell);

            $this->db->select('*');
            $this->db->from('ecc_companyMeta_cometa');
            $this->db->where('idco_cometa', $row->id_co);
            $getMeta = $this->db->get();

            $thisMeta = array();
            foreach ($getMeta->result() as $row2) {
                $thisMeta[$row2->metaKey_cometa] = $row2->metaValue_cometa;
            }; //meta

            $MetaCO = array(
                'creatorNameSpace' => $thisMeta['creatorNameSpace'],
                'address' => $thisMeta['address'],
                'website' => $thisMeta['website'],
                'registeredDate' => $thisMeta['registeredDate']
            );
            $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
            $this->excel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $this->excel->getActiveSheet()->setCellValue('E' . $cell, $MetaCO['registeredDate']);
            $this->excel->getActiveSheet()->getStyle('E' . $cell)->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('E' . $cell . ':F' . $cell);

            $this->excel->getActiveSheet()->setCellValue('H' . $cell, $MetaCO['creatorNameSpace']);
            $this->excel->getActiveSheet()->getStyle('H' . $cell)->getFont()->setSize(12);
            $this->excel->getActiveSheet()->mergeCells('H' . $cell . ':I' . $cell);

            $cell++;
            $i++;


            $filename = "Prospect Companies (from " . $from . ' to ' . $to . ').xls'; //save our workbook as this file name
            header('Content-Type: application/vnd.ms-excel'); //mime type
            header('Content-Disposition: attachment;filename="' . $filename . '"'); //tell browser what's the file name
            header('Cache-Control: max-age=0'); //no cache
            //save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
            //if you want to save it as .XLSX Excel 2007 format
            $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
        }
        $objWriter->save('php://output');
    }

    function edit() {
        $data['id'] = $this->id;
        $data['access'] = $this->status;
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['avatar'] = $this->avatar;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['object'] = $this->uri->segment(4);
        $data['manager'] = $this->manager;
        $data['level'] = $this->level;
        $data['loggedInId'] = $this->id;
        $data['department'] = $this->department;

        if ($data['type'] == 'calls') {
            $this->db->select('*');
            $this->db->from('ecc_calls_call');
            $this->db->join('ecc_company_co', 'idco_call = id_co', 'inner');
            $this->db->where('id_call', $data['object']);
            $data['getCall'] = $this->db->get();

            $this->db->select('*');
            $this->db->from('ecc_fileCallsRelations_fcr');
            $this->db->where('idcall_fcr', $data['object']);
            $data['getFiles'] = $this->db->get();
        } elseif ($data['type'] == 'companies') {

            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->where('id_co', $data['object']);
            $this->db->where('status_co', 'active');
            $data['getCompany'] = $this->db->get();

            $this->db->select('*');
            $this->db->from('ecc_companyCategory_cocat');
            $data['getCategory'] = $this->db->get();

            $this->db->select('*');
            $this->db->from('ecc_companyContact_cocontact');
            $this->db->where('idco_cocontact', $data['object']);
            $data['getContact'] = $this->db->get();

            $this->db->select('*');
            $this->db->from('ecc_companyBranch_cobr');
            $this->db->where('idco_cobr', $data['object']);
            $data['getBranch'] = $this->db->get();
        } elseif ($data['type'] == 'employees') {
            $this->db->select('*');
            $this->db->from('ecc_employees_emp');
            $this->db->where('id_emp', $data['object']);
            $data['getEmployee'] = $this->db->get();
        } elseif ($data['type'] == 'products') {
            $this->db->select('*');
            $this->db->from('ecc_products_prod');
            $this->db->where('id_prod', $data['object']);
            $data['getProduct'] = $this->db->get();
        } elseif ($data['type'] == 'resources') {
            $this->db->select('*');
            $this->db->from('ecc_files_file');
            $this->db->where('id_file', $data['object']);
            $data['getFiles'] = $this->db->get();
        } elseif ($data['type'] == 'departments') {
            $this->db->select('*');
            $this->db->from('ecc_department_dep');
            $this->db->where('id_dep', $data['object']);
            $data['getDepartment'] = $this->db->get();
        }

        $this->load->view('boffice/edit', $data);
    }

    function resetPassword() {
        $userId = $this->uri->segment(3);
        $this->db->where('id_emp', $userId);
        $this->db->set('password_emp', md5('@ecc_123'));
        $this->db->set('status_emp', '0');
        $this->db->set('force_password_emp', '1');
        $this->db->update('ecc_employees_emp');
    }

    function delete() {
        $this->load->helper('file');
        $data['access'] = $this->status;
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['avatar'] = $this->avatar;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['object'] = $this->uri->segment(4);
        $data['manager'] = $this->manager;
        $data['level'] = $this->level;

        if ($data['type'] == 'calls') {

            $this->db->select('*');
            $this->db->from('ecc_calls_call');
            $this->db->where('id_call', $data['object']);
            $getCall = $this->db->get();
            foreach ($getCall->result() as $rowCall) {
                $emp = $rowCall->idemp_call;
            }

            $this->db->where('id_call', $data['object']);
            $this->db->delete('ecc_calls_call');

            $this->db->where('idcall_cmeta', $data['object']);
            $this->db->delete('ecc_call_cmeta');

            //decrease dep count
            $this->db->where('id_emp', $emp);
            $this->db->set('callsCount_emp', 'callsCount_emp-1', false);
            $this->db->update('ecc_employees_emp');
        } elseif ($data['type'] == 'accounts') {


            $this->db->select('*');
            $this->db->from('ecc_accounts_acc');
            $this->db->where('id_acc', $data['object']);
            $getAcc = $this->db->get();

            foreach ($getAcc->result() as $rowAcc) {

                //get employee
                $this->db->SELECT('*');
                $this->db->FROM('ecc_employee_empmeta');
                $this->db->WHERE('idemp_empmeta', $rowAcc->idemp_acc);
                $meta = $this->db->GET();

                $thisMeta = array();
                foreach ($meta->result() as $row2) {
                    $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
                }//meta

                $MetaEmp = array(
                    'first_name' => $thisMeta['first_name'],
                    'last_name' => $thisMeta['last_name'],
                    'position' => $thisMeta['position'],
                    'avatar' => $thisMeta['avatar'],
                    'mobile' => $thisMeta['mobile']
                );

                //get employee
                $this->db->SELECT('*');
                $this->db->FROM('ecc_company_co');
                $this->db->WHERE('id_co', $rowAcc->idco_acc);
                $AccCompany = $this->db->GET();

                foreach ($AccCompany->result() as $coAcc) {
                    $theCo = $coAcc->name_co;
                }

                $AccOwner = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

                //push notification
                $push = array(
                    'objectid_push' => $data['object'],
                    'affectedEmp_push' => $rowAcc->idemp_acc,
                    'creatorEmp_push' => $this->id,
                    'affectedNameSpace_push' => $AccOwner,
                    'creatorNameSpace_push' => $this->name,
                    'type_push' => 'accounts',
                    'name_push' => 'You are no longer responsible to manage <b>' . $theCo . '</b> account any more',
                    'date_push' => date('Y-m-d H:i:s', now()),
                    'status_push' => '1'
                );
                $this->db->insert('ecc_pusher_push', $push);

                //decrease dep count
                $this->db->where('id_co', $rowAcc->idco_acc);
                $this->db->set('accountCount_co', 'accountCount_co-1', false);
                $this->db->update('ecc_company_co');

                //push notifications
                $this->db->select('*');
                $this->db->from('ecc_pusher_push');
                $this->db->where('status_push', '1');
                $this->db->where('affectedEmp_push', $rowAcc->idemp_acc);
                $getCount = $this->db->get();

                $PushNotification = array(
                    'count' => $getCount->num_rows(),
                    'name' => 'You are no longer responsible to manage <b>' . $theCo . '</b> account any more'
                );
                $this->load->library('pusher');
                $this->pusher->trigger('notifications-' . $rowAcc->idemp_acc, 'notifications', $PushNotification);
            }

            $this->db->where('id_acc', $data['object']);
            $this->db->delete('ecc_accounts_acc');
        } elseif ($data['type'] == 'companies') {

            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->where('id_co', $data['object']);
            $selectCoCat = $this->db->get();

            foreach ($selectCoCat->result() as $row) {
                $cat = $row->idcocat_co;
            }

            //decrease dep count
            $this->db->where('id_cocat', $cat);
            $this->db->set('count_cocat', 'count_cocat-1', false);
            $this->db->update('ecc_companyCategory_cocat');

            $this->db->where('id_co', $data['object']);
            $this->db->delete('ecc_company_co');
        } elseif ($data['type'] == 'products') {

            $this->db->where('id_prod', $data['object']);
            $this->db->delete('ecc_products_prod');
        } elseif ($data['type'] == 'notifications') {

            $this->db->where('id_notif', $data['object']);
            $this->db->delete('ecc_notifications_notif');
        } elseif ($data['type'] == 'resources') {

            $this->db->where('id_file', $data['object']);
            $this->db->delete('ecc_files_file');
        } elseif ($data['type'] == 'employees') {
            //delete Photo First
            $this->db->select('*');
            $this->db->from('ecc_employee_empmeta');
            $this->db->where('idemp_empmeta', $data['object']);
            $this->db->where('metaKey_empmeta', 'avatar');
            $getPhoto = $this->db->get();
            foreach ($getPhoto->result() as $row) {
                $avatar = $row->metaValue_empmeta;
            }
            unlink('./post_data/employees/photo/' . $avatar);
            //delete action
            $this->db->where('id_emp', $data['object']);
            $this->db->delete('ecc_employees_emp');

            //save the department before delete
            $this->db->select('*');
            $this->db->from('ecc_mempership_memper');
            $this->db->where('idemp_memper', $data['object']);
            $getDep = $this->db->get();
            foreach ($getDep->result() as $row2) {
                $dep = $row2->iddep_memper;
            }

            //delete mempership
            $this->db->where('idemp_memper', $data['object']);
            $this->db->delete('ecc_mempership_memper');

            $this->db->where('idemp_empmeta', $data['object']);
            $this->db->delete('ecc_employee_empmeta');
            //decrease dep count
            $this->db->where('id_dep', $dep);
            $this->db->set('count_dep', 'count_dep-1', false);
            $this->db->update('ecc_department_dep');

            //check if he a manager
            $this->db->select('*');
            $this->db->from('ecc_managers_man');
            $this->db->where('idemp_man', $data['object']);
            $checkMan = $this->db->get();
            //delete if yes
            if ($checkMan->num_rows == 1) {
                $this->db->where('idemp_man', $data['object']);
                $this->db->delete('ecc_managers_man');
            };
        } elseif ($data['type'] == 'products') {

        } elseif ($data['type'] == 'resources') {

        } elseif ($data['type'] == 'departments') {

            $this->db->where('id_dep', $data['object']);
            $this->db->delete('ecc_department_dep');

            $this->db->where('iddep_man', $data['object']);
            $this->db->delete('ecc_managers_man');
        } elseif ($data['type'] == 'coContacts') {

            $this->db->where('id_cocontact', $data['object']);
            $this->db->delete('ecc_companyContact_cocontact');
        }
    }

    function addFeedbackForm() {
        $data['call'] = $this->uri->segment('3');
        $data['company'] = $this->uri->segment('4');
        $data['employee'] = $this->uri->segment('5');

        $this->load->view('boffice/addFeedBackForEdit', $data);
    }

    function getFeedbacksForEdit() {
        $data['call'] = $this->uri->segment('3');
        $this->db->select('*');
        $this->db->from('ecc_feedback_feed');
        $this->db->where('idcall_feed', $data['call']);
        $data['getFeedback'] = $this->db->get();
        $this->load->view('boffice/getFeedbackForEdit', $data);
    }

    function insertFeedback() {
        $this->load->library('Sanitizer');
        $call = $this->uri->segment('3');
        $company = $this->uri->segment('4');
        $employee = $this->uri->segment('5');
        $PreFeedback = $this->input->post('feedback');

        $SanFeed = new Sanitizer($string);
        $feedback = $SanFeed->sanitize($PreFeedback);
        //date
        $date = date('Y-m-d H:i:s', now());
        $this->db->select('*');
        $this->db->from('ecc_feedback_feed');
        $this->db->where('idcall_feed', $call);
        $getFeedbacks = $this->db->get();

        if ($this->id == $employee) {
            $updateStatus = array('callStatus_call' => 'done', 'inDate_call' => date('Y-m-d H:i:s', now()));
            $this->db->where('id_call', $call);
            $this->db->update('ecc_calls_call', $updateStatus);

            //select employee of the department
            $this->db->select('*');
            $this->db->from('ecc_department_dep');
            $this->db->where('name_dep', $this->department);
            $getDep = $this->db->get();
            foreach ($getDep->result() as $depRow) {
                $this->db->select('*');
                $this->db->from('ecc_managers_man');
                $this->db->where('iddep_man', $depRow->id_dep);
                $getMan = $this->db->get();
                foreach ($getMan->result() as $manRow) {
                    $manId = $manRow->idemp_man;
                    //get employee
                    $this->db->SELECT('*');
                    $this->db->FROM('ecc_employee_empmeta');
                    $this->db->WHERE('idemp_empmeta', $manId);
                    $meta = $this->db->GET();

                    $thisMeta = array();
                    foreach ($meta->result() as $row2) {
                        $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
                    }//meta

                    $MetaEmp = array(
                        'first_name' => $thisMeta['first_name'],
                        'last_name' => $thisMeta['last_name'],
                        'position' => $thisMeta['position'],
                        'avatar' => $thisMeta['avatar'],
                        'mobile' => $thisMeta['mobile']
                    );
                    $EmpName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

                    //getCompany
                    $this->db->select('*');
                    $this->db->from('ecc_company_co');
                    $this->db->where('id_co', $company);
                    $getCoName = $this->db->get();

                    foreach ($getCoName->result() as $rowCo) {
                        $companyName = $rowCo->name_co;
                    }

                    $push = array(
                        'objectid_push' => $call,
                        'affectedEmp_push' => $manId,
                        'creatorEmp_push' => $this->id,
                        'affectedNameSpace_push' => $EmpName,
                        'creatorNameSpace_push' => $this->name,
                        'type_push' => 'calls',
                        'name_push' => '<b>' . $this->name . '</b> just added feedback to <b>' . $companyName . '</b> call',
                        'date_push' => date('Y-m-d H:i:s', now()),
                        'status_push' => '1'
                    );
                    $this->db->insert('ecc_pusher_push', $push);

                    //push notifications
                    $this->db->select('*');
                    $this->db->from('ecc_pusher_push');
                    $this->db->where('status_push', '1');
                    $this->db->where('affectedEmp_push', $employee);
                    $getCount = $this->db->get();

                    $PushNotification = array(
                        'count' => $getCount->num_rows(),
                        'name' => '<b>' . $this->name . '</b> just added feedback to <b>' . $companyName . '</b> call'
                    );
                    $this->load->library('pusher');
                    $this->pusher->trigger('notifications-' . $manId, 'notifications', $PushNotification);
                }
            }
        } else {

            //getCompany
            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->where('id_co', $company);
            $getCoName = $this->db->get();

            foreach ($getCoName->result() as $rowCo) {
                $companyName = $rowCo->name_co;
            }

            //get employee
            $this->db->SELECT('*');
            $this->db->FROM('ecc_employee_empmeta');
            $this->db->WHERE('idemp_empmeta', $employee);
            $meta = $this->db->GET();

            $thisMeta = array();
            foreach ($meta->result() as $row2) {
                $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
            }//meta

            $MetaEmp = array(
                'first_name' => $thisMeta['first_name'],
                'last_name' => $thisMeta['last_name'],
                'position' => $thisMeta['position'],
                'avatar' => $thisMeta['avatar'],
                'mobile' => $thisMeta['mobile']
            );
            $EmpName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

            $push = array(
                'objectid_push' => $call,
                'affectedEmp_push' => $employee,
                'creatorEmp_push' => $this->id,
                'affectedNameSpace_push' => $EmpName,
                'creatorNameSpace_push' => $this->name,
                'type_push' => 'calls',
                'name_push' => '<b>' . $this->name . '</b> just added feedback to <b>' . $companyName . '</b> call',
                'date_push' => date('Y-m-d H:i:s', now()),
                'status_push' => '1'
            );
            $this->db->insert('ecc_pusher_push', $push);

            //push notifications
            $this->db->select('*');
            $this->db->from('ecc_pusher_push');
            $this->db->where('status_push', '1');
            $this->db->where('affectedEmp_push', $employee);
            $getCount = $this->db->get();

            $PushNotification = array(
                'count' => $getCount->num_rows(),
                'name' => '<b>' . $this->name . '</b> just added feedback to <b>' . $companyName . '</b> call'
            );
            $this->load->library('pusher');
            $this->pusher->trigger('notifications-' . $empRow->id_emp, 'notifications', $PushNotification);
        }

        $insertFeedback = array(
            'idcall_feed' => $call,
            'idemp_feed' => $this->id,
            'idco_feed' => $company,
            'feedback_feed' => $feedback,
            'date_feed' => $date
        );
        $this->db->insert('ecc_feedback_feed', $insertFeedback);
    }

    function editBranch() {
        $data['id'] = $this->id;
        $data['access'] = $this->status;
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['object'] = $this->uri->segment(4);
        $data['manager'] = $this->manager;
        $data['level'] = $this->level;



        $data['object'] = $this->uri->segment(4);
        $data['co'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_companyBranch_cobr');
        $this->db->where('id_cobr', $data['object']);
        $data['getbranch'] = $this->db->get();

        $this->load->view('boffice/editbranch', $data);
    }

    function editContact() {
        $data['id'] = $this->id;
        $data['access'] = $this->status;
        $data['name'] = $this->name;
        $data['position'] = $this->position;
        $data['title'] = 'insert new ';
        $data['action'] = 'New Employee';
        $data['type'] = $this->uri->segment(3);
        $data['object'] = $this->uri->segment(4);
        $data['manager'] = $this->manager;
        $data['level'] = $this->level;

        $data['object'] = $this->uri->segment(4);
        $data['co'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_companyContact_cocontact');
        $this->db->where('id_cocontact', $data['object']);
        $data['getContact'] = $this->db->get();

        $this->load->view('boffice/editContact', $data);
    }

    function singleCompany() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_company_co');
        $this->db->where('id_co', $data['object']);
        $data['singleCompany'] = $this->db->get();

        $this->load->view('boffice/singleCompany.js', $data);
    }

    function singleContact() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_companyContact_cocontact');
        $this->db->where('id_cocontact', $data['object']);
        $data['singleContact'] = $this->db->get();

        $this->load->view('boffice/singleContact.js', $data);
    }

    function singleBranch() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_companyBranch_cobr');
        $this->db->where('id_cobr', $data['object']);
        $data['singleBranch'] = $this->db->get();

        $this->load->view('boffice/singleBranch.js', $data);
    }

    function singleCallMeta() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_calls_call');
        $this->db->where('id_call', $data['object']);
        $data['singleCall'] = $this->db->get();

        $this->load->view('boffice/singleCallMeta.js', $data);
    }

    function singleEmployeeMeta() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('id_emp', $data['object']);
        $data['singleEmployee'] = $this->db->get();

        $this->load->view('boffice/singleEmployeeMeta.js', $data);
    }

    function singleProduct() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_products_prod');
        $this->db->where('id_prod', $data['object']);
        $data['singleProduct'] = $this->db->get();

        $this->load->view('boffice/singleProduct.js', $data);
    }

    function singleFile() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_files_file');
        $this->db->where('id_file', $data['object']);
        $data['singleFile'] = $this->db->get();

        $this->load->view('boffice/singleFile.js', $data);
    }

    function singleDepartment() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_department_dep');
        $this->db->where('id_dep', $data['object']);
        $data['singleDepartment'] = $this->db->get();

        $this->load->view('boffice/singleDepartment.js', $data);
    }

    function updateContact() {
        echo $object = $this->uri->segment(3);
        echo $name = $this->input->post('name');
        echo $email = $this->input->post('email');
        echo $phone = $this->input->post('phone');
        echo $mobile = $this->input->post('mobile');
        echo $fax = $this->input->post('fax');
        echo $position = $this->input->post('position');
        echo $brief = $this->input->post('brief');

        $update = array('name_cocontact' => $name, 'email_cocontact' => $email, 'phone_cocontact' => $phone, 'mobile_cocontact' => $mobile, 'fax_cocontact' => $fax, 'position_cocontact' => $position, 'brief_cocontact' => $brief);
        $this->db->where('id_cocontact', $object);
        $this->db->update('ecc_companyContact_cocontact', $update);
    }

    function updateBranch() {
        $object = $this->uri->segment(3);
        $name = $this->input->post('name');
        $address = $this->input->post('address');
        $fax = $this->input->post('fax');
        $phone = $this->input->post('phone');

        $update = array('name_cobr' => $name, 'address_cobr' => $address, 'fax_cobr' => $fax, 'phone_cobr' => $phone);
        $this->db->where('id_cobr', $object);
        $this->db->update('ecc_companyBranch_cobr', $update);
    }

    function updateCallMeta() {
        $object = $this->uri->segment(3);
        $company = $this->input->post('company');
        $employee = $this->input->post('employee');
        $objective = $this->input->post('objective');
        $type = $this->input->post('type');
        $status = $this->input->post('status');
        $dueDate = date('Y-m-d', strtotime(str_replace('-', '/', $this->input->post('dueDate'))));
        $feedback = $this->input->post('feedback');


        $date = date('Y-m-d', now());
        $updateObjective = array('metaValue_cmeta' => $objective);
        $this->db->where('idcall_cmeta', $object);
        $this->db->where('metaKey_cmeta', 'reason');
        $this->db->update('ecc_call_cmeta', $updateObjective);

        $updateCall = array(
            'callStatus_call' => $status,
            'callType_call' => $type,
            'dueDate_call' => $dueDate,
                /* 'idco_call' => $company */
        );

        $this->db->where('id_call', $object);
        $this->db->update('ecc_calls_call', $updateCall);



        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('id_emp', $employee);
        $getEmp = $this->db->get();

        foreach ($getEmp->result() as $empRow) {

            //get employee
            $this->db->SELECT('*');
            $this->db->FROM('ecc_employee_empmeta');
            $this->db->WHERE('idemp_empmeta', $employee);
            $meta = $this->db->GET();

            $thisMeta = array();
            foreach ($meta->result() as $row2) {
                $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
            }//meta

            $MetaEmp = array(
                'first_name' => $thisMeta['first_name'],
                'last_name' => $thisMeta['last_name'],
                'position' => $thisMeta['position'],
                'avatar' => $thisMeta['avatar'],
                'mobile' => $thisMeta['mobile']
            );
            $EmpName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

            $this->db->select('*');
            $this->db->from('ecc_company_co');
            $this->db->where('id_co', $company);
            $getCo = $this->db->get();

            foreach ($getCo->result() as $coRow) {
                $coName = $coRow->name_co;
            }

            $push = array(
                'objectid_push' => $object,
                'affectedEmp_push' => $empRow->id_emp,
                'creatorEmp_push' => $this->id,
                'affectedNameSpace_push' => $EmpName,
                'creatorNameSpace_push' => $this->name,
                'type_push' => 'calls',
                'name_push' => '<b>' . $this->name . '</b> just edited your call to <b>' . $company . '</b>',
                'date_push' => date('Y-m-d H:i:s', now()),
                'status_push' => '1'
            );
            $this->db->insert('ecc_pusher_push', $push);

            //push notifications
            $this->db->select('*');
            $this->db->from('ecc_pusher_push');
            $this->db->where('status_push', '1');
            $this->db->where('affectedEmp_push', $employee);
            $getCount = $this->db->get();

            $PushNotification = array(
                'count' => $getCount->num_rows(),
                'name' => '<b>' . $this->name . '</b> just edited your call to <b>' . $company . '</b>'
            );
            $this->load->library('pusher');
            $this->pusher->trigger('notifications-' . $empRow->id_emp, 'notifications', $PushNotification);
        }
    }

    function updateEmployee() {

        $this->load->model('upload_model');
        $object = $this->uri->segment(3);
        $first_name = $this->input->post('first_name');
        $last_name = $this->input->post('last_name');
        $email = $this->input->post('email');
        $position = $this->input->post('position');
        $mobile = $this->input->post('mobile');
        $mobile2 = $this->input->post('mobile2');
        $homePhone = $this->input->post('home');
        $address = $this->input->post('address');
        $department = $this->input->post('department');

        //photo
        $name = $first_name . " " . $last_name;
        $userfile = $this->upload_model->do_upload_employee_photo($name);
        $avatar = $userfile['file_name'];
        $errorMessage = $this->upload->display_errors();
        //check if image uploaded
        if ($errorMessage != true || !isset($errorMessage)) {

            $updateavatar = array('metaValue_empmeta' => $avatar);
            $this->db->where('idemp_empmeta', $object);
            $this->db->where('metaKey_empmeta', 'avatar');
            $this->db->update('ecc_employee_empmeta', $updateavatar);
        }

        if ($department != '0') {
            //save the old department
            $this->db->select('*');
            $this->db->from('ecc_mempership_memper');
            $this->db->where('idemp_memper', $object);
            $getDep = $this->db->get();
            foreach ($getDep->result() as $row2) {
                $oldDep = $row2->iddep_memper;
            }

            $this->db->where('id_dep', $oldDep);
            $this->db->set('count_dep', 'count_dep-1', false);
            $this->db->update('ecc_department_dep');

            $this->db->where('idemp_memper', $object);
            $this->db->delete('ecc_mempership_memper');


            $this->db->select('*');
            $this->db->from('ecc_department_dep');
            $this->db->where('name_dep', $department);
            $getDep = $this->db->get();
            foreach ($getDep->result() as $row) {
                echo $depid = $row->id_dep;
            };

            $updateDepartment = array('iddep_memper' => $depid, 'idemp_memper' => $object);
            $this->db->insert('ecc_mempership_memper', $updateDepartment);

            $this->db->where('name_dep', $department);
            $this->db->set('count_dep', 'count_dep+1', false);
            $this->db->update('ecc_department_dep');
        }

        $updateFirstName = array('metaValue_empmeta' => $first_name);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'first_name');
        $this->db->update('ecc_employee_empmeta', $updateFirstName);

        $updatelastName = array('metaValue_empmeta' => $last_name);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'last_name');
        $this->db->update('ecc_employee_empmeta', $updatelastName);

        $updateposition = array('metaValue_empmeta' => $position);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'position');
        $this->db->update('ecc_employee_empmeta', $updateposition);

        $updateMobile = array('metaValue_empmeta' => $mobile);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'mobile');
        $this->db->update('ecc_employee_empmeta', $updateMobile);

        $updateMobile2 = array('metaValue_empmeta' => $mobile2);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'mobile2');
        $this->db->update('ecc_employee_empmeta', $updateMobile2);

        $updateHomePhone = array('metaValue_empmeta' => $homePhone);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'home_phone');
        $this->db->update('ecc_employee_empmeta', $updateHomePhone);

        $updateAddress = array('metaValue_empmeta' => $address);
        $this->db->where('idemp_empmeta', $object);
        $this->db->where('metaKey_empmeta', 'address');
        $this->db->update('ecc_employee_empmeta', $updateAddress);

        $updateEmail = array('email_emp' => $email);
        $this->db->where('id_emp', $object);
        $this->db->update('ecc_employees_emp', $updateEmail);
    }

    function updateProduct() {
        $object = $this->uri->segment(3);
        $name = $this->input->post('name');
        $description = $this->input->post('description');

        $updateMeta = array('metaValue_prodmeta' => $description);
        $this->db->where('idprod_prodmeta', $object);
        $this->db->where('metaKey_prodmeta', 'description');
        $this->db->update('ecc_product_prodmeta', $updateMeta);

        $update = array('name_prod' => $name);
        $this->db->where('id_prod', $object);
        $this->db->update('ecc_products_prod', $update);
    }

    function updateFile() {
        $this->load->model('upload_model');
        $object = $this->uri->segment(3);
        $name = $this->input->post('name');
        $userfile = $this->upload_model->upload_file($name);
        $file = $userfile['file_name'];
        $type = $userfile['file_type'];
        $size = $userfile['file_size'];
        $grade = 'a';
        //date
        $date = date('Y-m-d H:i:s', now());
        $errorMessage = $this->upload->display_errors();
        //check if image uploaded
        if ($errorMessage != true || !isset($errorMessage)) {
            $update = array('file_file' => $file, 'date_file' => $date, 'mimeType_file' => $type, 'fileSize_file' => $size,);
            $this->db->where('id_file', $object);
            $this->db->update('ecc_files_file', $update);
        }
        $update = array('name_file' => $name);
        $this->db->where('id_file', $object);
        $this->db->update('ecc_files_file', $update);
    }

    //update company
    function updateCompany() {

        $this->load->model('upload_model');
        $object = $this->uri->segment(3);
        $name = $this->input->post('name');
        $arName = $this->input->post('arName');
        $type = $this->input->post('type');
        $address = $this->input->post('address');
        $website = $this->input->post('website');


        $userfile = $this->upload_model->do_upload_company_logo($name);
        $logo = $userfile['file_name'];
        $errorMessage = $this->upload->display_errors();

        //check if image uploaded
        if ($errorMessage != true || !isset($errorMessage)) {
            $updatelogo = array('metaValue_cometa' => $logo);
            $this->db->where('idco_cometa', $object);
            $this->db->where('metaKey_cometa', 'logo');
            $this->db->update('ecc_companyMeta_cometa', $updatelogo);
        }

        $update = array('name_co' => $name, 'arName_co' => $arName);
        $this->db->where('id_co', $object);
        $this->db->update('ecc_company_co', $update);

        if ($type != 0) {
            $updateType = array('name_co' => $name, 'arName_co' => $arName, 'idcocat_co' => $type, 'status_co' => 'active');
            $this->db->where('id_co', $object);
            $this->db->update('ecc_company_co', $updateType);
        }

        $updateaddress = array('metaValue_cometa' => $address);
        $this->db->where('idco_cometa', $object);
        $this->db->where('metaKey_cometa', 'address');
        $this->db->update('ecc_companyMeta_cometa', $updateaddress);

        $updatewebsite = array('metaValue_cometa' => $website);
        $this->db->where('idco_cometa', $object);
        $this->db->where('metaKey_cometa', 'website');
        $this->db->update('ecc_companyMeta_cometa', $updatewebsite);
    }

    function updateDepartment() {
        $object = $this->uri->segment(3);
        $name = $this->input->post('name');
        $manager = $this->input->post('manager');

        $UpdateDep = array('name_dep' => $name);
        $this->db->where('id_dep', $object);
        $this->db->update('ecc_department_dep', $UpdateDep);

        if ($manager != 0) {

            $this->db->select('*');
            $this->db->from('ecc_managers_man');
            $this->db->where('iddep_man', $object);
            $checkMan = $this->db->get();
            if ($checkMan->num_rows() == '1') {
                //do nothing
            } else {
                //count
                //$this->db->where('id_dep', $object);
                //$this->db->set('count_dep', 'count_dep+1', false);
                //$this->db->update('ecc_department_dep');
            }

            //remove the current manager
            $this->db->where('iddep_man', $object);
            $this->db->delete('ecc_managers_man');

            $NewMan = array('iddep_man' => $object, 'idemp_man' => $manager);
            $this->db->insert('ecc_managers_man', $NewMan);
        }

        if ($manager != true || !isset($manager)) {
            $this->db->where('iddep_man', $object);
            $this->db->delete('ecc_managers_man');
            //count
            //$this->db->where('id_dep', $object);
            //$this->db->set('count_dep', 'count_dep-1', false);
            //$this->db->update('ecc_department_dep');
        }
    }

    function no_such_page() {
        echo 'No Such Page';
    }

    function calls_notification() {
        $this->db->select('*');
        $this->db->from('ecc_calls_call');
        $this->db->join('ecc_employees_emp', 'idemp_call = id_emp', 'inner');
        $this->db->where('callStatus_call', 'open');
        $this->db->where('status_call', 'active');
        $this->db->where('idemp_call', $this->id);
        $notification = $this->db->get();
        $this->getCallNotification = $notification->num_rows();
    }

    function getCompanyContactForEdit() {
        $data['co'] = $this->uri->segment(3);
        $data['level'] = $this->level;
        //$name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_companyContact_cocontact');
        $this->db->where('idco_cocontact', $data['co']);
        $data['getContact'] = $this->db->get();
        $this->load->view('boffice/getCompanyContactForEdit', $data);
    }

    function addCompanyContactForEdit() {

        $data['co'] = $this->uri->segment(3);
        $this->load->view('boffice/addCompanyContactForEdit.php', $data);
    }

    function getCompanyBranchForEdit() {
        $data['co'] = $this->uri->segment(3);
        //$name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_companyBranch_cobr');
        $this->db->where('idco_cobr', $data['co']);
        $data['getBranch'] = $this->db->get();
        $this->load->view('boffice/getCompanyBranchForEdit', $data);
    }

    function addCompanyBranchForEdit() {

        $data['co'] = $this->uri->segment(3);
        $this->load->view('boffice/addCompanyBranchForEdit.php', $data);
    }

    function assignAccount() {
        $data['company'] = $this->uri->segment(3);
        //insert drafted row
        $draft = array('status_acc' => 'draft');
        $this->db->insert('ecc_accounts_acc', $draft);
        $data['draftId'] = $this->db->insert_id();

        $this->db->select('*');
        $this->db->from('ecc_products_prod');
        $data['get_products'] = $this->db->get();

        $department = $this->department;

        if ($this->level == '1') {

            $this->db->select('*');
            $this->db->from('ecc_employees_emp');
            $this->db->join('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
            $this->db->join('ecc_department_dep', 'iddep_memper = id_dep ', 'inner');
            $this->db->where('level_emp != 1');
            //$this->db->where('name_dep', $department);
            $this->db->group_by('id_emp');
            $data['get_employees'] = $this->db->get();
        } elseif ($this->level == '3' && $this->manager == true) {

            $this->db->select('*');
            $this->db->from('ecc_employees_emp');
            $this->db->join('ecc_mempership_memper', 'id_emp = idemp_memper', 'inner');
            $this->db->join('ecc_department_dep', 'iddep_memper = id_dep ', 'inner');
            $this->db->where('level_emp != 1');
            $this->db->where('name_dep', $department);
            $this->db->group_by('id_emp');
            $data['get_employees'] = $this->db->get();
        }

        $this->load->view('boffice/assignAccount', $data);
    }

    function submitAccount() {
        $company = $this->uri->segment(3);
        $employee = $this->input->post('employee');
        //$product = $this->input->post('product');
        $date = date('Y-d-m H:i:s', now());
        $insertAccount = array(
            'idemp_acc' => $employee,
            'idco_acc' => $company,
            'status_acc' => 'active',
            'registeredDate_acc' => date('Y-m-d H:i:s', now())
        );
        $this->db->insert('ecc_accounts_acc', $insertAccount);
        $AccountId = $this->db->insert_id();
        //push
        //get employee
        $this->db->SELECT('*');
        $this->db->FROM('ecc_employee_empmeta');
        $this->db->WHERE('idemp_empmeta', $employee);
        $meta = $this->db->GET();

        $thisMeta = array();
        foreach ($meta->result() as $row2) {
            $thisMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
        }//meta

        $MetaEmp = array(
            'first_name' => $thisMeta['first_name'],
            'last_name' => $thisMeta['last_name'],
            'position' => $thisMeta['position'],
            'avatar' => $thisMeta['avatar'],
            'mobile' => $thisMeta['mobile']
        );
        $accOwnerName = $MetaEmp['first_name'] . " " . $MetaEmp['last_name'];

        //get employee
        $this->db->SELECT('*');
        $this->db->FROM('ecc_company_co');
        $this->db->WHERE('id_co', $company);
        $CoName = $this->db->GET();

        foreach ($CoName->result() as $coRow) {
            $coName = $coRow->name_co;
        };

        $push = array(
            'objectid_push' => $AccountId,
            'affectedEmp_push' => $employee,
            'creatorEmp_push' => $this->id,
            'affectedNameSpace_push' => $accOwnerName,
            'creatorNameSpace_push' => $this->name,
            'type_push' => 'accounts',
            'name_push' => $coName . ' just assigned for you as an account manager, good luck for you! we are waiting to sign the contract with them :)',
            'date_push' => date('Y-m-d H:i:s', now()),
            'status_push' => '1'
        );
        $this->db->insert('ecc_pusher_push', $push);

        //push notifications
        $this->db->select('*');
        $this->db->from('ecc_pusher_push');
        $this->db->where('status_push', '1');
        $this->db->where('affectedEmp_push', $employee);
        $getCount = $this->db->get();

        $PushNotification = array(
            'count' => $getCount->num_rows(),
            'name' => $coName . ' just assigned for you as an account manager, good luck for you! we are waiting to sign the contract with them :)'
        );
        $this->load->library('pusher');
        $this->pusher->trigger('notifications-' . $employee, 'notifications', $PushNotification);
    }

    function loadRelatedAccounts() {
        $data['level'] = $this->level;
        $data['manager'] = $this->manager;
        $data['department'] = $this->department;
        $data['company'] = $this->uri->segment(3);
        $this->load->view('boffice/loadRelatedAccounts', $data);
    }

    function getCallStatus() {
        $data['object'] = $this->uri->segment(3);
        $this->db->select('*');
        $this->db->from('ecc_calls_call');
        $this->db->where('id_call', $data['object']);
        $data['getCall'] = $this->db->get();


        $this->load->view('boffice/getCallStatus', $data);
    }

    /*     * *validation** */

    function checkMail() {
        $email = $this->input->post('email');
        $this->db->select('*');
        $this->db->from('ecc_employees_emp');
        $this->db->where('email_emp', $email);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validEmail.js', $data);
    }

    function checkFile() {
        $name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_files_file');
        $this->db->where('name_file', $name);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validFile.js', $data);
    }

    function checkDepName() {
        $name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_department_dep');
        $this->db->where('name_dep', $name);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validDepName.js', $data);
    }

    function checkCoName() {
        $name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_company_co');
        $this->db->where('name_co', $name);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validCompany.js', $data);
    }

    function checkProduct() {
        $name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_products_prod');
        $this->db->where('name_prod', $name);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validProduct.js', $data);
    }

    function checkCompanyContactMail() {
        $co = $this->uri->segment(3);
        $email = $this->input->post('email');
        $this->db->select('*');
        $this->db->from('ecc_companyContact_cocontact');
        $this->db->where('email_cocontact', $email);
        $this->db->where('idco_cocontact', $co);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validCompanyContact.js', $data);
    }

    function checkBranchName() {
        $co = $this->uri->segment(3);
        $name = $this->input->post('name');
        $this->db->select('*');
        $this->db->from('ecc_companyBranch_cobr');
        $this->db->where('name_cobr', $name);
        $this->db->where('idco_cobr', $co);
        $data['Valid'] = $this->db->get();

        $this->load->view('boffice/validCompanyBranch.js', $data);
    }

    function NotificationStatus() {

        //change status
        $this->db->set('status_push', '1');
        $this->db->where('affectedEmp_push', $this->id);
        $this->db->update('ecc_pusher_push');
    }

    /* push and pull */

    function pull() {
        /* login info
          www.pusher.com
          username: ahmed.saber@eccsolutions.net
          password: @ction#$@m
          app_id = '37571'
          key = '9e4a2b8b8abcd82218d0'
          secret = 'fa77e27fe0667870fc74'
         */
        $data['loggedIn'] = $this->id;
        $this->db->select('*');
        $this->db->from('ecc_pusher_push');
        $this->db->where('affectedEmp_push', $this->id);
        $this->db->order_by('id_push', 'DESC');
        $data['pull'] = $this->db->get();

        $this->load->view('boffice/pull', $data);
    }

    //**************************************************
    //**************************************************
    //**************************************************
    function credentials() {
        $this->id = $this->session->userdata('employeeid');
        $this->level = $this->session->userdata('level');
        $this->manager = $this->session->userdata('is_manager');
        $this->department = $this->session->userdata('department');
        $this->logged_username = $this->session->userdata('loggedin_user');
        $this->name = $this->session->userdata('name');
        $this->position = $this->session->userdata('position');
        $this->avatar = $this->session->userdata('avatar');
        $this->ForcePassword = $this->session->userdata('force_password');
        //check credentials
        $page = $this->uri->segment(2);
        //$method = $this->router->fetch_method();

        if ($this->level == 1 OR $this->level == 2 OR $this->manager == 1 OR ($this->level == 3 AND $this->manager == 1)) {
            $this->status = true;
        } elseif ($this->level == 3 OR $this->manager == 0) {
            $this->status = false;
            //echo "you don't have enough credientals";
        }
    }

    function is_logged_in() {
        $is_logged_in = $this->session->userdata('is_logged_in');
        if (!isset($is_logged_in) || $is_logged_in !== true) {

            redirect('login');
            //echo "you don\'t have permission to access this page login from here " . " <a href='". base_url() ."login'>please login first</a>";
            die();
        }
    }

    //logout
    function logout() {
        $this->session->sess_destroy();
        $this->load->view('boffice/login');
    }

}