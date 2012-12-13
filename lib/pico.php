<?php

class Pico {

	static $index = 0;

	function __construct()
	{
		// Get request url and script url
		$url = '';
		$request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
		$script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';

		// Get our url path and trim the / of the left and the right
		if($request_url != $script_url) $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');

		// Load the settings
		$settings = $this->get_config();
		$env = array('autoescape' => false);
		if($settings['enable_cache']) $env['cache'] = CACHE_DIR;

		if($settings['draft_auth'] !== false && strpos($url, '?draft') - strlen($url) + 6 === 0) $ext = '.draft';
		else $ext = '.md';

		if($ext === '.draft') {
			if(!$this->draft_auth($settings['draft_auth'])) {
				header('WWW-Authenticate: Basic realm="'.$settings['site_title'].'"', true, 401);
				exit;
			}
			$url = substr($url, 0, strlen($url) - 6);
		}

		// load the navigation
		$navigation = $this->get_navigation();

		// Get the file path
		if($url) $file = CONTENT_DIR . $url;
		else $file = CONTENT_DIR .'index';

		// Load the file
		if(is_dir($file)) $file = CONTENT_DIR . $url . '/index' . $ext;
		else $file .= $ext;

		if(file_exists($file)) $content = file_get_contents($file);
		else {
			$content = file_get_contents(CONTENT_DIR .'404.md');
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
		}

		$meta = $this->read_file_meta($content);
		$content = preg_replace('#/\*.+?\*/#s', '', $content); // Remove comments and meta
		$content = $this->parse_content($content);
		
		// Load the theme
		$theme = $meta['theme'] ? $meta['theme'] : $settings['theme'];
		Twig_Autoloader::register();
		$loader = new Twig_Loader_Filesystem(THEMES_DIR . $theme);
		$twig = new Twig_Environment($loader, $env);
		echo $twig->render('index.html', array(
			'config' => $settings,
			'base_dir' => rtrim(ROOT_DIR, '/'),
			'base_url' => $settings['base_url'],
			'theme_dir' => THEMES_DIR . $theme,
			'theme_url' => $settings['base_url'] .'/'. basename(THEMES_DIR) .'/'. $theme,
			'site_title' => $settings['site_title'],
			'meta' => $meta,
			'content' => $content,
			'navigation' => $navigation
		));
	}

	function draft_auth($credentials) {
		if(!isset($_SERVER['HTTP_AUTHORIZATION']))
		{
			return false;
		}

		return $_SERVER['HTTP_AUTHORIZATION'] === 'Basic '.base64_encode($credentials);
	}

	function parse_content($content)
	{
		$content = str_replace('%base_url%', $this->base_url(), $content);
		$content = $this->custom_vars($content); // custom variables available in the markdown
		$content = Markdown($content);

		return $content;
	}

	function read_file_meta($content)
	{
		global $headers;
		$headers = array(
			'title'       => 'Title',
			'description' => 'Description',
			'robots'      => 'Robots',
			'theme'       => 'Theme'
		);

	 	foreach ($headers as $field => $regex){
			if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match) && $match[1]){
				$headers[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
			} else {
				$headers[ $field ] = '';
			}
		}

		return $headers;
	}

	function get_config()
	{
		global $config;

		$defaults = array(
			'site_title' => 'Pico',
			'base_url' => $this->base_url(),
			'theme' => 'default',
			'enable_cache' => false,
			'draft_auth' => false
		);

		foreach($defaults as $key=>$val){
			if(isset($config[$key]) && $config[$key]) $defaults[$key] = $config[$key];
		}

		return $defaults;
	}

	function base_url()
	{
		global $config;
		if(isset($config['base_url']) && $config['base_url']) return $config['base_url'];

		$url = '';
		$request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
		$script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
		if($request_url != $script_url) $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');

		return rtrim(str_replace($url, '', "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), '/');
	}

	// replace custom variables within the content
	function custom_vars($c)
	{
		global $natureconfig;
		$changedContent = $c;

		foreach($natureconfig as $key=>$val){
			if(isset($val) && $val){
				$changedContent = str_replace('%'.$key.'%', $val, $changedContent);
			}
		}
		return $changedContent;
	}

	// build the navigation
	function get_navigation()
	{
		$start = "<nav><ul>";
		$end = "</nav></ul>";
		$main = $this->make_nav();
		
		return $start.$main.$end;
	}

	// building the navigation based on:
	// http://kvz.io/blog/2007/10/03/convert-anything-to-tree-structures-in-php/

	// capture the folder structure into an associative array
	function make_nav()
	{
		$data = '';
		if(exec("find ".CONTENT_DIR, $files)){
		    $key_files = array_combine(array_values($files), array_values($files));
		    $key_files_sorted = array();
		    $replaceValue = rtrim(CONTENT_DIR, "/");
		    foreach ($key_files as $k => $v) {
			    $key = str_replace($replaceValue, '', $k);
			    $value = str_replace($replaceValue, '', $v);
			    if($key !== '' && strpos($key,'404') === false && strpos($key,'index.') === false){
			    	if($key === "/"){
			    		$key = "home";
			    		$value = "home";
			    	}
			    	$key_files_sorted[$key] = $value;
			    }
			}
		    $tree = $this->explode_tree($key_files_sorted, "/");
		}

		return $this->make_list($tree, $key_files_sorted);
	}

	// convert nav array into tree structure (multi-dimensional array)
	function explode_tree($array, $delimiter = '_', $baseval = false)
	{
	    if(!is_array($array)) return false;
	    $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
	    $returnArr = array();
	    foreach ($array as $key => $val) {
	        // Get parent parts and the current leaf
	        $parts    = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
	        $leafPart = array_pop($parts);

	        // build parent structure 
	        // might be slow for really deep and large structures
	        $parentArr = &$returnArr;
	        foreach ($parts as $part) {
	            if (!isset($parentArr[$part])) {
	                $parentArr[$part] = array();
	            } elseif (!is_array($parentArr[$part])) {
	                if ($baseval) {
	                    $parentArr[$part] = array('__base_val' => $parentArr[$part]);
	                } else {
	                    $parentArr[$part] = array(); 
	                }
	            }
	            $parentArr = &$parentArr[$part];
	        }

	        // add the final part to the structure
	        if (empty($parentArr[$leafPart])) {
	            $parentArr[$leafPart] = $val;
	        } elseif ($baseval && is_array($parentArr[$leafPart])) {
	            $parentArr[$leafPart]['__base_val'] = $val;
	        }
	    }
	    return $returnArr;
	}

	// check if there is an index file in the folder 
	function check_index($value)
	{
    	$filename = CONTENT_DIR.preg_replace( "/^\//", '', $value );
    	if($value === 'home'){
    		$filename = preg_replace( "/\/home$/", '', $filename );
    	}
    	$filename = $filename."/index.md";
    	// check this is a folder 
    	if( substr( $value, -strlen( '.md' ) ) !== '.md' ){
    		// if a folder, check an index file exists
    		if ( file_exists( $filename ) ) {
    			return true;
    		}
    		return false;
    	}

    	return true;
	}

	// turn multi-dimensional array into html
	function make_list($array, $keyarray)
	{
		// copy values from original associative array into an indexed array
		$keyvalues = array_values($keyarray);

		// base case: an empty array produces no list 
        if (empty($array)) return '';

        $output = '';
        foreach ($array as $key => $value){
        	// where are we in the recursive loop
			$index = Pico::$index;

        	// find the url from the $keyvalues array
			$location = $keyvalues[$index];

			// increment the count
            Pico::$index++;

        	if(is_array($value)){
        		// tidy up the display
        		$k = preg_replace( "/-/", ' ', $key );
        		// does this folder have an index file
        		if( $this->check_index($location) ){
            		$output .= '<li class="'.$key.'"><a href="'.$this->base_url().$location.'">'.$k.'</a><ul>'.$this->make_list($value, $keyvalues).'</ul>'.'</li>';
            	} else {
            		$output .= '<li class="'.$key.'">'.$k.'<ul>'.$this->make_list($value, $keyvalues).'</ul>'.'</li>';
            	}
            } else {
				// trim end of value until first /
	            $trim = preg_replace( "/\/[^\/]*$/", '', $value );

            	// check if this is the home link
            	if($key === 'home'){
            		$key = $this->base_url();
            	} else {
            		// used trimmed value to generate the link ($key)
            		$key = $this->base_url().$trim.'/'.$key;
            		// now remove trim string from the value to leave the last node
            		$trim = ltrim($trim, '/');
            		$value = str_replace($trim, '', $value);
            	}
            	
            	// tidy up the links (keys) and values
            	$v = ltrim($value, '/');
            	$v = rtrim($v, '.md');
            	$k = rtrim($key, '.md');
            	$class = $v;
            	$v = preg_replace( "/-/", ' ', $v );

            	if($this->check_index($value)){
            		$output .= '<li class="'.$class.'"><a href="'.$k.'">'.$v.'</a></li>';
            	} else {
            		$output .= '<li class="'.$class.'">'.$v.'</li>';
            	}
            }
        }
         
        return $output; 
    }

}

?>
