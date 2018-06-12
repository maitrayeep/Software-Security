<?php
/* Filter Class
	Following functions are made public:
	 - filter_Disallow($string)
	 - filter_HTMLEntityEncode($string)
	 - filter_For_Attributes($string)
	 - filter_For_Javascript($string)
	 - filter_For_CSS($string)
	 - filter_For_URL($string)
	 - filter_SQL_Escaping($string)
	 - filter_SQL_CompleteEscape($input)
	 - filter_Char2Num_id($string)
*/
class xss_sql_filter {

	private $input;  // input to the function containing string to be sanitized
	private $patterns = array(); 
	private $hex = array();
	private $rule_array = array(); // Array of patterns and replacements
	private $output; private $repl; private $rule; private $num;
		
	/*
	Sanitize  according to rule 0:->
		Never put untrusted data except in allowed locations.
	*/
	public function filter_Disallow($input){
		$this->output = '';
		return $this->output;
	}
	
	/*
	Sanitize  according to rule 1:->
		HTML Escape Before Inserting Untrusted Data into HTML Element Content.
		Escape special characters with entity encoding.
	*/
	public function filter_HTMLEntityEncode($input){
		$this->filter_it($input, 1);
		return $this->output;
	}
	
	/*
	Sanitize  according to rule 2:->
		Attribute Escape Before Inserting Untrusted Data into HTML Common Attributes.
		Enocding format : &#xHH;.
	*/
	public function filter_For_Attributes($input){
		$this->filter_it($input, 2);
		return $this->output;
	}
	
	/*
	Sanitize  according to rule 3:->
		JavaScript Escape Before Inserting Untrusted Data into JavaScript Data Values.
		Enocding format : \xHH.
	*/
	public function filter_For_Javascript($input){
		$this->filter_it($input, 3);
		return $this->output;
	}	

	/*
	Sanitize  according to rule 4:->
		CSS Escape And Strictly Validate Before Inserting Untrusted Data into HTML Style Property Values.
		Only use untrusted data in a property value and not into other places in style data.
		Enocding format : \HH.
	*/
	public function filter_For_CSS($input){
		$this->filter_it($input, 4);
		return $this->output;
	}	
	
	/*
	Sanitize  according to rule 5:->
		URL Escape Before Inserting Untrusted Data into HTML URL Parameter Values.
		Enocding format : %HH.
	*/
	public function filter_For_URL($input){
		$this->filter_it($input, 5);
		return $this->output;
	}
	
	
	/*
	Escaping for SQL attack:->
		Escaping each element if not alphanumeric.
	*/	
	public function filter_SQL_Escaping($input){
		$input = html_entity_decode($input, ENT_NOQUOTES, 'UTF-8');
		
		$replace = array(
			'\\' => '\\\\',
			"\x00" => "\\x00",
			"\x1a" => "\\x1a",
			"\n" => "\\n",
			"\r" => "\\r",
			"'" => "\'",
			'"' => '\"'
		);
		
		$output = strtr($input, $replace);
		return $output;
	}
	
	/*
	Escaping for SQL attack:->
		Escaping each element if not alphanumeric.
	*/	
	public function filter_SQL_CompleteEscape($input){
		$input = html_entity_decode($input, ENT_NOQUOTES, 'UTF-8');
		$arr = str_split($input);
		$output1 = '';
		foreach($arr as $i)
		{
			if (!ctype_alnum($i))
                $output1 .='\\'.$i;
            else
                $output1 .= $i;
        }       
        return $output1;
	}

	
	//Not allowing character in variables (such as $idfor cases such as SELECT name FROM users WHERE id = $id) 
	//causing unexpected outputs.
	public function filter_Char2Num_id($input){
		$input = html_entity_decode($input, ENT_NOQUOTES, 'UTF-8');
		if(!is_numeric($input))
			$input = 0;
		return $input;
		
	}
	
	//Function to filter input
	private function filter_it($input, $num ){
		$input = html_entity_decode($input, ENT_NOQUOTES, 'UTF-8');
		$this->ascii_gen($num);
		$this->output = $this->normal_replace($input); 
		//return $this->output;
	}
	
	//Function to generate ascii characters
	private function ascii_gen($num){
		$count = 0;
		
		for ($i=32;$i<=127;$i++){ 
			// Exclude Alphanumeric characters
			if (!ctype_alnum(chr($i))){
				$this->patterns[$count] = chr($i);
				$this->hex[$count] = bin2hex(chr($i));
				$count++;
			}
		}
		
		switch ($num){
						
			// Array of patterns for rule 1
			case 1:
				$this->rule_array = array(
					'&' => '&amp;',
					'"' => '&quot;',
					'<' => '&lt;',
					'>' => '&gt;',
					"'" => '&#x27;',
					'//' => '&#x2F;',
				);
				break;
				
			// Array of patterns for rule 2
			case 2:
				foreach($this->hex as &$value){
					$value = '&#x'.$value.';';
				}
				$this->rule_array = array_combine($this->patterns, $this->hex);
				break;
				
			// Array of patterns for rule 3	
			case 3:
				foreach($this->hex as &$value){
					$value = '\x'.$value;
				}
				$this->rule_array = array_combine($this->patterns, $this->hex);
				break;
			
			// Array of patterns for rule 4			
			case 4:
				foreach($this->hex as &$value){
					$value = '\\'.$value;
				}
				$this->rule_array = array_combine($this->patterns, $this->hex);
				break;
			
			// Array of patterns for rule 5
			case 5:
				foreach($this->hex as &$value){
					$value = '%'.$value;
				}
				$this->rule_array = array_combine($this->patterns, $this->hex);
				break;
				
			default:
				print_r('Value should be between 1 to 5');
				break;
		}

	}
	
	//Function to replace unallowed characters
	private function normal_replace($ip){		
		$ip = strtr($ip,$this->rule_array);		
		return $ip;					
	} 
}

?>