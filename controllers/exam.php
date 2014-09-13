<?php
function watu_exams() {
	global $wpdb;
	
	if( isset($_REQUEST['message']) && $_REQUEST['message'] == 'updated') print '<div id="message" class="updated fade"><p>' . __('Test updated', 'watu') . '</p></div>';
	if(isset($_REQUEST['message']) && $_REQUEST['message'] == 'fail') print '<div id="message" class="updated error"><p>' . __('Error occured', 'watu') . '</p></div>';
	if( isset($_REQUEST['grade']) )  print '<div id="message" class="updated fade"><p>' . $_REQUEST['grade']. '</p></div>';
	
	if(!empty($_GET['action']) and $_GET['action'] == 'delete') {
		$wpdb->get_results("DELETE FROM ".WATU_EXAMS." WHERE ID='$_REQUEST[quiz]'");
		$wpdb->get_results("DELETE FROM ".WATU_ANSWERS." WHERE question_id IN (SELECT ID FROM ".WATU_QUESTIONS." WHERE exam_id='$_REQUEST[quiz]')");
		$wpdb->get_results("DELETE FROM ".WATU_QUESTIONS." WHERE exam_id='$_REQUEST[quiz]'");
		print '<div id="message" class="updated fade"><p>' . __('Test deleted', 'watu') . '</p></div>';
	}
	?>
	
	<div class="wrap">
	<h2><?php printf(__("Manage %s", 'watu'), __('Quizzes', 'watu')); ?></h2>
	
		<div class="postbox-container" style="margin-right:2%;">
                
		<p><?php _e('Go to', 'watu')?> <a href="options-general.php?page=watu.php"><?php _e('Watu Settings', 'watu')?></a>
			&nbsp;|&nbsp;
		<a href="admin.php?page=watu_exam&amp;action=new"><?php _e("Create New Quiz", 'watu')?></a></p>
		
		<p><b><?php _e('To publish a quiz copy its shortcode and place it in a post or page. Use only one quiz shortcode in each post or page.','watu')?></b></p>
			
		<table class="widefat">
			<thead>
			<tr>
				<th scope="col"><div style="text-align: center;"><?php _e('ID', 'watu') ?></div></th>
				<th scope="col"><?php _e('Title', 'watu') ?></th>
				<th scope="col"><?php _e('Shortcode', 'watu') ?></th>
				<th scope="col"><?php _e('Number Of Questions', 'watu') ?></th>
				<th scope="col"><?php _e('Taken', 'watu') ?></th>
				<th scope="col" colspan="3"><?php _e('Action', 'watu') ?></th>
			</tr>
			</thead>
		
			<tbody id="the-list">
		<?php
		// Retrieve the quizzes
		$exams = $wpdb->get_results("SELECT Q.ID,Q.name,Q.added_on,
			(SELECT COUNT(ID) FROM ".WATU_QUESTIONS." WHERE exam_id=Q.ID) AS question_count,
			(SELECT COUNT(ID) FROM ".WATU_TAKINGS." WHERE exam_id=Q.ID) AS taken
			FROM `".WATU_EXAMS."` AS Q ");
		
		// now select all posts that have watu shortcode in them
		$posts=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts 
		WHERE post_content LIKE '%[WATU %]%' 
		AND post_status='publish' AND post_title!=''
		ORDER BY post_date DESC");	
		
		// match posts to exams
		foreach($exams as $cnt=>$exam) {
			foreach($posts as $post) {
				if(strstr($post->post_content,"[WATU ".$exam->ID."]")) {
					$exams[$cnt]->post=$post;			
					break;
				}
			}
		}
	
		
		if(count($exams)):
			foreach($exams as $quiz):
				$class = ('alternate' == @$class) ? '' : 'alternate';
		
				print "<tr id='quiz-{$quiz->ID}' class='$class'>\n";
				?>
				<th scope="row" style="text-align: center;"><?php echo $quiz->ID ?></th>
				<td><?php if(!empty($quiz->post)) echo "<a href='".get_permalink($quiz->post->ID)."' target='_blank'>"; 
				echo stripslashes($quiz->name);
				if(!empty($quiz->post)) echo "</a>";?></td>
        <td><input type="text" size="8" readonly onclick="this.select()" value="[WATU <?php echo $quiz->ID ?>]"></td>
				<td><?php echo $quiz->question_count ?></td>
				<td><a href="admin.php?page=watu_takings&exam_id=<?php echo $quiz->ID?>"><?php echo $quiz->taken?> <?php _e('times', 'watu')?></a></td>
				<td><a href='admin.php?page=watu_questions&amp;quiz=<?php echo $quiz->ID?>' class='edit'><?php _e('Manage Questions', 'watu')?></a></td>
				<td><a href='admin.php?page=watu_exam&amp;quiz=<?php echo $quiz->ID?>&amp;action=edit' class='edit'><?php _e('Edit', 'watu'); ?></a></td>
				<td><a href='tools.php?page=watu_exams&amp;action=delete&amp;quiz=<?php echo $quiz->ID?>' class='delete' onclick="return confirm('<?php echo  addslashes(__("You are about to delete this quiz? This will delete all the questions and answers within this quiz. Press 'OK' to delete and 'Cancel' to stop.", 'watu'))?>');"><?php _e('Delete', 'watu')?></a></td>
				</tr>
		<?php endforeach;
			else:?>
			<tr>
				<td colspan="5"><?php _e('No Quizzes found.', 'watu') ?></td>
			</tr>
		<?php endif;?>
			</tbody>
		</table>
		
			<p><a href="admin.php?page=watu_exam&amp;action=new"><?php _e("Create New Quiz", 'watu')?></a></p>
		</div>
	</div>	
<?php } 

function watu_exam() {
	global $wpdb, $user_ID;
	$answer_display = get_option('watu_show_answers');
	
	if(isset($_REQUEST['submit'])) {
		if($_REQUEST['action'] == 'edit') { //Update goes here
			$exam_id = $_REQUEST['quiz'];
			$wpdb->query("delete from ".WATU_GRADES." where exam_id=".$_REQUEST['quiz']);
			$wpdb->query($wpdb->prepare("UPDATE ".WATU_EXAMS."
				SET name=%s, description=%s,final_screen=%s, max_num=%d, randomize=%d, single_page=%d,  
				show_answers=%d, require_login=%d, notify_admin=%d, randomize_answers=%d   
				WHERE ID=%d", $_POST['name'], $_POST['description'], $_POST['content'], 
				@$_POST['max_num'], @$_POST['randomize'], @$_POST['single_page'], $_POST['show_answers'], 
				@$_POST['require_login'], @$_POST['notify_admin'], @$_POST['randomize_answers'],
				$_POST['quiz']));
			
			$wp_redirect = 'tools.php?page=watu_exams&message=updated';
		
		} else {
			$wpdb->query($wpdb->prepare("INSERT INTO ".WATU_EXAMS." 
			(name, description, final_screen,  added_on, max_num, randomize, single_page, show_answers, require_login, 
			notify_admin, randomize_answers) 
			VALUES(%s, %s, %s, NOW(), %d, %d, %d, %d, %d, %d, %d)", 
			$_POST['name'], $_POST['description'], $_POST['content'], @$_POST['max_num'], @$_POST['randomize'], @$_POST['single_page'], 
			$_POST['show_answers'], @$_POST['require_login'], @$_POST['notify_admin'], @$_POST['randomize_answers']));
			$exam_id = $wpdb->insert_id;
			if($exam_id == 0 ) $wp_redirect = 'tools.php?page=watu_exams&message=fail';
			$wp_redirect = 'admin.php?page=watu_questions&message=new_quiz&quiz='.$exam_id;
		}
		
		if( $exam_id>0 and isset($_REQUEST['gradetitle']) and is_array($_REQUEST['gradetitle']) ) {
			$sql = "insert into {$wpdb->prefix}watu_grading (exam_id, gtitle, gdescription, gfrom, gto) values ";
			$saveGrade = false;
			$descArr = $_REQUEST['grade_description'];
			$fromArr = $_REQUEST['grade_from'];
			$toArr = $_REQUEST['grade_to'];
			
			foreach($_REQUEST['gradetitle'] as $key=>$title) {			
				$title = esc_sql($title);
				$desc = esc_sql( $descArr[$key] );
				$from =  $fromArr[$key];
				$to =  $toArr[$key];
				
				if( !empty($title)  && is_numeric($from) && is_numeric($to) ) {
					$saveGrade = true;
					$sql .= " ( $exam_id, '$title' , '$desc', $from, $to), ";
				} else { $errorPartial= true;}
			}
		
			if( $saveGrade) {
				$sql = preg_replace('/,\s$/', '', $sql);	
				$out = $wpdb->query($sql);				
			} 
		} //end grading block
		
		$wp_redirect = admin_url($wp_redirect);
		
		do_action('watu_exam_saved', $exam_id);
		
		echo "<meta http-equiv='refresh' content='0;url=$wp_redirect' />"; 
		exit;
	}

		
	$action = 'new';
	if($_REQUEST['action'] == 'edit') $action = 'edit';
	
	$dquiz = array();
	$grades = array();
	if($action == 'edit') {
		$dquiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATU_EXAMS." WHERE ID=%d", $_REQUEST['quiz']));
		$grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATU_GRADES." WHERE  exam_id=%d order by ID ", $_REQUEST['quiz']) );
		$final_screen = stripslashes($dquiz->final_screen);
	} else {
		$final_screen = __("<p>Congratulations - you have completed %%QUIZ_NAME%%.</p>\n\n<p>You scored %%POINTS%% points out of %%MAX-POINTS%% points total.</p>\n\n<p>Your performance have been rated as <b>%%RATING%%</b>.</p>\n\n<p>Your obtained grade is <b>%%GRADE-TITLE%%</b></p><p>%%GRADE-DESCRIPTION%%</p>", 'watu');
	}
	
	// see what is the show_answers to this exam
	if(!isset($dquiz->show_answers) or $dquiz->show_answers == 100) $answer_display = $answer_display; // assign the default
	else $answer_display = $dquiz->show_answers;
	
	require(WATU_PATH."/views/exam_form.php");
}