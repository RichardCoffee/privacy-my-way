<?php

/*
 *  classes/Form/Admin.php
 *
 *  copyright 2014-2017, The Creative Collective, the-creative-collective.com
 */

abstract class PMW_Form_Admin {

	protected $current   = '';
	protected $err_func  = 'log_entry';
	protected $form      =  array();
	protected $form_opts =  array();
	protected $form_text =  array();
	protected $hook_suffix;
	protected $options;
	protected $prefix    = 'tcc_options_';
	protected $register;
	protected $render;
	protected $slug      = 'default_page_slug';
	public    $tab       = 'about';
	protected $type      = 'single'; # two values: single, tabbed
	protected $validate;

	abstract protected function form_layout( $option );
	public function description() { return ''; }

	protected function __construct() {
		$this->screen_type();
		add_action( 'admin_init', array( $this, 'load_form_page' ) );
		if ( $this->type === 'tabbed' ) {
			if ( defined( 'PMW_TAB' ) )                { $this->tab = PMW_TAB; }
			if ( $trans = get_transient( 'PMW_TAB' ) ) { $this->tab = $trans; }
		}
	}

	public function load_form_page() {
		global $plugin_page;
		if ( ( $plugin_page === $this->slug ) || ( ( $refer = wp_get_referer() ) && ( strpos( $refer, $this->slug ) ) ) ) {
			if ( $this->type === 'tabbed' ) {
				if ( isset( $_GET['tab'] ) )  $this->tab = sanitize_key( $_GET['tab'] );
				if ( isset( $_POST['tab'] ) ) $this->tab = sanitize_key( $_POST['tab'] );
				set_transient( 'PMW_TAB', $this->tab, ( DAY_IN_SECONDS * 5 ) );
			}
			$this->form_text();
			$this->form = $this->form_layout();
			$this->check_tab();
			$this->determine_option();
			$this->get_form_options();
			$func = $this->register;
			$this->$func();
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	public function enqueue_scripts() {
		wp_register_style( 'admin-form.css', get_template_directory_uri() . "/css/admin-form.css", false );
		wp_register_script( 'admin-form.js', get_template_directory_uri() . "/js/admin-form.js", array( 'jquery', 'wp-color-picker' ), false, true );
		wp_enqueue_media();
		wp_enqueue_style( 'admin-form.css' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'admin-form.js' );
	}

  /**  Form text functions  **/

  private function form_text() {
    $text = array('error'  => array('render'    => _x('ERROR: Unable to locate function %s','string - a function name','tcc-privacy'),
                                    'subscript' => _x('ERROR: Not able to locate form data subscript:  %s','placeholder will be an ASCII character string','tcc-privacy')),
                  'submit' => array('save'      => __('Save Changes','tcc-privacy'),
                                    'object'    => __('Form','tcc-privacy'),
                                    'reset'     => _x('Reset %s','placeholder is a noun, may be plural','tcc-privacy'),
                                    'subject'   => __('Form','tcc-privacy'),
                                    'restore'   => _x('Default %s options restored.','placeholder is a noun, probably singular','tcc-privacy')),
                  'media'  => array('title'     => __('Assign/Upload Image','tcc-privacy'),
                                    'button'    => __('Assign Image','tcc-privacy'),
                                    'delete'    => __('Unassign Image','tcc-privacy')));
    $this->form_text = apply_filters( "form_text_{$this->slug}", $text, $text );
  }


  /**  Register Screen functions **/

  private function screen_type() {
    $this->register = "register_{$this->type}_form";
    $this->render   = "render_{$this->type}_form";
    $this->options  = "render_{$this->type}_options";
    $this->validate = "validate_{$this->type}_form";
  }

  public function register_single_form() {
    register_setting($this->current,$this->current,array($this,$this->validate));
    foreach($this->form as $key=>$group) {
      if (is_string($group)) continue; // skip string variables
      $title = (isset($group['title']))    ? $group['title'] : '';
      $desc  = (isset($group['describe'])) ? array($this,$group['describe']) : 'description';
      add_settings_section($key,$title,array($this,$desc),$this->slug);
      foreach($group['layout'] as $item=>$data) {
        $this->register_field($this->slug,$key,$item,$data);
      }
    }
  }

  public function register_tabbed_form() {
    $validater = (isset($this->form['validate'])) ? $this->form['validate'] : $this->validate;
    foreach($this->form as $key=>$section) {
      if (!((array)$section===$section)) continue; // skip string variabler
      if (!($section['option']===$this->current)) continue;
      $validate = (isset($section['validate'])) ? $section['validate'] : $validater;
      $current  = (isset($this->form[$key]['option'])) ? $this->form[$key]['option'] : $this->prefix.$key;
      #register_setting($this->slug,$current,array($this,$validate));
      register_setting($current,$current,array($this,$validate));
      $title    = (isset($section['title']))    ? $section['title']    : '';
      $describe = (isset($section['describe'])) ? $section['describe'] : 'description';
      $describe = (is_array($describe)) ? $describe : array($this,$describe);
      #add_settings_section($current,$title,$describe,$this->slug);
      add_settings_section($current,$title,$describe,$current);
      foreach($section['layout'] as $item=>$data) {
        $this->register_field($current,$key,$item,$data);
      }
    }
  } //*/

  private function register_field($option,$key,$itemID,$data) {
    if (is_string($data))        return; // skip string variables
    if (!isset($data['render'])) return;
    if ($data['render']=='skip') return;
/*    if ($data['render']=='array') {
      $count = max(count($data['default']),count($this->form_opts[$key][$itemID]));
      for ($i=0;$i<$count;$i++) {
        $label  = "<label for='$itemID'>{$data['label']} ".($i+1)."</label>";
        $args   = array('key'=>$key,'item'=>$itemID,'num'=>$i);
#        if ($i+1==$count) { $args['add'] = true; }
        add_settings_field("{$item}_$i",$label,array($this,$this->options),$this->slug,$current,$args);
      }
    } else { //*/
      $label = $this->field_label($itemID,$data);
      $args  = array('key'=>$key,'item'=>$itemID);
      #add_settings_field($itemID,$label,array($this,$this->options),$this->slug,$option,$args);
      add_settings_field($itemID,$label,array($this,$this->options),$option,$option,$args);
#    }
  }

  private function field_label($ID,$data) {
    $html = '';
    if (($data['render']==='display') || ($data['render']==='radio_multiple')) {
      $html = '<span';
      $html.= (isset($data['help']))  ? ' title="'.esc_attr($data['help']).'">' : '>';
      $html.= (isset($data['label'])) ? esc_html($data['label']) : '';
      $html.= '</span>';
    } elseif ($data['render']==='title') {
      $html = '<span';
      $html.= ' class="form-title"';
      $html.= (isset($data['help']))  ? ' title="'.esc_attr($data['help']).'">' : '>';
      $html.= (isset($data['label'])) ? esc_html($data['label']) : '';
      $html.= '</span>';
    } else {
      $html = '<label for="'.esc_attr($ID).'"';
      $html.= (isset($data['help']))  ? ' title="'.esc_attr($data['help']).'">' : '>';
      $html.= (isset($data['label'])) ? esc_html($data['label']) : '';
      $html.= '</label>';
    }
    return $html;
  }

  private function sanitize_callback($option) {
    $valid_func = "validate_{$option['render']}";
    if (method_exists($this,$valid_func)) {
      $retval = array($this,$valid_func);
    } else if (function_exists($valid_func)) {
      $retval = $valid_func;
    } else {
      $retval = 'wp_kses_post';
    }
    return $retval;
  }


  /**  Data functions  **/

	private function check_tab() {
		if ( ! isset( $this->form[ $this->tab ] ) ) {
			$this->tab = 'about';
		}
	}

  private function determine_option() {
    if ($this->type=='single') {
      $this->current = $this->slug;
    } elseif ($this->type=='tabbed') {
      if (isset($this->form[$this->tab]['option'])) {
        $this->current = $this->form[$this->tab]['option'];
      } else {
        $this->current = $this->prefix.$this->tab;
      }
    }
  }

  protected function get_defaults($option) {
    if (empty($this->form)) { $this->form = $this->form_layout(); }
    $defaults = array();
    if ($this->type=='single') {
      foreach($this->form as $key=>$group) {
        if (is_string($group)) continue;
        foreach($group['layout'] as $ID=>$item) {
          if (empty($item['default'])) continue;
          $defaults[$key][$ID] = $item['default'];
        }
      }
    } else {  #  tabbed page
      if (isset($this->form[$option])) {
        foreach($this->form[$option]['layout'] as $key=>$item) {
          if (empty($item['default'])) continue;
          $defaults[$key] = $item['default'];
        }
      } else {
        if (!empty($this->err_func)) {
          $func = $this->err_func;
          $func(sprintf($this->form_text['error']['subscript'],$option),debug_backtrace());
        }
      }
    }
    return $defaults;
  } //*/

  private function get_form_options() {
    $this->form_opts = get_option($this->current);
    if (empty($this->form_opts)) {
      $option = explode('_',$this->current); // FIXME: explode for tabbed, what about single?
      $this->form_opts = $this->get_defaults($option[2]);
      add_option($this->current,$this->form_opts);
    }
  }


  /**  Render Screen functions  **/

	public function render_single_form() { ?>
		<div class="wrap"><?php
			if ( isset( $this->form['title'] ) ) { ?>
				<h1>
					<?php echo esc_html( $this->form['title'] ); ?>
				</h1><?php
			}
			settings_errors(); ?>
			<form method="post" action="options.php"><?php
#				do_action( 'form_admin_pre_display' );
#				do_action( "form_admin_pre_display_{$this->current}" );
				settings_fields($this->current);
				do_settings_sections($this->current);
#				do_action( "form_admin_post_display_{$this->current}" );
#				do_action( 'form_admin_post_display' );
				$this->submit_buttons(); ?>
			</form>
		</div><?php //*/
	}

  public function render_tabbed_form() {
    $active_page = sanitize_key($_GET['page']); ?>
    <div class="wrap">
      <div id="icon-themes" class="icon32"></div>
      <h1 class='centered'>
        <?php echo esc_html($this->form['title']); ?>
      </h1><?php
      settings_errors(); ?>
      <h2 class="nav-tab-wrapper"><?php
        $refer = "admin.php?page=$active_page";
        foreach($this->form as $key=>$menu_item) {
          if (is_string($menu_item)) continue;
          $tab_css  = 'nav-tab';
          $tab_css .= ($this->tab==$key) ? ' nav-tab-active' : '';
          $tab_ref  = "$refer&tab=$key"; ?>
          <a href='<?php echo esc_attr($tab_ref); ?>' class='<?php echo esc_attr($tab_css); ?>'>
            <?php echo esc_html($menu_item['title']); ?>
          </a><?php
        } ?>
      </h2>
      <form method="post" action="options.php">
        <input type='hidden' name='tab' value='<?php echo $this->tab; ?>'><?php
        $current  = (isset($this->form[$this->tab]['option'])) ? $this->form[$this->tab]['option'] : $this->prefix.$this->tab;
        do_action( "form_admin_pre_display_{$this->tab}" );
        settings_fields($current); #$this->slug); #$this->current);
        do_settings_sections($current); #$this->slug); #$this->current);
        do_action("form_admin_post_display_{$this->tab}");
        $this->submit_buttons($this->form[$this->tab]['title']); ?>
      </form>
    <div><?php //*/
  }

  private function submit_buttons($title='') {
    $buttons = $this->form_text['submit']; ?>
    <p><?php
      submit_button($buttons['save'],'primary','submit',false); ?>
      <span style='float:right;'><?php
        $object = (empty($title)) ? $buttons['object'] : $title;
        $reset  = sprintf($buttons['reset'],$object);
        submit_button($reset,'secondary','reset',false); ?>
      </span>
    </p><?php
  }

  public function render_single_options($args) {
    extract($args);  #  array( 'key'=>$key, 'item'=>$item, 'num'=>$i);
    $data   = $this->form_opts;
    $layout = $this->form[$key]['layout'];
    $attr   = $this->render_attributes($layout[$item]);
    echo "<div $attr>";
    if (empty($layout[$item]['render'])) {
      echo $data[$key][$item];
    } else {
      $func  = "render_{$layout[$item]['render']}";
      $name  = $this->slug."[$item]";
      $value = (isset($data[$key][$item])) ? $data[$key][$item] : '';
      if ($layout[$item]['render']=='array') {
        $name.= "[$num]";
        #if ((isset($add)) && ($add)) { $layout[$item]['add'] = true; }
        $value = (isset($data[$key][$item][$num])) ? $data[$key][$item][$num] : '';
      }
      $field = str_replace(array('[',']'),array('_',''),$name);
      $fargs = array('ID'=>$field, 'value'=>$value, 'layout'=>$layout[$item], 'name'=>$name);
      if (method_exists($this,$func)) {
        $this->$func($fargs);
      } elseif (function_exists($func)) {
        $func($fargs);
      } else {
        if (!empty($this->err_func)) {
          $func = $this->err_func;
          $func(sprintf($this->form_text['error']['render'],$func)); }
      }
    }
    echo "</div>";
  }

  public function render_tabbed_options($args) {
    extract($args);  #  $args = array( 'key' => {group-slug}, 'item' => {item-slug})
    $data   = $this->form_opts;
    $layout = $this->form[$key]['layout'];
    $attr   = $this->render_attributes($layout[$item]);
    echo "<div $attr>";
    if (empty($layout[$item]['render'])) {
      echo $data[$item];
    } else {
      $func = "render_{$layout[$item]['render']}";
      $name = $this->current."[$item]";
      if (!isset($data[$item])) $data[$item] = (empty($layout[$item]['default'])) ? '' : $layout[$item]['default'];
      $fargs = array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name);
      if (method_exists($this,$func)) {
        $this->$func($fargs);
      } elseif (function_exists($func)) {
        $func($fargs);
      } else {
        if (!empty($this->err_func))
          $func = $this->err_func;
          $func(sprintf($this->form_text['error']['render'],$func));
      }
    }
    echo "</div>"; //*/
  }

  public function render_multi_options($args) {
  }

	private function render_attributes($layout) {
		$attr = ( ! empty( $layout['divcss'] ) )  ? ' class="'.esc_attr($layout['divcss']).'"'   : '';
		$attr.= ( isset( $layout['help'] ) )      ? ' title="'.esc_attr($layout['help']).'"'     : '';
		if ( ! empty( $layout['showhide'] ) ) {
			$attr.= ' data-item="' . esc_attr( $layout['showhide']['item'] ) . '"';
			$attr.= ' data-show="' . esc_attr( $layout['showhide']['show'] ) . '"';
		}
		return $attr;
	}


  /**  Render Items functions
    *
    *
    *  $data = array('ID'=>$field, 'value'=>$value, 'layout'=>$layout[$item], 'name'=>$name);
    *
    **/

	// FIXME:  needs add/delete/sort
	private function render_array( $data ) {
		extract( $data );  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
		if ( ! isset( $layout['type'] ) ) { $layout['type'] = 'text'; }
		if ( $layout['type'] === 'image' ) {
			$this->render_image( $data );
		} else {
			$this->render_text( $data );
		}
	}

	private function render_checkbox( $data ) {
		extract( $data );	#	associative array: keys are 'ID', 'value', 'layout', 'name'
		$onchange = ( isset( $layout['change'] ) ) ? $layout['change'] : ''; ?>
		<label>
			<input type="checkbox"
			       id="<?php echo esc_attr( $ID ); ?>"
			       name="<?php echo esc_attr( $name ); ?>"
			       value="yes"
			       <?php checked( $value ); ?>
			       onchange="<?php echo esc_attr( $onchange ); ?>" />&nbsp;
			<span>
				<?php echo esc_html( $layout['text'] ); ?>
			</span>
		</label><?php
	}

	private function render_checkbox_multiple( $data ) {
		extract( $data );	#	associative array: keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) return;
		foreach( $layout['source'] as $key => $text ) {
			$check = isset( $value[ $key ] ) ? true : false; ?>
			<div>
				<label>
					<input type="checkbox"
					       id="<?php echo esc_attr( $ID.'-'.$key ); ?>"
					       name="<?php echo esc_attr( $name.'['.$key.']' ); ?>"
					       value="yes" <?php checked( $check ); ?> />&nbsp;
					<span>
						<?php echo esc_html( $text ); ?>
					</span>
				</label>
			</div><?php
		}
	}



	private function render_colorpicker($data) {
		extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
		$text = ( ! empty( $layout['text'] ) ) ? $layout['text'] : ''; ?>
		<input type="text" class="form-colorpicker"
		       name="<?php e_esc_attr( $name ); ?>"
		       value="<?php e_esc_attr( $value ); ?>"
		       data-default-color="<?php e_esc_attr( $layout['default'] ); ?>" />&nbsp;
		<span class="form-colorpicker-text">
			<?php e_esc_html( $text ); ?>
		</span><?php
	}

  private function render_display($data) {
    extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
    if (isset($layout['default']) && !empty($value)) echo $value;
    if (!empty($layout['text'])) echo " <span>{$layout['text']}</span>";
  }

  private function render_font($data) {
    extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
    $html = "<select id='$ID' name='$name'";
    $html.= (isset($layout['change'])) ? " onchange='{$layout['change']}'>" : ">";
    foreach($layout['source'] as $key=>$text) {
      $html.= "<option value='$key'";
      $html.= ($key===$value) ? " selected='selected'" : '';
      $html.= "> $key </option>";
    }
    $html.= '</select>';
    $html.= (!empty($data['layout']['text'])) ? "<span class=''> {$data['layout']['text']}</span>" : '';
    echo $html;
  }

	private function render_image( $data ) {
		extract( $data );  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
		$media   = $this->form_text['media'];
		$img_css = 'form-image-container' . ( ( empty( $value ) ) ? ' hidden' : '');
		$btn_css = 'form-image-delete' . ( ( empty( $value ) ) ? ' hidden' : '');
		if ( isset( $layout['media'] ) ) { $media = array_merge( $media, $layout['media'] ); } ?>
		<div data-title="<?php e_esc_attr( $media['title'] ); ?>"
			  data-button="<?php e_esc_attr( $media['button'] ); ?>" data-field="<?php e_esc_attr( $ID ); ?>">
			<button type="button" class="form-image">
				<?php e_esc_html( $media['button'] ); ?>
			</button>
			<input id="<?php e_esc_attr( $ID ); ?>_input" type="text" class="hidden" name="<?php e_esc_attr( $name ); ?>" value="<?php e_esc_html( $value ); ?>" />
			<div class="<?php echo $img_css; ?>">
				<img id="<?php e_esc_attr( $ID ); ?>_img" src="<?php e_esc_attr( $value ); ?>" alt="<?php e_esc_attr( $value ); ?>">
			</div>
			<button type="button" class="<?php echo $btn_css; ?>">
				<?php e_esc_html( $media['delete'] ); ?>
			</button>
		</div><?php
	}

	private function render_radio($data) {
		extract( $data );	#	associative array: keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) return;
		$uniq = uniqid();
		$tooltip     = ( isset( $layout['help'] ) )    ? $layout['help']    : '';
		$before_text = ( isset( $layout['text'] ) )    ? $layout['text']    : '';
		$after_text  = ( isset( $layout['postext'] ) ) ? $layout['postext'] : '';
		$onchange    = ( isset( $layout['change'] ) )  ? $layout['change']  : ''; ?>
		<div title="<?php echo esc_attr( $tooltip ); ?>">
			<div id="<?php echo $uniq; ?>">
				<?php echo esc_html( $before_text ); ?>
			</div><?php
			foreach( $layout['source'] as $key => $text ) { ?>
				<div>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $name ) ; ?>"
						       value="<?php echo esc_attr( $key ); ?>"
						       <?php checked( $value, $key ); ?>
						       onchange="<?php echo esc_attr( $onchange ); ?>"
						       aria-describedby="<?php echo $uniq; ?>">
						<?php echo esc_html( $text ); ?>
					</label>
				</div><?php
			} ?>
			<div>
				<?php echo esc_html( $after_text ) ; ?>
			</div>
		</div><?php
	} //*/

	#	Note:  this has limited use - only displays yes/no radios
	private function render_radio_multiple( $data ) {
		extract( $data );   #   associative array: keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) return;
		$tooltip   = ( isset( $layout['help'] ) )    ? $layout['help']    : '';
		$pre_css   = ( isset( $layout['textcss'] ) ) ? $layout['textcss'] : '';
		$pre_text  = ( isset( $layout['text'] ) )    ? $layout['text']    : '';
		$post_text = ( isset( $layout['postext'] ) ) ? $layout['postext'] : '';
		$preset    = ( isset( $layout['preset'] ) )  ? $layout['preset']  : 'no'; ?>
		<div class="radio-multiple-div" title="<?php echo esc_attr( $tooltip ); ?>">
			<div class="<?php echo $pre_css; ?>">
				<?php e_esc_html( $pre_text ); ?>
			</div>
			<div class="radio-multiple-header">
				<span class="radio-multiple-yes"><?php esc_html_e( 'Yes',  'tcc-privacy' ); ?></span>&nbsp;
				<span class="radio-multiple-no" ><?php esc_html_e( 'No', 'tcc-privacy' ); ?></span>
			</div><?php
			foreach( $layout['source'] as $key => $text ) {
				$check  = ( isset( $value[ $key ] ) ) ? $value[ $key ] : $preset; ?>
				<div class="radio-multiple-list-item">
					<label>
						<input type="radio" value="yes" class="radio-multiple-list radio-multiple-list-yes"
						       name="<?php echo esc_attr( $name.'['.$key.']' ) ; ?>"
						       <?php checked( $check, 'yes' ); ?> />&nbsp;
						<input type="radio" value="no" class="radio-multiple-list radio-multiple-list-no"
						       name="<?php echo esc_attr( $name.'['.$key.']' ) ; ?>"
						       <?php checked( $check, 'no' ); ?> />
						<span class="radio-multiple-list-text">
							<?php echo $text; ?>
						</span>
					</label>
				</div><?php
			} ?>
			<div class="radio-multiple-post-text">
				<?php echo esc_html( $post_text ) ; ?>
			</div>
		</div><?php
	}

  private function render_select($data) {
    extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
    if (empty($layout['source'])) return;
    $source_func = $layout['source'];
    if (!empty($layout['text'])) echo "<div class='form-select-text'> ".esc_attr($layout['text'])."</div>";
    $html = "<select id='$ID' name='$name'";
    $html.= (isset($layout['change'])) ? " onchange='{$layout['change']}'>" : ">";
    echo $html;
    if (is_array($source_func)) {
      foreach($source_func as $key=>$text) {
        $select = ($key==$value) ? "selected='selected'" : '';
        echo "<option value='$key' $select> $text </option>";
      }
    } elseif (method_exists($this,$source_func)) {
      $this->$source_func($value);
    } elseif (function_exists($source_func)) {
      $source_func($value);
    }
    echo '</select>';
  }

	private function render_spinner( $data ) {
		extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
		$tooltip = ( isset( $layout['help'] ) ) ? $layout['help'] : ''; ?>
		<input type="number" class="small-text" min="1" step="1"
		       id="<?php e_esc_attr( $ID ); ?>"
		       name="<?php e_esc_attr( $name ); ?>"
		       title="<?php e_esc_attr( $tooltip ); ?>"
		       value="<?php e_esc_attr( sanitize_text_field( $value ) ); ?>" /> <?php
		if ( ! empty( $layout['stext'] ) ) { e_esc_attr( $layout['stext'] ); }
	}

  private function render_text($data) {
    extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
    $html = (!empty($layout['text']))  ? "<p> ".esc_attr($layout['text'])."</p>" : "";
    $html.= "<input type='text' id='$ID' class='";
    $html.= (isset($layout['class']))  ? esc_attr($layout['class'])."'" : "regular-text'";
    $html.= " name='$name' value='".esc_attr(sanitize_text_field($value))."'";
    $html.= (isset($layout['help']))   ? " title='".esc_attr($layout['help'])."'"        : "";
    $html.= (isset($layout['place']))  ? " placeholder='".esc_attr($layout['place'])."'" : "";
    $html.= (isset($layout['change'])) ? " onchange='{$layout['change']}' />"            : "/>";
    $html.= (!empty($layout['stext'])) ? ' '.esc_attr($layout['stext'])                  : "";
    $html.= (!empty($layout['etext'])) ? "<p> ".esc_attr($layout['etext'])."</p>"        : "";
    echo $html;
  }

  private function render_text_color($data) {
    $this->render_text($data);
    $basic = explode('[',$data['name']);
    $index = substr($basic[1],0,-1).'_color';
    $data['name']  = "{$basic[0]}[{$index}]";
    $data['value'] = (isset($this->form_opts[$index])) ? $this->form_opts[$index] : $data['layout']['color'];
    $data['layout']['default'] = $data['layout']['color'];
    $data['layout']['text']    = '';
    $this->render_colorpicker($data);
  }

  private function render_title($data) {
    extract($data);  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
    if (!empty($layout['text'])) {
      $data['layout']['text'] = "<b>{$layout['text']}</b>"; }
    $this->render_display($data);
  }

  /**  Validate functions  **/

  public function validate_single_form($input) {
    $form   = $this->form;
    $output = $this->get_defaults();
    if (isset($_POST['reset'])) {
      $object = (isset($this->form['title'])) ? $this->form['title'] : $this->form_test['submit']['object'];
      $string = sprintf($this->form_text['submit']['restore'],$object);
      add_settings_error($this->slug,'restore_defaults',$string,'updated fade');
      return $output;
    }
    foreach($input as $key=>$data) {
      if (!((array)$data==$data)) continue;
      foreach($data as $ID=>$subdata) {
        $item = $form[$key]['layout'][$ID];
        if ($item['render']==='array') {
          $item['render'] = (isset($item['type'])) ? $item['type'] : 'text';
          $vals = array();
          foreach($subdata as $indiv) {
            $vals[] = $this->do_validate_function($indiv,$item);
          }
          $output[$key][$ID] = $vals;
        } else {
          $output[$key][$ID] = $this->do_validate_function($subdata,$item);
        }
      }
    }
    // check for required fields
    foreach($output as $key=>$data) {
      if (!((array)$data==$data)) continue;
      foreach($data as $ID=>$subdata) {
        if (!isset($form[$key]['layout'][$ID]['require'])) { continue; }
        if (empty($output[$key][$ID])) {
          $output[$key][$ID] = $subdata;
        }
      }
    }
    return apply_filters($this->slug.'_validate_settings',$output,$input);
  }

  public function validate_tabbed_form($input) {
    #log_entry('_GET',$_GET);
    #log_entry('_POST',$_POST);
    #log_entry('form',$this->form);
    #log_entry('input',$input);
    $option = sanitize_key($_POST['tab']);
    $output = $this->get_defaults($option);
    if (isset($_POST['reset'])) {
      $object = (isset($this->form[$option]['title'])) ? $this->form[$option]['title'] : $this->form_test['submit']['object'];
      $string = sprintf($this->form_text['submit']['restore'],$object);
      add_settings_error('creatom','restore_defaults',$string,'updated fade');
      return $output;
    }
    foreach($input as $key=>$data) {
      $item = (isset($this->form[$option]['layout'][$key])) ? $this->form[$option]['layout'][$key] : array();
      if ((array)$data==$data) {
        foreach($data as $ID=>$subdata) {
          $output[$key][$ID] = $this->do_validate_function($subdata,$item);
        }
      } else {
        $output[$key] = $this->do_validate_function($data,$item);
      }
    }
    #log_entry('output',$output);
    return apply_filters($this->current.'_validate_settings',$output,$input);
  }

  private function do_validate_function($input,$item) {
    if (empty($item['render'])) { $item['render'] = 'non_existing_render_type'; }
    $func = 'validate_';
    $func.= (isset($item['validate'])) ? $item['validate'] : $item['render'];
    if (method_exists($this,$func)) {
      $output = $this->$func($input);
    } elseif (function_exists($func)) {
      $output = $func($input);
    } else { // FIXME:  test for data type
      $output = $this->validate_text($input);
    }
    return $output;
  }

  private function validate_colorpicker($input) {
    return (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|',$input)) ? $input : '';
  }

  private function validate_image($input) {
    return esc_url_raw(strip_tags(stripslashes($input)));
  }

  private function validate_post_content($input) {
    return wp_kses_post($input);
  }

  private function validate_radio($input) {
    return sanitize_key($input);
  }

  private function validate_select($input) {
    return sanitize_file_name($input);
  }

  protected function validate_text($input) {
    return strip_tags(stripslashes($input));
  }

  private function validate_text_color($input) {
    return $this->validate_text($input);
  }

  private function validate_url($input) {
    return esc_url_raw(strip_tags(stripslashes($input)));
  }


}
