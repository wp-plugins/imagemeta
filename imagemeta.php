<?php
/*
Plugin Name: ImageMeta
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: The fastest way to manage meta-data for your wordpress images.
Version: 0.4.1
Author: era404 Creative Group, Inc.
Author URI: http://www.era404.com
License: GPLv2 or later.
Copyright 2012  era404 Creative Group, Inc.  (email : in4m@era404.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//globals
define('RECORDS_PER_PAGE', 50);
define('IMAGEMETA_URL', admin_url() . 'options-general.php?page=imagemeta');

// Setup plugin menus
add_action( 'admin_menu', 'imagemeta_admin_menu' );
function imagemeta_admin_menu() {
	add_options_page( 'ImageMeta', 'ImageMeta', 'manage_options', 'imagemeta', 'imagemeta_plugin_options' );
}

// Setup plugin scripts and styles
wp_enqueue_script( 'ajax-script', plugins_url('/imagemeta.js', __FILE__), array('jquery'), 1.0 ); // jQuery will be included automatically
wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); // setting ajaxurl
add_action( 'wp_ajax_ajax_action', 'ajax_updatedb' ); 	//for updates


// Build admin page
function imagemeta_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	//get database object
	global $wpdb;
		
	//handle deleting images
	$cwpp = 0; $cwppm = 0;
	if(isset($_GET['remove'])) {
		$qwpp = "SELECT count(ID) FROM {$wpdb->prefix}posts WHERE ID={$_GET['remove']}"; $cwpp = $wpdb->get_var($qwpp);
		$rwpp = "DELETE FROM {$wpdb->prefix}posts WHERE ID={$_GET['remove']}"; $wpdb->query($rwpp);
		
		$qwppm = "SELECT count(meta_id) FROM {$wpdb->prefix}postmeta WHERE post_id={$_GET['remove']}"; $cwppm = $wpdb->get_var($qwppm);
		$rwppm = "DELETE FROM {$wpdb->prefix}postmeta WHERE post_id={$_GET['remove']}"; $wpdb->query($rwppm);
	}
	

	//handle sorting
	$sorting = array("d"=>"post_date ASC",
					 "d-"=>"post_date DESC",
					 "t"=>"post_title ASC",
					 "t-"=>"post_title DESC");

	$s = $_GET['s'] = (@!isset($_GET['s']) || !array_key_exists($_GET['s'],$sorting) ? "d" : $_GET['s']);
	$sort = $sorting[$_GET['s']];
		

	
	//get count, first
	$cq =  "SELECT ID from {$wpdb->prefix}posts, {$wpdb->prefix}postmeta
			WHERE {$wpdb->prefix}postmeta.post_ID = {$wpdb->prefix}posts.ID
			AND {$wpdb->prefix}postmeta.meta_key ='_wp_attached_file'
			GROUP BY {$wpdb->prefix}posts.ID";
	$crs = $wpdb->get_results($cq, ARRAY_A);
	$count = count($crs);
	//echo "COUNT: ".count($crs)."<br /><br />";
	
	/* build page array [$pg]
	*  0:total records
	*  1:records per page << defined above
	*  2:total pages
	*  3:current page (also:$p)
	*  4:record start
	*  5:record end
	*/
	
	$pg = array($count,RECORDS_PER_PAGE,ceil($count/RECORDS_PER_PAGE));
	$p = $_GET['p'] = $pg[3] = (!isset($_GET['p']) || $_GET['p']<1 || $_GET['p']>$pg[2] ? 1 : $_GET['p']);
	$pg[4] = ($pg[1]*$pg[3])-$pg[1]; 
	$pg[5]=(($pg[4]+$pg[1])-1);
			
	//build query
	$q =   "SELECT  {$wpdb->prefix}posts.ID as postid, 
					{$wpdb->prefix}posts.post_parent as parentid, 
					{$wpdb->prefix}posts.post_content, 
					{$wpdb->prefix}posts.post_title, 
					{$wpdb->prefix}posts.post_excerpt, 
					{$wpdb->prefix}posts.post_date, 
					{$wpdb->prefix}postmeta.meta_value as img,
	
			(SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_ID = postid AND meta_key='_wp_attachment_image_alt' ORDER BY meta_id DESC LIMIT 1) AS alt,
			(SELECT meta_id FROM {$wpdb->prefix}postmeta WHERE post_ID = postid AND meta_key='_wp_attachment_image_alt' ORDER BY meta_id DESC LIMIT 1) AS meta_id
			
			FROM 	{$wpdb->prefix}posts,{$wpdb->prefix}postmeta
			WHERE 	{$wpdb->prefix}postmeta.post_ID = {$wpdb->prefix}posts.ID
			AND 	{$wpdb->prefix}postmeta.meta_key = '_wp_attached_file'
			GROUP BY {$wpdb->prefix}postmeta.meta_id
			ORDER BY {$wpdb->prefix}posts.{$sort}
			LIMIT {$pg[4]},{$pg[5]}
			"; 
			//echo $q;
	
	//verify all images have a paired meta field / create record otherwise
	$imgs = $wpdb->get_results($q, ARRAY_A);
	foreach($imgs as $k=>$i) {
		if($i['meta_id']=="") {
			$wpdb->query("INSERT INTO {$wpdb->prefix}postmeta (meta_id,post_id,meta_key,meta_value) VALUES (NULL,{$i['postid']},'_wp_attachment_image_alt','')");
			}
	}

	//pull 30 rows of images
	$imgs = $wpdb->get_results($q, ARRAY_A);

	//myprint_r($imgs);
	addstyles();
	
	//build images table
?>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="donate">
Hello Friend.<br /><br />
Donations are entirely optional.<br /> 
If <b>ImageMeta</b> has made your life easier, and you wish to say thank you, a Secure PayPal link has been provided below.
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="JT8N86V6D2SG6">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
<?php 
	
	echo "<h1>Image Meta</h1><br />
		  A very light-weight plugin, designed to make updating meta-values (title, caption, description and alternate text) more efficient, by providing one central table of imagery and a duplicate button to copy this information across fields. <b>Note: </b>Updates are performed immediately after defocusing a field.<br /><br />";
	
	// handle pager
	echo "<div style='clear:both;'>&nbsp; Page: $p (".($pg[4]+1)." - ".($pg[5]+1)." of {$pg[0]}) ";
	for($page=1;$page<=$pg[2];$page++){ echo "<p class='pager'><a href='".IMAGEMETA_URL."&p={$page}&s={$s}'>{$page}</a></p>"; }
	echo "</div>
	<br /><br />";
	
	echo "<div id='warning' class='orange'>".(($cwpp>1||$cwppm>1)?"NOTE:</strong><br />($cwpp) post records removed.<br />($cwppm) postmeta records removed.<br /><br />":"")."</div>";
	echo "<table id='imagemetas'>";
	echo "<thead id='imagemetahead'><tr><th>&nbsp;</th>
			<th>
				<a href='".IMAGEMETA_URL."&s=t' title='Sort by Title A-Z' class='sort_a ".($_GET['s']=='t'?'selected':'')."'><span>Ascending</span></a>
				Title
				<a href='".IMAGEMETA_URL."&s=t-' title='Sort by Title Z-A' class='sort_d ".($_GET['s']=='t-'?'selected':'')."'><span>Descending</span></a>
			</th>
			<th>&nbsp;</th>
			<th>
				<a href='".IMAGEMETA_URL."&s=d' title='Sort by Date Ascending' class='sort_a ".($_GET['s']=='d'?'selected':'')."'><span>Ascending</span></a>
				Date
				<a href='".IMAGEMETA_URL."&s=d-' title='Sort by Date Descending' class='sort_d ".($_GET['s']=='d-'?'selected':'')."'><span>Descending</span></a>
			</th>
		</tr></thead>";
	
	$missing = 0;
	foreach($imgs as $k=>$i){
		$lg = ( wp_get_attachment_image_src( $i['postid'], 'large' ) );
		$sm = ( wp_get_attachment_image_src( $i['postid'], 'thumbnail' ) );
		$cp = plugins_url('/copyacross.gif', __FILE__);
		$mia = plugins_url('/missing.png', __FILE__);
		$rm = plugins_url('/delete.png', __FILE__);
		$ed = plugins_url('/edit.png', __FILE__);
		$admin = admin_url(); 
		$date = date("m/d/Y",strtotime($i['post_date']));
		
		$contentdir = explode("/", WP_CONTENT_DIR);						  //get content folder name
		$filesplit = explode($contentdir[(count($contentdir)-1)],$sm[0]); //get image file path

		if(file_exists(WP_CONTENT_DIR . $filesplit[1])) {
			$link = "<a href='{$lg[0]}' target='_blank'><img src='{$sm[0]}' width='60'></a>";
			$rmStyle = "";
			$del = "";
		} else {
			$link = "<img src='{$mia}' />";
			$del = "<a href='" . IMAGEMETA_URL . "&remove={$i['postid']}' onclick='javascript:if(!confirm(\"Are you sure you want to move this item to trash?\")) return false;' title='Permanently Delete This Image' target='_self' /><img src='{$rm}' width='16' height='16' align='absmiddle' /></a>&nbsp;";
			//echo WP_CONTENT_DIR . $filesplit[1] . " could not be found.<br />";
			$rmStyle = "orange"; $missing++;
		}

	$edits = array();

	$postid = $i['postid'];
	//get all posts for which this image has been attached.
	$qATT = "SELECT p.post_title,pm.post_id as edit 
			 FROM {$wpdb->prefix}postmeta pm,{$wpdb->prefix}posts p
			 WHERE pm.post_id = p.ID
			 AND pm.meta_key = '_thumbnail_id' AND pm.meta_value = $postid"; //echo $qATT;
	$ATT = $wpdb->get_results($qATT, ARRAY_A); //myprint_r($ATT);
	if(!empty($ATT)){
		$edits['a'] = "Attached (".(count($ATT)).") <select onChange='javascript:edit(this.options[this.selectedIndex].value);'><option value='-1'>Edit One</option>";
		foreach($ATT as $v) $edits['a'].="<option value='{$v['edit']}' >".substr($v['post_title'],0,30)."</option>";
		$edits['a'] .= "</select><br />";
	}
	//get all posts for which this image has been embedded.
	$qEMB = "SELECT ID,post_title,post_parent FROM {$wpdb->prefix}posts WHERE post_content LIKE '%wp-image-{$postid}%' ORDER BY post_date DESC"; //echo $qEMB;
	$EMB = $wpdb->get_results($qEMB, ARRAY_A); //myprint_r($EMB);
	if(!empty($EMB)){
		foreach($EMB as $v){
			if(array_key_exists($v['post_parent'],$edits['e']) && $v['post_parent']>0)continue;
			if(array_key_exists($v['ID'],$edits['e']) && $v['post_parent']<1)continue;
			if($v['post_parent']<1) $v['post_parent'] = $v['ID'];
			$edits['e'][$v['post_parent']] = "<option value='{$v['post_parent']}' >".substr($v['post_title'],0,30)."</option>";
		}
		$edits['e'] = "Embedded (".count($edits['e']).") <select onChange='javascript:edit(this.options[this.selectedIndex].value);'><option value='-1'>Edit One</option>".implode("",$edits['e'])."</select>";
	}

echo <<<EOHTML
	<tr class='info $rmStyle'>
		<td colspan='4'>
			<div style='float: right;'>
				{$edits['a']}
				{$edits['e']}
			</div>
			<a href='{$admin}media.php?attachment_id={$i['postid']}&action=edit' title='Edit This Item' target='_blank'><img src="$ed" width='16' height=16' align='absmiddle' /></a>
				&nbsp;&nbsp;{$del}
			<b>$date</b>: {$filesplit[1]} 
		</td>
	</tr>
	<tr class='row'>
		<td><div class='thumb'>{$link}</div></td>
		<td>
			<p>Title:</p>	 	 <input type='text' id='post_title:{$i['postid']}' value='{$i['post_title']}' />
			<p>Caption:</p> 	 <input type='text' id='post_excerpt:{$i['postid']}' value='{$i['post_excerpt']}' />			
		</td>
		<td width='30' valign='top'><a href='javascript:void(0);' onclick='javascript:copyAcross({$i['postid']},{$i['meta_id']});' title='Copy Title Across'><img src='{$cp}' alt='Copy Title Across' border='0' class='copyAcross' /></a> </td>
		<td>
			<p>Descr:</p>  <input type='text' id='post_content:{$i['postid']}' value='{$i['post_content']}' />
			<p>Alt:</p>    <input type='text' id='meta_value:{$i['meta_id']}' value='{$i['alt']}' />
		</td>
	</tr>
EOHTML;
	}
	echo "</table>
	<script type='text/javascript'>
		function edit(postid){
		if(postid<1) return;
		window.open('{$admin}post.php?post='+postid+'&action=edit');
		return;
	}
	</script>";

	if($missing>0) { echo <<<EOWARNING
<script type='text/javascript'>
	var warning = document.getElementById('warning');
	warning.innerHTML += "<strong>WARNING:</strong> ($missing) image files do not appear to exist in your content folder. You can delete these images individually using the links below.";
</script>
EOWARNING;
	}
	else { echo "<script type='text/javascript'>document.getElementById('warning').style.display = 'none';</script>";
	}

}


/**************************************************************************************************
*	Some useful functions
**************************************************************************************************/


function ajax_updatedb() {
	global $wpdb;
	
	//build query from passed vars
	$fval = $_POST['fval'];
	$fname = $_POST['fname'];
	$q = "UPDATE ".(substr($fname[0],0,4)=="post"?"{$wpdb->prefix}posts":"{$wpdb->prefix}postmeta").
		" SET {$fname[0]} = '{$fval}' WHERE ".
		(substr($fname[0],0,4)=="post"?"ID":"meta_id")." = {$fname[1]}";
		
	print_r($_POST); echo "Query: $q";
	
	$wpdb->query($q);
	die(); // stop executing script
}
function myprint_r($in) {
	echo "<pre>"; print_r($in); echo "</pre>"; return;
}
function addstyles() {
	echo <<<STYLES
	<style type="text/css">
    *{
        margin:0;
        padding:0;
        font-family: "Lucida Grande", Arial, sans-serif;
    }
    #imagemetas p {
    	width:60px;
    	overflow:hidden;
    	margin: 5px 0;
    	float:left;
    	font-weight:bold;
		clear: left;
		height
    }
	#imagemetas {
		border-collapse: collapse;
		border-spacing: 0;
		width: 100%;
	}
    #imagemetas tr.info td {
		padding: 5px;
		font-size: 11px;
    }

	tr:nth-child(2n+1){
	  background-color: #f0f0f0;
	}

	#imagemetahead tr, #imagemetahead tr th {
		height: 20px;
		background-color: #cfcfcf;
		font-weight: bold;
	}
	.sort_a, .sort_d {
		width: 9px;
		height: 9px;
		display: inline-block;
		margin: 0 2px;
	}
	.sort_a span, .sort_d span {
		display: none;
	}
	.sort_a {
		background-position: 9px 0;
	}
	.sort_d {
		background-position: 9px 9px;
	}
	.sort_a.selected {
		background-position: 0 0;
	}
	.sort_d.selected {
		background-position: 0 9px;
	}
		.sort_a:hover {
		background-position: 18px 0;
	}
	.sort_d:hover {
		background-position: 18px 9px;
	}
    .thumb {
    	height:45px;
    	width:60px;
    	overflow:hidden;
    	margin-right:10px;
    }
    body {
        padding: 10px;
    }
    #status {
        width:50%;
        padding:10px;
        outline:none;
        height:36px;
    }
    .pager {
    	width:18px;
    	height:14px;
    	background-color:#DDD;
    	border:1px solid #AAA;
    	display:block;
    	text-decoration: none;
    	text-align:center;
    	float:left;
		margin: 0 3px;
    }
	.pager a {
		text-decoration:none;
	}
	.pager a:hover {
		font-weight:bold;
	}
	.editbutton {
		width: auto !important;
		margin: 0 4px !important;
		float:right !important;
		padding: 0 4px;
		clear: none;
    	height:14px;
    	background-color:#DDD;
    	border:1px solid #AAA;
    	display:block;
    	text-decoration: none;
		margin: 0 3px;
    }
	.editbutton a {
		text-decoration:none;
	}
	.editbutton a:hover {
		font-weight:bold;
	}
	.editmaster {
		border:1px solid black;
	}
    .copyAcross {
    	margin-top:10px;
    }
	tr.row {
		height: 70px;
	}
	tr.orange, #warning.orange {
		background: #ffe4db;
	}
	#warning {
		padding: 10px;
		margin-bottom: 10px;
	}
	.focusField {
		padding-left: 20px;
		width: 300px;
        border:solid 2px #73A6FF;
        background:#EFF5FF;
        color:#000;
    }
    .idleField {
		padding-left: 20px;
        width: 300px;
		background:#EEE;
        color: #6F6F6F;
        border: solid 2px #DFDFDF;
    }
	#donate {
		float:right;
		width:180px;
		height:170px;
		border:1px solid #B74327;
		margin:10px;
		padding:20px 10px 10px 10px;
	}
STYLES;
echo "
	.sort_a {
		background-image: url(".plugins_url('/sort.gif', __FILE__).");
	}
	.sort_d {
		background-image: url(".plugins_url('/sort.gif', __FILE__).");
	}
    .upd {
    	margin:0;
    	padding:0;
    	float:left;
    	width:16px;
    	height:16px;
    }
    .updA {
    	background-image: url(".plugins_url('/updating.gif', __FILE__).");
    	margin:0;
    	padding:0;
    	float:left;
    	width:16px;
    	height:16px;
    }
	.focusField {
		background: url(".plugins_url('/edit.png', __FILE__).") no-repeat 2px 2px;
	}
	.idleField {
		background: url(".plugins_url('/okay.png', __FILE__).") no-repeat 2px 2px;
	}
	.updateField {
		padding-left: 20px;
		 width: 300px;
		background: url(".plugins_url('/updating.gif', __FILE__).") no-repeat 2px 2px;
	}
</style>
";
return;
}
?>
