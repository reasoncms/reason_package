<?php

reason_include_once('minisite_templates/modules/default.php');

include_once(DISCO_INC . 'disco.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'StudySkillsAssessmentModule';

class StudySkillsAssessmentForm extends Disco {

    var $_log_errors = true;
    var $error;
    var $elements = array(
        
        // Motivation and Responsibility
        'M&R_header' => array(
            'type' => 'comment',
            'text' => '<h3> Motivation and Responsibility </h3>',
        ),
        'M&R1_question' => array(
            'type' => 'comment',
            'text' => 'I have high expecations of myself.',
        ),
        'M&R1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I have high expecations of myself.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R2_question' => array(
            'type' => 'comment',
            'text' => 'I work hard at everything I do.',
        ),
        'M&R2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I work hard at everything I do.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R3_question' => array(
            'type' => 'comment',
            'text' => 'I set goals for myself which are ambitious and attainable.',
        ),
        'M&R3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I set goals for myself which are ambitious and attainable.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R4_question' => array(
            'type' => 'comment',
            'text' => 'I take purposeful actions to achieve my goals and dreams.',
        ),
        'M&R4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I take purposeful actions to achieve my goals and dreams.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R5_question' => array(
            'type' => 'comment',
            'text' => 'I am persistent.',
        ),
        'M&R5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am persistent.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R6_question' => array(
            'type' => 'comment',
            'text' => 'I seek help when I need it.',
        ),
        'M&R6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I seek help when I need it.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R7_question' => array(
            'type' => 'comment',
            'text' => 'I place great value on earning my college degree.',
        ),
        'M&R7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I place great value on earning my college degree.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R8_question' => array(
            'type' => 'comment',
            'text' => 'I take responsibility for my actions (or inaction), rather than making excuses or putting the blame
                               elsewhere.',
        ),
        'M&R8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I take responsibility for my actions (or inaction), rather than making excuses or putting the blame
                               elsewhere.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R9_question' => array(
            'type' => 'comment',
            'text' => 'I know how to change habits of mine that hinder my success.',
        ),
        'M&R9' => array(
            'type' => 'radio_scale',
            'display_name' => 'I know how to change habits of mine that hinder my success.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R10_question' => array(
            'type' => 'comment',
            'text' => 'I keep promises that I make to myself or to others.',
        ),
        'M&R10' => array(
            'type' => 'radio_scale',
            'display_name' => 'I keep promises that I make to myself or to others.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R11_question' => array(
            'type' => 'comment',
            'text' => 'When I get off track, I realize it right away.',
        ),
        'M&R11' => array(
            'type' => 'radio_scale',
            'display_name' => 'When I get off track, I realize it right away.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Organization
        'org_header' => array(
            'type' => 'comment',
            'text' => '<h3> Organization </h3>',
        ),
        'ORG_1_question' => array(
            'type' => 'comment',
            'text' => 'I am on time.',
        ),
        'ORG_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am on time.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_2_question' => array(
            'type' => 'comment',
            'text' => 'I plan ahead.',
        ),
        'ORG_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I plan ahead.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_3_question' => array(
            'type' => 'comment',
            'text' => 'I keep track of what I need to do.',
        ),
        'ORG_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I keep track of what I need to do.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_4_question' => array(
            'type' => 'comment',
            'text' => 'I keep everything (handouts, assignments, papers, etc.) organized in a folder or binder by class.',
        ),
        'ORG_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I keep everything (handouts, assignments, papers, etc.) organized in a folder or binder by class.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_5_question' => array(
            'type' => 'comment',
            'text' => 'I remember where I put something when I need it.',
        ),
        'ORG_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I remember where I put something when I need it.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_6_question' => array(
            'type' => 'comment',
            'text' => 'I am aware of deadlines and meet them.',
        ),
        'ORG_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am aware of deadlines and meet them.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_7_question' => array(
            'type' => 'comment',
            'text' => 'Others consider me to be dependable.',
        ),
        'ORG_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'Others consider me to be dependable.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Self Confidence and Social Interaction
        'SC&SI_header' => array(
            'type' => 'comment',
            'text' => '<h3> Self confidence and Social Interactions </h3>',
        ),
        'SC&SI_1_question' => array(
            'type' => 'comment',
            'text' => 'I believe in myself.',
        ),
        'SC&SI_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I believe in myself.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_2_question' => array(
            'type' => 'comment',
            'text' => 'I am a positive person.',
        ),
        'SC&SI_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am a positive person.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_3_question' => array(
            'type' => 'comment',
            'text' => 'I expect to do well in my college classes.',
        ),
        'SC&SI_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I expect to do well in my college classes.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_4_question' => array(
            'type' => 'comment',
            'text' => 'I need to be around other people.',
        ),
        'SC&SI_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I need to be around other people.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_5_question' => array(
            'type' => 'comment',
            'text' => 'I am comfortable around other people.',
        ),
        'SC&SI_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am comfortable around other people.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_6_question' => array(
            'type' => 'comment',
            'text' => 'I make friends easily.',
        ),
        'SC&SI_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I make friends easily.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_7_question' => array(
            'type' => 'comment',
            'text' => 'I am aware of thoughts or beliefs I have that hinder my success.',
        ),
        'SC&SI_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am aware of thoughts or beliefs I have that hinder my success.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_8_question' => array(
            'type' => 'comment',
            'text' => 'I seek out opportunities that will help me grow as a person.',
        ),
        'SC&SI_8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I seek out opportunities that will help me grow as a person.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Healthy Living
        'HL_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Healthy Living </h3>',
        ),
        'HL_1_question' => array(
            'type' => 'comment',
            'text' => 'I get plenty of sleep at night and feel rested in the morning.',
        ),
        'HL_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I get plenty of sleep at night and feel rested in the morning.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_2_question' => array(
            'type' => 'comment',
            'text' => 'I eat breakfast.',
        ),
        'HL_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I eat breakfast.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_3_question' => array(
            'type' => 'comment',
            'text' => 'I engage in regular physical activity.',
        ),
        'HL_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I engage in regular physical activity.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_4_question' => array(
            'type' => 'comment',
            'text' => 'I am conscientious about eating healthy food.',
        ),
        'HL_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am conscientious about eating healthy food.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_5_question' => array(
            'type' => 'comment',
            'text' => 'I avoid consumption of alcohol or other drugs.',
        ),
        'HL_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I avoid consumption of alcohol or other drugs.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_6_question' => array(
            'type' => 'comment',
            'text' => 'I feel happy and fully alive.',
        ),
        'HL_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I feel happy and fully alive.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_7_question' => array(
            'type' => 'comment',
            'text' => 'When I’m angry, sad, or afraid, I know how to manage my emotions so I don’t do or say
                               anything I’ll regret later.',
        ),
        'HL_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'When I’m angry, sad, or afraid, I know how to manage my emotions so I don’t do or say
                               anything I’ll regret later.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_8_question' => array(
            'type' => 'comment',
            'text' => 'I handle stress well.',
        ),
        'HL_8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I handle stress well.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // General Learning Strategies
        'GLS_header' => array(
            'type' => 'comment',
            'text' => '<h3>  General Learning Strategies </h3>',
        ),
        'GLS_1_question' => array(
            'type' => 'comment',
            'text' => 'I make studying a top priority.',
         ),
        'GLS_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I make studying a top priority.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_2_question' => array(
            'type' => 'comment',
            'text' => 'I read and understand the syllabus for each class.',
         ),
        'GLS_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I read and understand the syllabus for each class.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_3_question' => array(
            'type' => 'comment',
            'text' => 'I communicate personally with my instructors/professors.',
         ),
        'GLS_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I communicate personally with my instructors/professors.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_4_question' => array(
            'type' => 'comment',
            'text' => 'I am prepared for my classes.',
         ),
        'GLS_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am prepared for my classes.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_5_question' => array(
            'type' => 'comment',
            'text' => 'I study in a quiet place where I can concentrate.',
         ),
        'GLS_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I study in a quiet place where I can concentrate.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_6_question' => array(
            'type' => 'comment',
            'text' => 'When I study, I am able to concentrate for at least 30 minutes at a time.',
         ),
        'GLS_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'When I study, I am able to concentrate for at least 30 minutes at a time.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_7_question' => array(
            'type' => 'comment',
            'text' => 'I study as I go, putting in time to understand how the pieces fit together.',
         ),
        'GLS_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I study as I go, putting in time to understand how the pieces fit together.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_8_question' => array(
            'type' => 'comment',
            'text' => 'I learn with the intention of remembering and applying the information learned.',
         ),
        'GLS_8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I learn with the intention of remembering and applying the information learned.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_9_question' => array(
            'type' => 'comment',
            'text' => 'I read all assigned materials, including supplemental materials not discussed in .',
         ),
        'GLS_9' => array(
            'type' => 'radio_scale',
            'display_name' => 'I read all assigned materials, including supplemental materials not discussed in .',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_10_question' => array(
            'type' => 'comment',
            'text' => 'I readily recall those things which I have studied.',
         ),
        'GLS_10' => array(
            'type' => 'radio_scale',
            'display_name' => 'I readily recall those things which I have studied.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_11_question' => array(
            'type' => 'comment',
            'text' => 'I use sensory inputs to reinforce my learning (visual aids, mnemonic devices, flash cards, audio
                               recordings, etc.).',
         ),
        'GLS_11' => array(
            'type' => 'radio_scale',
            'display_name' => 'I use sensory inputs to reinforce my learning (visual aids, mnemonic devices, flash cards, audio
                               recordings, etc.).',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_12_question' => array(
            'type' => 'comment',
            'text' => 'When I take a difficult class, I find a study partner or join a study group.',
         ),
        'GLS_12' => array(
            'type' => 'radio_scale',
            'display_name' => 'When I take a difficult class, I find a study partner or join a study group.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_13_question' => array(
            'type' => 'comment',
            'text' => 'I know how to think critically and analytically about complex topics.',
         ),
        'GLS_13' => array(
            'type' => 'radio_scale',
            'display_name' => 'I know how to think critically and analytically about complex topics.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Writing
        'WR_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Writing </h3>',
        ),
        'WR_1_question' => array(
            'type' => 'comment',
            'text' => 'I am able to express my thoughts well in writing.',
        ),
        'WR_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am able to express my thoughts well in writing.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_2_question' => array(
            'type' => 'comment',
            'text' => 'I put aside a written assignment for a day or so, then rewrite it.',
        ),
        'WR_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I put aside a written assignment for a day or so, then rewrite it.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_3_question' => array(
            'type' => 'comment',
            'text' => 'I review my writing carefully for errors.',
        ),
        'WR_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I review my writing carefully for errors.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_4_question' => array(
            'type' => 'comment',
            'text' => 'I have someone else read my written work and consider their suggestions for improvement.',
        ),
        'WR_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I have someone else read my written work and consider their suggestions for improvement.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_5_question' => array(
            'type' => 'comment',
            'text' => 'I am comfortable using library sources for research.',
        ),
        'WR_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am comfortable using library sources for research.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_6_question' => array(
            'type' => 'comment',
            'text' => 'I am able to narrow a topic for an essay, research paper, etc.',
        ),
        'WR_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am able to narrow a topic for an essay, research paper, etc.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_7_question' => array(
            'type' => 'comment',
            'text' => 'I allow sufficient time to collect information, organize material, write, and rewrite the
                               assignment.',
        ),
        'WR_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I allow sufficient time to collect information, organize material, write, and rewrite the
                               assignment.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Active Reading
        'AR_header' => array(
            'type' => 'comment',
            'text' => '<h3> Active Reading </h3>',
        ),
        'AR_1_question' => array(
            'type' => 'comment',
            'text' => 'I survey each chapter before I begin reading.',
        ),
        'AR_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I survey each chapter before I begin reading.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_2_question' => array(
            'type' => 'comment',
            'text' => 'I review reading materials more than once.',
        ),
        'AR_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I review reading materials more than once.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_3_question' => array(
            'type' => 'comment',
            'text' => 'When learning new material, I summarize it in my own words.',
        ),
        'AR_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'When learning new material, I summarize it in my own words.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_4_question' => array(
            'type' => 'comment',
            'text' => 'I am comfortable with my reading rate.',
        ),
        'AR_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am comfortable with my reading rate.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_5_question' => array(
            'type' => 'comment',
            'text' => 'When reading, I can distinguish readily between important and unimportant points.',
        ),
        'AR_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'When reading, I can distinguish readily between important and unimportant points.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_6_question' => array(
            'type' => 'comment',
            'text' => 'I take notes and/or highlight important parts while reading.',
        ),
        'AR_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I take notes and/or highlight important parts while reading.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Notetaking
        'NT_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Notetaking </h3>',
        ),
        'NT_1_question' => array(
            'type' => 'comment',
            'text' => 'While I am taking notes, I think about how I will use them later.',
        ),
        'NT_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'While I am taking notes, I think about how I will use them later.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'NT_2_question' => array(
            'type' => 'comment',
            'text' => 'I understand the lecture and classroom discussion while I am taking notes.',
        ),
        'NT_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I understand the lecture and classroom discussion while I am taking notes.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'NT_3_question' => array(
            'type' => 'comment',
            'text' => 'I write down the main points, rather than trying to record every word I hear.',
        ),
        'NT_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I write down the main points, rather than trying to record every word I hear.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'NT_4_question' => array(
            'type' => 'comment',
            'text' => 'I organize my notes in some meaningful manner (outline, concept map, etc.).',
        ),
        'NT_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I organize my notes in some meaningful manner (outline, concept map, etc.).',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'NT_5_question' => array(
            'type' => 'comment',
            'text' => 'I review my class notes within a few hours of taking them.',
        ),
        'NT_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I review my class notes within a few hours of taking them.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'NT_6_question' => array(
            'type' => 'comment',
            'text' => 'I am able to understand and learn from my notes.',
        ),
        'NT_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am able to understand and learn from my notes.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Testing
        'TST_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Testing </h3>',
        ),
        'TST_1_question' => array(
            'type' => 'comment',
            'text' => 'I try to find out what an exam will cover and how it will be graded.',
        ),
        'TST_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I try to find out what an exam will cover and how it will be graded.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_2_question' => array(
            'type' => 'comment',
            'text' => 'I get a good night’s rest prior to a scheduled exam.',
        ),
        'TST_2' => array(
            'type' => 'radio_scale',
            'display_name' =>'I get a good night’s rest prior to a scheduled exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_3_question' => array(
            'type' => 'comment',
            'text' => 'I avoid cramming for an exam.',
        ),
        'TST_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I avoid cramming for an exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_4_question' => array(
            'type' => 'comment',
            'text' => 'I am prepared for my exams.',
        ),
        'TST_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am prepared for my exams.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_5_question' => array(
            'type' => 'comment',
            'text' => 'I follow directions carefully when taking an exam.',
        ),
        'TST_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I follow directions carefully when taking an exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_6_question' => array(
            'type' => 'comment',
            'text' => 'I take time to understand the exam questions before starting to answer.',
        ),
        'TST_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I take time to understand the exam questions before starting to answer.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_7_question' => array(
            'type' => 'comment',
            'text' => 'I am calmly able to recall what I know during an exam.',
        ),
        'TST_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I am calmly able to recall what I know during an exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_8_question' => array(
            'type' => 'comment',
            'text' => 'I understand the structure of different types of tests and am able to prepare for each type.',
        ),
        'TST_8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I understand the structure of different types of tests and am able to prepare for each type.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Classroom Engagement
        'CE_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Classroom Engagement </h3>',
        ),
        'CE_1_question' => array(
            'type' => 'comment',
            'text' => 'I attend class regularly.',
        ),
        'CE_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I attend class regularly.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_2_question' => array(
            'type' => 'comment',
            'text' => 'I sit near the front of the class whenever possible.',
        ),
        'CE_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I sit near the front of the class whenever possible.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_3_question' => array(
            'type' => 'comment',
            'text' => 'I give non-verbal signals in class that I am interested (eye contact, ready to take notes, book
                               open, etc.).',
        ),
        'CE_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I give non-verbal signals in class that I am interested (eye contact, ready to take notes, book
                               open, etc.).',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_4_question' => array(
            'type' => 'comment',
            'text' => 'I volunteer answers to questions posed by my instructor during class.',
        ),
        'CE_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I volunteer answers to questions posed by my instructor during class.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_5_question' => array(
            'type' => 'comment',
            'text' => 'I participate in class discussions.',
        ),
        'CE_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I participate in class discussions.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_6_question' => array(
            'type' => 'comment',
            'text' => 'I take the initiative in group activities.',
        ),
        'CE_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I take the initiative in group activities.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_7_question' => array(
            'type' => 'comment',
            'text' => 'After each class, I can identify the major points and understand why they are important to the
                               material being covered.',
        ),
        'CE_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'After each class, I can identify the major points and understand why they are important to the
                               material being covered.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Time Management
        'TM_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Time Management </h3>',
        ),
        'TM_1_question' => array(
            'type' => 'comment',
            'text' => 'I avoid putting things off that need to be done.',
        ),
        'TM_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I avoid putting things off that need to be done.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_2_question' => array(
            'type' => 'comment',
            'text' => 'When studying, I make a point to avoid distractions and interruptions.',
        ),
        'TM_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'When studying, I make a point to avoid distractions and interruptions.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_3_question' => array(
            'type' => 'comment',
            'text' => 'I use prime time (when I am most alert) for studying.',
        ),
        'TM_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I use prime time (when I am most alert) for studying.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_4_question' => array(
            'type' => 'comment',
            'text' => 'I devote sufficient study time to each of my courses.',
        ),
        'TM_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I devote sufficient study time to each of my courses.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_5_question' => array(
            'type' => 'comment',
            'text' => 'I utilize monthly and weekly calendars.',
        ),
        'TM_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I utilize monthly and weekly calendars.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_6_question' => array(
            'type' => 'comment',
            'text' => 'I avoid activities which tend to interfere with my planned schedule.',
        ),
        'TM_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I avoid activities which tend to interfere with my planned schedule.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_7_question' => array(
            'type' => 'comment',
            'text' => 'I begin major course assignments well in advance.',
        ),
        'TM_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I begin major course assignments well in advance.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_8_question' => array(
            'type' => 'comment',
            'text' => 'I break larger assignments into smaller tasks with specific deadlines.',
        ),
        'TM_8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I break larger assignments into smaller tasks with specific deadlines.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_9_question' => array(
            'type' => 'comment',
            'text' => 'I spend most of my time doing important things.',
        ),
        'TM_9' => array(
            'type' => 'radio_scale',
            'display_name' => 'I spend most of my time doing important things.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_10_question' => array(
            'type' => 'comment',
            'text' => 'I have time to relax and have fun.',
        ),
        'TM_10' => array(
            'type' => 'radio_scale',
            'display_name' => 'I have time to relax and have fun.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Money Management
        'MM_header' => array(
            'type' => 'comment',
            'text' => '<h3> Money Management </h3>',
        ),
        'MM_1_question' => array(
            'type' => 'comment',
            'text' => 'I pay my bills on time.',
        ),
        'MM_1' => array(
            'type' => 'radio_scale',
            'display_name' => 'I pay my bills on time.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_2_question' => array(
            'type' => 'comment',
            'text' => 'I track my spending.',
        ),
        'MM_2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I track my spending.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_3_question' => array(
            'type' => 'comment',
            'text' => 'I control my spending by distinguishing between wants and needs.',
        ),
        'MM_3' => array(
            'type' => 'radio_scale',
            'display_name' => 'I control my spending by distinguishing between wants and needs.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_4_question' => array(
            'type' => 'comment',
            'text' => 'I think about how my credit score is affected by my money management habits.',
        ),
        'MM_4' => array(
            'type' => 'radio_scale',
            'display_name' => 'I think about how my credit score is affected by my money management habits.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_5_question' => array(
            'type' => 'comment',
            'text' => 'I stick to a budget I have set for myself.',
        ),
        'MM_5' => array(
            'type' => 'radio_scale',
            'display_name' => 'I stick to a budget I have set for myself.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_6_question' => array(
            'type' => 'comment',
            'text' => 'I save/invest some of my money.',
        ),
        'MM_6' => array(
            'type' => 'radio_scale',
            'display_name' => 'I save/invest some of my money.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_7_question' => array(
            'type' => 'comment',
            'text' => 'I take the initiative to understand my financial aid package.',
        ),
        'MM_7' => array(
            'type' => 'radio_scale',
            'display_name' => 'I take the initiative to understand my financial aid package.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_8_question' => array(
            'type' => 'comment',
            'text' => 'I seek additional forms of financial assistance to minimize my debt.',
        ),
        'MM_8' => array(
            'type' => 'radio_scale',
            'display_name' => 'I seek additional forms of financial assistance to minimize my debt.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
    );
    function on_every_time(){
        
     

        }
    
    
     

}

class StudySkillsAssessmentModule extends DefaultMinisiteModule {
    var $my_form;
    function run() {

        $this->my_form = new StudySkillsAssessmentForm();
        $this->my_form->run();

        
        
        
        // $this->calculate_score();
    }

    function calculate_score(){
        
        
        
      $myelements = $this->my_form->elements;
      $MR_group = array_slice($myelements,2,21);
      $ORG_group = array_slice($myelements,24,14);
      $SC_SI_group = array_slice($myelements,39,16);
      $HL_group = array_slice($myelements,56,16);
      $GLS_group = array_slice($myelements,73,26);
      $WR_group = array_slice($myelements,100,14);
      $AR_group = array_slice($myelements,115,12);
      $NT_group = array_slice($myelements,128,12);
      $TST_group = array_slice($myelements,141,16);
      $CE_group = array_slice($myelements,158,14);
      $TM_group = array_slice($myelements,173,20);
      $MM_group = array_slice($myelements,194,12);

      
      
      
      //Motivation and Responsibilty
     
      $MR_sum = 0;
      $MR_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach($MR_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
             $MR_sum = $MR_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $MR_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      
      echo '<h3> Motivation and Responsibility </h3>';
      if($MR_sum<22){
          echo 'You could take greater ownership in your educational and personal success. Talk to your SSS Advisor about discovering your
               purpose and creating the outcomes you desire.';
          
          echo $MR_improve;
      }else{
          echo 'Your answers indicate that you are determined to be successful and are taking positive steps to create the outcomes you desire.';
          
          echo $MR_improve;
          
          }
      
      // Organization
     
      $ORG_sum = 0;
      $ORG_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach($ORG_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
             $ORG_sum = $ORG_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $ORG_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      
      echo '<h3> Organization </h3>';
      if($ORG_sum<14){
          echo 'You need to identify an effective system for getting organized. Talk to your SSS
                Advisor about specific strategies you can implement.';
          
          echo $ORG_improve;
      }else{
          echo 'Your answers indicate that you are organized, which is important for your college success.';
          
          echo $ORG_improve;
          
      }
      // Self Confidence and Social Interaction
     
      $SC_SI_sum = 0;
      $SC_SI_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach($SC_SI_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
             $SC_SI_sum = $SC_SI_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $SC_SI_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3>Self Confidence and Social Interaction </h3>';
      if($SC_SI_sum<16){
          echo 'You have an opportunity to strengthen your self-awareness and social connections. Talk
                with your SSS Advisor about confidence-building and relationship-building activities.';
          
          echo $SC_SI_improve;
      }else{
          echo 'You answers indicate that you have an empowering attitude and mutually supportive relationships.';
          echo $SC_SI_improve;  
      }
      
      // Healthy living
      
      $HL_sum = 0;
      $HL_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $HL_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $HL_sum = $HL_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $HL_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> Healthy living </h3>';
      if( $HL_sum<16    ){
          echo 'You could take better care of yourself. Talk with your SSS Advisor about ways to
                strengthen your physical and mental well-being.';
          
          echo $HL_improve;
      }else{
          echo 'You are tuned into your physical and mental well-being.';
          echo $HL_improve;  
      }
      
      // General learning strategies
      
      $GLS_sum = 0;
      $GLS_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $GLS_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $GLS_sum = $GLS_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $GLS_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> General learning strategies </h3>';
      if( $GLS_sum <26){
          echo 'You could improve your study skills. Talk with your SSS Advisor about helpful
                resources and strategies.';
          
          echo $GLS_improve;
      }else{
          echo 'Your answers indicate your study skills will contribute to your learning and success in college.';
          echo $GLS_improve;  
      }
      
      // Writing
      
      $WR_sum = 0;
      $WR_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $WR_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $WR_sum = $WR_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $WR_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> Writing </h3>';
      if( $WR_sum <14){
          echo 'You could improve your college writing skills. Talk with your SSS Advisor and consider taking advantage of the Writing Center.';
          
          echo $WR_improve;
      }else{
          echo 'Your answers indicate your writing skills will contribute to your success in college.';
          echo $WR_improve;  
      }
      
      // Active Reading
      
      $AR_sum = 0;
      $AR_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $AR_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $AR_sum = $AR_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $AR_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      
      
      echo '<h3> Active Reading </h3>';
      
      if( $AR_sum <12){
          echo 'You could improve your college reading skills. Talk with your SSS Advisor for more
                information.';
          
          echo $AR_improve;
      }else{
          echo 'Your answers indicate that you are using appropriate strategies to help you understand
                and learn what you read.';
          echo $AR_improve;  
      }
      
      // Notetaking
      
      $NT_sum = 0;
      $NT_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $NT_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $NT_sum = $NT_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $NT_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> Notetaking </h3>';
      if( $NT_sum <12){
          echo 'You need to develop more active approaches for taking notes in the classroom. Talk
                with your SSS Advisor about effective note-taking strategies and tools.';
          
          echo $NT_improve;
      }else{
          echo 'Your answers indicate that your note-taking skills will contribute to your learning and
                success in college.';
          echo $NT_improve;  
      }
      
      // Testing
      
      $TST_sum = 0;
      $TST_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $TST_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $TST_sum = $TST_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $TST_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> Testing </h3>';
      if( $TST_sum <16){
          echo 'You are not doing all you can to perform well on college exams. Talk with your SSS
                Advisor about test preparation, testing strategies, and/or test anxiety.';
          
          echo $TST_improve;
      }else{
          echo 'Your answers indicate that you are using appropriate strategies to prepare for and take
                tests.';
          echo $TST_improve;  
      }
      
      // Classroom Engagement
      
      $CE_sum = 0;
      $CE_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $CE_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $CE_sum = $CE_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
             $CE_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> Classroom Engagement </h3>';
      if( $CE_sum <14){
          echo 'You are not displaying classroom behaviors characteristic of successful college students.
                Talk with your SSS Advisor.';
          
          echo $CE_improve;
      }else{
          echo 'You are displaying classroom behaviors characteristic of successful college students.';
          echo $CE_improve;  
      }
      
      // Time Management
      
      $TM_sum = 0;
      $TM_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $TM_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $TM_sum =  $TM_sum +$this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
              $TM_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      echo '<h3> Time Management </h3>';
      if( $TM_sum <20){
          echo 'You need to prioritize and organize your time better. Talk with your SSS Advisor about
                setting priorities and overcoming procrastination.';
          
          echo $TM_improve;
      }else{
          echo 'Your answers indicate that you are good at utilizing your time.';
          echo $TM_improve;  
      }
      
      // Money  Management
      
      $MM_sum = 0;
      $MM_improve = '</br> </br> Area(s) with room for improvement:';
      
      foreach( $MM_group as $key=>$value ){
          
          if($this->my_form->get_value($key) !== NULL){
              $MM_sum = $MM_sum + $this->my_form->get_value($key);
             
             if($this->my_form->get_value($key) < 3){
              $MM_improve .= '</br>' . $this->my_form->get_display_name($key);
             }   
      }
      }
      
      echo '<h3> Money  Management </h3>';
      if( $MM_sum <16){
          echo 'You need to improve your money management skills. Talk with your SSS Advisor about
                setting a budget and tracking your expenses.';
          
          echo $MM_improve;
      }else{
          echo 'Your answers indicate you are following wise money management practices.';
          echo $MM_improve;  
      }
  
     
    }
}

include_once('/usr/local/webapps/reason/reason_package/disco/plasmature/types/options.php');
class radio_scaleType extends radio_inline_no_sortType
{
    var $type = 'radio_scale';
    var $_labeled = false;

    function get_display()
    {
        $i = 0;
        $str = '<div id="'.$this->name.'_container" class="radioButtons scaleRadioButtons">'."\n";
        foreach( $this->options as $key => $val )
        {
            $id = 'radio_scale_'.$this->name.'_'.$i++;
            $str .= '<span class="radioItem"><span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
            if ( $key == $this->value )
                $str .= ' checked="checked"';
            $str .= ' /></span> <label for="'.$id.'">'.$val.'</label></span> '."\n";
        }
        $str .= '</div>'."\n";
        return $str;
    }
}

?>
