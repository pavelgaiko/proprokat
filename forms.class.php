<?php 
/*



*/
class forms
{
	private $fid;
	private $db;
	private $form_name;
	private $form_data;

	function __construct($fid) {
		//TODO ::::: Ошибка если fid не число
		is_numeric($fid) ? $this->fid = $fid : die(); 
		$this->db = new PDO('mysql:host=localhost;dbname=iphone;charset=utf8', 'root', 'pass');
		$this->db->query("SET NAMES 'utf8'"); 
		$this->load_form_info();
	}	

	private function load_form_info() {

		$form = $this->db->prepare("SELECT * FROM forms WHERE fid = ? LIMIT 1");
		$form->execute(array($this->fid));
		$form = $form->fetchObject();
		//TODO ::::: проверка на существование формы.
		$this->form_name = $form->name;
		$this->form_data = (object)unserialize($form->data);
		$this->fields = $this->load_form_fields();
		
		return $form;
	}

	public function load_form_fields() {

		$fields = $this->db->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_weight");
		$fields->execute(array($this->fid));
		$fields = $fields->fetchAll(PDO::FETCH_OBJ);
		return $fields;
	}

	public function save_form() {

		foreach ($this->fields as $value) {
			if (!empty($_POST[$value->field_name])){
				$output['qString'][] = $value->field_name. " = ?";
				$output['values'][] = $_POST[$value->field_name];
			}
		}
		$output['qString'] = implode(",", $output['qString']);
		$ins = $this->db->prepare("INSERT INTO ".$this->form_data->save_table." SET p_ins_date = NOW(), p_upd_date = NOW(), ".$output['qString']);
		$ins->execute($output['values']);
	}

	/*
			В колонку data в таблице описания полей form_fields передается наименование функции которая возвращает список для полей 
		типа select. Передается массив array('selectlist' => имя функции); 
			Если данных не передано и массив дата не содержит информации, то применяется стандарная обработка списков, 
		которая предполагает что в поле значения список записан в строку через разделитель ','; 
			get_options получает этот список и возвращает HTML код для select.
	*/
	private function get_options($field) {
		$data = $field->attributes->selectlist;
		if(!empty($data) && is_callable($data)) {
			$data = $data();
			if(is_array($data)) {
				return $this->get_simpleOptions($data);
			}
		} else {
			if ($field->field_value) {
				return $this->get_simpleOptions(explode(",", $field->field_value));
			}
			return false;
		}
		
	}

	private function get_simpleOptions($data) {
		$html = '';
		array_walk($data, function($v, $k) use (&$html) {$html .= "<option value=\"$k\">$v</option>";});
		return $html;
	}

	private function get_data($field) {
		$attributes;
		$data = unserialize($field->data);
		if (is_array($data))
		{
			foreach ($data as $key => $value) {
				switch ($key) {
					case 'class':
						$attributes->class = ' class='.implode(",", $value);
					break;
					case 'id':
						$attributes->id = ' id='.$value;
					break;
					case 'selectlist':
						$attributes->selectlist = $value;
					break;
					case 'placeholder':
						$attributes->placeholder = $value;
					break;
					default:
						$attributes = array();
					break;
				}
			}
			return $attributes;
		}
		return false;
	}

	public function preload_form() {
		foreach ($this->fields as $value) {	
			$this->form[$value->field_name] = (object)array(
				'field_type' => $value->field_type,
				'field_name' => $value->field_name,
				'field_title' => $value->field_title,
				'field_value' => $value->field_value,
				'fid' => $this->fid,
				'attributes' => $this->get_data($value),
			);
		}
		return $this;
	}
	// создание формы
	public function load_form(){
		$html = "<form class="." \"form-autogeneration form-item-" . $this->fid ."\" method='".$this->form_data->method."' action=".$this->form_data->action." >";
		foreach ($this->form as $value) {
			switch($value->field_type){
				case "textarea":
					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_type." form-input-".$this->fid."\">".$value->field_title."</label>".
					"<textarea id ="."\"form-input-".$value->field_type." form-input-".$this->fid."\" name=" . $value->field_name . " cols = 40 rows = 10></textarea></div>";
				break;

				case "checkboxes":
					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_type." form-input-".$this->fid."\">".$value->field_title."</label>".
					"<input id ="."\"form-input-".$value->field_type." form-input-".$this->fid."\" name=" . $value->field_name . " type=checkbox>".$value->field_value."</div>";
				break;	

				case "select":
					$html .= "<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_type." form-input-".$this->fid."\">".$value->field_title."</label>".
					"<select id ="."\"form-input-".$value->field_type." form-input-".$this->fid."\" name=" . $value->field_name .">";
					$html .= $this->get_options($value);
					$html .= "</select></div>";

				break;

				case "file":
					!empty($value->attributes->class) ? $class = $value->attributes->class : $class = '';
					!empty($value->attributes->id) ? $id = $value->attributes->id : $id = '';
					!empty($value->attributes->data) ? $data = ' data='.$value->attributes->data : $data = '';
					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_type." form-input-".$this->fid."\">".$value->field_title."</label>".
					"<input id ="."\"form-input-".$value->field_type."\" name=" . $value->field_name . " type=" . $value->field_type .">
					<input type='submit'$class$id$data name='upload' value = 'Загрузить'></div>";
				break;

				case "text":
					!empty($value->attributes->placeholder) ? $placeholder = $value->attributes->placeholder : $placeholder = '';
					!empty($value->attributes->class) ? $class = $value->attributes->class : $class = '';
					!empty($value->attributes->id) ? $id = $value->attributes->id : $id = '';
					!empty($value->attributes->data) ? $data = ' data='.$value->attributes->data : $data = '';				
					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_type." form-input-".$this->fid."\">".$value->field_title."</label>".
					"<input id ="."\"form-input-".$value->field_type." form-input-".$this->fid."\" name=" . $value->field_name . "$class$placeholder type=" . $value->field_type ."></div>";
				break;

				case "hidden":
					!empty($value->attributes->data) ? $data = ' data='.$value->attributes->data : $data = '';
					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<input id ="."\"form-input-".$value->field_type." form-input-".$this->fid."\" value=".$value->field_value." name=" . $value->field_name . " type=" . $value->field_type ."></div>";
				break;

				case "p_ins_date, p_upd_date":
				break;

				default:
					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_type." form-input-".$this->fid."\">".$value->field_title."</label>".
					"<input id ="."\"form-input-".$value->field_type." form-input-".$this->fid."\" name=" . $value->field_name . " type=" . $value->field_type ."></div>";

			}

		}
		return $html . "<input type='submit' name='news' value = 'Сохранить'></form>";
	}	

}