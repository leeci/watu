<?php
function watu_questions() {
	global $wpdb;
	
	$action = 'new';
	if(!empty($_GET['action']) and $_GET['action'] == 'edit') $action = 'edit';
	
	if(isset($_REQUEST['submit'])) {
		if($action == 'edit'){ //Update goes here
			$wpdb->query($wpdb->prepare("UPDATE ".WATU_QUESTIONS." 
			SET question=%s, answer_type=%s, is_required=%d 
			WHERE ID=%d", $_POST['content'], $_POST['answer_type'], @$_POST['is_required'], $_POST['question']));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}watu_answer WHERE question_id=%d", $_REQUEST['question']));
				
		} else {	
			$sql = $wpdb->prepare("INSERT INTO ".WATU_QUESTIONS." (exam_id, question, answer_type, is_required) 
			VALUES(%d, %s, %s, %d)", $_GET['quiz'], $_POST['content'], $_POST['answer_type'], @$_POST['is_required']);
			$wpdb->query($sql);//Inserting the questions
	
			$_POST['question'] = $wpdb->insert_id;
			$action='edit';
		}
		
		$question_id = $_POST['question'];
		if($question_id>0) {
			// the $counter will skip over empty answers, $sort_order_counter will track the provided answers order.
			$counter = 1;
			$sort_order_counter = 1;
			$correctArry = @$_POST['correct_answer'];
			$pointArry = $_POST['point'];
			
			if(is_array($_POST['answer']) and !empty($_POST['answer'])) {
				
				foreach ($_POST['answer'] as $key => $answer_text) {
					$correct=0;
					if( @in_array($counter, $correctArry) ) $correct=1;
					$point = $pointArry[$key];
					if($answer_text!='') {
						$wpdb->query($wpdb->prepare("INSERT INTO ".WATU_ANSWERS." (question_id,answer,correct,point, sort_order)
							VALUES(%d, %s, %s, %d, %d)", $question_id, $answer_text, $correct, $point, $sort_order_counter));
						$sort_order_counter++;
					}
					$counter++;
				}
			} 	// end if(is_array($_POST['answer']) and !empty($_POST['answer']))
		}
	}
	
	if(!empty($_GET['action']) and $_GET['action'] == 'delete') {
		$wpdb->query($wpdb->prepare("DELETE FROM ".WATU_ANSWERS." WHERE question_id=%d", $_GET['question']));
		$wpdb->query($wpdb->prepare("DELETE FROM ".WATU_QUESTIONS." WHERE ID=%d", $_GET['question']));		
	}
	$exam_name = stripslashes($wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}watu_master WHERE ID=%d", $_REQUEST['quiz'])));
	?>
	
	<div class="wrap">
	<h2><?php echo __("Manage Questions in", 'watu') . ' ' . $exam_name; ?></h2>
	
		<div class="postbox-container" style="width:73%;margin-right:2%;">
		
		<p><a href="tools.php?page=watu_exams"><?php _e('Back to quizzes', 'watu')?></a> &nbsp; <a href="admin.php?page=watu_exam&quiz=<?php echo $_GET['quiz']?>&action=edit"><?php _e('Edit this quiz', 'watu')?></a> &nbsp;
		<a href="admin.php?page=watu_grades&quiz_id=<?php echo $_GET['quiz']?>"><?php _e('Manage Grades / Results', 'watu')?></p>
		
		<?php
		wp_enqueue_script( 'listman' );
		wp_print_scripts();
		?>
		
		<p style="color:green;"><?php _e('To add this exam to your blog, insert the code ', 'watu') ?> <input type="text" readonly size="8" onclick="this.select();" value="[WATU <?php echo $_REQUEST['quiz'] ?>]"> <?php _e('into any post or page.', 'watu') ?></p>
		
		<table class="widefat">
			<thead>
			<tr>
				<th scope="col"><div style="text-align: center;">#</div></th>
				<th scope="col"><?php _e('Question', 'watu') ?></th>
				<th scope="col"><?php _e('Number Of Answers', 'watu') ?></th>
				<th scope="col" colspan="3"><?php _e('Action', 'watu') ?></th>
			</tr>
			</thead>
		
			<tbody id="the-list">
		<?php
		// Retrieve the questions
		$all_question = $wpdb->get_results("SELECT Q.ID,Q.question,(SELECT COUNT(*) FROM {$wpdb->prefix}watu_answer WHERE question_id=Q.ID) AS answer_count
												FROM `{$wpdb->prefix}watu_question` AS Q
												WHERE Q.exam_id=$_REQUEST[quiz] ORDER BY Q.ID");
		
		if (count($all_question)) {
			$bgcolor = '';			
			$question_count = 0;
			foreach($all_question as $question) {
				$class = ('alternate' == @$class) ? '' : 'alternate';
				$question_count++;
				print "<tr id='question-{$question->ID}' class='$class'>\n";
				?>
				<th scope="row" style="text-align: center;"><?php echo $question_count ?></th>
				<td><?php echo stripslashes($question->question) ?></td>
				<td><?php echo $question->answer_count ?></td>
				<td><a href='admin.php?page=watu_question&amp;question=<?php echo $question->ID?>&amp;action=edit&amp;quiz=<?php echo $_REQUEST['quiz']?>' class='edit'><?php _e('Edit', 'watu'); ?></a></td>
				<td><a href='admin.php?page=watu_questions&amp;action=delete&amp;question=<?php echo $question->ID?>&amp;quiz=<?php echo $_REQUEST['quiz']?>' class='delete' onclick="return confirm('<?php echo addslashes(__("You are about to delete this question. This will delete the answers to this question. Press 'OK' to delete and 'Cancel' to stop.", 'watu'))?>');"><?php _e('Delete', 'watu')?></a></td>
				</tr>
		<?php
				}
			} else {
		?>
			<tr style='background-color: <?php echo $bgcolor; ?>;'>
				<td colspan="4"><?php _e('No questiones found.', 'watu') ?></td>
			</tr>
		<?php
		}
		?>
			</tbody>
		</table>
		
		<a href="admin.php?page=watu_question&amp;action=new&amp;quiz=<?php echo $_REQUEST['quiz'] ?>"><?php _e('Create New Question', 'watu')?></a>
		</div>
		<div id="watu-sidebar">
				<?php include(WATU_PATH."/views/sidebar.php");?>
		</div>
	</div>	
<?php } 

function watu_question() {
	global $wpdb;	
	
	$action = 'new';
	if($_REQUEST['action'] == 'edit') $action = 'edit';
	
	$all_answers = array();
	
	if(!empty($_GET['question'])) {
		$question= $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}watu_question WHERE ID=%d", $_GET['question']));
		$all_answers = $wpdb->get_results($wpdb->prepare("SELECT answer, correct, point FROM {$wpdb->prefix}watu_answer 
			WHERE question_id=%d ORDER BY sort_order", $_GET['question']));	
	}
	
	$ans_type = $action =='new'? get_option('watu_answer_type'): $question->answer_type;
	$answer_count = 4;
	if($action == 'edit' and $answer_count < count($all_answers)) $answer_count = count($all_answers) ;
	
	require(WATU_PATH."/views/question-form.html.php");
}