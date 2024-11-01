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
	
   /**
    * function to print a single form element
	*/
if(!function_exists('xpu_PrintFormElement'))
{
   function xpu_PrintFormElement($element)
   { 
      	 if($element['type'] == "hidden"):
			echo $element['html'];
    	 elseif($element['required']): ?>
		  	<div class="element required">
            	<label for="<?php echo $element['name']; ?>">*&nbsp; <?php echo $element['label']; ?></label>
                <?php echo $element['html']; ?>
        	</div>
   <?php else: ?>
   		  	<div class="element">
            	<?php // echo $element['label']; ?> 
                <label for="<?php echo $element['name'].'">'.$element['label']; ?></label>
                <?php echo $element['html']; ?>
        	</div>
   <?php endif;
   }
}
   // Send no-cache headers
   header('Expires: Mon, 26 Jul 2002 05:00:00 GMT');              // Date in the past
   header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
   header('Cache-Control: no-cache="set-cookie", private');       // HTTP/1.1
   header('Pragma: no-cache');                                    // HTTP/1.0

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php echo $title ?></title>

<style type="text/css">
html
{
	background-color: threedface;
}
	
body
{
	width: 500px;
	background-color: threedface;
	color: infotext;
	font-family: Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11pt;
}
	
br, .spacer
{
	clear: both;
}

.form
{
	position: relative;	
}

.form .errors
{
	border: 1px solid red;
	padding: 4px;
	margin-bottom: 10px;
	font-weight: bold;
}

.form .errors p
{
	padding: 0px;
	margin: 0px;
}


.form .element
{
	clear: both;
	padding-top: 5px;
	font-size: 100%;

}

.form .element label
{
	float: left;
	width: 100px;
	text-align: right;
	padding-right: 20px;
	font-size: 90%;
}

.form .element input,
.form .element select,
.form .element textarea,
.form .element .static,
.form .element a
{
	width: 300px;
	font-size: 90%;
}

.form .element .date
{
	width: auto;
}

.form .element .checkbox
{
	width: auto;
}

.form .element .textarea
{
	height: 7em;
}

.form .element .submit
{
	float: left;
	width: auto;
}

.form .element.required label
{
	color: #930;
}

</style>

<script language="javascript">

function OnBack()
{
	<?php echo $backscript ?>

}

function OnNext()
{
	<?php echo $nextscript ?>

}

function OnCancel()
{
	// Don't know what this is good for:
  	content.innerHtml+='<br>OnCancel';
}

function window.onload()
{
	<?php
		if($title) echo 'window.external.SetHeaderText("'.$title.'","'.$formtitle.'");'."\n";
		if($buttons) echo "window.external.SetWizardButtons($buttons);"."\n";
		if($onload) echo $onload;
	?>

} 

</script>


</head>

<body>

<?php if($description) echo '<div id="description">'.$description."</div>"; ?>
	
<?php if($form):?>
    <div class="box">
    <div class='form'>
    <div class="spacer"></div>
    <?php echo $form['javascript'] ?>
    
    <?php if($form['errors']): ?>
    <div class='errors'>
        <p>There are some errors while processing the form. Please correct the following:</p>
        <ul>
        <?php foreach($form['errors'] as $error): ?>
            <li><?php echo $error ?></li>
        <?php endforeach; ?>    
        </ul>
    </div>
    <?php endif; ?>
	
    <form <?php echo $form['attributes'] ?> >
        <?php echo $form['hidden'] ?>
        <?php 
        foreach($form['elements'] as $element) 
             xpu_PrintFormElement($element);
        
        if($form['sections']):
			foreach($form['sections'] as $section): ?>
				<div class='section'>
				<?php if($section['header']):?><div class="header"><?php echo $section['header']; ?></div><?php endif;?>
				<?php foreach($section['elements'] as $element):
						 if($element['elements']): ?>
							 <div class='group'>
							 <?php foreach($element['elements'] as $item) xpu_PrintFormElement($item); ?>
							 </div>		
				   <?php else:
							xpu_PrintFormElement($element);
						 endif;
					  endforeach; ?>
				</div>
			<?php endforeach; 
		endif;?>
    </form>
    <div class="spacer"></div>
    </div>
    </div>
<?php endif;?>


</body>
</html>

