<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * jobber job board platform
 *
 * @author     Filip C.T.E. <http://www.filipcte.ro> <hide@address.com>
 * @license    You are free to edit and use this work, but it would be nice if you always referenced the original author ;)
 *             (see license.txt).
 * 
 * Sanitizer class cleans up stuff! 
 * taken from WordPress, I believe...
 */

class Sanitizer {

        function __construct()
        {
            // get the CI instance and store it class wide
            $this->CI =& get_instance();
        }
        
        public function sanitize($string){
	        
	        $string = nl2br($string);
			$string = str_replace('"',"'", $string);
			$string = str_replace('/',"-", $string);
			$string = str_replace('!',"-", $string);			
			$string = str_replace('\n',"-", $string);
			//$string = preg_replace("/[^0-9]+/","",$string);
			$string = preg_replace("/[^\p{L}\p{N}-]/u", ' ', $string);
			$string = trim($string);
			
			$string = str_replace(array("\r\n", "\r"), "\n", $string);
			$string = explode("\n", $string);
			$new_lines = array();
			
			foreach ($string as $i => $string) {
			    if(!empty($string))
			        $new_lines[] = trim($string);
			}
			$string = implode($new_lines);
			
			return $string;

        }

        public function SubStrThis($string, $count){
			$StringCount = strlen($string);
			if($StringCount >= $count){
				return substr($string, 0, $count)."...";	
			}else{
				return $string;
			}   
        }
		public function latestFeedback($call){	
			$this->CI->load->model('common');
			$latestFeedback = $this->CI->common->latestFeedback($call);
		 	return $latestFeedback;
		}          
		public function EmployeeMeta($EmpId){	
			$this->CI->load->model('common');
			$EmployeeMeta = $this->CI->common->employee($EmpId);
		 	return $EmployeeMeta;
		}        
		public function CallMeta($CallId){	
			$this->CI->load->model('common');
			$CallMeta = $this->CI->common->call($CallId);
		 	return $CallMeta;
		}
		public function CallFeedback($CallId){	
			$this->CI->load->model('common');
			$CallMeta = $this->CI->common->CallFeedback($CallId);
		 	return $CallMeta;
		}
}