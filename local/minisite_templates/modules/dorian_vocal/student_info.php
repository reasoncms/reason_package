<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith and Lucas Welper
//    2010-09-20
//
//    Work on the first page of the dorian vocal nomination form
//    which collects Student Info
//
////////////////////////////////////////////////////////////////////////////////

class StudentInfoForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Vocal Festival Nomination Student Information';
	var $elements = array(
		'student_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Student Information</h3>',
		),
		'student_first_name' => array(
			'type' => 'text',
                ),
                'student_last_name' => array(
                        'type' => 'text',
                ),
                'student_gender' => array(
                    'type' => 'radio_inline_no_sort',
                    'options' => array('Female' => 'Female', 'Male' => 'Male'),
                ),
                'student_email' => array(
                        'type' => 'text',
                       'display_name' => 'Student E-mail',
                ),
                'student_school_name' => array(
                        'type' => 'text',
                ),
                'student_phone' => array(
                        'type' => 'text',
                ),
                'student_street_address' => array(
                        'type' => 'text',
                ),
                'student_city' => array(
                        'type' => 'text',
                ),
                'student_state' => 'state',
                'student_zip' => array(
                        'type' => 'text',
                ),
                'voice_part' => array(
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'options' => array(
                            'S1' => 'Soprano 1',
                            'S2' => 'Soprano 2',
                            'A1' => 'Alto 1',
                            'A2' => 'Alto 2',
                            'T1' => 'Tenor 1',
                            'T2' => 'Tenor 2',
                            'B1' => 'Bass 1',
                            'B2' => 'Bass 2',

                        ),
                ),
                'rank' => array(
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'comments' => '<br>If nominating 4 students, rank them 1 - 4 with 1 being the highest',
                        'options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4,
                            5 => 5, 6 => 6, 7 => 7,
                        ),
                ),
                'year_in_school' => array(
                    'type' => 'radio_inline_no_sort',
                    'options' => array(11 => 'Junior', 12 => 'Senior'),
                ),
                'years_of_singing_experience' => 'text',
                'desired_participation_text' => array(
                    'type' => 'comment',
                    'text' => '<br />In addition to being considered for the Festival Choir, check boxes if the student should be considered for Chamber Choir membership or a mini-lesson.  <br /><b>Check all that apply</b>:',
                ),
                'desired_participation' => array(
                    'type' => 'checkboxgroup_no_sort',
                    'display_name' => ' ',
                    'options' => array('ml' => 'Mini-lesson', 'cc' => 'Chamber Choir'),
                ),
                'desired_participation_note' => array(
                    'type' => 'comment',
                    'text' => '<br /><b>PLEASE NOTE:</b> To be considered for the Chamber Choir, a student must <b>either</b>:<br />1) be a member of their respective All-State Choir<br />2) have earned “ Superior” ratings on a contest solo for two years in a row at the highest level of contest in which the school participates<br />3) submit a recorded audition of one contest-level piece.<br /><b>The student must also be able to be at Luther on Saturday, January 8 for rehearsals</b>… a day earlier than the rest of the Festival students.',
                ),
                'housing_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Overnight Housing</h3>',
                ),
                'housing_needed' => array(
                    'type' => 'radio_inline_no_sort',
                    'options' => array('Y' => 'Yes', 'N' => 'No'),
                    'display_name' => 'Is on-campus overnight housing needed?'
                ),
                'director_comments' => array(
                    'type' => 'textarea',
                ),
            );

        var $required = array('student_first_name','student_last_name', 'student_email',
                'student_school_name', 'student_phone', 'student_street_address', 'student_city',
                'student_state', 'student_zip', 'student_gender', 'voice_part', 'rank', 'year_in_school',
                'years_of_singing_experience', 'housing_needed', 'director_comments');

        function on_every_time()
        {
            $this->set_value('student_school_name', $this->controller->get('school_name'));
        }

        function pre_show_form()
	{
            //pray($_SESSION);
		echo '<div id="dorianBandForm" class="studentForm">'."\n";
	}

	function post_show_form()
	{
		echo '</div>'."\n";
	}

    function run_error_checks() {

        if($this->has_errors() <> true){

            if (isset($_SESSION['student_count'])) {
                $_SESSION['student_count'] += 1;
            } else {
                $_SESSION['student_count'] = 1;
            }
            $session_string = 'student' . $_SESSION['student_count'];

            //save form fields to array in _SESSION
            foreach ($this->elements as $key => $value) {
                $_SESSION[$session_string][$key] = $this->get_value($key);
            }

            //clear form fields
            foreach ($this->elements as $key => $value) {
                $this->set_value($key, '');
            }

        }
    }
}
?>