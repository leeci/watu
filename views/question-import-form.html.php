
<div class="wrap">
<h2><?php echo __(ucfirst($action)) . ' '. __("Question", 'watu'); ?></h2>

<p><a href="admin.php?page=watu_questions&quiz=<?php echo $_GET['quiz']?>"><?php _e('Back to questions', 'watu')?></a>
&nbsp; <a href="tools.php?page=watu_exams"><?php _e('Back to quizzes', 'watu')?></a></p>

<div id="titlediv">
<input type="hidden" id="title" name="ignore_me" value="This is here for a workaround for a editor bug" />
</div>

<script type="text/javascript">
function init() {
	
	document.getElementById("wp-content-editor-container").style.display="none";

	jQuery("#post").submit(function(e) {
		var file_link;
		// Make sure question is suplied
		if(update_file())
		{		
		e.preventDefault();
		e.stopPropagation();
		return true;
		}
		file_link = xmlDoc.getElementsByTagName("a")[0].getAttribute("href");
		
		jQuery("#file-link").val(xmlDoc.getElementsByTagName("a")[0].getAttribute("href"));
	});
	jQuery("#name-display").click(function(e) {
		update_file();
	});	
	
	//清除顯示的舊資料
	jQuery("#insert-media-button").click(function(e) {
		jQuery("#name-display").html('<a href="#"><?php _e('Click to show', 'watu') ?></a>');
		contents = document.getElementById("content").value = "";
	});	
}

function update_file(){
	var contents;
	if(window.tinyMCE && document.getElementById("content").style.display=="none") { // If visual mode is activated.
			contents = tinyMCE.get("content").getContent();
	} else {
			contents = document.getElementById("content").value;
	}
	
	if(!contents) {
		alert("<?php _e('Please upload a file', 'watu'); ?>");
		return true;
	}
	
	jQuery("#name-display").html(contents);
	
	//取得連結資訊
	try //Internet Explorer
	  {
	  xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
	  xmlDoc.async="false";
	  xmlDoc.loadXML(contents);
	  }
	catch(e)
	  {
	  try //Firefox, Mozilla, Opera, etc.
	    {
	    parser=new DOMParser();
	    xmlDoc=parser.parseFromString(contents,"text/xml");
	    }
	  catch(e) {alert(e.message)}
	  }
	//alert(xmlDoc.getElementsByTagName("a")[0].childNodes[0].nodeValue);
	//alert(xmlDoc.getElementsByTagName("a")[0].getAttribute("href"));
	}

jQuery(document).ready(init);
</script>

<form name="post" action="admin.php?page=watu_questions&amp;quiz=<?php echo $_GET['quiz']; ?>&amp;action=import" method="post" id="post">
<div id="poststuff">

<div class="postarea">

<div class="postbox">
<h3 class="hndle"><?php _e('Upload a File(xls,xlsx)', 'watu') ?></h3>
<div class="inside">
<?php wp_editor("", 'content',$settings = array(
'textarea_rows'=>0,
'media_buttons'=>true,
'quicktags'=>false,
'wpautop'=>false,
'editor_css'=>false,
'tinymce' => array()
)); ?>
<?php _e('Upload file:', 'watu'); ?><span id="name-display"><a href="#"><?php _e('Please upload a file', 'watu'); ?></a></span>
</div></div>
</div>


<p class="submit">
<input type="hidden" name="quiz" value="<?php echo $_REQUEST['quiz']?>" />
<input type="hidden" name="question" value="<?php echo stripslashes($_REQUEST['question'])?>" />
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="file-link" name="file-link" value="" />
<input type="hidden" name="action" value="<?php echo $action ?>" />
<span id="autosave"></span>
<input type="submit" name="submit" value="<?php _e('Import', 'watu') ?>" style="font-weight: bold;" />
</p>
<a href="admin.php?page=watu_questions&amp;quiz=<?php echo $_REQUEST['quiz']?>"><?php _e("Go to Questions Page", 'watu') ?></a>
</div>
</form>

</div>