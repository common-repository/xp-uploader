<?php
/*
	This file is part of YAPB XP Upload Wizard.

    'YAPB XP Upload Wizard' is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    'YAPB XP Upload Wizard' is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with 'YAPB XP Upload Wizard'.  If not, see <http://www.gnu.org/licenses/>.
	
	Note that this software makes use of other libraries that are published under their own
	license. This program in no way tends to violate the licenses under which these 
	libraries are published.
*/

//require wp stuff
$wpdir = realpath(dirname(__file__) . '/../../../');
require_once(realpath($wpdir . '/wp-config.php'));
require_once(realpath($wpdir . '/wp-admin/includes/admin.php'));

//require PEAR QuickForm functionality
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__file__) . '/lib/pear'));
require_once('HTML/QuickForm.php');

//state machine states
define('XPU_REGISTER', 'reg');
define('XPU_LOGIN' , 'login');
define('XPU_UPLOADINFO', 'info');
define('XPU_CATEGORY' , 'selcategory');
define('XPU_NEWCATEGORY' , 'newcategory');
define('XPU_PREPARE' , 'prepare');
define('XPU_UPLOAD' , 'upload');
define('XPU_DONE' , 'done');

//make sure that errors are shown in wizard template
set_error_handler("xpu_errorHandler");

//go for it...
@session_start();
xpu_start();

/** helper function to create a http link; */
function xpu_createlink($step)
{
	$dbstring = xpu_onDebug() ? '&xpdebug' : '';
	return 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ] . "?step=$step".$dbstring;
}

/** check if debugMode */
function xpu_onDebug()
{
	return isset($_REQUEST['xpdebug']);
}	

/** function to show the template */
function xpu_showpage($attrs)
{
	extract($attrs);
	
	if(isset($form))
	{
		//in case of debugging, add a submit button to any form
		if(xpu_onDebug()) $form->addElement('submit', 'go', 'go', 'class=button');
		
		$form = $form->toArray();
	}
	
	include_once('xpu_template.php');
	exit;
}

/** function to show error using the template */
function xpu_errorHandler($errno, $errstr, $errfile, $errline) 
{
	switch ($errno) 
	{
		case E_ERROR          : $errType = "ERROR"; break;
		//case E_WARNING        : $errType = 'WARNING'; break;
		case E_PARSE          : $errType = 'PARSING ERROR'; break;
		//case E_NOTICE         : $errType = 'NOTICE'; break;
		case E_CORE_ERROR     : $errType = 'CORE ERROR'; break;
		case E_CORE_WARNING   : $errType = 'CORE WARNING'; break;
		case E_COMPILE_ERROR  : $errType = 'COMPILE ERROR'; break;
		case E_COMPILE_WARNING : $errType = 'COMPILE WARNING'; break;
		case E_USER_ERROR     : $errType = 'USER ERROR'; break;
		case E_USER_WARNING   : $errType = 'USER WARNING'; break;
		//case E_USER_NOTICE    : $errType = 'USER NOTICE'; break;
		//case E_STRICT         : $errType = 'STRICT NOTICE'; break;
		//case E_RECOVERABLE_ERROR  : $errType = 'RECOVERABLE ERROR'; break;
	}
	if($errType)
		xpu_error("A $errType occured in $errfile on line $errline: $errstr");
	else
		return false; //default handler
}

/** show error message */
function xpu_error($message)
{
	$attrs['description'] = "The upload has been cancelled, or an error occurred. <br/> $message";
	$attrs['formtitle'] = "Cancel/Error";
	$attrs['title'] = "Upload unexpectedly finished";
	$attrs['backscript'] =  "window.external.FinalBack();";
	$attrs['buttons'] = "1,0,0";
	xpu_showpage($attrs);
}


/** run the statemachine */
function xpu_start()
{
	$step = $_REQUEST['step'];
	
	//go into state machine
	while(true)
	{
		switch($step)
		{
			case XPU_REGISTER:
				xpu_login();
				$step = xpu_do_reg();
				break;
			case XPU_LOGIN:
				//no explicit action
			case XPU_UPLOADINFO:
				
				//clear session info
				if(xpu_onDebug())
				{
					unset($_SESSION['xpu_manifest']);
					unset($_SESSION['xpu_category']);
					unset($_SESSION['xpu_author']);
					unset($_SESSION['xpu_exifdate']);
					unset($_SESSION['xpu_tags']);
					unset($_SESSION['xpu_total']);
				}
			
				xpu_login();
				$step = xpu_get_info();
				break;
			case XPU_CATEGORY:
				xpu_login();
				$step = xpu_category();
				break;
			case XPU_NEWCATEGORY:
				xpu_login();
				$step = xpu_newcategory();
				break;
			case XPU_PREPARE:
				xpu_login();
				$step = xpu_prepare();
				break;
			case XPU_UPLOAD:
				xpu_login();
				$step = xpu_upload();
				break;
			case XPU_DONE:
				$step = xpu_done();
				break;
			default:
				$step = XPU_UPLOADINFO;
		}
	}
}

/** login the user */
function xpu_login()
{
	//create album selection form
	$form = new HTML_QuickForm("LoginForm",'post', xpu_createlink(XPU_LOGIN),NULL,NULL, true);
	$form->addElement('text', 'name', 'Name','class=text');
	$form->addElement('password', 'password', 'Password','class=password');
	$form->addElement('checkbox', 'remember', 'Remember Me', '', 'class=checkbox');
	$form->addRule('name', 'You must specify a name', 'required');
	$form->addRule('password', 'You must specify a password', 'required');
	
	//get credentials from post (if any)
	$credentials = '';	
	if($form->validate())
	{
		//parse form input
		$values = $form->exportValues();
		$credentials['user_login'] = sanitize_user($values['name']);
		$credentials['user_password'] = $values['password'];
		$credentials['remember'] = isset($values['remember']);
	}
	
	//try signon
	$user = wp_signon($credentials);
	
	//if no success, show login form
	if(is_wp_error($user))
	{
		//show login page
		$attrs['form'] = $form;
		$attrs['formtitle'] = 'Login';
		$attrs['title'] =  'Please provide username and password';
		$attrs['nextscript'] =  "LoginForm.submit();";
		$attrs['backscript'] =  "window.external.FinalBack();";
		$attrs['buttons'] = "1,1,0";
		xpu_showpage($attrs);
	}
}

function xpu_do_reg()
{
		header('Content-Type: application/octet-stream; name="xppubwiz.reg"');
		header('Content-disposition: attachment; filename="xppubwiz.reg"');

		$id = strtr(get_bloginfo('url'), '/.:', '___');
		$name =  get_bloginfo('name');
		$description = 'Publish to '.get_bloginfo('url');
		$link = xpu_createlink(XPU_LOGIN);
		$icon = get_bloginfo('url') . '/favicon.ico';
						
		echo
			'Windows Registry Editor Version 5.00' . "\n\n" .
			'[HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\PublishingWizard\\PublishingWizard\\Providers\\' . $id . ']' . "\n" .
			'"displayname"="' . $name . '"' . "\n" .
			'"description"="' . $description . '"' . "\n" .
			'"href"="'. $link . "\"\n" .
			'"icon"="' . $icon . '"';

		exit;
}


function xpu_get_info()
{
	global $user_ID;
	
	if(xpu_onDebug())
	{
		//set random filelist for debugging...
		$_SESSION['xpu_manifest'] = '<transfermanifest><filelist><file id="0" source="C:\\Scan253.jpg" extension=".jpg" contenttype="image/jpeg" destination="Scan253.jpg" size="76422"><metadata><imageproperty id="cx">1055</imageproperty><imageproperty id="cy">1645</imageproperty></metadata></file></filelist></transfermanifest>';
		
		return XPU_CATEGORY;
	}
	
	//create info form
	$form = new HTML_QuickForm("GetInfoForm",'post', xpu_createlink(XPU_UPLOADINFO),NULL,NULL, true);
	$form->addElement('hidden', 'manifest', ""); //manifest with picture info

	//parse form input (if any)
	if($form->validate())
	{
		$values = $form->exportValues();
		$_SESSION['xpu_manifest'] = stripslashes($values['manifest']);
				
		//success
		return XPU_CATEGORY;
	}

	//show page
	$attrs['form'] = $form;
	$attrs['formtitle'] = 'Get File Information';
	$attrs['title'] =  'Get File Information';
	$attrs['onload'] =  "
		var xml = window.external.Property('TransferManifest'); 
		GetInfoForm.manifest.value = xml.xml; 
		GetInfoForm.submit();";
	
	$attrs['backscript'] =  "window.external.FinalBack();";
	$attrs['buttons'] = "1,1,0";
	xpu_showpage($attrs);
}

/** select category and other upload information */
function xpu_category()
{	
	global $user_ID;
	
	//set image info default values, reuse set values when existing
	$defaults = array(
		'category' => isset($_SESSION['xpu_category']) ? $_SESSION['xpu_category'] : (get_option("yapb_default_post_category_activate") ? get_option("yapb_default_post_category") : get_option('default_category')),
		'author' => isset($_SESSION['xpu_author']) ? $_SESSION['xpu_author'] : $user_ID,
		'exifdate' => isset($_SESSION['xpu_exifdate']) ? $_SESSION['xpu_exifdate'] : get_option("yapb_check_post_date_from_exif"),
		'tags' => isset($_SESSION['xpu_tags']) ? $_SESSION['xpu_tags'] : get_option("xpu_iptc")
		);
	
	//if user does *not* wish to set details per upload, set upload session values to default values
	if(!get_option("xpu_details"))
	{
		$_SESSION['xpu_category'] = $defaults['category'];
		$_SESSION['xpu_author'] = $defaults['author'];
		$_SESSION['xpu_exifdate'] = $defaults['exifdate'];
		$_SESSION['xpu_tags'] = $defaults['tags'];
		//goto next step
		return XPU_PREPARE;
	}
	
	//create info form
	$form = new HTML_QuickForm("SelectForm",'post', xpu_createlink(XPU_CATEGORY),NULL,NULL, true);
	$categories = get_categories('hide_empty=0&orderby=name&hierarchical=0');
	foreach($categories as $category)
	{
		$selCategories[$category->cat_ID] = $category->name;
	}	
	$form->addElement('select', 'category', 'Category',$selCategories, 'class=select');
	$form->addElement('link', XPU_NEWCATEGORY, '', xpu_createlink(XPU_NEWCATEGORY),"New Category...", 'class=link');
	$users = get_users_of_blog();
	foreach($users as $user)
	{
		$selUsers[$user->user_id] = $user->display_name;
	}	
	$form->addElement('select', 'author', 'Post as', $selUsers, 'class=select');
	$form->addElement('checkbox', 'exifdate', 'Use EXIF date', '', 'class=checkbox');
	$form->addElement('checkbox', 'tags', 'Use IPTC data', '', 'class=checkbox');
		
	//set default values
	$form->setDefaults($defaults);
	
	//parse form input (if any)
	if($form->validate())
	{
		$values = $form->exportValues();
		$_SESSION['xpu_category'] = $values['category'];
		$_SESSION['xpu_author'] = $values['author'];
		$_SESSION['xpu_exifdate'] = $values['exifdate'];
		$_SESSION['xpu_tags'] = $values['tags'];
		
		//next step
		return XPU_PREPARE;
	}
	
	//show page
	//$attrs['description'] .= '<pre>'.htmlentities($_SESSION['xpu_manifest']).'</pre>';
	$attrs['form'] = $form;
	$attrs['formtitle'] = 'Select Category';
	$attrs['title'] =  'Select Category';
	$attrs['nextscript'] =  "SelectForm.submit();";
	$attrs['backscript'] =  "window.external.FinalBack();";
	$attrs['buttons'] = "1,1,0";
	xpu_showpage($attrs);
}

/** form to create new category */
function xpu_newcategory()
{	
	//create new category form
	$form = new HTML_QuickForm("CategoryForm",'post', xpu_createlink(XPU_NEWCATEGORY),NULL,NULL, true);
	$form->addElement('text', 'cat_name', 'Name', 'class=text');
	$form->addElement('textarea', 'category_description', 'Description', 'class=textarea');
	$form->addRule('cat_name', 'You must specify a name', 'required');
	$categories = get_categories('hide_empty=0&orderby=name&hierarchical=0');
	foreach($categories as $category)
	{
		$selCategories[$category->cat_ID] = $category->name;
	}
	$selCategories[0] = "(No parent)";	
	$form->addElement('select', 'category_parent', 'Parent',$selCategories, 'class=select');
	
	//set default values		
	$form->setDefaults(array(
		'category_parent' => 0
		));
	
	//parse form input (if any)
	if($form->validate())
	{
		$values = $form->exportValues();

		$result = wp_insert_category($values);
		if(is_wp_error($result))
			$form->setElementError('category_name', $result->get_error_message());
		else
		{
			$_SESSION['xpu_category'] = $result;
			
			//next step
			return XPU_CATEGORY;
		}
	}
	
	//show page
	$attrs['form'] = $form;
	$attrs['formtitle'] = 'Create Category';
	$attrs['title'] =  'Create New Category';
	$attrs['nextscript'] =  "CategoryForm.submit();";
	$attrs['backscript'] =  "window.location='".xpu_createlink(XPU_CATEGORY)."';";
	$attrs['buttons'] = "1,1,0";
	xpu_showpage($attrs);
}

/** get upload info (manifest) and add upload information */
function xpu_prepare()
{
	global $user_ID;
	
	//still using PHP4 dom library. include when PHP5 is used...
	if (version_compare(PHP_VERSION,'5','>='))
		require_once(realpath(dirname(__FILE__).'/lib/xml_dom.php'));
		
	//check existence of manifest file
	if(!isset($_SESSION['xpu_manifest']) || strlen($_SESSION['xpu_manifest'])==0)
		trigger_error("No file information received!", E_USER_ERROR);
	
	//create xml dom from manifest
	$dom = domxml_open_mem($_SESSION['xpu_manifest']);

	//find all file nodes in xml using xpath
	$xpath =& $dom->xpath_new_context();
	$result =& $xpath->xpath_eval('//transfermanifest/filelist/file');
	$files =& $result->nodeset;
	
	//set counters to check if all has gone right
	$_SESSION['xpu_total'] = count($files); 
	
	//add post elements
	foreach($files as $key => $filenode ) 
	{
	  $filenode =& $files[$key]; //get reference to filenode iso copy (PHP4 necessity)
	  
	  //define post information
	  $postinfo = array(
	   "post_type" => "post",
	   "post_status" => "publish",
	   "post_author" => $_SESSION['xpu_author'],
	   "post_category[]" => $_SESSION['xpu_category'],
	   "user_ID" => $user_ID,
	   "post_title" => str_replace("_"," ", substr($filenode->get_attribute('destination'), 0, strrpos($filenode->get_attribute('destination'), "."))),
	   "comment_status" => get_option('default_comment_status'),
	   "ping_status" => get_option('default_ping_status'),
	   "action" => "XPUPost", //needed for wp_handle_upload
	   );
	   if($_SESSION['xpu_exifdate']) $postinfo['exifdate'] = "1";
					  
	  //create post element
	  $post =& $dom->create_element('post');
	  $post->set_attribute("href", xpu_createlink(XPU_UPLOAD));
	  $post->set_attribute("name", 'yapb_imageupload'); //make the post a yapb image post
	  
	  //add additional post form data
	  foreach($postinfo as $name => $value)
	  {
		  $formdata =& $dom->create_element('formdata');
		  $formdata->set_attribute("name", $name);
		  $formdata->set_content($value);
		  $post->append_child($formdata);
		  unset($formdata);
	  } 
	  
	  //append post info
	  $filenode->append_child($post);

	  // make sure the filenode is not overwritten in the next run!
	  unset($filenode); 
	}
	unset($result);
		
	//add upload information
	$uploadinfo =& $dom->create_element('uploadinfo');
	$failurepage =& $dom->create_element('failurepage');
	$failurepage->set_attribute('href', xpu_createlink(XPU_ERROR));
	$uploadinfo->append_child($failurepage);
	$cancelpage =& $dom->create_element('cancelpage');
	$cancelpage->set_attribute('href', xpu_createlink(XPU_ERROR));
	$uploadinfo->append_child($cancelpage);
	$htmlui =& $dom->create_element('htmlui');
	if(((int)$_SESSION['xpu_total'])==1)
		$htmlui->set_attribute('href',xpu_createlink(XPU_DONE.'&ispost=true'));
	else
		$htmlui->set_attribute('href',xpu_createlink(XPU_DONE.'&catid='.$_SESSION['xpu_category']));
	$uploadinfo->append_child($htmlui);
	$result =& $xpath->xpath_eval('//transfermanifest');
	$result->nodeset[0]->append_child($uploadinfo);
	
	//write the info to XML string
	$xml = trim($dom->html_dump_mem());
			
	//show page
	$attrs['description'] = "
		Number of files to be uploaded: {$_SESSION['xpu_total']}<br>
		Click next/finish to upload the files";
	$attrs['onload'] = "
		var newxml = '".addslashes($xml)."';
		var manxml = window.external.Property('TransferManifest');
		manxml.loadXML(newxml);
		window.external.Property('TransferManifest') = manxml;
		";
	$attrs['title'] =  'Upload Pictures';
	$attrs['nextscript'] =  "window.external.finalNext();";
	$attrs['backscript'] =  "window.location='".xpu_createlink(XPU_CATEGORY)."';";
	$attrs['buttons'] = "1,1,1";
	
	
	if(xpu_onDebug())
	{ 
		//create form that shows and allows tweaking of chosen values
		$attrs['description'] .= '<pre>'.htmlentities($xml).'</pre>';
		$form = new HTML_QuickForm("UploadForm",'post', xpu_createlink(XPU_UPLOAD),NULL,NULL, true);
		$form->addElement('file', 'yapb_imageupload','yapb_imageupload', 'class=file');
		foreach($postinfo as $name=>$value)
			$form->addElement('text', $name, $name, 'class=text');

		$form->setDefaults($postinfo);
		$attrs['form'] = $form;
	}
		
	xpu_showpage($attrs);
}

/** add uploaded image to blog. Called once for every image */
function xpu_upload()
{
	//get link to post
	global $post;
		
	//reuse form post of WP
	$postid = write_post();
	$_SESSION['xpu_postid'] = $postid;
	
	if(xpu_onDebug())
		echo "<p>New post added: $postid</p>";
	
	//optionally add tags from image info
	if($_SESSION['xpu_tags'])
	{
		$post = get_post($postid);
		if(yapb_is_photoblog_post())
		{
			if(xpu_onDebug())
				echo "<p>Post is a YABP post</p>";
			
			$image = $post->image->systemFilePath();
			unset($post->image); //get rid of image, causes errors when updating post
			
			//get iptc info
			getimagesize($image, $data);
			if(isset($data['APP13']))
			{
				$iptc = iptcparse($data['APP13']);
				if($iptc)
				{
					$updates = array('ID'=>"$postid");
					
					//get keywords
					if($iptc['2#025'])
						$updates['tags_input'] = implode(", ",$iptc['2#025']);

					//get title	
					if($iptc["2#005"][0]) //title
						$updates['post_title'] = $iptc["2#005"][0];
					else if($iptc["2#105"][0]) //headline
						$updates['post_title'] = $iptc["2#105"][0];
					
					//get description
					if($iptc["2#120"][0])
						$updates['post_content'] = $iptc["2#120"][0];
						
					wp_update_post(add_magic_quotes($updates));
					
					if(xpu_onDebug())
						echo "<p>IPTC data set</p>";

				}
			}
			
		}
	}
	
	
	
	exit;		
}

/** finalize upload, redirect to blog (image or category) */
function xpu_done()
{
	global $wpdb;
	
	//link to post, if one post, otherwise to selected category
	if(isset($_REQUEST['ispost']))
	{
		if(isset($_SESSION['xpu_postid']))
			$postid = $_SESSION['xpu_postid'];
		else
			//session info gets lost: do best guess of posted image (last modified)
			$postid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
		
		header("Location: ".get_permalink($postid));
	}
	else if(isset($_REQUEST['catid']))
		header("Location: ".get_category_link($_REQUEST['catid']));
	else
		header("Location: ".get_bloginfo('url'));
		
	exit;
}

?>
