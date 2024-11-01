<?php
	/*	
		Plugin Name: XP Uploader
		Plugin URI: http://joost.reuzel.nl/about/plugin/
		Version: 0.2
		Description: WordPress Plugin and extension to YAPB, which allows images to be posted using the Windows XP Publish to Web wizard.
		Author: Joost Reuzel
		Author URI: http://joost.reuzel.nl
	*/
	
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

	// Hook for adding admin menus
	add_action('admin_menu', 'xpu_add_admin_pages');
	
	function xpu_add_admin_pages()
	{
		add_options_page('XP Upload', 'XP Upload', 5, __FILE__, 'xpu_add_option_page');
	}
	
	function xpu_add_option_page()
	{

		//get link to registry file download
		$pathelems = preg_split('[/|\\\\]', dirname(__FILE__),-1, PREG_SPLIT_NO_EMPTY);
		$plugin_dirname = $pathelems[count($pathelems)-1];
		//echo "<em>"; print_r($pathelems); echo "</em>";
		$link = get_option('siteurl') . '/wp-content/plugins/' . $plugin_dirname . '/xpu_uploader.php?step=reg';
	
		//require PEAR QuickForm functionality
		set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__file__) . '/lib/pear'));
		require_once('HTML/QuickForm.php');
		
		//create options form
		$form = new HTML_QuickForm("XPUOptionsForm",'post', $_SERVER['REQUEST_URI'],NULL,NULL, true);
		$form->addElement('hidden', 'page');
		$form->addElement('checkbox', 'xpu_details',"", 'Choose submit information at each upload');
		$form->addElement('checkbox', 'xpu_iptc', "", 'Use IPTC data (tags, description and title)');
		$form->addElement('submit', 'submit', 'Submit');		
		
		$form->setDefaults(array('page'=>$_GET['page'], 'xpu_details'=>get_option('xpu_details'), 'xpu_iptc'=>get_option('xpu_iptc')));
		
		if($form->validate())
		{
			$values = $form->exportValues();
			update_option('xpu_details', $values['xpu_details']);
			update_option('xpu_iptc', $values['xpu_iptc']);
		}
		?>
        
        <div class="wrap">
			<h2>XP Image uploader</h2>
            
            <h3>Download registry settings</h3>
			<p>You can now upload your pictures using Windows XP publish to Web feature. Click <a href="<?php echo $link?>">here</a> to install.</p>

			<h3>XP Image uploader options</h3>
			<?php echo $form->toHtml(); ?>
		</div>
        <?php
	}
?>