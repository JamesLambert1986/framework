<?php
class form
{ 
    public function __construct($request,$data) 
    {
		$this->request = $request;
		$this->data = $data;
		$this->passed = true; // pass or fail
		
		
		
		$this->data = $this->search_data($data,$request);
		
    }
	
	private function search_data($data){
		
		foreach($data as $index => $value) {

			if(is_array($value) && isset($value['form_id']) && $value['form_id'] == $this->request['form_id']){
				
				$data[$index] = $this->process_form($value);
				break;
			}
			else if(is_array($value)){
				
				$data[$index] = $this->search_data($value);
			}
		}
		
		
		return $data;
	}
	
	
	private function process_form($data){
		
		// Find out the form type, this will be used later to select what we do with the data
		$form_type = "contact";
		
		if(isset($data['form_type']) && is_string($data['form_type'])){
			
			$form_type = $data['form_type'];
		}
		else if(isset($data['form_type']) && is_array($data['form_type'])){
			
			$form_type = $data['form_type'][0];
		}
		
		
		// Find the fields 
		foreach($data as $index => $value)
		{
			if(is_array($value) && isset($value['_type']) && $value['_type'] == "element_group" && isset($value['_elements']))
			{
				foreach($value['_elements'] as $sub_index => $field)
				{
					$value['_elements'][$sub_index] = $this->process_field($field);
				}

				$data[$index] = $value;
			}
		}

		// TO DO do somethin g with the form, i.e. send email
		
		if($this->passed == true)
		{
			// send email if a recipient is found
			$email_to = array();
			if(isset($data["form_recipient"]) && !empty($data["form_recipient"]))
			{
				$recipients = array();
				if(is_string($data["form_recipient"]))
					$recipients = explode(',', $data["form_recipient"]);
				else if(is_array($data["form_recipient"]))
					$recipients = $data["form_recipient"];
				
				foreach($recipients as $recipient)
				{
					if(preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $recipient))
					{
						$email_to[] = trim($recipient);
					}
				}
			}
			
			$message = "";
			foreach($this->request as $key => $value)
			{
				if(!in_array($key,array("form","form_id","form_type","submit")))
				{
					if(is_array($value))
						$value = implode(", ", $value);

					$message .= $key . " : " . $value . "\n\r";
				}
			}

			$subject = (isset($data["form_subject"])) ? $data["form_subject"] : '';

			if(!empty($email_to) && !empty($message) && !empty($subject))
			{
				// send email
				$result = wp_mail($email_to, $subject, $message);
				
				if(!$result)
					$data["_error_msg"] = "failed to send email.";
			}
			
			if(!isset($data["_error_msg"]))
				$data["_completed"] = 'yes';
		}
		
		return $data;
	}
	
	private function process_field($arr_field)
	{
		$field_id = $arr_field['id']; // get form field id
		$field_value = (!empty($this->request[$field_id])) ? $this->request[$field_id] : ''; // get form field value
		$field_type = (isset($arr_field['_name'])) ? $arr_field['_name'] : ''; // form field element type (input,textarea,checkbox)
		$field_required = (isset($arr_field['required']) && $arr_field['required'] == 'yes') ? true : false;

		// Check if it is a required field
		if($field_required && empty($field_value))
		{
			$arr_field["_error_msg"] = "Field required";
		}

		// check to see if form field is valid
		switch($field_type)
		{
			case 'input' :
			case 'textarea' :
			
				$input_type = (isset($arr_field['type'])) ? $arr_field['type'] : 'text';

				switch($input_type)
				{
					case 'email' :

					// Validate an email
					if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $field_value))
					{
						if(empty($arr_field["_error_msg"]))
						{
							$arr_field["_error_msg"] = "Email address required";
						}
					}

					break;
				}
				
				$arr_field['_value'] = $field_value; // update form value
			
			break;
		}

		// Check the validation
		if(!empty($arr_field["_error_msg"]))
		{
			$arr_field["class"] = "error";
			$this->passed = false;
		}
		
		return $arr_field;
	}
}
?>