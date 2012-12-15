<?php
/*
	
	== PHP FILE TREE ==
	
		Version 1.1-john-DEV
	
	== AUTHOR ==
	
		Cory S.N. LaViska
		http://abeautifulsite.net/
	
	== CONTRIBUTORS ==
	
		John Ko
		
	== DOCUMENTATION ==
	
		For documentation and updates, visit http://www.abeautifulsite.net/blog/2007/06/php-file-tree/
		
*/

### Standard Function Interface ###

@define( 'PHP_FILE_TREE_CLASS',  'Php_File_Tree' );

function PhpFileTree($directory, $return_link, $extensions = array(), $version = "") {
#
# Initialize the parser and return the result of its transform method.
#
	# Setup static parser variable.
	static $parser;
	if (!isset($parser)) {
		$parser_class = PHP_FILE_TREE_CLASS;
		$parser = new $parser_class;
	}

	# Transform text using parser.
	return $parser->xhtml($directory, $return_link, $extensions, $version);
}

class Php_File_Tree {

function Php_File_Tree() {
}

function xhtml($directory, $return_link, $extensions = array(), $version = "") {
	// Generates a valid XHTML list of all directories, sub-directories, and files in $directory
	// Remove trailing slash
	if( substr($directory, -1) == "/" ) $directory = substr($directory, 0, strlen($directory) - 1);
	$code .= $this->php_file_tree_dir($directory, $return_link, $extensions, $version);
	return $code;
}

function php_file_tree_dir($directory, $return_link, $extensions = array(), $version = "", $first_call = true) {
	// Recursive function called by php_file_tree() to list directories/files
	
	// Get and sort directories/files
	if( function_exists("scandir") ) $file = scandir($directory); else $file = $this->php4_scandir($directory);
	natcasesort($file);
	// Make directories first
	$files = $dirs = array();
	foreach($file as $this_file) {
		if( is_dir("$directory/$this_file" ) ) $dirs[] = $this_file; else $files[] = $this_file;
	}
	$file = array_merge($dirs, $files);
	
	// Filter unwanted extensions
	if( !empty($extensions) ) {
		foreach( array_keys($file) as $key ) {
			if( !is_dir("$directory/$file[$key]") ) {
				$ext = substr($file[$key], strrpos($file[$key], ".") + 1); 
				if( !in_array($ext, $extensions) ) unset($file[$key]);
			}
		}
	}
	
	if( count($file) > 2 ) { // Use 2 instead of 0 to account for . and .. "directories"
		$php_file_tree = "<ul";
		if( $first_call ) { $php_file_tree .= " class=\"php-file-tree\""; $first_call = false; }
		$php_file_tree .= ">";
		foreach( $file as $this_file ) {
			if( 1 === preg_match( "/content\/index.md/", "$directory/$this_file" ) ) {
				$php_file_tree .= "<li class=\"pft-file indexmd\"><a href=\"/\">Home</a></li>";
			}
			if( $this_file != "." && $this_file != ".." && ( $version === "pico" && $this_file !== "index.md" ) && ( $version === "pico" && $this_file !== "404.md" ) ) {
				if( is_dir("$directory/$this_file") ) {
					// Directory
					$php_file_tree .= "<li class=\"pft-directory\"><a href=\"";
					if( $version === "pico" ) {
						if( file_exists( "$directory/$this_file"."/index.md" ) ) {
							$php_file_tree .= preg_replace("/\.\/content\//", "/", "$directory/$this_file");
						}
					} else {
						$php_file_tree .= "#";
					}
					$php_file_tree .= "\">" . htmlspecialchars($this_file) . "</a>";
					$php_file_tree .= $this->php_file_tree_dir("$directory/$this_file", $return_link ,$extensions, $version, false);
					$php_file_tree .= "</li>";
				} else {
					// File
					// Get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
					$ext = "ext-" . substr($this_file, strrpos($this_file, ".") + 1); 
					$link = str_replace("[link]", "$directory/" . urlencode($this_file), $return_link);
					if( $version !== "pico" ) {
						$php_file_tree .= "<li class=\"pft-file " . strtolower($ext) . "\"><a href=\"$link\">" . htmlspecialchars($this_file) . "</a></li>";
					} else {
						$link = preg_replace("/\.\/content\//", "/", $link);	//remove content folder
						$link = preg_replace("/\.md$/", "", $link);				//remove md
						$this_file = preg_replace("/\.md$/", "", $this_file);	//remove md
						$php_file_tree .= "<li class=\"pft-file " . strtolower($ext) . "\"><a href=\"$link\">" . htmlspecialchars($this_file) . "</a></li>";
					}
				}
			}
		}
		$php_file_tree .= "</ul>";
	}
	return $php_file_tree;
}

// For PHP4 compatibility
function php4_scandir($dir) {
	$dh  = opendir($dir);
	while( false !== ($filename = readdir($dh)) ) {
	    $files[] = $filename;
	}
	sort($files);
	return($files);
}

}
