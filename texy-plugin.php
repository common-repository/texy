<?php
/*
Plugin Name: Texy!
Version: 0.2
Plugin URI: http://kahi.cz/wordpress/plugins/texy
Description: Texy! is text-to-HTML formatter and converter library. It allows you to write structured documents without knowledge or using of HTML language. You write documents in humane easy-to-read plain text format and Texy! converts it to structurally and valid (X)HTML code.
Author: Peter Kahoun, David Grudl, Acci 
*/

// error_reporting(E_ALL);
define('TEXY_ACTIVE', TRUE);

// configuration
$Texy_enableSmilies   = false;
$Texy_cachePath       = dirname(__FILE__) . '/texy.cache/';
$Texy_smiliesPath     = get_option('siteurl') . '/wp-includes/images/smilies/';
$Texy_pluginPath      = get_option('siteurl') . '/wp-content/plugins/texy/';
$Texy_fshlStyle       = $Texy_pluginPath . 'fshl/styles/COHEN_style.css';
$Texy_quicktagsScript = $Texy_pluginPath . 'quicktags-texy.js';
$Texy_uploadScript    = $Texy_pluginPath . 'upload.js';
$Texy_encoding        = 'utf-8';  // enable UTF-8
$Texy_enableFshl      = false;  // Syntax Highlighter configuration
$Texy_enableSmartLink = TRUE;


/*
usage: texyizing own string
***************************

$t = new Texy();
$t = $TexyPlugin->loadSet('comment');
$t->headingModule->top = 2;

echo $t->process($string);
*/


function newTexy()
{
	global $Texy_enableSmilies, $Texy_smiliesPath, $Texy_encoding, $Texy_enableFshl, $Texy_enableSmartLink;

	// INCLUDE TEXY & FSHL
	require_once dirname(__FILE__).'/texy.compact.php';
	if ($Texy_enableFshl) include_once(dirname(__FILE__).'/fshl/fshl.php');

	$texy = new Texy();
	$texy->encoding = $Texy_encoding;

	// classes for left- or right- aligned images, figures and text
	$texy->alignClasses['left'] = 'left';
	$texy->alignClasses['right'] = 'right';
	$texy->alignClasses['center'] = 'test-center'; // inst. of .<>

	$texy->imageModule->root        = TexyPlugin::$uploads_dir_url;  // images root  
	$texy->imageModule->linkedRoot  = TexyPlugin::$uploads_dir_url;  // images root  

	if ($Texy_enableSmilies) {
		$texy->allowed['emoticon'] = TRUE;
		$texy->emoticonModule->root  = $Texy_smiliesPath; // where are smilies located
		$texy->emoticonModule->class  = 'smiley';     // css class
		$texy->emoticonModule->icons = array (
			':-)'  =>  'icon_smile.gif',
			':-('  =>  'icon_sad.gif',
			';-)'  =>  'icon_wink.gif',
			':-D'  =>  'icon_biggrin.gif',
			'8-O'  =>  'icon_eek.gif',
			'8-)'  =>  'icon_cool.gif',
			':-?'  =>  'icon_confused.gif',
			':-x'  =>  'icon_mad.gif',
			':-P'  =>  'icon_razz.gif',
			':-|'  =>  'icon_neutral.gif',
		);
	}

	// set user callback function for /---code blocks
	if ($Texy_enableFshl  && class_exists('fshlParser')) {
		$texy->addHandler('block', 'texyBlockHandler');
	}

	if ($Texy_enableSmartLink) {
		$texy->addHandler('phrase', 'texyPhraseHandler');
	}

	return $texy;
}



function read_texy_cache($id)
{
	if (!TexyPlugin::$settings['cache_enabled'])
		return false;
		
	global $Texy_cachePath;

	$file = rtrim($Texy_cachePath, '/\\') . '/' . $id . '.html';

	if (is_file($file)) {
		$cache = file_get_contents($file);
		if ($cache) {
			$cache = @unserialize($cache);
			if (is_array($cache)) {
				return $cache[0];
			}
		}
	}
	return FALSE;
}



function write_texy_cache($id, $html)
{
	if (!TexyPlugin::$settings['cache_enabled'])
		return false;
		
	global $Texy_cachePath;

	if (is_writable($Texy_cachePath)) {
		// TODO: what about chmod($Texy_cachePath, ...) ???

		$file = rtrim($Texy_cachePath, '/\\') . '/' . $id . '.html';
		fwrite(
			fopen($file, 'w'),
			serialize( array(0 => $html) )
		);
	}
}



function do_texy($text)
{
	global $id; // current post id

	if (!has_texy_header($text) && !get_post_meta($id, '_use_texy', true) ) {

		// apply default filters
		$text = wptexturize($text);
		$text = convert_smilies($text);
		$text = convert_chars($text);
		$text = wpautop($text);
		return $text;
	}

	// check, if cached file exists
	$cache_id = md5($text); // TODO: or use md($text) . '-item' ???
	$html = read_texy_cache($cache_id);
	if (is_string($html)) return $html;

	$texy = newTexy();
	$texy->headingModule->top = TexyPlugin::$settings['heading'];
	$texy->allowed['phrase/cite'] = true;
	$html = $texy->process($text);
	$texy->free();

	write_texy_cache($cache_id, $html);
	return $html;
}



function do_texy_comment($text)
{
	if (!has_texy_header($text)) {

		// apply default filters
		$text = wptexturize($text);
		$text = convert_chars($text);
		$text = make_clickable($text);
		$text = wpautop($text);
		$text = convert_smilies($text);
		$text = balanceTags($text);
		return $text;
	}

	// check, if cached file exists
	$id = md5($text) . '-comment';
	$html = read_texy_cache($id);
	if (is_string($html)) return $html;

	$texy = newTexy();
	TexyConfigurator::safeMode($texy);
	$texy->headingModule->top = 4;
	$texy->mergeLines = FALSE;
	$texy->allowed['link/definition'] = FALSE;
	$html = $texy->process($text);
	$texy->free();

	write_texy_cache($id, $html);

	return $html;
}



function add_texy_header($text)
{
	if (!empty($text)) {
		return '<!--texy-->' . $text;
	}
}



function add_texy_meta($post_id)
{
   add_post_meta($post_id, '_use_texy', true, true);
}



function has_texy_header(&$text)
{
	if (substr($text, 0, 6) === '<texy>') {
		$text = substr($text, 6);
		return TRUE;

	} elseif (substr($text, 0, 11) === '<!--texy-->') {
		$text = substr($text, 11);
		return TRUE;

	} else {
		return FALSE;
	}
}



function remove_texy_header($text)
{
	has_texy_header($text);
	return $text;
}



/**
 * User handler for code block
 *
 * @param TexyHandlerInvocation  handler invocation
 * @param string  block type
 * @param string  text to highlight
 * @param string  language
 * @param TexyModifier modifier
 * @return TexyHtml
 */
function texyBlockHandler($invocation, $blocktype, $content, $lang, $modifier)
{
	if ($blocktype !== 'block/code') {
		return $invocation->proceed();
	}

	$lang = strtoupper($lang);
	if ($lang == 'JAVASCRIPT') $lang = 'JS';

	$parser = new fshlParser('HTML_UTF8', P_TAB_INDENT);
	if (!$parser->isLanguage($lang)) {
		return $invocation->proceed();
	}

	$texy = $invocation->getTexy();
	$content = Texy::outdent($content);
	$content = $parser->highlightString($lang, $content);
	$content = $texy->protect($content, Texy::CONTENT_BLOCK);

	$elPre = TexyHtml::el('pre');
	if ($modifier) $modifier->decorate($texy, $elPre);
	$elPre->attrs['class'] = strtolower($lang);

	$elCode = $elPre->create('code', $content);

	return $elPre;
}



/**
 * @param TexyHandlerInvocation  handler invocation
 * @param string
 * @param string
 * @param TexyModifier
 * @param TexyLink
 * @return TexyHtml|string|FALSE
 */
function texyPhraseHandler($invocation, $phrase, $content, $modifier, $link)
{
	// is there link?
	if (!$link) return $invocation->proceed();

	if (is_numeric($link->URL)) {
		$link->URL = get_permalink($link->URL);
	}

	if (substr($link->URL, 0, 1) === '-') {
		$post_title = substr($link->URL, 1);
		$post_id = texy_get_post_by_title($post_title);

		if(!$post_id) {
			return $invocation->proceed();
		}

		$link->URL = get_permalink($post_id);
	}

	return $invocation->proceed();
}



function texy_get_post_by_title($post_title)
{
	global $wpdb;

	$post_name = sanitize_title($post_title);
	$post_name = $wpdb->escape($post_name);
	$post = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '$post_name' LIMIT 1");
	return $post;
}



function add_fshl_style()
{
	global $Texy_enableFshl, $Texy_fshlStyle;

	if ($Texy_enableFshl && $Texy_fshlStyle) {
		$style = "\n".'<link rel="stylesheet" type="text/css" href="'.$Texy_fshlStyle.'" />';
	}

	echo $style;
}



function texy_scripts()
{
	global $Texy_quicktagsScript, $Texy_uploadScript, $wp_scripts, $Texy_pluginPath;
	
// 	// don't show quicktags on dashboard in 2.7
// 	$page = array_pop(explode('/', rtrim($_SERVER['REQUEST_URI'], '/?')));
	
	
	
// 	wp_enqueue_script( 'markitup', $Texy_pluginPath . 'scripts/markitup/jquery.markitup.js', 'jquery', '1.1.5');
	// markitup texy set included to head inline
	
	
	if ($Texy_quicktagsScript /*AND $page != 'wp-admin' AND $page != 'index.php'*/) {
		wp_deregister_script('quicktags');
		// wp_register_script('quicktags', $Texy_quicktagsScript, 'jquery', '3958');
	}


	if ($Texy_uploadScript) {
		if (!is_a($wp_scripts, 'WP_Scripts')) {
			$wp_scripts = new WP_Scripts();
		}

		// WP sucks!
		if (isset($wp_scripts->scripts['upload'])) {
			$wp_scripts->scripts['upload']->src = $Texy_uploadScript;
		}
	}
}

function admin_markitup_setting () { 
	global $Texy_pluginPath;
?>

<!-- Texy plugin -->
<script type="text/javascript" src="<?php echo $Texy_pluginPath ?>scripts/markitup/jquery.markitup.js"></script>
<script type="text/javascript" src="<?php echo $Texy_pluginPath ?>scripts/markitup/markitup.additional.js"></script>
<script type="text/javascript" src="<?php echo $Texy_pluginPath ?>scripts/jquery.debug.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $Texy_pluginPath ?>scripts/markitup/skins/markitup/style.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $Texy_pluginPath ?>scripts/markitup/sets/texy/style.css" />
<script type="text/javascript">
/* <![CDATA[ */

// prepare Markitup settings
mySettings = {
	onShiftEnter: {keepDefault:false, replaceWith:'\n '},
// 	onTab: {keepDefault:true, openWith:'\t'},
	markupSet: [	 

<?php
// @todo move into this file
//       the file is (later) editable via administration
require_once 'editor-buttons.php'; ?>			
	]
}

// var debug = new jQuery.debug({ posTo:{x:'right',y:'bottom'}, height:'200px',width:'250px' });

function bullet_list (h) {

	// debug.dump(h);

	var selection = h.selection;

	if (selection[0] != "\n") selection = "\n" + selection;

	return selection.replace(/\n/gm, "\n- ");

}

/* ]]> */
</script>
<!-- / Texy plugin -->


<?php
}


function my_admin_print_footer_scripts() { ?>

<!-- by Texy plugin -->
<script type="text/javascript" charset="utf-8">
		
// see: media-upload.dev.js > send_to_editor (since I don't know how to overwrite original function)
function send_to_editor (h) {
	var ed;

	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
		ed.focus();
		if ( tinymce.isIE )
			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

		if ( h.indexOf('[caption') === 0 ) {
			if ( ed.plugins.wpeditimage )
				h = ed.plugins.wpeditimage._do_shcode(h);
		} else if ( h.indexOf('[gallery') === 0 ) {
			if ( ed.plugins.wpgallery )
				h = ed.plugins.wpgallery._do_gallery(h);
		} else if ( h.indexOf('[embed') === 0 ) {
			if ( ed.plugins.wordpress )
				h = ed.plugins.wordpress._setEmbed(h);
		}

		ed.execCommand('mceInsertContent', false, h);

	} else if ( typeof edInsertContent == 'function' ) {
		edInsertContent(edCanvas, h);
	} else {
		// +
		edCanvas.focus();
		if (edCanvas.selectionStart||edCanvas.selectionStart=="0") {
			edCanvas.value = edCanvas.value.substring(0, edCanvas.selectionStart) + h + edCanvas.value.substring(edCanvas.selectionEnd,edCanvas.value.length);
		} else{
			edCanvas.value+=h;
		}
		// /+
		
	}

	tb_remove();
}

</script>


<?php
}
add_action('admin_print_footer_scripts', 'my_admin_print_footer_scripts', 100);


/** remove WordPress default filters! **/

// Comments wp 1.5
remove_filter('pre_comment_content', 'stripslashes', 1);
remove_filter('pre_comment_content', 'wp_filter_kses');
remove_filter('pre_comment_content', 'wp_rel_nofollow', 15);
remove_filter('pre_comment_content', 'balanceTags', 30);
remove_filter('pre_comment_content', 'addslashes', 50);
remove_filter('comment_save_pre', 'balanceTags', 50);

// Comments wp 1.2 & 1.5
remove_filter('comment_text', 'wptexturize');
remove_filter('comment_text', 'convert_chars');
remove_filter('comment_text', 'make_clickable');
remove_filter('comment_text', 'wpautop', 30);
remove_filter('comment_text', 'convert_smilies', 20);
remove_filter('comment_text', 'balanceTags');
remove_filter('comment_text', 'wp_filter_kses');

// Content wp 1.5
remove_filter('content_save_pre', 'balanceTags', 50);
remove_filter('excerpt_save_pre', 'balanceTags', 50);

// Content wp 1.2 & 1.5
remove_filter('the_content', 'wptexturize');
remove_filter('the_content', 'convert_smilies');
remove_filter('the_content', 'convert_chars');
remove_filter('the_content', 'wpautop');

remove_filter('the_excerpt', 'wptexturize');
remove_filter('the_excerpt', 'convert_smilies');
remove_filter('the_excerpt', 'convert_chars');
remove_filter('the_excerpt', 'wpautop');



/** add Texy! filters! **/

add_filter('wp_head', 'add_fshl_style');
add_action('init', 'texy_scripts');
add_action('admin_head', 'admin_markitup_setting');

add_filter('the_content', 'do_texy');
add_filter('the_excerpt', 'do_texy');
add_filter('comment_text', 'do_texy_comment');

add_filter('format_to_edit', 'remove_texy_header');

add_filter('save_post', 'add_texy_meta'); // 2.3
add_filter('content_save_pre', 'remove_texy_header'); // 1.5
add_filter('excerpt_save_pre', 'remove_texy_header');
add_filter('pre_comment_content', 'add_texy_header', 80);
add_filter('content_edit_pre', 'remove_texy_header');
add_filter('excerpt_edit_pre', 'remove_texy_header');
add_filter('comment_edit_pre', 'remove_texy_header');

add_filter('the_content_rss',  'do_texy');
add_filter('the_excerpt_rss',  'do_texy');
add_filter('comment_text_rss', 'do_texy_comment');

add_filter('image_send_to_editor', array('TexyPlugin', 'image_send_to_editor'), 100, 8);


class TexyPlugin {
	
	
	
	// Descr: full name. used on options-page, ...
	static $full_name = 'Texy';

	// Descr: short name. used in menu-item name, ...
	static $short_name = 'Texy';

	// Descr: abbreviation. used in textdomain, ...
	// Descr: must be same as the name of the class
	static $abbr = 'TexyPlugin';

	// Descr: path to this this file
	// filled on Init() autom.
	static $dir_name = '';
	static $dir_path = '';
	static $dir_url = '';

	static $cache_dir_path = '';
	
	// Descr: path to the cachefile
	// filled automatically
	static private $cache_file_fullname = '';

	// Descr: settings: names => default values
	// Descr: in db are these settings prefixed with abbr_
	static $settings = array (

		'cache_enabled' => true,
		'visual_editor_disabled' => true,
		'heading' => 3,

	);
	
	static public $uploads_dir_url;


	function Init () {

		// set self::$dir_name
		self::$dir_name = plugin_basename(dirname(__FILE__));
		self::$dir_url = WP_CONTENT_URL.'/plugins/' . TexyPlugin::$dir_name . '/';
		self::$dir_path = WP_PLUGIN_DIR. '/' . TexyPlugin::$dir_name . '/';
		self::$cache_dir_path = TexyPlugin::$dir_path . 'texy.cache/';
		
		$uploads_dir_relative = (get_option('upload_path')) ? get_option('upload_path') : 'wp-content/uploads';
		self::$uploads_dir_url = get_bloginfo('wpurl') .'/'. $uploads_dir_relative . '/';
		
		self::prepareSettings();
		
		// localization
		load_plugin_textdomain(self::$abbr, 'wp-content/plugins/' . self::$dir_name . '/languages/');

		// hooks
		add_action('admin_menu', array (self::$abbr, 'admin_menu'));
		
		if (self::$settings['visual_editor_disabled']) {
			add_action('profile_update', array(__CLASS__, 'disable_visual_editor'));
			add_action('user_register', array(__CLASS__, 'disable_visual_editor'));
		}
		
	}
	
	
	/**
	 * Loads settings from db (wp_options) and stores them to self::$settings[setting_name_without_plugin_prefix]
	 * Settings-names are in db prefixed with "{self::$abbr}_", keys in $settings aren't. Very reusable.
	 * @see self::$settings
	 * @return void
	 */
	public static function prepareSettings () {

		foreach (self::$settings as $name => $default_value) {
			if (false !== ($option = get_option(self::$abbr . '_' . $name))) {
				self::$settings[$name] = $option;
			} else {
				// do nothing, let there be the default value
			}
		}

		// self::debug(self::$settings);

	}
	


	// Hook: Action: admin_menu
	// Descr: adds own item into menu in administration
	function admin_menu () {

		if (function_exists('add_submenu_page'))
			add_options_page(
				self::$short_name, // page title
				self::$short_name, // menu-item label
				'manage_options',
				'texy/admin-settings.php'
				);

	}
	
	
	
	// Expected input: 
	// '<a href="http://localhost:8888/Work-for-work/10.01.hledackem/1.wp/tyden-hledackem/testovaci-tyden-hledackem/attachment/photo5/" rel="attachment wp-att-12"><img src="http://localhost:8888/Work-for-work/10.01.hledackem/1.wp/wp-content/uploads/2010/02/photo5-580x386.jpg" alt="" title="photo5" width="580" height="386" class="alignnone size-medium wp-image-12" /></a>'
	// '12'
	// ''
	// 'photo5'
	// 'none'
	// 'http://localhost:8888/Work-for-work/10.01.hledackem/1.wp/tyden-hledackem/testovaci-tyden-hledackem/attachment/photo5/'
	// 'medium'
	// ''
	
	public static function image_send_to_editor ($html, $id, $caption, $title, $align, $url, $size, $alt = '') {
		// error_reporting(E_ALL);
		list( $src, $width, $height ) = image_downsize($id, $size);
		$src = str_replace(self::$uploads_dir_url, '', $src);

		$code = '[* '.$src.' ';
		
		if ($alt)
			$code .= ".($alt) "; // @maybe remove some characters from $alt first
		
		switch ($align) {
			case 'none': 
				$code .= '*]'; 
				break;
			case 'left': 
				$code .= '<]';
				break;
			case 'right':
				$code .= '>]';
				break;
			default: $code .= '*]'; break;
		}
		
		if ($url) {
			// maybe shorten image-url
			if (0 === strpos($url, self::$uploads_dir_url)) // @todo AND its settings state
				$url = '[* '. str_replace(self::$uploads_dir_url, '', $url) .' *]';
			
			// maybe shorten local URL
			elseif (0 === strpos($url, get_bloginfo('url'))) {
				$url = substr($url, strpos($url, '/', 7));
			}
			$url = ':'.$url;
		}
		$code .= $url;
		
		
		if ($caption)
			$code .= " *** $caption"; 
		
		// $code .= "\n"; // buggs (new line in JS string -> fail)
		
		if ($align == 'center') 
			$code .= ' .<>';
		
		return $code;
		
	}

	
	
	/**
	 * Disable the visual editor for all users
	 * 
	 * We can ignore the passed $userID because we're disabling for all users.
	 * (c) ?
	 */
	function disable_visual_editor($userID = false) {

		global $wpdb;

		$wpdb->query("UPDATE `" . $wpdb->prefix . "usermeta` SET `meta_value` = 'false' WHERE `meta_key` = 'rich_editing'");

	}

	
	/**
	 * 
	 */
	function clearCache() {

		if (!is_dir(TexyPlugin::$cache_dir_path) OR !is_writable(TexyPlugin::$cache_dir_path))
			return false;

		if ($handler = opendir(TexyPlugin::$cache_dir_path)) {
		
			$i = 0;
			while (false !== ($filename = readdir($handler))) {
				
				if (!in_array($filename, array('.', '..', '.htaccess'))) {
					if (unlink(TexyPlugin::$cache_dir_path . $filename))
						$i++;
					else 
						return false; // one fails all fail
				}
			
			}
			closedir($handler);
			return $i;

		} 
		else
			return false;
		
	}
	

}
TexyPlugin::Init();