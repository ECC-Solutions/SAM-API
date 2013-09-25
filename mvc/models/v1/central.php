<?php

class Central extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->helper('date');
    }

    function login($username, $password, $date) {
        //validation
        if (!isset($username) || !isset($password)) {
            $data = array(
                'status' => false,
                'authenticated' => false,
                'error' => 'Kindly ensure that you fullfil all required information in order to log you in',                
                'hint' => 'Some information is missing'                
            );
        } else {
            $chk_usr = $this->db->query("SELECT * FROM ecc_employees_emp JOIN ecc_mempership_memper ON id_emp = idemp_memper JOIN ecc_department_dep ON iddep_memper = id_dep WHERE email_emp = '{$username}' GROUP BY id_emp");
            if ($chk_usr->num_rows() == 1) {
                foreach ($chk_usr->result() as $row) {
                    if ($row->ban_emp == 1) {
                        //this user is banned
                        $currentDay = date('Y-m-d', now());
                        $last_attempt_day = date('Y-m-d', strtotime($row->failed_login_time_emp));
                        if ($currentDay == $last_attempt_day) {
                            //get difference between time
                            $diff = (strtotime(date('Y-m-d H:i:s', now())) - strtotime($row->failed_login_time_emp)) / 60;
                            //$diff = date('H.i',strtotime(date('H.i', now())-date('H.i', strtotime($row->failed_login_time_emp))));
                            if ($diff < 60) {
                                //ban for 1 hour
                                $data = array(
                                    'status' => false,
                                    'authenticated' => false,
                                    'error' => "Sorry, we are no longer accepting login attempts from this account due to many failed login attempts. <br /><br />Your account is disabled. You still have to wait for <b>" . round((60 - $diff), 0) . "</b> minutes",
                                    'hint' => "We're sorry to hear that you're experiencing an issue using <b>S.A.M Cloud</b>. <br /><br />While we protect our informations by disabling accounts that failed to login more than 5 times in short time. You still could contact administrator to re-enabling your account. :)"
                                );
                                return $data;
                            } else {
                                //try login again
                                if ($password == $row->password_emp) {
                                    $this->db->query("UPDATE ecc_employees_emp set failed_login_attempts_emp = failed_login_attempts_emp = 0,failed_login_time_emp = '0000-00-00 00:00:00', ban_emp = 0 WHERE id_emp = {$row->id_emp}");
                                    //check manager status
                                    $manager = $this->db->query("SELECT * FROM ecc_managers_man WHERE idemp_man = {$row->id_emp} && iddep_man = {$row->id_dep}");
                                    if ($manager->num_rows() == 1) {
                                        $isManager = true;
                                    } else {
                                        $isManager = 0;
                                    }
                                    //check employee meta
                                    $meta = $this->db->query("SELECT * FROM ecc_employee_empmeta WHERE idemp_empmeta = {$row->id_emp}");
                                    $employeeMeta = array();
                                    foreach ($meta->result() as $row2) {
                                        $employeeMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
                                    }
                                    $data = array(
                                        'status' => true,
                                        'authenticated' => true,
                                        'error' => 'logged in successfully',
                                        'employeeid' => $row->id_emp,
                                        'loggedin_user' => $username,                                        
                                        'is_manager' => $isManager,
                                        'level' => $row->level_emp, //level: admin:1, moderator:2, user:3, //master details username: master, password: #@dm!n
                                        'department' => $row->name_dep,
                                        'force_password' => $row->force_password_emp,
                                        'name' => $employeeMeta['first_name'] . " " . $employeeMeta['last_name'],
                                        'position' => $employeeMeta['position'],
                                        'avatar' => $employeeMeta['avatar']
                                    );
                                    return $data;
                                } else {
                                    $this->db->query("UPDATE ecc_employees_emp set ban_emp = 0 WHERE id_emp = {$row->id_emp}");
                                    //login attempts not equal 5 yet
                                    //rais failed attemp login +1
                                    $this->db->query("UPDATE ecc_employees_emp set failed_login_attempts_emp = failed_login_attempts_emp +1,failed_login_time_emp = '{$date}'  WHERE id_emp = {$row->id_emp}");
                                    $getAttempts = $this->db->query("SELECT * FROM ecc_employees_emp WHERE id_emp = {$row->id_emp}");

                                    foreach ($getAttempts->result() as $attempt) {
                                        $data = array(
                                            'status' => false,                                            
                                            'authenticated' => false,
                                            'error' => "<b>WRONG PASSWORD!</b>, <br />you have <b>" . (5 - $attempt->failed_login_attempts_emp) . "</b> login attempts left, unless your account would be banned.",
                                            'message' => "Please be careful when dealing with such senstive informations",
                                        );
                                    }
                                    return $data;
                                }
                            }
                        } else {
                            //account was banned from yesterday or later
                            //should be log in
                            //it cause errors
                        }
                        //end banned user
                    } else {
                        //start active user
                        //proceed to check password
                        if ($password == $row->password_emp) {
                            //correct password
                            $this->db->query("UPDATE ecc_employees_emp set failed_login_attempts_emp = failed_login_attempts_emp = 0,failed_login_time_emp = '0000-00-00 00:00:00', ban_emp = 0 WHERE id_emp = {$row->id_emp}");
                            //check manager status
                            $manager = $this->db->query("SELECT * FROM ecc_managers_man WHERE idemp_man = {$row->id_emp} && iddep_man = {$row->id_dep}");
                            if ($manager->num_rows() == 1) {
                                $isManager = true;
                            } else {
                                $isManager = 0;
                            }
                            //check employee meta
                            $meta = $this->db->query("SELECT * FROM ecc_employee_empmeta WHERE idemp_empmeta = {$row->id_emp}");
                            $employeeMeta = array();
                            foreach ($meta->result() as $row2) {
                                $employeeMeta[$row2->metaKey_empmeta] = $row2->metaValue_empmeta;
                            }
                                      
                            $data = array(
                                'status' => true,
                                'authenticated' => true,
                                'error' => 'logged in successfully',
                                'employeeid' => $row->id_emp,
                                'loggedin_user' => $username,
                                'is_manager' => $isManager,
                                'level' => $row->level_emp, //level: admin:1, moderator:2, user:3, //master details username: master, password: #@dm!n
                                'department' => $row->name_dep,
                                'force_password' => $row->force_password_emp,
                                'name' => $employeeMeta['first_name'] . " " . $employeeMeta['last_name'],
                                'position' => $employeeMeta['position'],
                                'avatar' => $employeeMeta['avatar']
                            );
                            return $data;
                        } else {
                            //wrong password
                            if ($row->failed_login_attempts_emp == 4) {
                                //ban user
                                $this->db->query("UPDATE ecc_employees_emp set failed_login_attempts_emp = failed_login_attempts_emp = 0, ban_emp = 1, failed_login_time_emp = '{$date}' WHERE id_emp = {$row->id_emp}");
                                $data = array(                                    
                                    'status' => false,
                                    'authenticated' => false,
                                    'error' => "Sorry, we are no longer accepting login attempts from this account due to many failed login attempts. <br /><br />Your account will be disabled for the next hour.",
                                    'hint' => "While we protect our informations by disabling accounts that failed to login more than 5 times in short time. You still could contact administrator to re-enabling your account. :)"
                                );
                                return $data;
                            } else {
                                //login attempts not equal 5 yet
                                //rais failed attemp login +1
                                $this->db->query("UPDATE ecc_employees_emp set failed_login_attempts_emp = failed_login_attempts_emp +1,failed_login_time_emp = '{$date}'  WHERE id_emp = {$row->id_emp}");
                                $getAttempts = $this->db->query("SELECT * FROM ecc_employees_emp WHERE id_emp = {$row->id_emp}");

                                foreach ($getAttempts->result() as $attempt) {
                                    if ($attempt->failed_login_attempts_emp == 4) {
                                        $data = array(                                            
                                            'status' => false,
                                            'authenticated' => false,
                                            'error' => "<b>Please wait!</b>, <br />You're about to running the last login attempt, Try to Make it succeed otherwise your account will be disabled for the next hour",
                                            'hint' => "Please be careful when dealing with such senstive informations",
                                        );
                                    } else {
                                        $data = array(                                            
                                            'status' => false,
                                            'authenticated' => false,
                                            'error' => "<b>WRONG PASSWORD!</b>, <br />you have <b>" . (5 - $attempt->failed_login_attempts_emp) . "</b> login attempts left, unless your account would be banned.",
                                            'hint' => "Please be careful when dealing with such senstive informations",
                                        );
                                    }
                                }
                                return $data;
                            }
                        }
                    }
                }
            } else {
                //no such username
                $data = array(                    
                    'status' => false,
                    'authenticated' => false,
                    'error' => 'No such username'
                );
                return $data;
            }
        }
    }

    /* Register employee
     * used when this verb called employee in the post method
     */

    function employee_post($firstName, $lastName, $mobile, $mobile2, $homePhone, $homeAddress, $department, $position, $email, $password) {
        //$date = date('Y-m-d', now());
        $query = "INSERT INTO ecc_employees_emp (email_emp, password_emp, status_emp, regsiteredDate_emp, level_emp, force_password_emp) VALUES (?, ?, ?, ?, ?, ?)";
        $do = $this->db->query($query, array($email, $password, 0, date('Y-m-d', now()), 3, 1));

        if ($do) {
            $id = $this->db->insert_id();
            $meta = array(
                array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'first_name',
                    'metaValue_empmeta' => $firstName
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'last_name',
                    'metaValue_empmeta' => $lastName
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'avatar',
                    'metaValue_empmeta' => $photo
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
                    'metaValue_empmeta' => $homePhone
                ), array(
                    'idemp_empmeta' => $id,
                    'metaKey_empmeta' => 'address',
                    'metaValue_empmeta' => $homeAddress
                )
            );
            $this->db->insert_batch('ecc_employee_empmeta', $meta);

            //assign employee to the department
            $query = "INSERT INTO ecc_mempership_memper (iddep_memper, idemp_memper) VALUES (?, ?)";
            $this->db->query($query, array($department, $id));
            //increase department members by 1
            $this->db->query("UPDATE ecc_department_dep set count_dep = count_dep+1 WHERE id_dep = {$department}");

            $department = $this->department_get($department);

            $data = array(
                'error' => FALSE,
                'message' => 'User registered successfully',
                'id' => $id,
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'mobile' => $mobile,
                'mobile2' => $mobile2,
                'homePhone' => $homePhone,
                'homeAddress' => $homeAddress,
                'department' => $department['name'],
                'position' => $position,
                'password' => 'Default password generated & you could ask the administrator for it. ' . $firstName . ' should change it after first succesful login'
            );
        } else {
            $data = array(
                'error' => TRUE,
                'message' => 'Error encountered',
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'mobile' => $mobile,
                'mobile2' => $mobile2,
                'homePhone' => $homePhone,
                'homeAddress' => $homeAddress,
                'department' => $department,
                'position' => $position,
            );
        }
        return $data;
    }

    function department_get($id) {
        $query = $this->db->query("SELECT * FROM ecc_department_dep WHERE id_dep = '{$id}'");
        if ($query->num_rows() === 1) {
            foreach ($query->result() as $row) {
                $data = array(
                    'error' => FALSE,
                    'id' => $row->id_dep,
                    'name' => $row->name_dep
                );
            }
            return $data;
        } else {
            $data = array(
                'error' => TRUE,
                'message' => 'no such department',
                'name' => 'no such department'
            );
            return $data;
        }
    }

    /* Get calls for current employee
     *
     */

    function calls_get($id, $manager, $department, $level) {

        if ($manager == true && $level == '3') {
            $query = $this->db->query("SELECT id_call, name_co, callStatus_call, dueDate_call, idemp_call FROM ecc_calls_call JOIN ecc_employees_emp ON idemp_call = id_emp JOIN ecc_company_co ON idco_call = id_co JOIN ecc_mempership_memper ON idemp_memper = idemp_call JOIN ecc_department_dep ON id_dep = iddep_memper WHERE status_call = 'active' && name_dep = {$department} ORDER BY id_call DESC");
            if ($query->num_rows() >= 1) {
                foreach ($query->result() as $row) {
                    $data[] = $row;
                }
                return $data;
            }
        } elseif ($level == 1) {
            $query = $this->db->query("SELECT id_call, name_co, callStatus_call, dueDate_call, idemp_call  FROM ecc_calls_call JOIN ecc_employees_emp ON idemp_call = id_emp JOIN ecc_company_co ON idco_call = id_co WHERE status_call = 'active' ORDER BY id_call DESC");
            if ($query->num_rows() >= 1) {               
                $data = array();
                $calls = array();
                foreach ($query->result() as $row) {
                    $data[] = array('callId' => $row->id_call, 'company' => $row->name_co, 'status' => $row->callStatus_call, 'DueDate' => $row->dueDate_call, 'EmployeeId' => $row->idemp_call);
                    $calls = $data;
                }
                return $calls;
            }
        } elseif ($level == 3) {
            $query = $this->db->query("SELECT id_call, name_co, callStatus_call, dueDate_call, idemp_call FROM ecc_calls_call JOIN ecc_employees_emp ON idemp_call = id_emp JOIN ecc_company_co ON idco_call = id_co WHERE status_call = 'active' && idemp_call = {$id} ORDER BY id_call DESC");
            if ($query->num_rows() >= 1) {
                foreach ($query->result() as $row) {
                    $data[] = $row;
                }
                return $data;
            }
        }
    }

}

