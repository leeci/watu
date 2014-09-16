<?php
function watu_questions() {
	global $wpdb;
	
	$action = 'new';
 	if(!empty($_GET['action'])){
 		if($_GET['action'] == 'edit') $action = 'edit';
 		else if($_GET['action'] == 'import') $action = 'import';
 	}
 	
 	// mass cleanup
 	if(!empty($_POST['delete_all_Ques'])) {
 		$wpdb->query($wpdb->prepare("DELETE FROM ".WATU_QUESTIONS." WHERE exam_id=%d", $_REQUEST['quiz']));
 	}
	
	if(isset($_REQUEST['submit'])) {
		if($action == 'edit'){ //Update goes here
			$wpdb->query($wpdb->prepare("UPDATE ".WATU_QUESTIONS." 
			SET question=%s, answer_type=%s, is_required=%d 
			WHERE ID=%d", $_POST['content'], $_POST['answer_type'], @$_POST['is_required'], $_POST['question']));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}watu_answer WHERE question_id=%d", $_REQUEST['question']));
                //import start        
		}else if($action == 'import'){ //import excel file
			$file_link = $_POST['file-link'];  //取得下載連結
			$file_name = strrchr($file_link,'/');					
			$upload_dir = wp_upload_dir();     //取得上傳的目錄陣列
			$upload_base_dir = str_replace('\\','/',$upload_dir['basedir']); 
			//取得上傳目錄，並統一斜線			
			$upload_folder = strrchr($upload_base_dir,'/');
			//取得上傳的目錄名	
			$file_link_noname = str_replace($file_name,"",$file_link);
			$file_link_nohttp = substr($file_link_noname,strrpos($file_link_noname,$upload_folder));
			$file_link_nodir  = str_replace($upload_folder,"",$file_link_nohttp);			
			$file_link_final = $upload_base_dir.$file_link_nodir.$file_name;

			$ext = substr(strrchr($file_name, '.'), 1);

                        //載入excel 開始
			$ext = substr(strrchr($file_name, '.'), 1);
		
                        require_once WATU_PATH.'/lib/Build/PHPExcel.php';
                        $inputFileName = $file_link_final;

                        if($ext == "xlsx"){
                                $objReader = new PHPExcel_Reader_Excel2007();
                        }else if($ext == "xls") {
                                $objReader = PHPExcel_IOFactory::createReader('Excel5'); // 讀取舊版 excel 檔案
                        }

                        $load_flag = true;

                        try{
                        $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
                        $objPHPExcel = $objReader->load($inputFileName);

                        }catch(Exception $e){

                        echo "找不到檔案，請確定你的檔案已上傳至伺服器上";
                        $load_flag = false;
                        }

                        if($load_flag)
                        {

                                $sheet = $objPHPExcel->getSheet(0);
                                $totalRow = $sheet->getHighestRow();
                                $totalColumn = $sheet->getHighestColumn();
                                $colNumber = PHPExcel_Cell::columnIndexFromString($totalColumn);

                                $ques_num = 0;
                                $questions = array();
                                for($i = 1; $i < $totalRow; $i++)
                                {
                                $ques_num ++;
                                $empty_flag = true;
                                        for($j = 0; $j < $colNumber; $j++)
                                        {
                                                $cell = $sheet->getCellByColumnAndRow($j,$i);
                                                
                                                if ($cell->getValue() instanceof PHPExcel_RichText) {
                                                        $cellValueAsString = "";
                                                    foreach ($cell->getValue()->getRichTextElements() as $element) {
                                                        if ($element instanceof PHPExcel_RichText_Run) {
                                                                if ($element->getFont()->getSuperScript()) {
                                                                        $cellValueAsString .= '<sup>';
                                                                } else if ($element->getFont()->getSubScript()) {
                                                                        $cellValueAsString .= '<sub>';
                                                                }
                                                        }
                                                        // Convert UTF8 data to PCDATA
                                                        $cellText = $element->getText();
                                                        $cellValueAsString .= htmlspecialchars($cellText);
                                                        if ($element instanceof PHPExcel_RichText_Run) {
                                                                if ($element->getFont()->getSuperScript()) {
                                                                        $cellValueAsString .= '</sup>';
                                                                } else if ($element->getFont()->getSubScript()) {
                                                                        $cellValueAsString .= '</sub>';
                                                                }
                                                        }
                                                   }
                                                }else{
                                                        $cellValueAsString = $sheet->getCellByColumnAndRow($j,$i);
                                                }
                                                $str = "";
                                                $str = (string)$cellValueAsString;		
                                                if (!empty($str))//空值判斷
                                                        $empty_flag = false; 
                                                //資料存入
                                                switch($j)
                                                {
                                                case 0:
                                                        $questions[$ques_num]['no'] = $str;
                                                        break;
                                                case 1:
                                                        $questions[$ques_num]['ans'] = $str;
                                                        break;
                                                case 2:
                                                        $questions[$ques_num]['quiz'] = $str;
                                                        break;
                                                case 3:
                                                        $questions[$ques_num]['op1'] = $str;
                                                        break;
                                                case 4:
                                                        $questions[$ques_num]['op2'] = $str;
                                                        break;
                                                case 5:
                                                        $questions[$ques_num]['op3'] = $str;
                                                        break;
                                                case 6:
                                                        $questions[$ques_num]['op4'] = $str;
                                                        break;		
                                                }
                                        }
                                        if($empty_flag)
                                                $ques_num --;
                                }
                                //試題編號
                                $quiz_id = $_POST['quiz'];

                                for($i=2;$i<count($questions);$i++){		
                                        $wpdb->query($wpdb->prepare("INSERT INTO ".WATU_QUESTIONS." SET
                                                exam_id=%d, question=%s, answer_type=%s", $quiz_id, $questions[$i]['quiz'], 'radio'));
                                        $qid = $wpdb->insert_id;
                                        
                                        for($x=1;$x<=4;$x++){
                                                $corr[$x]['corr']="0";
                                                $corr[$x]['point']="0";
                                        }		
                                        $corr[$questions[$i]['ans']]['corr'] = 1;
                                        $corr[$questions[$i]['ans']]['point'] = 1;		
                                        
                                        $wpdb->query($wpdb->prepare("INSERT INTO ".WATU_ANSWERS." SET
                                                question_id=%d, answer=%s, correct='%d', point=%d, sort_order=1",
                                                $qid, $questions[$i]['op1'], $corr['1']['corr'], $corr['1']['point']));
                                        $wpdb->query($wpdb->prepare("INSERT INTO ".WATU_ANSWERS." SET
                                                question_id=%d, answer=%s, correct='%d', point=%d, sort_order=2",
                                                $qid, $questions[$i]['op2'], $corr['2']['corr'], $corr['2']['point']));
                                        $wpdb->query($wpdb->prepare("INSERT INTO ".WATU_ANSWERS." SET
                                                question_id=%d, answer=%s, correct='%d', point=%d, sort_order=3",
                                                $qid, $questions[$i]['op3'], $corr['3']['corr'], $corr['3']['point']));	
                                        $wpdb->query($wpdb->prepare("INSERT INTO ".WATU_ANSWERS." SET
                                                question_id=%d, answer=%s, correct='%d', point=%d, sort_order=4",
                                                $qid, $questions[$i]['op4'], $corr['4']['corr'], $corr['4']['point']));
                                                
                                }
                        }		
			//import end	
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
        
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATU_QUESTIONS." WHERE exam_id=%d", $_REQUEST['quiz']));
	?>
	
	<div class="wrap">
	<h2><?php echo __("Manage Questions in", 'watu') . ' ' . $exam_name; ?></h2>

		<div class="postbox-container" style="margin-right:2%;">
		
		<p><a href="tools.php?page=watu_exams"><?php _e('Back to quizzes', 'watu')?></a> &nbsp; <a href="admin.php?page=watu_exam&quiz=<?php echo $_GET['quiz']?>&action=edit"><?php _e('Edit this quiz', 'watu')?></a> &nbsp;
		<a href="admin.php?page=watu_grades&quiz_id=<?php echo $_GET['quiz']?>"><?php _e('Manage Grades / Results', 'watu')?></a> &nbsp; <a href="admin.php?page=watu_question&amp;action=import&amp;quiz=<?php echo $_REQUEST['quiz'] ?>"><?php _e('Import Quiz', 'watu')?></a>
                
                <?php if($count):?>
	&nbsp;|&nbsp;
	<a href="#" onclick="WatuDelAll();return false;"><?php _e('Delete all questions for this exam', 'watu')?></a><?php endif;?></p>
                
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
                
                <form id="deleteQuesForm" method="post">
                        <input type="hidden" name="delete_all_Ques" value="0">
                </form>                
		</div>
                <script type="text/javascript" >
                //function WatuDelTaking(id) {
                //	if(confirm("<?php //_e('Are you sure?', 'watu')?>")) {
                //		window.location = 'admin.php?page=watu_takings&exam_id=<?php //echo $exam->ID?>&del_taking=1&id=' + id;
                //	} 
                //}                
                function WatuDelAll() {
                        if(!confirm("<?php _e('Are you sure? This will delete ALL questions for this quiz!', 'watu')?>")) return false;
                        
                        jQuery('#deleteQuesForm input[name=delete_all_Ques]').val("1");
                        jQuery('#deleteQuesForm').submit();
                }
                </script>
	</div>	
<?php } 

function watu_question() {
	global $wpdb;	
	
	$action = 'new';
	if($_REQUEST['action'] == 'edit') $action = 'edit';
	else if($_REQUEST['action'] == 'import') $action = 'import';
 	
        if($action != "import")
        {
                $all_answers = array();
                
                if(!empty($_GET['question'])) {
                        $question= $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}watu_question WHERE ID=%d", $_GET['question']));
                        $all_answers = $wpdb->get_results($wpdb->prepare("SELECT answer, correct, point FROM {$wpdb->prefix}watu_answer 
                                WHERE question_id=%d ORDER BY sort_order", $_GET['question']));	
                }   
                
                $ans_type = $action =='new'? get_option('watu_answer_type'): $question->answer_type;
                $answer_count = 4;
                if($action == 'edit' and $answer_count < count($all_answers)) $answer_count = count($all_answers);
                
                require(WATU_PATH."/views/question-form.html.php");
	}else{
                require(WATU_PATH."/views/question-import-form.html.php");	
        }
}