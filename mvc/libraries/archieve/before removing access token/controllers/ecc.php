<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class ecc extends CI_Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        //check if there any available vacancies
        echo "API Working Succefully";
    }

}