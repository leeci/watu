<?php
class WatuQuestion {
	// calculate points, correctness and return the class
	static function calculate($question, $ans, $ansArr, $correct, $class) {
		$points = 0;
				
		if($question->answer_type != 'textarea') {
			if($ans->correct == 1) $class .= ' correct-answer';
			if(  in_array($ans->ID , $ansArr) ) $class .= ' user-answer';
			if( in_array($ans->ID , $ansArr ) and $ans->correct == 1) $correct = true;
			if( in_array($ans->ID , $ansArr ) ) $points = $ans->point;
		}
		else {
			// textareas			
			$user_answer = $ansArr[0];		
			
			if( strtolower(trim($ans->answer)) == strtolower(trim($user_answer)) ) { 
				$class .= ' user-answer';
				$points = $ans->point;
			}
			if( strtolower(trim($ans->answer)) == strtolower(trim($user_answer)) and $ans->correct == 1) {
				$correct = true; 
				$class .= ' correct-answer';
			}			 
		} // end working with textareas
		
		return array($points, $correct, $class);
	}
	
	// figure out the maximum number of points the user can get on the question
	static function max_points($question, $all_answers) {
		$max_points = 0;
		
		// get only the answers of this question
		$q_answers = array();
		foreach($all_answers as $answer) {
			if($answer->question_id == $question->ID) $q_answers[] = $answer;
		}		
		
		if(!sizeof($q_answers)) return 0;
		
		switch($question->answer_type) {
			case 'radio':
			case 'textarea':
				// get the answer with most points
				$max = 0;
				foreach($q_answers as $answer) {
					if($answer->point > $max) $max = $answer->point;
				} 
				$max_points = $max;
			break;
			
			case 'checkbox':
				foreach($q_answers as $answer) {
					if($answer->point > 0) $max_points += $answer->point;
				}
			break;
		}
		
		return $max_points;
	} // end max_points
}