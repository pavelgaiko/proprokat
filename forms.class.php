<?php 
/* 
	Объединить классы Editor & Constructor, реализовать как расширение класса Forms
*/

class Editor
{
	private $form;

	function __construct($form) {		
		if (get_class($form) == 'Forms') {
			$this->form = $form;
		}
		else die();
	}


	public function load_form_editor() {
		print '<form id="editor" method="post" action="/t/test">';
		print '<input type="hidden" name="form_id" value="'.$this->form->fid.'">';
		$this->render_attributes();
		$this->render_fields();
		print '<input type="submit" value="save"></form>';
	}

	private function render_attributes() {
		$html = '<div class="row form-field">';
		$html .= '<div class="head">Edit form ['.$this->form->form_name.']</div>';
		foreach ($this->form->form_data as $key => $value) {
			$html .= '<p>';
			$html .= '<label for="data['.$key.']">'.$key.':</label>';
			$html .= '<input name="data['.$key.']" type="text" id="data['.$key.']" value="'.$value.'">';
			$html .= '</p>';	
		}
		$html .= '</div>';
		print $html;
	}

	private function render_fields() {
		foreach ($this->form->fields as $field) {
			$this->render_fields_field($field);
		}
	}

	private function render_fields_field($field) {
		
		$html = '<div class="row form-field">';
		$html .= '<div class="head">['.$field->field_name.']</div>';
		$html .= '<div>';
		$html .= '<label for="'.$field->field_name.'[field_title]">Метка поля:</label>';
		$html .= '<input name="'.$field->field_name.'[field_title]" type="text" id="'.$field->field_name.'[field_title]" value="'.$field->field_title.'">';
		$html .= '</div>';
		$html .= '<div>';
		$html .= '<label for="'.$field->field_name.'[field_type]">Тип поля:</label>';
		$html .= '<select name="'.$field->field_name.'[field_type]" type="text" id="'.$field->field_name.'[field_type]">';
		foreach ($this->form->available_field_types as $type) {
			($field->field_type == $type) ? $selected = 'selected' : $selected = '';
			$html .= '<option '.$selected.'>'.$type.'</option>';	
		}
		$html .= '</select>';
		$html .= '</div>';
		$html .= '<div>';


		$html .= '<label for="'.$field->field_name.'[field_value]">Значение:</label>';
		$html .= '<input name="'.$field->field_name.'[field_value]" type="text" id="'.$field->field_name.'[field_value]" value="'.$field->field_value.'">';
		$html .= '</div>';
		$html .= '<div>';
		$html .= '<label for="'.$field->field_name.'[field_weight]">Вес:</label>';
		$html .= '<select name="'.$field->field_name.'[field_weight]" type="text" id="'.$field->field_name.'[field_weight]">';
		for ($i=-10; $i < 10; $i++) { $html .= "<option>$i</option>"; }
		$html .= '</select>';
		$html .= '</div>';		
		$html .= '</div>';
		print $html;
	}

	public function post_preprocess($post) {
		// print_r($post);
		switch ($post['target']) {
			case 'field':
				
				break;
			
			case 'form':
				
				break;
		}
	}
}

/* Добавить checkbox (Full html / Plain text) */

class Constructor
{
	private $db;
	private $tables;
	private $table_name;
	private $fields;
	private $post;

	function __construct($table_name = null) {		
		$this->db = new PDO('mysql:host=localhost;dbname=prestigemotors;charset=utf8', 'root', 'pass');
		$this->db->query("SET NAMES 'utf8'");
		if (is_null($table_name)) {
			$this->load_tables();	
			$this->render_table_selector();
		}
		else {
			$this->table_name = $table_name;	
			$this->load_constructor();
		}
	}

	private function load_tables($db = 'prestigemotors') {
		try {
			$tables = $this->db->prepare("SHOW TABLES FROM $db");
			$tables->execute();
			$tables = $tables->fetchAll(PDO::FETCH_UNIQUE);
			$tables = array_keys($tables);

			$this->tables = $tables;
		}
		catch (PDOException $e) {
			print $e;
		}
	}

	private function load_constructor() {
		try {
			$fields = $this->db->prepare("SHOW COLUMNS FROM $this->table_name");
			$fields->execute();
			$this->fields = $fields->fetchAll(PDO::FETCH_COLUMN, 0);
			// print_r($fields);
			// $this->fields = array_keys($fields);	
			// print_r($this->fields);
			$this->render_constructor();
		}
		catch (PDOException $e) {
			print $e;
		}
	}

	public function save_form($post) {
		$ins_form = $this->db->prepare("INSERT INTO forms SET name = ?, data = ?");
		$ins_form->execute(array($post['name'], serialize($post['data'])));
		$id = $this->db->lastInsertId();
		foreach ($post['fields'] as $key => $value) {
			if (isset($value['enabled'])) {
				unset($value['enabled']);
				$value['data'] = serialize($value['data']);
				$qs['fields'] = implode(' = ?,', array_keys($value));
				$qs['values'] = array_values($value);
				$ins_fields = $this->db->prepare("INSERT INTO form_fields SET ".$qs['fields']." = ?, form_id = ".$id);
				$ins_fields->execute($qs['values']);
			}
		}
	}

	private function render_table_selector() {
		$html = '<form id="form_constructor" action="/t/test" method="POST">';
		$html .= '<input type="hidden" name="constructor_step" value="select_table">';
		$html .= '<label for="table_selector">Выберите таблицу:</label>';
		$html .= '<select name="table_selector" id="table_selector">';
		foreach ($this->tables as $key => $value) {
			$html .= '<option>'.$value.'</option>';		
		}
		$html .= '</select>';
		$html .= '<input type="submit" value="next"></form>';
		print $html;
	}

	private function render_constructor() {
		$html = '<form id="form_constructor" action="/t/test" method="POST">';
		$html .= '<input name="constructor_step" type="hidden" value="add_fields">';
		$html .= '<div class="Constructor">';
		$html .= '<p>';
		$html .= '<label for="form-name">Метка формы:</label>';
		$html .= '<input name="name" type="text" id="form-name">';
		$html .= '<p>';
		$html .= '<label for="form_prefix">Prefix:</label>';
		$html .= '<input name="data[prefix]" type="text" id="form_prefix">';
		$html .= '<p>';
		$html .= '<label for="form_suffix">Suffix:</label>';
		$html .= '<input name="data[suffix]" type="text" id="form_suffix">';
		$html .= '<p>';
		$html .= '<label for="form-action">Action:</label>';
		$html .= '<input name="data[attributes][action]" type="text" id="form-action">';
		$html .= '<p>';
		$html .= '<label for="form-method">Method:</label>';
		$html .= '<select name="data[attributes][method]" id="form-method">';
		$html .= '<option>POST</option>';		
		$html .= '<option>GET</option>';		
		$html .= '</select>';
		$html .= '</p>';
		$html .= '<p>';
		$html .= '<a class="add_attributes" data="data[attributes]" href="#">Add attributes</a>';
		$html .= '</p>';
		$html .= '<input name="data[attributes][save_table]" type="hidden" value="'.$this->table_name.'">';
		foreach ($this->fields as $key => $value) {
			$html .= '<div class="row form-field">';
			$html .= '<div class="head"><input type="checkbox" name="fields['.$value.'][enabled]"> '.$value.' </div>';
			$html .= '<input name="fields['.$value.'][field_name]" type="hidden" value="'.$value.'">';
			$html .= '<p>';
			$html .= '<label for="field_title-'.$value.'">Метка поля:</label>';
			$html .= '<input name="fields['.$value.'][field_title]" type="text" id="field_title-'.$value.'">';
			$html .= '<p>';
			$html .= '<label for="field_type-full_html">Full HTML </label>';
			$html .= '<input id="field_type-full_html" type="checkbox" name="fields['.$value.'][data][attributes][full_html]"> ';
			$html .= '<p>';
			$html .= '<label for="field_type-'.$value.'">Тип поля:</label>';
			$html .= '<select name="fields['.$value.'][field_type]" type="text" id="field_type-'.$value.'">';
			$html .= '<option>textarea</option>';		
			$html .= '<option>checkbox</option>';		
			$html .= '<option>select</option>';		
			$html .= '<option>file</option>';		
			$html .= '<option>text</option>';		
			$html .= '<option>hidden</option>';		
			$html .= '<option>meta</option>';
			$html .= '<option>date</option>';		
			$html .= '</select>';
			$html .= '<p>';
			$html .= '<label for="field_value-'.$value.'">Значение:</label>';
			$html .= '<input name="fields['.$value.'][field_value]" type="text" id="field_value-'.$value.'">';
			$html .= '<p>';
			$html .= '<label for="field_weight-'.$value.'">Вес:</label>';
			$html .= '<select name="fields['.$value.'][field_weight]" type="text" id="field_weight-'.$value.'">';
			for ($i=-10; $i < 10; $i++) { $html .= "<option>$i</option>"; }
			$html .= '</select>';
			$html .= '<p>';
			$html .= '<label for="field_prefix-'.$value.'">Prefix:</label>';
			$html .= '<input name="fields['.$value.'][data][prefix]" type="text" id="field_prefix-'.$value.'">';
			$html .= '<p>';
			$html .= '<label for="field_suffix-'.$value.'">Suffix:</label>';
			$html .= '<input name="fields['.$value.'][data][suffix]" type="text" id="field_suffix-'.$value.'">';
			$html .= '<p>';
			$html .= '<a class="add_attributes" data="fields['.$value.'][data][attributes]" href="#">Add attributes</a>';
			$html .= '</div>';
		}
		$html .= '</div><input type="submit">';
		$html .= '</form>';
		print $html;
	}
}

/* 	Новая версия класса форм.
	Поля - объекты класа Field
	Аттрибуты формы - объект типа FormsData

	Доработать: 
	-загрузку полей при создании объекта; +
	-рендер формы;	+
	-сохранение данных(вставка-изменение);
*/

class Form
{
	private $db;
	public $id;
	public $name;
	public $data;
	public $fields;

	function __construct($id = null) {			
		
		$this->db = new PDO('mysql:host=localhost;dbname=prestigemotors;charset=utf8', 'root', 'pass');
		$this->db->query("SET NAMES 'utf8'");

		if ($id) {
			$this->load($id);
			// $this->data = new FormsData($this->data);
		}
		else {
			// $this->create_empty();
		}
	}

	private function load($id) {
		try {
			$form = $this->db->prepare("SELECT * FROM forms WHERE id = ? LIMIT 1");
			$form->execute(array($id));
			$form = $form->fetchObject();
			// print_r($form);
			if ($form) {
				$this->id = $form->id;
				$this->name = $form->name;
				$this->data = new FormsData($form->data);

				$fields = $this->db->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_weight");
				$fields->execute(array($this->id));
				$fields = $fields->fetchAll(PDO::FETCH_OBJ);
				// print_r($fields);
				foreach ($fields as $key => $value) {
					$this->fields[] = new Field($value);
				}
			}
			// else die();
		}
		catch (PDOException $e) {
			print $e;
		}
	}

	// public function load_forms_list() {
	// 	try {
	// 		$this->forms_list = $this->db->prepare('SELECT * FROM forms');
	// 		$this->forms_list->execute();
	// 		$this->forms_list = $this->forms_list->fetchAll(PDO::FETCH_OBJ);
	// 	}
	// 	catch (PDOException $e) {
	// 		print $e;
	// 	}
	// }

	public function save_data() {
		$output = $this->preprocess_save_data();
		$ins = $this->db->prepare("INSERT INTO ".$this->data->attributes['save_table']." SET ".$output['qString']);
		$ins->execute($output['values']);
	}



	private function preprocess_save_data() {
		print_r($_POST);
		foreach ($this->fields as $key => $value) {
			switch ($value->field_type) {
				case 'meta':
					if ($_POST[$value->field_name]) {
						$output['qString'][] = $value->field_name. " = ?";						
						$output['values'][] = serialize($_POST[$value->field_name]);
					}
					break;
				
				default:
					if (!empty($_POST[$value->field_name])){
						$output['qString'][] = $value->field_name. " = ?";
						(@$value->data->attributes['full_html']) ? $output['values'][] = $_POST[$value->field_name] : $output['values'][] = htmlspecialchars($_POST[$value->field_name]);
						// $output['values'][] = $_POST[$value->field_name];
						// print $value->field_name."\n";
					}
					break;
			}
		}
		$output['qString'] = implode(",", $output['qString']);
		return $output;
	}

	public function render($name = null, $value_button_name = "Send") {
		print $this->data->prefix;
		print '<form ';
		foreach ($this->data->attributes as $key => $value) {
			print " $key=\"$value\"";
		}
		print '><input type="hidden" name="form_id" value="'.$this->id.'">';
		foreach ($this->fields as $field) {
			$field->renderField();
		}
		if ($name == null){
			print '<input type="submit" value="'.$value.'"></form>';		
		}
		else{
			print '<input type="submit" name = "'.$name.'" value="'.$value_button_name.'"></form>';
		}
		
		print $this->data->suffix;
	}
}

class Field
{
	public $id;
	public $form_id;
	public $field_title;
	public $field_name;
	public $field_type;
	public $field_value;
	public $field_weight;
	public $data;
	public $available_field_types;

	function __construct($field = null) {			
		if ($field) {
			// $this = $field;	
			$this->id = $field->id;
			$this->form_id = $field->form_id;
			$this->field_title = $field->field_title;
			$this->field_name = $field->field_name;
			$this->field_type = $field->field_type;
			$this->field_value = $field->field_value;
			$this->field_weight = $field->field_weight;
			$this->data = new FormsData($field->data);
		}
		else {
			$this->create_empty();
		}
		$this->available_field_types = array('hidden' ,'text', 'textarea', 'checkbox', 'select', 'radio', 'file', 'meta', 'date');
	}



	private function emptyField() {
		$this->id = '';
		$this->form_id = '';
		$this->field_title = '';
		$this->field_name = '';
		$this->field_type = '';
		$this->field_value = '';
		$this->field_weight = '';
		$this->data = new FormsData(null, 'newEmptyField');
	}

	//TODO Отработать рендер атрибутов (select и далее)

	public function renderField() {

		if (@$this->data->attributes['class']) { $class = $this->data->attributes['class']; unset($this->data->attributes['class']);}  
		else $class = $this->field_name;
		
		if (@$this->data->attributes['id']) {$id = $this->data->attributes['id']; unset($this->data->attributes['id']);}
		else $id = $this->field_name;
		
		if (@$this->data->attributes['name']) {$name = $this->data->attributes['name']; unset($this->data->attributes['name']);} 
		else $name = $this->field_name;

		$html = $this->data->prefix;
		switch($this->field_type){
				case "textarea":
					$html .= "<label class=\"form-textarea $class\" for=\"$id\">$this->field_title</label>";
					$html .= "<textarea id =\"$id\" class=\"form-textarea $class\" name=\"$name\"";
					foreach (@$this->data->attributes as $key => $value) {
						$html .= " $key=\"$value\"";
					}
					$html .= ">$this->field_value</textarea>";
				break;

				case "checkbox":
					$html .= "<label class =\"form-checkbox ".$class."\" for=\"$id\">".$this->field_title."</label>";
					$html .= "<input id=\"$id\" class=\"form-checkbox $class\" name=\"$name\" type=checkbox ";
					foreach (@$this->data->attributes as $key => $value) {
						$html .= " $key=\"$value\"";
					}
					$html .= ">".$this->field_value;
				break;	

				case "select":
					$html .= "<label class=\"form-label ".$class."\" for=\"$id\">".$this->field_title."</label>";
					$html .= "<select id=\"$id\" class=\"$class\" name=\"$name\"";
					foreach (@$this->data->attributes as $key => $value) {
						$html .= " $key=\"$value\"";
					}
					$html .= ">";
					foreach (explode(',', $this->field_value) as $option) {
						$html .= "<option>\"$option\"</option>";
					}
					$html .= "</select>";

				break;

				case "file":
					$html .= "<label class=\"form-label $class\" for=\"$id\">".$this->field_title."</label>";
					$html .= "<input id =\"$id\" class=\"form-file $class\" name=\"$name\" type=\"file\"";
					foreach (@$this->data->attributes as $key => $value) {
						$html .= " $key=\"$value\"";
					}
					$html .= ">";
					$html .= "<input type=\"submit\" name=\"upload\" value=\"Загрузить\">";
				break;

				case "hidden":
					$html .= "<input value=\"$this->field_value\" name=\"$name\" type=\"hidden\">";
				break;

				case "text":
					$html .= "<label class=\"form-label $class\" for=\"$id\">".$this->field_title."</label>";
					$html .= "<input id =\"$id\" class=\"form-text $class\" name=\"$name\" type=\"text\"";
					foreach (@$this->data->attributes as $key => $value) {
						$html .= " $key=\"$value\"";
					}
					$html .= ">";
				break;

				case "date":
					$html .= "<input name=\"$name\" type=\"hidden\" value=\"".date('Y-m-d H:i:s')."\">";
				break;

				case "meta":

					$html .= "<div>$this->field_title</div>";
					$html .= "<ul class=\"meta\"><li>";
					$html .= "<label class=\"form-label-title $class\" for=\"title-$id\">Title</label>";
					$html .= "<textarea id =\"title-$id\" class=\"form-meta meta $class\" name=\"".$name."[title]\" type=\"text\"></textarea>";
					$html .= "</li><li>";
					$html .= "<label class=\"form-label-keywords $class\" for=\"keywords-$id\">Keywords</label>";
					$html .= "<textarea id =\"keywords-$id\" class=\"form-meta meta $class\" name=\"".$name."[keywords]\" type=\"text\"></textarea>";
					$html .= "</li><li>";
					$html .= "<label class=\"form-label-description $class\" for=\"description-$id\">Description</label>";
					$html .= "<textarea id =\"description-$id\" class=\"form-meta meta $class\" name=\"".$name."[description]\" type=\"text\"></textarea>";
					$html .= "</li></ul>";
					// foreach (@$this->data->attributes as $key => $value) {
					// 	$html .= " $key=\"$value\"";
					// }
				
				break;

				default:
					$html .= "<label class=\"form-label $class\" for=\"$id\">".$this->field_title."</label>";
					$html .= "<input id =\"$id\" class=\"form-$this->field_type $class\" name=\"$name\" type=\"$this->field_type\"";
					foreach (@$this->data->attributes as $key => $value) {
						$html .= " $key=\"$value\"";
					}
					$html .= ">";
			}
		$html .= $this->data->suffix;
		print $html;
	}
	/**/
	public function constructorRenderField() {
		$html .= '<div class="head">['.$this->field_name.']</div>';
		$html .= '<div>';
		$html .= '<label for="'.$this->field_name.'[field_title]">Метка поля:</label>';
		$html .= '<input name="'.$this->field_name.'[field_title]" type="text" id="'.$this->field_name.'[field_title]" value="'.$this->field_title.'">';
		$html .= '</div>';
		$html .= '<div>';
		$html .= '<label for="'.$this->field_name.'[field_type]">Тип поля:</label>';
		$html .= '<select name="'.$this->field_name.'[field_type]" type="text" id="'.$this->field_name.'[field_type]">';
		foreach ($this->available_field_types as $type) {
			($this->field_type == $type) ? $selected = 'selected' : $selected = '';
		$html = '<div class="row form-field">';
			$html .= '<option '.$selected.'>'.$type.'</option>';	
		}
		$html .= '</select>';
		$html .= '</div>';
		$html .= '<div>';


		$html .= '<label for="'.$this->field_name.'[field_value]">Значение:</label>';
		$html .= '<input name="'.$this->field_name.'[field_value]" type="text" id="'.$this->field_name.'[field_value]" value="'.$this->field_value.'">';
		$html .= '</div>';
		$html .= '<div>';
		$html .= '<label for="'.$this->field_name.'[field_weight]">Вес:</label>';
		$html .= '<select name="'.$this->field_name.'[field_weight]" type="text" id="'.$this->field_name.'[field_weight]">';
		for ($i=-10; $i < 10; $i++) { $html .= "<option>$i</option>"; }
		$html .= '</select>';
		$html .= '</div>';
		$this->data->renderData();		
		$html .= '</div>';
		print $html;
	}
}

class FormsData 
{
	public $name;
	public $prefix;
	public $suffix;
	public $attributes;

	function __construct($data = null, $name = null) {			
		if ($data) {
			$this->unpackData($data);
		} 
		else {
			if ($name) {$this->name = $name;} 
			$this->emptyData();
			// else die();
		}
	}

	private function emptyData() {
		$this->prefix = '';
		$this->suffix = '';
		$this->attributes = array();
	}

	private function packData($data) {
		return serialize($this);
	}

	private function unpackData($str) {
		$d = (object)unserialize($str);
		isset($d->prefix) ? $this->prefix = $d->prefix : $this->prefix = '';
		isset($d->suffix) ? $this->suffix = $d->suffix : $this->suffix = '';
		isset($d->attributes) ? $this->attributes = $d->attributes : $this->attributes = array();
	}

	public function renderData() {
		$html = '<label>Prefix</label><input type="text" name="'.$this->name.'[prefix]" value="'.$this->prefix.'">';
		$html = '<label>Suffix</label><input type="text" name="'.$this->name.'[suffix]" value="'.$this->suffix.'">';
		$html .= '<input type="hidden" name="'.$this->name.'[name]" value="'.$this->name.'">';
		foreach ($this->attributes as $key => $value) {
			$html = '<label>'.$key.'</label><input type="text" name="'.$this->name.'['.$key.']" value="'.$value.'">';
		}
		print $html;
	}
}


// class Forms
// {
// 	public $fid;
// 	private $db;
// 	public $form_name;
// 	public $form_data;
// 	private $service_path;
// 	public $available_field_types;

// 	function __construct() {		
// 		$this->db = new PDO('mysql:host=localhost;dbname=prestigemotors;charset=utf8', 'root', 'pass');
// 		$this->db->query("SET NAMES 'utf8'"); 
// 		$this->service_path = '/t/test';
// 		$this->available_field_types = array('hidden' ,'text', 'textarea', 'checkbox', 'select', 'radio', 'file');
// 		$this->attributes = array('id', 'class', 'name', 'method', 'action', 'data');
// 	}

// 	public function load_form_info($fid) {

// 		is_numeric($fid) ? $this->fid = $fid : die(); 

// 		try {
// 			$form = $this->db->prepare("SELECT * FROM forms WHERE id = ? LIMIT 1");
// 			$form->execute(array($this->fid));
// 			$form = $form->fetchObject();

// 			if ($form) {
// 				$this->form_name = $form->name;
// 				$this->form_data = (object)unserialize($form->data);
// 				$this->load_form_fields();
// 				return $form;
// 			}
// 			else return false;
// 		} 
// 		catch (PDOException $e) {

// 		}
// 	}

// 	public function load_form_fields() {

// 		$fields = $this->db->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_weight");
// 		$fields->execute(array($this->fid));
// 		$fields = $fields->fetchAll(PDO::FETCH_OBJ);
// 		$this->fields = $fields;
// 	}

// 	public function save_data($fid) {

// 		$output = $this->preprocess_save_data($fid);
// 		$ins = $this->db->prepare("INSERT INTO ".$this->form_data->save_table." SET ".$output['qString']);
// 		$ins->execute($output['values']);
// 	}

// 	private function preprocess_save_data($fid) {
// 		foreach ($this->fields as $value) {
// 			if (!empty($_POST[$value->field_name])){
// 				$output['qString'][] = $value->field_name. " = ?";
// 				$output['values'][] = $_POST[$value->field_name];
// 			}
// 		}
// 		$output['qString'] = implode(",", $output['qString']);
// 		return $output;
// 	}

// 	public function load_forms_list() {
// 		try {
// 			$this->forms_list = $this->db->prepare('SELECT * FROM forms');
// 			$this->forms_list->execute();
// 			$this->forms_list = $this->forms_list->fetchAll(PDO::FETCH_OBJ);
// 		}
// 		catch (PDOException $e) {
// 			print $e;
// 		}
// 	}
// 	/*
// 			В колонку data в таблице описания полей form_fields передается наименование функции которая возвращает список для полей 
// 		типа select. Передается массив array('selectlist' => имя функции); 
// 			Если данных не передано и массив дата не содержит информации, то применяется стандарная обработка списков, 
// 		которая предполагает что в поле значения список записан в строку через разделитель ','; 
// 			get_options получает этот список и возвращает HTML код для select.
// 	*/
// 	private function get_options($field) {
// 		$data = $field->attributes->selectlist;
// 		if(!empty($data) && is_callable($data)) {
// 			$data = $data();
// 			if(is_array($data)) {
// 				return $this->get_simpleOptions($data);
// 			}
// 		} else {
// 			if ($field->field_value) {
// 				return $this->get_simpleOptions(explode(",", $field->field_value));
// 			}
// 			return false;
// 		}
		
// 	}

// 	private function get_simpleOptions($data) {
// 		$html = '';
// 		array_walk($data, function($v, $k) use (&$html) {$html .= "<option value=\"$k\">$v</option>";});
// 		return $html;
// 	}

// 	private function get_data($field) {
// 		$attributes;
// 		$data = unserialize($field->data);
// 		if (is_array($data))
// 		{
// 			foreach ($data as $key => $value) {
// 				switch ($key) {
// 					case 'class':
// 						$attributes->class = ' class='.implode(",", $value);
// 					break;
// 					case 'id':
// 						$attributes->id = ' id='.$value;
// 					break;
// 					case 'selectlist':
// 						$attributes->selectlist = $value;
// 					break;
// 					case 'placeholder':
// 						$attributes->placeholder = $value;
// 					break;
// 					default:
// 						$attributes = array();
// 					break;
// 				}
// 			}
// 			return $attributes;
// 		}
// 		return false;
// 	}

// 	public function preprocess_form() {
// 		foreach ($this->fields as $value) {	
// 			$this->form[$value->field_name] = (object)array(
// 				'field_type' => $value->field_type,
// 				'field_name' => $value->field_name,
// 				'field_title' => $value->field_title,
// 				'field_value' => $value->field_value,
// 				'fid' => $this->fid,
// 				'attributes' => $this->get_data($value),
// 			);
// 		}
// 		return $this;
// 	}
// 	// создание формы
// 	public function render_form(){
// 		$html = "<form class="." \"form-auto form-item-" . $this->fid ."\" method='".$this->form_data->method."' action=".$this->form_data->action." >";
// 		foreach ($this->form as $value) {
// 			switch($value->field_type){
// 				case "textarea":
// 					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
// 					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-textarea-".$value->field_name."\">".$value->field_title."</label>".
// 					"<textarea id ="."\"form-textarea-".$value->field_name."\" name=" . $value->field_name . " cols = 40 rows = 10></textarea></div>";
// 				break;

// 				case "checkboxes":
// 					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
// 					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-checkboxes-".$value->field_name."\">".$value->field_title."</label>".
// 					"<input id ="."\"form-checkboxes-".$value->field_name."\" name=" . $value->field_name . " type=checkbox>".$value->field_value."</div>";
// 				break;	

// 				case "select":
// 					$html .= "<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
// 					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-select-".$value->field_name."\">".$value->field_title."</label>".
// 					"<select id ="."\"form-select-".$value->field_name."\" name=" . $value->field_name .">";
// 					$html .= $this->get_options($value);
// 					$html .= "</select></div>";

// 				break;

// 				case "file":
// 					!empty($value->attributes->class) ? $class = $value->attributes->class : $class = '';
// 					!empty($value->attributes->id) ? $id = $value->attributes->id : $id = '';
// 					!empty($value->attributes->data) ? $data = ' data='.$value->attributes->data : $data = '';
// 					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
// 					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-file-".$value->field_name."\">".$value->field_title."</label>".
// 					"<input id ="."\"form-file-".$value->field_name."\" name=" . $value->field_name . " type=" . $value->field_type .">
// 					<input type='submit'$class$id$data name='upload' value = 'Загрузить'></div>";
// 				break;

// 				case "text":
// 					!empty($value->attributes->placeholder) ? $placeholder = $value->attributes->placeholder : $placeholder = '';
// 					!empty($value->attributes->class) ? $class = $value->attributes->class : $class = '';
// 					!empty($value->attributes->id) ? $id = $value->attributes->id : $id = '';
// 					!empty($value->attributes->data) ? $data = ' data='.$value->attributes->data : $data = '';				
// 					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
// 					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-text-".$value->field_name."\">".$value->field_title."</label>".
// 					"<input id ="."\"form-text-".$value->field_name."\" name=" . $value->field_name . "$class$placeholder type=" . $value->field_type ."></div>";
// 				break;

// 				case "hidden":
// 					!empty($value->attributes->data) ? $data = ' data='.$value->attributes->data : $data = '';
// 					$html .= "<input id ="."\"form-hidden-".$value->field_name."\" value=".$value->field_value." name=" . $value->field_name . " type=" . $value->field_type .">";
// 				break;

// 				case "p_ins_date, p_upd_date":
// 				break;

// 				default:
// 					$html .="<div class ="."\"form-type-".$value->field_type." form-block-".$this->fid."\">".
// 					"<label class ="."\"form-label-".$value->field_type." form-label-".$this->fid."\" for="."\"form-input-".$value->field_name."\">".$value->field_title."</label>".
// 					"<input id ="."\"form-input-".$value->field_name."\" name=" . $value->field_name . " type=" . $value->field_type ."></div>";

// 			}

// 		}
// 		return $html . "<input type='submit' name='news' value = 'Сохранить'></form>";
// 	}	

// }