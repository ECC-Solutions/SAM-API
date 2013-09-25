<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class samCloud extends CI_Controller {

    function calls() {
        
        
        $this->load->view('client/template/header');
        $this->load->view('client/template/calls');
        $this->load->view('client/template/footer');
    }

    function __construct() {
        parent::__construct();
        //helper
        $this->load->helper('date');
        $this->load->helper('text');

    }
    function initial() {
        
        $this->load->view('client/js/libs/require.js');
        
    }
    function mainApplicaion(){
        
        $this->load->view('client/js/main.js');
        
    }
    
    function index(){
      echo "s";  
      
    }
}