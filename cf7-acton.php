<?php 
/*
Plugin Name: Contact Form 7 Act-On
Author: Robert Meyer Jr
Description: Provides ActOn Integration with Contact Form 7 
Version: 0.5
*/

/*
Implemented via details on:
https://university.act-on.com/User_Guides/Inbound_Marketing/Landing_Pages_and_Forms/Working_with_Forms/Publishing_Forms/15_Integrating_an_Act-On_Form_External_Post_with_WordPress#Contact_Form_7
*/
new CF7Acton();
class CF7Acton{
	protected static $script_included = false;
	
	public function __construct(){
		add_action('wpcf7_editor_panels',		array($this,'add_panel'));
		add_action('wpcf7_save_contact_form',	array($this,'save_form'));
		add_filter('wpcf7_form_elements',		array($this,'form_html'));
	}
	
	public function form_html($html){		
		$script = $this->render_script();
		$html = $html . $script;
		return $html;
	}
	
	public function save_form($form){
		$form_id = $form->id();
		if( !empty($_POST['acton_account_id']) ){			
			$data = [
				'account_id'=> $_POST['acton_account_id'],
				'form_id'	=> $_POST['acton_form_id'],
				'domain'	=> $_POST['acton_marketing_domain'],
			];
			update_post_meta($form_id, 'cf7ActOn', $data);
		}
	}
	
	public function add_panel($panels){
		$panels['acton-panel'] = array( 
            'title' 	=> 'ActOn',
            'callback' 	=> [$this,'render_panel']
		);
		return $panels;
	}
	
	public function render_panel(){
		$wpcf7 = WPCF7_ContactForm::get_current();
		$m = get_post_meta($wpcf7->id(), 'cf7ActOn', true);
		$defaults = [
			'account_id'=>'',
			'form_id'	=>'',
			'domain'	=>'',
		];
		$m = shortcode_atts( $defaults, $m);#Not in a shortcode, but same function can be used
		?>
		<h2>ActOn Form Information</h2>
		<table>
			<tr><th>Account ID<td><input type=text name=acton_account_id value="<?=esc_attr($m['account_id'])?>">
			<tr><th>Form ID<td><input type=text name=acton_form_id value="<?=esc_attr($m['form_id'])?>">
			<tr><th>Marketing Domain<td><input type=text name=acton_marketing_domain value="<?=esc_attr($m['domain'])?>">
		</table>
		
		<p style="font-size:1.5em;">
			Example from ActOn Form URL:
			<br/>
			http://<span style="color:green;">marketing-domain</span>/acton/form/<span style="color:blue">AccountID</span>/<span style="color:red">FormID</span>:d-001/0/index.htm
		</p>
		<?php 
	}
	
	public function render_script(){
		
		$wpcf7 = WPCF7_ContactForm::get_current();
		$FORM_ID = $wpcf7->id();
		
		$m = get_post_meta($FORM_ID, 'cf7ActOn', true);
		if( empty($m) ){
			return;
		}
		
		$ACTON_FORM_ID	= $m['form_id'];
		$ACCOUNT_ID		= $m['account_id'];
		$DOMAIN			= $m['domain'];
		
		ob_start();
		if( !empty($ACTON_FORM_ID) && !empty($ACCOUNT_ID) && !empty($DOMAIN) ){
			?>
			<?php if(!self::$script_included) : self::$script_included=true; ?>
			<script src="//a11058.actonservice.com/cdnr/67/acton/attachment/11058/f-008b/1/-/-/-/-/wpcf7byID.js"></script>
			<?php endif; ?>
			<script>
				document.querySelector("form[action*='<?=esc_attr($FORM_ID)?>']").addEventListener('submit', function(event){event.preventDefault(); 
				var divId = document.querySelector("div[id*='<?=$FORM_ID?>']").getAttribute("id");processwpcf7("<?=$ACCOUNT_ID?>", "<?=$ACTON_FORM_ID?>", "<?=$DOMAIN?>", divId, ""),false});
			</script>
			<?php 
		}
		else{
			echo "<!-- No CF7 ActOn Settings Found -->";
		}
		return ob_get_clean();
	}	
}

